<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\TicketStatusTransition;
use App\Models\User;

/**
 * Autorización de tickets (FASE 6.2).
 *
 * No existe jerarquía de roles: las capacidades se evalúan por código exacto
 * (ADMIN, TECH o REQUESTER). El alcance de consulta es:
 *
 * - ADMIN: todos los tickets.
 * - REQUESTER: únicamente los tickets donde es solicitante.
 * - TECH: únicamente los tickets asignados a él.
 */
class TicketPolicy
{
    private const ADMIN = 'ADMIN';

    private const TECH = 'TECH';

    private const REQUESTER = 'REQUESTER';

    /**
     * Todos los usuarios con un rol funcional pueden listar (el alcance real se
     * aplica en la consulta); los usuarios sin rol no ven nada.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasRole($user, self::ADMIN)
            || $this->hasRole($user, self::TECH)
            || $this->hasRole($user, self::REQUESTER);
    }

    /**
     * Alcance de consulta: base del control anti-IDOR (fuera de alcance → 404).
     *
     * Es la unión de las capacidades de cada rol: un usuario con varios roles
     * ve todo lo que cualquiera de sus roles le permite ver.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        if ($this->hasRole($user, self::ADMIN)) {
            return true;
        }

        if ($this->hasRole($user, self::REQUESTER) && $ticket->requester_id === $user->id) {
            return true;
        }

        if ($this->hasRole($user, self::TECH) && $ticket->assigned_to_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Creación: ADMIN (para sí o para otro solicitante) y REQUESTER.
     * TECH no crea tickets.
     */
    public function create(User $user): bool
    {
        return $this->hasRole($user, self::ADMIN) || $this->hasRole($user, self::REQUESTER);
    }

    /**
     * Edición de campos permitidos dentro del alcance (FASE 6.2).
     *
     * El estado terminal (409) y los campos permitidos por rol (403) se
     * validan en la capa HTTP, no aquí: la política solo decide alcance.
     */
    public function update(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket);
    }

    /**
     * Asignación/reasignación: solo ADMIN sobre tickets dentro de su alcance
     * (docs §G.2.1: "Solo Administrador asigna/reasigna").
     */
    public function assign(User $user, Ticket $ticket): bool
    {
        return $this->hasRole($user, self::ADMIN) && $this->view($user, $ticket);
    }

    /**
     * Cambio de estado: alcance + roles admitidos por la transición resuelta
     * (configuración de ticket_status_transitions cuando existe fila para el
     * par; matriz documentada en su defecto).
     */
    public function changeStatus(User $user, Ticket $ticket, string $toStatusCode): bool
    {
        if (! $this->view($user, $ticket)) {
            return false;
        }

        $transition = TicketStatusTransition::resolve($ticket->status->code, $toStatusCode);

        if ($transition === null) {
            return false;
        }

        $roles = $transition['roles'];

        if ($roles === null) {
            // allowed_role_id NULL = cualquier rol con alcance (docs §D.31).
            return true;
        }

        foreach ($roles as $roleCode) {
            if ($this->hasRole($user, $roleCode)) {
                return true;
            }
        }

        return false;
    }

    private function hasRole(User $user, string $code): bool
    {
        return $user->roles->contains('code', $code);
    }
}
