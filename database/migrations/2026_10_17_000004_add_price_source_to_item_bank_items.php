<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('item_bank_items', function (Blueprint $table) {
            $table->string('price_source', 255)->nullable()->after('reference_price');
        });
    }

    public function down(): void
    {
        Schema::table('item_bank_items', function (Blueprint $table) {
            $table->dropColumn('price_source');
        });
    }
};
