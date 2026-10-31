<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('business_name');
            $table->string('business_type', 100)->nullable();
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 64)->nullable();
            $table->text('business_address')->nullable();
            $table->string('tin', 64)->nullable()->index();
            $table->string('philgeps_registration_number', 128)->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('business_name');
        });

        // Supplier profile documents are append-only versions. The newest version for a
        // supplier/document type is the current copy; retain older copies for audit.
        $this->createSupplierDocumentsTable();

        Schema::create('procurement_awards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procurement_id')->unique()->constrained('procurements')->cascadeOnDelete();
            $table->string('award_basis', 16)->nullable(); // LOT or ITEM
            $table->dateTime('opening_at')->nullable();
            $table->string('opening_place')->nullable();
            $table->string('resolution_number', 64)->nullable();
            $table->date('resolution_date')->nullable();
            $table->string('resolution_place')->nullable();
            $table->longText('recommendation')->nullable();
            $table->string('status', 24)->default('DRAFT')->index();
            $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();
        });

        Schema::create('procurement_award_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procurement_award_id')->constrained('procurement_awards')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
            // Snapshot printed identity/title so a later directory change cannot alter old records.
            $table->string('name');
            $table->string('position')->nullable();
            $table->unsignedSmallInteger('sequence')->default(1);
            $table->boolean('attended')->default(true);
            $table->boolean('is_retired')->default(false);
            $table->timestamps();
            $table->index(['procurement_award_id', 'sequence'], 'award_participants_sequence_idx');
        });

        Schema::create('procurement_bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procurement_id')->constrained('procurements')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->unsignedSmallInteger('revision')->default(1);
            $table->string('status', 24)->default('DRAFT')->index(); // DRAFT, SUBMITTED, WITHDRAWN, EVALUATED
            $table->timestamp('received_at')->nullable();
            // Payment details are bid-specific and are not exposed on the reusable public profile.
            $table->string('landbank_account_name')->nullable();
            $table->string('landbank_account_number', 128)->nullable();
            $table->decimal('total_bid_amount', 15, 2)->nullable(); // recalculated from bid items on save
            $table->boolean('is_responsive')->nullable();
            $table->text('evaluation_remarks')->nullable();
            $table->foreignId('evaluated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('evaluated_at')->nullable();
            $table->timestamps();
            $table->unique(['procurement_id', 'supplier_id', 'revision'], 'procurement_supplier_bid_revision_unique');
            $table->index(['procurement_id', 'status']);
        });

        Schema::create('procurement_bid_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procurement_bid_id')->constrained('procurement_bids')->cascadeOnDelete();
            $table->foreignId('procurement_item_id')->constrained('procurement_items')->cascadeOnDelete();
            $table->decimal('quoted_quantity', 12, 3)->nullable();
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->decimal('line_total', 15, 2)->nullable(); // server-calculated: quoted_quantity * unit_price
            $table->string('brand', 255)->nullable();
            $table->string('model', 255)->nullable();
            $table->text('offered_specifications')->nullable();
            $table->string('compliance_status', 24)->default('NOT_EVALUATED')->index();
            $table->boolean('is_responsive')->nullable();
            $table->text('evaluation_remarks')->nullable();
            $table->timestamps();
            $table->unique(['procurement_bid_id', 'procurement_item_id'], 'procurement_bid_item_unique');
            $table->index(['procurement_item_id', 'unit_price']);
        });

        Schema::create('procurement_bid_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procurement_bid_id')->constrained('procurement_bids')->cascadeOnDelete();
            $table->string('document_type', 100)->nullable()->index();
            $table->string('document_name');
            $table->string('reference_number', 128)->nullable();
            $table->string('issuer')->nullable();
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->longText('details')->nullable(); // text metadata only; submitted PDFs live in supplier_documents
            $table->string('compliance_status', 24)->default('NOT_EVALUATED')->index();
            $table->text('evaluation_remarks')->nullable();
            $table->unsignedSmallInteger('sequence')->default(1);
            $table->timestamps();
            $table->index(['procurement_bid_id', 'sequence'], 'bid_documents_sequence_idx');
        });

        Schema::create('procurement_award_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procurement_award_id')->constrained('procurement_awards')->cascadeOnDelete();
            // References a standalone item or, for LOT awards, the lot header.
            $table->foreignId('procurement_item_id')->constrained('procurement_items')->cascadeOnDelete();
            $table->foreignId('procurement_bid_id')->constrained('procurement_bids')->cascadeOnDelete();
            $table->decimal('awarded_amount', 15, 2)->nullable();
            $table->string('result_status', 24)->default('RECOMMENDED')->index();
            $table->text('remarks')->nullable();
            $table->foreignId('determined_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('determined_at')->nullable();
            $table->timestamps();
            $table->unique(['procurement_award_id', 'procurement_item_id'], 'procurement_award_item_unique');
            $table->index(['procurement_award_id', 'procurement_bid_id'], 'award_results_bid_idx');
        });
    }

    private function createSupplierDocumentsTable(): void
    {
        Schema::create('supplier_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->string('document_type', 100)->index();
            $table->string('document_name');
            $table->string('document_number', 128)->nullable();
            $table->string('issuer')->nullable();
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->string('disk', 32)->default('local');
            $table->string('file_path');
            $table->string('original_filename');
            $table->string('mime_type', 100)->default('application/pdf');
            $table->unsignedInteger('file_size')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['supplier_id', 'document_type', 'created_at'], 'supplier_document_latest_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_award_results');
        Schema::dropIfExists('procurement_bid_documents');
        Schema::dropIfExists('procurement_bid_items');
        Schema::dropIfExists('procurement_bids');
        Schema::dropIfExists('procurement_award_participants');
        Schema::dropIfExists('procurement_awards');
        Schema::dropIfExists('supplier_documents');
        Schema::dropIfExists('suppliers');
    }
};
