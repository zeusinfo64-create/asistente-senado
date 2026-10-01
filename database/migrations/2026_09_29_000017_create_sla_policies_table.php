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
        Schema::create('sla_policies', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('code', 50);
            $table->string('name', 100);
            $table->unsignedBigInteger('priority_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('organizational_unit_id')->nullable();
            $table->unsignedBigInteger('ticket_type_id')->nullable();
            $table->decimal('response_hours', 8, 2);
            $table->decimal('resolution_hours', 8, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('code');
            $table->index('priority_id');
            $table->index('category_id');
            $table->index('organizational_unit_id');
            $table->index('ticket_type_id');
            $table->index('is_active');

            $table->foreign('priority_id')->references('id')->on('ticket_priorities')->restrictOnDelete();
            $table->foreign('category_id')->references('id')->on('categories')->restrictOnDelete();
            $table->foreign('organizational_unit_id')->references('id')->on('organizational_units')->restrictOnDelete();
            $table->foreign('ticket_type_id')->references('id')->on('ticket_types')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sla_policies');
    }
};
