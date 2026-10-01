<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ticket_comments', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('author_id');
            $table->string('visibility', 20)->default('publico');
            $table->text('body');
            $table->timestamps();

            $table->index(['ticket_id', 'created_at']);
            $table->index('author_id');

            $table->foreign('ticket_id')->references('id')->on('tickets')->cascadeOnDelete();
            $table->foreign('author_id')->references('id')->on('users')->restrictOnDelete();
        });

        // §F.2 IR18: visibilidad de comentarios
        DB::statement("ALTER TABLE ticket_comments ADD CONSTRAINT chk_ticket_comments_visibility CHECK (visibility IN ('publico', 'interno', 'supervisor'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_comments');
    }
};
