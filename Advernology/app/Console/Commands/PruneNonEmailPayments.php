<?php

namespace App\Console\Commands;

use App\Models\Payment;
use Illuminate\Console\Command;

class PruneNonEmailPayments extends Command
{
    protected $signature = 'payments:prune-non-email {--force : Actually delete the matched rows instead of just listing them}';
    protected $description = "Review (and optionally delete) payments whose stored reference_number isn't one of the known email-product codes";

    private const VALID_REFERENCES = ['2658ISPMAIL1D', '2658ISPMAIL1', '2658ISPMAIL6', '2658ISPMAIL25'];

    public function handle(): int
    {
        $query = Payment::whereNotNull('reference_number')
            ->whereNotIn('reference_number', self::VALID_REFERENCES);

        $count = $query->count();

        if ($count === 0) {
            $this->info('No payments found with a non-email reference_number on file.');
        } else {
            $this->warn("Found {$count} payment(s) with a reference_number that isn't a known email product:");
            $query->get()->each(function (Payment $payment) {
                $this->line(sprintf(
                    '  #%d | txn %s | ref %s | $%s | %s | %s',
                    $payment->id,
                    $payment->transaction_id,
                    $payment->reference_number,
                    number_format($payment->amount, 2),
                    $payment->cardholder_name ?? optional($payment->customer)->name ?? 'unknown',
                    optional($payment->payment_date)->toDateString() ?? 'no date',
                ));
            });
        }

        $reviewCount = Payment::where('needs_review', true)->count();
        if ($reviewCount > 0) {
            $this->comment("Note: {$reviewCount} payment(s) are flagged needs_review (valid email reference, but no matching Product record in the catalog) — these are NOT touched by this command. Check the Payments admin page for those.");
        }

        if ($count === 0) {
            return self::SUCCESS;
        }

        if (! $this->option('force')) {
            $this->line('');
            $this->line('Re-run with --force to permanently delete the payments listed above.');
            return self::SUCCESS;
        }

        if (! $this->confirm("Permanently delete these {$count} payment(s)? This cannot be undone.")) {
            $this->info('Cancelled — nothing was deleted.');
            return self::SUCCESS;
        }

        $deleted = $query->delete();
        $this->info("Deleted {$deleted} payment(s).");

        return self::SUCCESS;
    }
}
