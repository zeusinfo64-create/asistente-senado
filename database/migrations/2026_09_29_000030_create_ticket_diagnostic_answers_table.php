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
        Schema::create('ticket_diagnostic_answers', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('question_id');
            $table->unsignedBigInteger('option_id')->nullable();
            $table->text('free_text')->nullable();
            $table->unsignedBigInteger('answered_by_id')->nullable();
            $table->timestamp('answered_at');
            $table->timestamps();

            $table->unique(['ticket_id', 'question_id']);
            $table->index('option_id');

            $table->foreign('ticket_id')->references('id')->on('tickets');
            $table->foreign('question_id')->references('id')->on('diagnostic_questions')->cascadeOnDelete();
            $table->foreign('option_id')->references('id')->on('diagnostic_options')->restrictOnDelete();
            $table->foreign('answered_by_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_diagnostic_answers');
    }
};
