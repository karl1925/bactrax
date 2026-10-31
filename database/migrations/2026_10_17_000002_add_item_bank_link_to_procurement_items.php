<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('procurement_items', function (Blueprint $table) {
            $table->foreignId('item_bank_item_id')
                ->nullable()
                ->after('procurement_id')
                ->constrained('item_bank_items')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('procurement_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('item_bank_item_id');
        });
    }
};
