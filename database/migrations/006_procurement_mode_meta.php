<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('procurement_modes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name');
            $table->decimal('min_abc', 15, 2)->default(0.00);
            $table->decimal('max_abc', 15, 2)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('procurement_mode_phases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procurement_mode_id')->constrained()->cascadeOnDelete();
            $table->integer('phase_sequence');
            $table->string('phase_name');
            $table->text('description')->nullable();
            $table->unsignedInteger('allowable_days')->default(7);
            $table->timestamps();

            // Shortened index identifier name to stay under MySQL's 64-character limit
            $table->unique(['procurement_mode_id', 'phase_sequence'], 'pm_phase_seq_unique');
        });

        Schema::create('procurement_mode_phase_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phase_id')->constrained('procurement_mode_phases')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->boolean('can_act')->default(true);
            $table->timestamps();
            $table->unique(['phase_id', 'role_id']);
        });

        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('path');
            $table->string('status')->default('draft');
            $table->longText('configuration')->nullable();
            $table->timestamps();
        });

        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procurement_mode_phase_id')->constrained('procurement_mode_phases')->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('form_id')->nullable()->constrained('forms')->nullOnDelete();
            $table->boolean('is_mandatory')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('attachment_signatories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attachment_id')->constrained('attachments')->cascadeOnDelete();
            $table->integer('sequence');
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachment_signatories');
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('forms');
        Schema::dropIfExists('procurement_mode_phase_roles');
        Schema::dropIfExists('procurement_mode_phases');
        Schema::dropIfExists('procurement_modes');
    }
};
