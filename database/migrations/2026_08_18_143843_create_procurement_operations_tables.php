<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('procurements', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_number', 32)->unique()->index();
            $table->string('title')->index();
            $table->foreignId('end_user_office_id')->constrained('offices');
            $table->foreignId('procurement_mode_id')->constrained('procurement_modes');
            $table->foreignId('current_phase_id')->nullable()->constrained('procurement_mode_phases');
            $table->decimal('abc_amount', 15, 2);
            $table->enum('status', ['DRAFT', 'ROUTED', 'RETURNED', 'CANCELLED', 'REJECTED', 'COMPLETED'])->default('DRAFT');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('procurement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procurement_id')->constrained()->cascadeOnDelete();
            $table->string('item_code', 32)->nullable();
            $table->text('description');
            $table->unsignedInteger('quantity');
            $table->string('unit_of_measure', 32);
            $table->decimal('unit_cost', 15, 2);
            $table->decimal('total_cost', 15, 2);
            $table->timestamps();
        });

        Schema::create('procurement_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procurement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('phase_id')->constrained('procurement_mode_phases');
            $table->string('file_name');
            $table->string('file_path');
            $table->unsignedInteger('file_size');
            $table->string('mime_type', 255);
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('procurement_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procurement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_office_id')->constrained('offices');
            $table->foreignId('to_office_id')->constrained('offices');
            $table->foreignId('from_user_id')->constrained('users');
            $table->foreignId('to_user_id')->nullable()->constrained('users');
            $table->text('action_taken');
            $table->text('remarks')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('procurement_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procurement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('phase_id')->nullable()->constrained('procurement_mode_phases');
            $table->foreignId('user_id')->constrained('users');
            $table->string('action', 64);
            $table->string('status_from', 32);
            $table->string('status_to', 32);
            $table->text('remarks')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_history');
        Schema::dropIfExists('procurement_routes');
        Schema::dropIfExists('procurement_documents');
        Schema::dropIfExists('procurement_items');
        Schema::dropIfExists('procurements');
    }
};
