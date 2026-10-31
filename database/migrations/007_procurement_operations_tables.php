<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('procurements', function (Blueprint $table) {
            $table->id();
            $table->string('pr_number', 32)->nullable()->unique();
            $table->text('purpose')->nullable();
            $table->string('fund_cluster')->nullable();
            $table->string('responsibility_code')->nullable();
            $table->date('pr_date')->nullable();
            $table->boolean('is_delivery_cd')->default(false);
            $table->unsignedInteger('delivery_cd')->nullable();
            $table->date('delivery_date')->nullable();
            $table->dateTime('quotation_deadline')->nullable();
            $table->foreignId('end_user_office_id')->nullable()->constrained('offices');
            $table->foreignId('procurement_mode_id')->constrained('procurement_modes');
            $table->foreignId('current_phase_id')->nullable()->constrained('procurement_mode_phases');
            $table->foreignId('returned_from_phase_id')->nullable()->constrained('procurement_mode_phases')->nullOnDelete();
            $table->decimal('abc_amount', 15, 2)->nullable();
            $table->enum('status', ['DRAFT', 'RETURNED', 'REJECTED', 'CANCELLED', 'ROUTING', 'COMPLETED', 'ARCHIVED'])->default('DRAFT');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            
        });

        Schema::create('procurement_items', function (Blueprint $table) {
            $table->id();
            
            // Relates item to the main procurement record
            $table->foreignId('procurement_id')
                ->constrained('procurements')
                ->cascadeOnDelete();

            // Self-referencing foreign key pointing to the Lot Header (null for standalone items & lot headers)
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('procurement_items')
                ->cascadeOnDelete();

            // Sequential line item index
            $table->integer('line_no');

            // Flag indicating if this row represents a Lot Header
            $table->boolean('is_lot')->default(false);

            // Item specifications and units
            $table->string('unit', 50)->nullable(); // Nullable for Lot sub-items or Lot headers
            $table->text('description')->nullable();

            // Up to 3 decimals for fractional unit quantities (e.g., volume, length, weights)
            $table->decimal('quantity', 12, 3)->nullable(); 
            
            // Decimal cost (nullable for Lot headers whose total comes from sub-items)
            $table->decimal('cost', 15, 2)->nullable();

            $table->timestamps();

            // Indexes for optimizing query performance
            $table->index(['procurement_id', 'line_no']);
            $table->index('parent_id');
        });

        Schema::create('procurement_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procurement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('phase_id')->nullable()->constrained('procurement_mode_phases');
            $table->unsignedBigInteger('attachment_id')->nullable()->index();
            $table->unsignedBigInteger('form_id')->nullable();
            $table->json('form_data')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->string('workflow_status', 20)->default('INCOMPLETE')->index();
            $table->string('file_name')->nullable();
            $table->string('file_path')->nullable();
            $table->unsignedInteger('file_size')->nullable();
            $table->string('mime_type', 255)->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['procurement_id', 'attachment_id'], 'procurement_attachment_config_unique');
        });

        Schema::create('procurement_attachment_signatories', function (Blueprint $table) {
            $table->id();

            // Pass 'pas_attachment_id_foreign' (26 chars) as the second argument
            $table->foreignId('procurement_attachments_id')
                ->constrained('procurement_attachments', 'id', 'pas_attachment_id_foreign')
                ->cascadeOnDelete();

            $table->unsignedInteger('sequence');
            // Keep the assigned role as a snapshot even if role configuration changes later.
            $table->unsignedBigInteger('configured_role_id')->nullable()->index();
            $table->boolean('is_signed')->default(false);
            $table->timestamp('signed_at')->nullable();
            $table->foreignId('signatory_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['procurement_attachments_id', 'sequence'], 'pas_attachment_sequence_unique');
        });

        Schema::create('procurement_signatories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procurement_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sequence');
            // Role is an audit snapshot; it intentionally has no FK to roles.
            $table->unsignedBigInteger('configured_role_id')->nullable()->index();
            $table->foreignId('signatory_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_signed')->default(false);
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();
            $table->unique(['procurement_id', 'sequence'], 'procurement_signatory_sequence_unique');
        });

        Schema::create('procurement_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procurement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('phase_id')->nullable()->constrained('procurement_mode_phases')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->string('action', 64);
            $table->string('status_from', 32)->nullable();
            $table->string('status_to', 32);
            $table->text('remarks')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_histories');
        Schema::dropIfExists('procurement_signatories');
        Schema::dropIfExists('procurement_attachment_signatories');
        Schema::dropIfExists('procurement_attachments');
        Schema::dropIfExists('procurement_items');
        Schema::dropIfExists('procurements');
    }
};
