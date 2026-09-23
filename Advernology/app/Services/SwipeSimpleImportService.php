<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\ImportLog;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

class SwipeSimpleImportService
{
    /**
     * SwipeSimple "Reference Number" (normalized: whitespace stripped,
     * uppercased) -> Product name. These are the only reference codes that
     * represent an email product. Anything else (e.g. "Advernology Pay Now",
     * "Advernology LB link") is a different service and is skipped, never
     * imported.
     *
     * '2658ISPMAIL1D' and '2658ISPMAIL1' both price at $9.99, matching the
     * current "1 Email Account" product — they're the same product under two
     * reference-code spellings, not separate products.
     */
    private const REFERENCE_PRODUCT_MAP = [
        '2658ISPMAIL1D' => '1 Email Account',
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
        $missingProductsWarned = []; // productName => true, so we don't log the same one per row

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
                    $needsReview = true;
                    if (! isset($missingProductsWarned[$productName])) {
                        $missingProductsWarned[$productName] = true;
                        $errors[] = "Reference '{$reference}' expects product '{$productName}' but it wasn't found in the catalog (starting at row {$line}) — these rows still imported, flagged Needs Review.";
                    }
                }

                $cardholderName = trim($row['cardholder_name'] ?? '');
                if (! $cardholderName) {
                    $skipped++;
                    continue;
                }

                // The customers table is the authoritative Lumos roster — don't
                // fabricate new customer records from a card name. Only attach
                // to an existing customer with an exact name match; otherwise
                // leave the payment unlinked (customer_id null) and flagged for
                // manual review, rather than inventing a placeholder identity.
                $customer = Customer::where('name', $cardholderName)->first();
                if (! $customer) {
                    $needsReview = true;
                }

                $amount = (float) preg_replace('/[^0-9.]/', '', $row['amount'] ?? 0);

                Payment::create([
                    'customer_id'      => optional($customer)->id,
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

        $errorText = $errors ? implode("\n", $errors) : null;
        if ($errorText && strlen($errorText) > 60000) {
            // Defense in depth: however unlikely after deduping the common
            // case above, don't let a flood of distinct error messages
            // overflow the errors column and crash the whole import.
            $errorText = substr($errorText, 0, 60000) . "\n… (truncated, see logs for the rest)";
        }

        return ImportLog::create([
            'filename'      => $file->getClientOriginalName(),
            'rows_imported' => $imported,
            'rows_skipped'  => $skipped,
            'rows_refunded' => $refunded,
            'errors'        => $errorText,
            'admin_id'      => $adminId,
        ]);
    }

    /**
     * Read the file into an array of rows keyed by normalized header name,
     * handling a UTF-8 BOM on the first header cell explicitly. SwipeSimple
     * only ever exports CSV, so this reads it directly with fgetcsv rather
     * than going through maatwebsite/excel — that package's internal temp
     * file handling doesn't reliably find Livewire's uploaded file on this
     * host, and a plain CSV reader avoids the whole dependency.
     */
    private function readRows(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        if (! $path || ! is_readable($path)) {
            return [];
        }

        $handle = fopen($path, 'r');
        if (! $handle) {
            return [];
        }

        $headerRow = fgetcsv($handle);
        if ($headerRow === false) {
            fclose($handle);
            return [];
        }

        $headers = array_map([$this, 'normalizeHeader'], $headerRow);
        $count   = count($headers);

        $result = [];
        $line   = 1; // the header row is line 1
        while (($values = fgetcsv($handle)) !== false) {
            $line++;
            if (! array_filter($values, fn ($v) => $v !== null && $v !== '')) {
                continue; // blank row
            }
            $values        = array_pad(array_slice($values, 0, $count), $count, null);
            $result[$line] = array_combine($headers, $values);
        }
        fclose($handle);

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
}
