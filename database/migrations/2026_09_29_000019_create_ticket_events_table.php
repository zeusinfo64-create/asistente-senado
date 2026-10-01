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
        Schema::create('ticket_events', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('event_type', 50);
            $table->unsignedBigInteger('from_status_id')->nullable();
            $table->unsignedBigInteger('to_status_id')->nullable();
            $table->unsignedBigInteger('from_priority_id')->nullable();
            $table->unsignedBigInteger('to_priority_id')->nullable();
            $table->unsignedBigInteger('from_assignee_id')->nullable();
            $table->unsignedBigInteger('to_assignee_id')->nullable();
            $table->unsignedBigInteger('from_category_id')->nullable();
            $table->unsignedBigInteger('to_category_id')->nullable();
            $table->text('notes')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['ticket_id', 'created_at']);
            $table->index('actor_id');
            $table->index('event_type');
            $table->index('from_category_id');
            $table->index('to_category_id');

            $table->foreign('ticket_id')->references('id')->on('tickets')->cascadeOnDelete();
            $table->foreign('actor_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('from_status_id')->references('id')->on('ticket_statuses')->restrictOnDelete();
            $table->foreign('to_status_id')->references('id')->on('ticket_statuses')->restrictOnDelete();
            $table->foreign('from_priority_id')->references('id')->on('ticket_priorities')->restrictOnDelete();
            $table->foreign('to_priority_id')->references('id')->on('ticket_priorities')->restrictOnDelete();
            $table->foreign('from_assignee_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('to_assignee_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('from_category_id')->references('id')->on('categories')->restrictOnDelete();
            $table->foreign('to_category_id')->references('id')->on('categories')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_events');
    }
};
