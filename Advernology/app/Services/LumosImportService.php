<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\EligibleDomain;
use App\Models\ImportLog;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;

class LumosImportService
{
    public function import(UploadedFile $file, int $adminId = null): ImportLog
    {
        $rows     = Excel::toArray(new HeadingRowImport, $file);
        $sheet    = $rows[0] ?? [];
        $imported = 0;
        $skipped  = 0;
        $errors   = [];

        foreach ($sheet as $index => $row) {
            try {
                $email = trim($row['email'] ?? $row['customer_email'] ?? '');
                if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $skipped++;
                    continue;
                }

                $domain = strtolower(substr(strrchr($email, '@'), 1));

                // Auto-add domain to eligible list
                EligibleDomain::firstOrCreate(['domain' => $domain], ['active' => true]);

                $customer = Customer::firstOrCreate(
                    ['email' => strtolower($email)],
                    [
                        'name'   => trim($row['name'] ?? $row['customer_name'] ?? 'Lumos Customer'),
                        'phone'  => trim($row['phone'] ?? ''),
                        'domain' => $domain,
                        'notes'  => trim($row['notes'] ?? ''),
                    ]
                );

                $amount = (float) preg_replace('/[^0-9.]/', '', $row['amount'] ?? $row['total'] ?? 0);
                if ($amount <= 0) {
                    $skipped++;
                    continue;
                }

                // Match to closest product by price
                $product = Product::orderByRaw('ABS(price - ?)', [$amount])->first();

                $paymentDate = null;
                $rawDate = $row['date'] ?? $row['payment_date'] ?? $row['transaction_date'] ?? null;
                if ($rawDate) {
                    try {
                        $paymentDate = Carbon::parse($rawDate);
                    } catch (\Exception) {
                        $paymentDate = now();
                    }
                }

                Payment::firstOrCreate(
                    ['transaction_id' => trim($row['transaction_id'] ?? $row['order_id'] ?? '') ?: null],
                    [
                        'customer_id'    => $customer->id,
                        'product_id'     => optional($product)->id,
                        'amount'         => $amount,
                        'status'         => 'paid',
                        'payment_method' => 'imported',
                        'payment_date'   => $paymentDate ?? now(),
                        'notes'          => 'Imported from Lumos export',
                    ]
                );

                $imported++;
            } catch (\Throwable $e) {
                $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
                $skipped++;
            }
        }

        return ImportLog::create([
            'filename'      => $file->getClientOriginalName(),
            'rows_imported' => $imported,
            'rows_skipped'  => $skipped,
            'errors'        => $errors ? implode("\n", $errors) : null,
            'admin_id'      => $adminId,
        ]);
    }
}
