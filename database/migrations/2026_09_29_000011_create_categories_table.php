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
        Schema::create('categories', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('code', 30)->nullable();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('code');
            $table->index('parent_id');
            $table->index('is_active');
            $table->unique(['id', 'parent_id'], 'uq_categories_id_parent');
            $table->foreign('parent_id')->references('id')->on('categories')->restrictOnDelete();
        });

        // Columna generada COALESCE(parent_id, 0) → UNIQUE(name, parent_key) evita duplicados con NULL (§D.10, §K.2)
        DB::statement('ALTER TABLE categories ADD COLUMN parent_key BIGINT AS (COALESCE(parent_id, 0)) STORED');
        DB::statement('ALTER TABLE categories ADD UNIQUE KEY categories_name_parent_key_unique (name, parent_key)');

        // Nota: el CHECK (parent_id IS NULL OR parent_id <> id) de §D.10/§F.1 NO es implementable
        // en MySQL 8.4 (error 3818: un CHECK no puede referenciar una columna auto-increment).
        // La FK a categories.id impide la auto-referencia al INSERTar.

        // Capa 4 (§F.1, opcional): máximo 2 niveles de categorías
        DB::statement("CREATE TRIGGER trg_categories_max_depth_insert BEFORE INSERT ON categories FOR EACH ROW BEGIN IF NEW.parent_id IS NOT NULL AND (SELECT parent_id FROM categories WHERE id = NEW.parent_id) IS NOT NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Máximo 2 niveles de categorías'; END IF; END");
        DB::statement("CREATE TRIGGER trg_categories_max_depth_update BEFORE UPDATE ON categories FOR EACH ROW BEGIN IF NEW.parent_id IS NOT NULL AND (SELECT parent_id FROM categories WHERE id = NEW.parent_id) IS NOT NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Máximo 2 niveles de categorías'; END IF; END");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS trg_categories_max_depth_insert');
        DB::statement('DROP TRIGGER IF EXISTS trg_categories_max_depth_update');

        Schema::dropIfExists('categories');
    }
};
