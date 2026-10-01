<?php

namespace Tests\Feature\Tickets;

use App\Models\Asset;
use App\Models\AssetState;
use App\Models\AssetType;
use App\Models\Category;
use App\Models\OrganizationalUnit;
use App\Models\OrganizationalUnitType;
use App\Models\Ticket;
use App\Models\TicketEvent;
use App\Models\TicketPriority;
use App\Models\TicketSequence;
use App\Models\TicketStatus;
use App\Models\TicketType;
use App\Models\User;
use Tests\Feature\Auth\AuthTestCase;

/**
 * Infraestructura común de las pruebas de tickets (FASE 6.2).
 *
 * Crea datos dentro de la transacción de prueba (se revierten al terminar)
 * sobre el seed real de helpdesk_v11, sin fábricas de tickets (convención:
 * asignación directa de propiedades).
 */
abstract class TicketTestCase extends AuthTestCase
{
    private ?User $memoizedRequester = null;

    /**
     * Primera unidad organizacional activa del seed.
     */
    protected function unit(): OrganizationalUnit
    {
        return OrganizationalUnit::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->firstOrFail();
    }

    /**
     * Crea una unidad adicional para probar límites de unidad.
     */
    protected function createUnit(string $code): OrganizationalUnit
    {
        $type = OrganizationalUnitType::query()->orderBy('id')->firstOrFail();

        $unit = new OrganizationalUnit;
        $unit->organizational_unit_type_id = $type->id;
        $unit->code = $code;
        $unit->name = "Unidad {$code}";
        $unit->is_active = true;
        $unit->save();

        return $unit;
    }

    /**
     * Usuario ADMIN activo con unidad organizacional.
     */
    protected function admin(): User
    {
        return $this->withUnit($this->createUserWithRole('ADMIN'));
    }

    /**
     * Usuario TECH activo con unidad organizacional.
     */
    protected function tech(): User
    {
        return $this->withUnit($this->createUserWithRole('TECH'));
    }

    /**
     * Usuario REQUESTER activo con unidad organizacional.
     */
    protected function requester(): User
    {
        return $this->withUnit($this->createUserWithRole('REQUESTER'));
    }

    /**
     * Solicitante por defecto de createTicket (memorizado por test).
     */
    protected function defaultRequester(): User
    {
        return $this->memoizedRequester ??= $this->requester();
    }

    /**
     * Crea un ticket con valores por defecto y registra su evento "created".
     *
     * @param  array<string, mixed>  $attributes  Sobrescrituras de columnas.
     */
    protected function createTicket(array $attributes = []): Ticket
    {
        $requesterId = $attributes['requester_id'] ?? $this->defaultRequester()->id;

        $ticket = new Ticket;
        $ticket->ticket_number = TicketSequence::nextNumber();
        $ticket->ticket_type_id = $attributes['ticket_type_id'] ?? $this->typeId('SUPPORT');
        $ticket->requester_id = $requesterId;
        $ticket->created_by_id = $attributes['created_by_id'] ?? $requesterId;
        $ticket->organizational_unit_id = $attributes['organizational_unit_id'] ?? $this->unit()->id;
        $ticket->category_id = $attributes['category_id'] ?? null;
        $ticket->subcategory_id = $attributes['subcategory_id'] ?? null;
        $ticket->priority_id = $attributes['priority_id'] ?? $this->priorityId('MEDIUM');
        $ticket->status_id = $attributes['status_id'] ?? $this->statusId('REGISTERED');
        $ticket->asset_id = $attributes['asset_id'] ?? null;
        $ticket->assigned_to_id = $attributes['assigned_to_id'] ?? null;
        $ticket->subject = $attributes['subject'] ?? 'Falla en el equipo asignado';
        $ticket->description = $attributes['description'] ?? 'El equipo no enciende después de la actualización.';
        $ticket->resolution_notes = $attributes['resolution_notes'] ?? null;
        $ticket->channel = 'web';

        if (array_key_exists('assigned_at', $attributes)) {
            $ticket->assigned_at = $attributes['assigned_at'];
        }

        $ticket->save();

        $event = new TicketEvent;
        $event->ticket_id = $ticket->id;
        $event->event_type = 'created';
        $event->actor_id = $ticket->created_by_id;
        $event->to_status_id = $ticket->status_id;
        $event->to_priority_id = $ticket->priority_id;
        $event->save();

        return $ticket;
    }

    /**
     * Usuario con unidad organizacional asignada.
     */
    protected function withUnit(User $user): User
    {
        $user->organizational_unit_id = $this->unit()->id;
        $user->save();

        return $user;
    }

    protected function statusId(string $code): int
    {
        return TicketStatus::query()->where('code', $code)->firstOrFail()->id;
    }

    protected function priorityId(string $code): int
    {
        return TicketPriority::query()->where('code', $code)->firstOrFail()->id;
    }

    protected function typeId(string $code): int
    {
        return TicketType::query()->where('code', $code)->firstOrFail()->id;
    }

    /**
     * Par [raíz, subcategoría válida] del seed.
     *
     * @return array{0: Category, 1: Category}
     */
    protected function categoryPair(): array
    {
        $root = Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->whereHas('children', fn ($query) => $query->where('is_active', true))
            ->orderBy('id')
            ->firstOrFail();

        return [$root, $root->children->where('is_active', true)->first()];
    }

    /**
     * Subcategoría válida de una raíz distinta a la indicada.
     */
    protected function foreignSubcategory(Category $root): Category
    {
        return Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->whereKeyNot($root->id)
            ->whereHas('children', fn ($query) => $query->where('is_active', true))
            ->orderBy('id')
            ->firstOrFail()
            ->children
            ->where('is_active', true)
            ->first();
    }

    /**
     * Activo dado de baja para negativos de negocio.
     */
    protected function createDecommissionedAsset(): Asset
    {
        $state = AssetState::query()->where('code', 'DECOMMISSIONED')->firstOrFail();
        $type = AssetType::query()->orderBy('id')->firstOrFail();

        $asset = new Asset;
        $asset->asset_code = 'TEST-OFF-'.uniqid();
        $asset->asset_type_id = $type->id;
        $asset->asset_state_id = $state->id;
        $asset->decommissioned_at = now();
        $asset->save();

        return $asset;
    }

    /**
     * Primer activo activo del seed (TEST-PC-001).
     */
    protected function activeAsset(): Asset
    {
        $state = AssetState::query()->where('code', 'ACTIVE')->firstOrFail();

        return Asset::query()
            ->where('asset_state_id', $state->id)
            ->whereNull('decommissioned_at')
            ->orderBy('id')
            ->firstOrFail();
    }
}
