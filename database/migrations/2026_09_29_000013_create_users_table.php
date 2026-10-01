<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 190);
            $table->string('phone', 30)->nullable();
            $table->string('job_title', 100)->nullable();
            $table->string('password')->nullable();
            $table->string('auth_provider', 30)->default('local');
            $table->string('external_id', 190)->nullable();
            $table->unsignedBigInteger('organizational_unit_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->unique('email');
            $table->unique(['auth_provider', 'external_id']);
            $table->index('organizational_unit_id');
            $table->index('is_active');

            $table->foreign('organizational_unit_id')->references('id')->on('organizational_units')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
