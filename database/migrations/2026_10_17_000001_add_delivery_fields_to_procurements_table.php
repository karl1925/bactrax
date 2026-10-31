<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('procurements', function (Blueprint $table) {
            if (!Schema::hasColumn('procurements', 'is_delivery_cd')) {
                $table->boolean('is_delivery_cd')->default(false);
            }
            if (!Schema::hasColumn('procurements', 'delivery_cd')) {
                $table->unsignedInteger('delivery_cd')->nullable();
            }
            if (!Schema::hasColumn('procurements', 'delivery_date')) {
                $table->date('delivery_date')->nullable();
            }
            if (!Schema::hasColumn('procurements', 'quotation_deadline')) {
                $table->dateTime('quotation_deadline')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('procurements', function (Blueprint $table) {
            foreach (['is_delivery_cd', 'delivery_cd', 'delivery_date', 'quotation_deadline'] as $column) {
                if (Schema::hasColumn('procurements', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
