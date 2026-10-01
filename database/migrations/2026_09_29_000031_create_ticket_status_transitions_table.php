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
        Schema::create('ticket_status_transitions', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->unsignedBigInteger('from_status_id');
            $table->unsignedBigInteger('to_status_id');
            $table->unsignedBigInteger('allowed_role_id')->nullable();
            $table->boolean('requires_reason')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['from_status_id', 'to_status_id']);
            $table->index('is_active');

            $table->foreign('from_status_id')->references('id')->on('ticket_statuses')->restrictOnDelete();
            $table->foreign('to_status_id')->references('id')->on('ticket_statuses')->restrictOnDelete();
            $table->foreign('allowed_role_id')->references('id')->on('roles')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_status_transitions');
    }
};
