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
        Schema::create('ai_analyses', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('analyzable_type', 100);
            $table->unsignedBigInteger('analyzable_id');
            $table->string('analysis_type', 50);
            $table->string('provider', 50);
            $table->string('model', 100);
            $table->string('model_version', 50)->nullable();
            $table->unsignedBigInteger('suggested_category_id')->nullable();
            $table->unsignedBigInteger('suggested_subcategory_id')->nullable();
            $table->unsignedBigInteger('suggested_priority_id')->nullable();
            $table->decimal('confidence', 5, 4)->nullable();
            $table->text('summary')->nullable();
            $table->string('status', 20)->default('pendiente');
            $table->unsignedBigInteger('applied_by_id')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->json('raw_response')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('analyzed_at');
            $table->timestamps();

            // Índice polimórfico compuesto NO único (§B.4 / §E.2 V1.1)
            $table->index(['analyzable_type', 'analyzable_id']);
            $table->index('analysis_type');
            $table->index('provider');
            $table->index('status');
            $table->index('suggested_category_id');
            $table->index('applied_by_id');
            $table->index('applied_at');

            $table->foreign('suggested_category_id')->references('id')->on('categories')->restrictOnDelete();
            $table->foreign('suggested_subcategory_id')->references('id')->on('categories')->restrictOnDelete();
            $table->foreign('suggested_priority_id')->references('id')->on('ticket_priorities')->restrictOnDelete();
            $table->foreign('applied_by_id')->references('id')->on('users')->nullOnDelete();
        });

        // §F.2 IR13: confianza de IA entre 0 y 1
        DB::statement('ALTER TABLE ai_analyses ADD CONSTRAINT chk_ai_analyses_confidence CHECK (confidence >= 0 AND confidence <= 1)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_analyses');
    }
};
