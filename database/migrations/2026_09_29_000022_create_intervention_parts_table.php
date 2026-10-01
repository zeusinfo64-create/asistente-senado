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
        Schema::create('intervention_parts', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->unsignedBigInteger('intervention_id');
            $table->unsignedBigInteger('spare_part_id');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->string('unit', 30)->default('unidad');
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->date('used_at')->nullable();
            $table->timestamps();

            $table->index('intervention_id');
            $table->index('spare_part_id');

            $table->foreign('intervention_id')->references('id')->on('ticket_interventions')->cascadeOnDelete();
            $table->foreign('spare_part_id')->references('id')->on('spare_parts')->restrictOnDelete();
        });

        // §F.2 IR12: repuestos con cantidad positiva
        DB::statement('ALTER TABLE intervention_parts ADD CONSTRAINT chk_intervention_parts_quantity_positive CHECK (quantity > 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intervention_parts');
    }
};
