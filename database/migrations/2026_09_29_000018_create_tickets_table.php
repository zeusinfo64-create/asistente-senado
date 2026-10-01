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
        Schema::create('tickets', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('ticket_number', 30);
            $table->unsignedBigInteger('ticket_type_id');
            $table->unsignedBigInteger('requester_id');
            $table->unsignedBigInteger('created_by_id');
            $table->unsignedBigInteger('organizational_unit_id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('subcategory_id')->nullable();
            $table->string('classification_source', 20)->nullable();
            $table->unsignedBigInteger('priority_id');
            $table->unsignedBigInteger('status_id');
            $table->unsignedBigInteger('asset_id')->nullable();
            $table->unsignedBigInteger('assigned_to_id')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->unsignedBigInteger('sla_policy_id')->nullable();
            $table->timestamp('sla_first_response_due_at')->nullable();
            $table->timestamp('sla_resolution_due_at')->nullable();
            $table->boolean('sla_response_breached')->nullable();
            $table->boolean('sla_resolution_breached')->nullable();
            $table->string('subject', 200);
            $table->text('description');
            $table->text('resolution_notes')->nullable();
            $table->string('channel', 20)->default('web');
            $table->timestamps();

            $table->unique('ticket_number');
            $table->index(['status_id', 'created_at']);
            $table->index(['requester_id', 'created_at']);
            $table->index(['assigned_to_id', 'status_id']);
            $table->index(['organizational_unit_id', 'created_at']);
            $table->index(['category_id', 'created_at']);
            $table->index('subcategory_id');
            $table->index('priority_id');
            $table->index('asset_id');
            $table->index('channel');
            $table->index('ticket_type_id');
            $table->index('created_by_id');
            $table->index('created_at');
            $table->index('started_at');
            $table->index('first_response_at');
            $table->index('resolved_at');
            $table->index('closed_at');
            $table->index('sla_policy_id');
            $table->index('sla_first_response_due_at');
            $table->index('sla_resolution_due_at');

            $table->foreign('ticket_type_id')->references('id')->on('ticket_types')->restrictOnDelete();
            $table->foreign('requester_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('created_by_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('assigned_to_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('organizational_unit_id')->references('id')->on('organizational_units')->restrictOnDelete();
            $table->foreign('priority_id')->references('id')->on('ticket_priorities')->restrictOnDelete();
            $table->foreign('status_id')->references('id')->on('ticket_statuses')->restrictOnDelete();
            $table->foreign('asset_id')->references('id')->on('assets')->restrictOnDelete();
            $table->foreign('sla_policy_id')->references('id')->on('sla_policies')->restrictOnDelete();

            // FK compuesta (§F.1 Capa 3): subcategoría debe ser hija exacta de la categoría.
            // Nota V1.1/MySQL 8.4: se omite ON UPDATE CASCADE porque MySQL 8.4 prohíbe usar en un
            // CHECK columnas que participan en acciones de cascada de una FK (error 3823).
            // ON DELETE RESTRICT se conserva; ON UPDATE queda en RESTRICT (comportamiento por defecto).
            $table->foreign(['subcategory_id', 'category_id'])
                ->references(['id', 'parent_id'])
                ->on('categories')
                ->onDelete('restrict');
        });

        // Capa 2 (§F.1 / §F.2 IR2, IR3): paridad categoría-subcategoría a nivel de fila
        DB::statement('ALTER TABLE tickets ADD CONSTRAINT chk_tickets_category_subcategory_parity CHECK ((category_id IS NULL) = (subcategory_id IS NULL))');
        DB::statement('ALTER TABLE tickets ADD CONSTRAINT chk_tickets_subcategory_not_category CHECK (subcategory_id IS NULL OR subcategory_id <> category_id)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
