<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_signatures', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            $table->string('p12_path');
            $table->string('p12_name')->nullable();
            $table->text('p12_password')->nullable();
            $table->string('signature_image_path')->nullable();
            $table->text('signature_text')->nullable();
            $table->string('certificate_subject')->nullable();
            $table->string('certificate_issuer')->nullable();
            $table->text('certificate_serial')->nullable();

            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_signatures');
    }
};
