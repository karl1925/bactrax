<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('item_bank_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_code', 64)->nullable()->unique();
            $table->string('description');
            $table->string('category', 120)->nullable()->index();
            $table->string('unit', 64)->nullable();
            $table->decimal('reference_price', 15, 2)->nullable();
            $table->text('specifications')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index('description');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('item_bank_items');
    }
};
