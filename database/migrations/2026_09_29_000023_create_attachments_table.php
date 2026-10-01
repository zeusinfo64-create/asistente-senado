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
        Schema::create('attachments', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('attachable_type', 100);
            $table->unsignedBigInteger('attachable_id');
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('path', 500);
            $table->string('disk', 30)->default('local');
            $table->string('extension', 10)->nullable();
            $table->string('mime_type', 100);
            $table->unsignedInteger('size_bytes');
            $table->unsignedBigInteger('uploaded_by_id');
            $table->timestamps();

            // Índice polimórfico compuesto NO único (§B.4 / §E.2 V1.1)
            $table->index(['attachable_type', 'attachable_id']);
            $table->index('uploaded_by_id');

            $table->foreign('uploaded_by_id')->references('id')->on('users')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
