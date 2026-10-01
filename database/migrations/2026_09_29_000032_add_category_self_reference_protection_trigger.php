<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Fase 2.1: refuerzo de la integridad de `categories`. El CHECK
     * `parent_id IS NULL OR parent_id <> id` de §D.10/§F.1 no es implementable en
     * MySQL 8.4 (error 3818: un CHECK no puede referenciar una columna auto-increment),
     * por lo que la protección contra autorreferencia se garantiza en la capa trigger.
     *
     * Se conserva intacta la validación de máximo 2 niveles (§F.1 Capa 4).
     */
    public function up(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS trg_categories_max_depth_insert');
        DB::statement('DROP TRIGGER IF EXISTS trg_categories_max_depth_update');

        DB::statement("CREATE TRIGGER trg_categories_max_depth_insert BEFORE INSERT ON categories FOR EACH ROW BEGIN IF NEW.parent_id IS NOT NULL AND NEW.id > 0 AND NEW.parent_id = NEW.id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Una categoría no puede ser su propio padre'; END IF; IF NEW.parent_id IS NOT NULL AND (SELECT parent_id FROM categories WHERE id = NEW.parent_id) IS NOT NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Máximo 2 niveles de categorías'; END IF; END");

        DB::statement("CREATE TRIGGER trg_categories_max_depth_update BEFORE UPDATE ON categories FOR EACH ROW BEGIN IF NEW.parent_id IS NOT NULL AND NEW.id > 0 AND NEW.parent_id = NEW.id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Una categoría no puede ser su propio padre'; END IF; IF NEW.parent_id IS NOT NULL AND (SELECT parent_id FROM categories WHERE id = NEW.parent_id) IS NOT NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Máximo 2 niveles de categorías'; END IF; END");
    }

    /**
     * Reverse the migrations.
     *
     * Restaura los triggers originales de la Fase 2 (solo máximo 2 niveles).
     */
    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS trg_categories_max_depth_insert');
        DB::statement('DROP TRIGGER IF EXISTS trg_categories_max_depth_update');

        DB::statement("CREATE TRIGGER trg_categories_max_depth_insert BEFORE INSERT ON categories FOR EACH ROW BEGIN IF NEW.parent_id IS NOT NULL AND (SELECT parent_id FROM categories WHERE id = NEW.parent_id) IS NOT NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Máximo 2 niveles de categorías'; END IF; END");

        DB::statement("CREATE TRIGGER trg_categories_max_depth_update BEFORE UPDATE ON categories FOR EACH ROW BEGIN IF NEW.parent_id IS NOT NULL AND (SELECT parent_id FROM categories WHERE id = NEW.parent_id) IS NOT NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Máximo 2 niveles de categorías'; END IF; END");
    }
};
