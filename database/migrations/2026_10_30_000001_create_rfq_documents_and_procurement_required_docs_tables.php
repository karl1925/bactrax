<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rfq_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('sequence');
            $table->string('title');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sequence']);
        });

        Schema::create('procurement_required_docs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procurement_id')->constrained('procurements')->cascadeOnDelete();
            $table->foreignId('rfq_document_id')->constrained('rfq_documents')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['procurement_id', 'rfq_document_id'], 'procurement_required_docs_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_required_docs');
        Schema::dropIfExists('rfq_documents');
    }
};
