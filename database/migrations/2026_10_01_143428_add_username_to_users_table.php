<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Agrega `users.username` como identificador institucional de acceso.
     *
     * Secuencia sobre bases con datos existentes:
     *   1. Columna nullable para permitir el backfill controlado.
     *   2. Backfill a partir del correo institucional de cada registro.
     *   3. Índice UNIQUE.
     *   4. Columna NOT NULL.
     *
     * `email` se conserva intacto (UNIQUE, NOT NULL) como dato institucional de
     * contacto; deja de ser el identificador de autenticación. `password`
     * permanece nullable porque existen proveedores externos de autenticación,
     * pero `username` es obligatorio para todo usuario del sistema.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('username', 190)->nullable()->after('email');
        });

        $this->backfillUsernames();

        Schema::table('users', function (Blueprint $table): void {
            $table->unique('username');
        });

        // Límite de 190 caracteres por el índice con utf8mb4.
        Schema::table('users', function (Blueprint $table): void {
            $table->string('username', 190)->nullable(false)->change();
        });
    }

    /**
     * Deriva un username institucional único a partir del correo existente.
     *
     * Los usuarios de desarrollo ya sembrados reciben su identidad conocida
     * (tecnico, solicitante) en lugar de un valor genérico. Cualquier otro
     * registro recibe la parte local del correo saneada.
     *
     * No existe ningún username de reserva: si la parte local del correo no
     * permite derivar una identidad válida, la migración se detiene para que el
     * dato institucional se corrija en origen.
     */
    private function backfillUsernames(): void
    {
        $canonical = [
            'admin@helpdesk.local' => 'admin',
            'tecnico@helpdesk.local' => 'tecnico',
            'solicitante@helpdesk.local' => 'solicitante',
        ];

        // Solo se reserva lo ya persistido en la columna, no los candidatos
        // canónicos: si se sembraran aquí, "admin" se renumeraría a "admin.2".
        $taken = DB::table('users')
            ->whereNotNull('username')
            ->where('username', '<>', '')
            ->pluck('username')
            ->all();

        DB::table('users')->orderBy('id')->each(function (object $user) use ($canonical, &$taken): void {
            if ($user->username !== null && $user->username !== '') {
                return;
            }

            $candidate = $canonical[$user->email] ?? $this->usernameFromEmail((string) $user->email);

            $username = $this->uniqueUsername($candidate, $taken);

            DB::table('users')->where('id', $user->id)->update(['username' => $username]);

            $taken[] = $username;
        });
    }

    /**
     * Convierte un correo en un username institucional válido.
     *
     * @throws RuntimeException Si el correo no tiene una parte local utilizable.
     */
    private function usernameFromEmail(string $email): string
    {
        $local = Str::before($email, '@');
        $clean = Str::of($local)->lower()->replaceMatches('/[^a-z0-9._-]+/', '-')->trim('-')->limit(180, '')->value();

        if ($clean === '') {
            throw new RuntimeException(sprintf(
                'No se puede derivar el username institucional del correo "%s": su parte local no contiene '
                .'caracteres alfanuméricos válidos. Corrija el correo del usuario antes de migrar; no se genera '
                .'ninguna identidad genérica.',
                $email
            ));
        }

        return $clean;
    }

    /**
     * Garantiza unicidad sin truncar silenciosamente la identidad.
     */
    private function uniqueUsername(string $candidate, array $taken): string
    {
        if (! in_array($candidate, $taken, true)) {
            return $candidate;
        }

        $suffix = 2;

        while (in_array($candidate.'.'.$suffix, $taken, true)) {
            $suffix++;
        }

        return $candidate.'.'.$suffix;
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['username']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('username');
        });
    }
};
