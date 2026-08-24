<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g. procurement.add
            $table->string('module')->index();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('privileges', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // e.g. can_route,
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create('role_privileges', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('privilege_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'privilege_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_privileges');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('privileges');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
