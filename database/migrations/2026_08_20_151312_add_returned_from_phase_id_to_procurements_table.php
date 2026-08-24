<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('procurements', function (Blueprint $table) {
            $table->foreignId('returned_from_phase_id')
                ->nullable()
                ->after('current_phase_id')
                ->constrained('procurement_mode_phases')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('procurements', function (Blueprint $table) {
            $table->dropForeign(['returned_from_phase_id']);
            $table->dropColumn('returned_from_phase_id');
        });
    }
};
