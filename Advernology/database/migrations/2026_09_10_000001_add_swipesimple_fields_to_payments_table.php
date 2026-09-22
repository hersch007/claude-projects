<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $fk = $this->findCustomerForeignKey();

        if ($fk) {
            DB::statement("ALTER TABLE `payments` DROP FOREIGN KEY `$fk`");
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->nullable()->change();
        });

        // Note: not re-adding the customer_id foreign key here — some existing
        // payments rows reference a customer_id that no longer exists in the
        // customers table, which would block the constraint from being added.
        // That's a pre-existing data issue, left alone rather than guessed at.

        // Some of these columns were already added to the live table outside
        // of any tracked migration at some point, so add only what's missing.
        if (!Schema::hasColumn('payments', 'cardholder_name')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->string('cardholder_name')->nullable()->after('product_id');
            });
        }

        if (!Schema::hasColumn('payments', 'card_last4')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->string('card_last4', 4)->nullable()->after('cardholder_name');
            });
        }

        if (!Schema::hasColumn('payments', 'card_brand')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->string('card_brand')->nullable()->after('card_last4');
            });
        }

        if (!Schema::hasColumn('payments', 'needs_review')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->boolean('needs_review')->default(false)->after('notes');
            });
        }
    }

    public function down(): void
    {
        $fk = $this->findCustomerForeignKey();

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['cardholder_name', 'card_last4', 'card_brand', 'needs_review']);
        });

        if ($fk) {
            DB::statement("ALTER TABLE `payments` DROP FOREIGN KEY `$fk`");
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->nullable(false)->change();
            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
        });
    }

    private function findCustomerForeignKey(): ?string
    {
        $row = DB::selectOne(
            "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'payments'
               AND COLUMN_NAME = 'customer_id'
               AND REFERENCED_TABLE_NAME IS NOT NULL
             LIMIT 1"
        );

        return $row->CONSTRAINT_NAME ?? null;
    }
};
