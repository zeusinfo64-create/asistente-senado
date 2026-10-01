<?php

namespace Database\Seeders;

use Illuminate\Database\Eloquent\Model;

/**
 * Estrategia de upsert idempotente para datos maestros.
 *
 * Los modelos del proyecto no declaran $fillable (mass assignment deshabilitado a
 * propósito, ver comentario en App\Models\User), por lo que los atributos se asignan
 * explícitamente al modelo en lugar de usar relleno masivo.
 *
 * La localización se hace siempre por claves naturales/únicas (código, email, etc.),
 * nunca por IDs numéricos fijos, de modo que `php artisan db:seed` puede repetirse
 * sin generar duplicados.
 */
trait UpsertsMasterData
{
    /**
     * Crea o actualiza un registro localizándolo por atributos únicos.
     *
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $modelClass  Modelo Eloquent a persistir
     * @param  array<string, mixed>  $lookup  Atributos únicos que localizan el registro
     * @param  array<string, mixed>  $values  Atributos a crear o actualizar
     * @return TModel
     */
    protected function updateOrCreateRecord(string $modelClass, array $lookup, array $values): Model
    {
        /** @var TModel $record */
        $record = $modelClass::query()->where($lookup)->first();

        if ($record === null) {
            /** @var TModel $record */
            $record = new $modelClass;

            foreach ($lookup as $attribute => $value) {
                $record->{$attribute} = $value;
            }
        }

        foreach ($values as $attribute => $value) {
            $record->{$attribute} = $value;
        }

        $record->save();

        return $record;
    }
}
