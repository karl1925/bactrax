<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('item_bank_items', function (Blueprint $table) {
            $table->date('reference_date')->nullable()->after('reference_price');
        });

        // Existing catalog entries use their creation date as the initial verification date.
        DB::table('item_bank_items')
            ->whereNull('reference_date')
            ->update(['reference_date' => DB::raw('COALESCE(DATE(created_at), CURRENT_DATE)')]);

        Schema::table('item_bank_items', function (Blueprint $table) {
            $table->date('reference_date')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('item_bank_items', function (Blueprint $table) {
            $table->dropColumn('reference_date');
        });
    }
};
