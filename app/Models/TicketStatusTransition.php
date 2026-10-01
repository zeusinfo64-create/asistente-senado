<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketStatusTransition extends Model
{
    protected function casts(): array
    {
        return [
            'requires_reason' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Resuelve la transición de estado entre dos códigos.
     *
     * Precedencia: si existe una fila configurada en `ticket_status_transitions`
     * para el par, manda la configuración (is_active, requires_reason,
     * allowed_role_id). Si no existe fila para el par se usa la matriz
     * documentada del flujo (docs §G.1/§G.3, FASE 6.2 §21).
     *
     * @param  string  $fromCode  Código del estado actual.
     * @param  string  $toCode  Código del estado destino.
     * @return array{requires_reason: bool, roles: array<int, string>|null}|null
     *                                                                           null = transición no permitida; roles null = cualquier rol con alcance.
     */
    public static function resolve(string $fromCode, string $toCode): ?array
    {
        $configured = static::query()
            ->whereHas('fromStatus', fn ($query) => $query->where('code', $fromCode))
            ->whereHas('toStatus', fn ($query) => $query->where('code', $toCode))
            ->with('allowedRole')
            ->first();

        if ($configured !== null) {
            if (! $configured->is_active) {
                return null;
            }

            return [
                'requires_reason' => $configured->requires_reason,
                'roles' => $configured->allowedRole === null ? null : [$configured->allowedRole->code],
            ];
        }

        return self::defaultMatrix()[$fromCode.'|'.$toCode] ?? null;
    }

    /**
     * Matriz documentada del ciclo de vida cuando la tabla está vacía.
     *
     * No existe REOPENED. CANCELLED está disponible desde cualquier estado no
     * terminal (docs §G.1). WAITING y CANCELLED exigen motivo (docs §G.3.4).
     *
     * @return array<string, array{requires_reason: bool, roles: array<int, string>}>
     */
    private static function defaultMatrix(): array
    {
        $nonTerminal = ['REGISTERED', 'ASSIGNED', 'IN_PROGRESS', 'WAITING', 'RESOLVED'];

        $matrix = [
            'REGISTERED|ASSIGNED' => ['requires_reason' => false, 'roles' => ['ADMIN']],
            'ASSIGNED|IN_PROGRESS' => ['requires_reason' => false, 'roles' => ['ADMIN', 'TECH']],
            'IN_PROGRESS|WAITING' => ['requires_reason' => true, 'roles' => ['ADMIN', 'TECH']],
            'WAITING|IN_PROGRESS' => ['requires_reason' => false, 'roles' => ['ADMIN', 'TECH']],
            'IN_PROGRESS|RESOLVED' => ['requires_reason' => false, 'roles' => ['ADMIN', 'TECH']],
            'RESOLVED|CLOSED' => ['requires_reason' => false, 'roles' => ['ADMIN', 'REQUESTER']],
        ];

        foreach ($nonTerminal as $from) {
            $matrix[$from.'|CANCELLED'] = ['requires_reason' => true, 'roles' => ['ADMIN']];
        }

        return $matrix;
    }

    public function fromStatus(): BelongsTo
    {
        return $this->belongsTo(TicketStatus::class, 'from_status_id');
    }

    public function toStatus(): BelongsTo
    {
        return $this->belongsTo(TicketStatus::class, 'to_status_id');
    }

    public function allowedRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'allowed_role_id');
    }
}
