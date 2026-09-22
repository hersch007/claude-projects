<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('cardholder_name')->nullable()->after('notes');
            $table->string('card_last4', 4)->nullable()->after('cardholder_name');
            $table->string('card_brand')->nullable()->after('card_last4');
            $table->string('reference_number')->nullable()->after('card_brand');
            $table->boolean('needs_review')->default(false)->after('reference_number');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['cardholder_name', 'card_last4', 'card_brand', 'reference_number', 'needs_review']);
        });
    }
};
