<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\ImportLog;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class LumosImportService
{
    /**
     * SwipeSimple "Reference Number" (normalized: whitespace stripped,
     * uppercased) -> Product name. These are the only reference codes that
     * represent an email product. Anything else (e.g. "Advernology Pay Now",
     * "Advernology LB link") is a different service and is skipped, never
     * imported.
     */
    private const REFERENCE_PRODUCT_MAP = [
        '2658ISPMAIL1D' => 'Drop-back / 1D Package',
        '2658ISPMAIL1'  => '1 Email Account',
        '2658ISPMAIL6'  => '6 Email Package',
        '2658ISPMAIL25' => '25 Email Package',
    ];

    /**
     * @param  string|null  $since  Only import transactions dated on/after this date (Y-m-d). Null imports everything.
     */
    public function import(UploadedFile $file, int $adminId = null, ?string $since = null): ImportLog
    {
        $sheet = $this->readRows($file);

        if (empty($sheet)) {
            return ImportLog::create([
                'filename'      => $file->getClientOriginalName(),
                'rows_imported' => 0,
                'rows_skipped'  => 0,
                'errors'        => 'File had no data rows.',
                'admin_id'      => $adminId,
            ]);
        }

        $sinceDate = $since ? Carbon::parse($since)->startOfDay() : null;
        $imported  = 0;
        $skipped   = 0;
        $refunded  = 0;
        $errors    = [];
        $refundRows = [];

        // Pass 1: import approved email-product sales.
        foreach ($sheet as $line => $row) {
            try {
                $transactionId = trim($row['transaction'] ?? '');
                if (! $transactionId) {
                    $skipped++;
                    continue;
                }

                $paymentDate = $this->parseDate($row['date'] ?? null);
                if ($sinceDate && $paymentDate && $paymentDate->lt($sinceDate)) {
                    $skipped++;
                    continue;
                }

                $type = strtolower(trim($row['type'] ?? ''));

                if ($type === 'refund') {
                    // Handle in pass 2, after all sales in this file are in.
                    $refundRows[$line] = $row;
                    continue;
                }

                if ($type !== 'sale') {
                    $skipped++;
                    continue;
                }

                if (Payment::where('transaction_id', $transactionId)->exists()) {
                    // Already imported in a previous run.
                    $skipped++;
                    continue;
                }

                $result = strtolower(trim($row['result'] ?? ''));
                if ($result !== 'approved') {
                    $skipped++;
                    continue;
                }

                $reference   = strtoupper(preg_replace('/\s+/', '', $row['reference_number'] ?? ''));
                $productName = self::REFERENCE_PRODUCT_MAP[$reference] ?? null;
                if (! $productName) {
                    // Not one of the known email-product reference codes.
                    $skipped++;
                    continue;
                }

                $product     = Product::where('name', $productName)->first();
                $needsReview = false;
                if (! $product) {
                    $errors[]    = "Row {$line}: reference '{$reference}' expects product '{$productName}' but it wasn't found in the catalog.";
                    $needsReview = true;
                }

                $cardholderName = trim($row['cardholder_name'] ?? '');
                if (! $cardholderName) {
                    $skipped++;
                    continue;
                }

                $customer = Customer::firstOrCreate(
                    ['name' => $cardholderName],
                    [
                        'email'  => $this->placeholderEmail($cardholderName),
                        'domain' => 'no-email.import',
                        'notes'  => 'Created from SwipeSimple transaction import — no email on file.',
                    ]
                );

                $amount = (float) preg_replace('/[^0-9.]/', '', $row['amount'] ?? 0);

                Payment::create([
                    'customer_id'      => $customer->id,
                    'product_id'       => optional($product)->id,
                    'amount'           => $amount,
                    'transaction_id'   => $transactionId,
                    'status'           => 'paid',
                    'payment_method'   => 'swipesimple',
                    'payment_date'     => $paymentDate ?? now(),
                    'cardholder_name'  => $cardholderName,
                    'card_last4'       => trim($row['last_4'] ?? '') ?: null,
                    'card_brand'       => trim($row['brand'] ?? '') ?: null,
                    'reference_number' => $reference,
                    'needs_review'     => $needsReview,
                    'notes'            => 'Imported from SwipeSimple export.',
                ]);

                $imported++;
            } catch (\Throwable $e) {
                $errors[] = "Row {$line}: " . $e->getMessage();
                $skipped++;
            }
        }

        // Pass 2: apply refunds to sales (this run's or a previous run's) with a matching transaction ID.
        foreach ($refundRows as $line => $row) {
            try {
                $transactionId = trim($row['transaction'] ?? '');
                $result        = strtolower(trim($row['result'] ?? ''));

                if (! $transactionId || $result !== 'approved') {
                    $skipped++;
                    continue;
                }

                $payment = Payment::where('transaction_id', $transactionId)
                    ->where('payment_method', 'swipesimple')
                    ->first();

                if (! $payment || $payment->status === 'refunded') {
                    // No matching email-product sale on file (or already marked refunded).
                    $skipped++;
                    continue;
                }

                $payment->update(['status' => 'refunded']);
                $refunded++;
            } catch (\Throwable $e) {
                $errors[] = "Row {$line}: " . $e->getMessage();
                $skipped++;
            }
        }

        return ImportLog::create([
            'filename'      => $file->getClientOriginalName(),
            'rows_imported' => $imported,
            'rows_skipped'  => $skipped,
            'rows_refunded' => $refunded,
            'errors'        => $errors ? implode("\n", $errors) : null,
            'admin_id'      => $adminId,
        ]);
    }

    /**
     * Read the file into an array of rows keyed by normalized header name,
     * handling a UTF-8 BOM on the first header cell explicitly rather than
     * relying on the Excel/CSV reader to strip it.
     */
    private function readRows(UploadedFile $file): array
    {
        $sheets = Excel::toArray(null, $file);
        $rows   = $sheets[0] ?? [];

        if (empty($rows)) {
            return [];
        }

        $headers = array_map([$this, 'normalizeHeader'], array_shift($rows));
        $count   = count($headers);

        $result = [];
        $line   = 1; // the header row is line 1
        foreach ($rows as $values) {
            $line++;
            if (! array_filter($values, fn ($v) => $v !== null && $v !== '')) {
                continue; // blank row
            }
            $values        = array_pad(array_slice($values, 0, $count), $count, null);
            $result[$line] = array_combine($headers, $values);
        }

        return $result;
    }

    private function normalizeHeader(mixed $header): string
    {
        $header = (string) $header;
        $header = preg_replace('/^\xEF\xBB\xBF/', '', $header); // strip UTF-8 BOM
        $header = strtolower(trim($header));
        $header = preg_replace('/[^a-z0-9]+/', '_', $header);

        return trim($header, '_');
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * SwipeSimple exports don't include the customer's email, only their
     * name on the card. We still need a unique, non-null email to satisfy
     * the customers table, so generate a placeholder until a real one is
     * known (e.g. from a support reply or future checkout).
     */
    private function placeholderEmail(string $name): string
    {
        $slug = Str::slug($name, '.');
        $slug = $slug !== '' ? $slug : 'customer';

        $email  = "{$slug}@no-email.import";
        $suffix = 2;
        while (Customer::where('email', $email)->exists()) {
            $email = "{$slug}.{$suffix}@no-email.import";
            $suffix++;
        }

        return $email;
    }
}
