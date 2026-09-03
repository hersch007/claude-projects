<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\EligibleDomain;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ImportLumos extends Command
{
    protected $signature = 'lumos:import {file}';
    protected $description = 'Import Lumos customer CSV';

    public function handle()
    {
        $path = $this->argument('file');
        $handle = fopen($path, 'r');
        $headers = fgetcsv($handle);
        $imported = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($headers, $row);
            $email = strtolower(trim($data['email'] ?? ''));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $skipped++; continue; }
            $domain = strtolower(substr(strrchr($email, '@'), 1));
            EligibleDomain::firstOrCreate(['domain' => $domain], ['active' => true]);
            $customer = Customer::firstOrCreate(
                ['email' => $email],
                ['name' => trim($data['name'] ?? 'Lumos Customer'), 'phone' => trim($data['phone'] ?? ''), 'domain' => $domain]
            );
            $amount = (float) preg_replace('/[^0-9.]/', '', $data['amount'] ?? 0);
            if ($amount <= 0) { $skipped++; continue; }
            $product = Product::orderByRaw('ABS(price - ?)', [$amount])->first();
            $existing = Payment::where('customer_id', $customer->id)
                ->where('amount', $amount)
                ->where('payment_method', 'imported')
                ->exists();
            if (!$existing) {
                Payment::create([
                    'customer_id'    => $customer->id,
                    'product_id'     => optional($product)->id,
                    'amount'         => $amount,
                    'status'         => 'paid',
                    'payment_method' => 'imported',
                    'payment_date'   => Carbon::parse($data['date'] ?? now()),
                    'notes'          => 'Imported from Lumos export',
                ]);
            }
            $imported++;
        }
        fclose($handle);
        $this->info("Imported: $imported, Skipped: $skipped");
    }
}
