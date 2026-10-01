<?php

namespace Tests\Feature\Tickets;

use Laravel\Sanctum\Sanctum;

/**
 * Pruebas del ciclo de vida de tickets (FASE 6.2 §19-§21, §38.5).
 */
class TicketLifecycleTest extends TicketTestCase
{
    public function test_full_lifecycle_happy_path(): void
    {
        $admin = $this->admin();
        $technician = $this->tech();
        $requester = $this->requester();
        $ticket = $this->createTicket(['requester_id' => $requester->id]);

        Sanctum::actingAs($admin);
        $this->patchJson("/api/tickets/{$ticket->id}", ['assigned_technician_id' => $technician->id])->assertOk();

        Sanctum::actingAs($technician);

        $this->patchJson("/api/tickets/{$ticket->id}", ['status' => 'IN_PROGRESS'])->assertOk();
        $this->assertNotNull($ticket->refresh()->started_at);

        $this->patchJson("/api/tickets/{$ticket->id}", ['status' => 'WAITING'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['notes' => 'Debe indicar el motivo del cambio de estado.']);

        $this->patchJson("/api/tickets/{$ticket->id}", [
            'status' => 'WAITING',
            'notes' => 'Esperando repuesto en bodega.',
        ])->assertOk();

        $this->patchJson("/api/tickets/{$ticket->id}", ['status' => 'IN_PROGRESS'])->assertOk();

        $this->patchJson("/api/tickets/{$ticket->id}", ['status' => 'RESOLVED'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'resolution_notes' => 'Debe indicar las notas de resolución al resolver el ticket.',
            ]);

        $this->patchJson("/api/tickets/{$ticket->id}", [
            'status' => 'RESOLVED',
            'resolution_notes' => 'Se reemplaza la fuente de poder y el equipo opera normalmente.',
        ])->assertOk();

        $ticket->refresh();
        $this->assertSame('RESOLVED', $ticket->status->code);
        $this->assertNotNull($ticket->resolved_at);
        $this->assertNull($ticket->closed_at);

        Sanctum::actingAs($requester);
        $this->patchJson("/api/tickets/{$ticket->id}", ['status' => 'CLOSED'])->assertOk();

        $ticket->refresh();
        $this->assertSame('CLOSED', $ticket->status->code);
        $this->assertNotNull($ticket->closed_at);

        $this->assertSame(
            ['created', 'assigned', 'status_changed', 'status_changed', 'status_changed', 'resolved', 'closed'],
            $ticket->events()->orderBy('id')->pluck('event_type')->all(),
        );

        $waiting = $ticket->events()->where('notes', 'Esperando repuesto en bodega.')->firstOrFail();
        $this->assertSame($this->statusId('IN_PROGRESS'), $waiting->from_status_id);
        $this->assertSame($this->statusId('WAITING'), $waiting->to_status_id);

        $this->patchJson("/api/tickets/{$ticket->id}", ['subject' => 'Post cierre'])
            ->assertStatus(409)
            ->assertJson(['message' => 'Los tickets cerrados o cancelados no pueden modificarse.']);
    }

    public function test_invalid_transitions_return_409(): void
    {
        $ticket = $this->createTicket();

        Sanctum::actingAs($this->admin());

        $this->patchJson("/api/tickets/{$ticket->id}", ['status' => 'CLOSED'])
            ->assertStatus(409)
            ->assertJson(['message' => 'Transición de estado no permitida.']);

        $this->patchJson("/api/tickets/{$ticket->id}", ['status' => 'REGISTERED'])
            ->assertStatus(409)
            ->assertJson(['message' => 'Transición de estado no permitida.']);

        $this->assertSame('REGISTERED', $ticket->refresh()->status->code);
    }

    public function test_transition_roles_are_enforced(): void
    {
        $requester = $this->requester();
        $technician = $this->tech();

        $waitingTicket = $this->createTicket([
            'requester_id' => $requester->id,
            'status_id' => $this->statusId('WAITING'),
        ]);
        $resolvedTicket = $this->createTicket([
            'assigned_to_id' => $technician->id,
            'status_id' => $this->statusId('RESOLVED'),
        ]);

        Sanctum::actingAs($requester);
        $this->patchJson("/api/tickets/{$waitingTicket->id}", ['status' => 'IN_PROGRESS'])
            ->assertForbidden()
            ->assertJson(['message' => 'No tiene permisos para realizar esta acción.']);

        Sanctum::actingAs($technician);
        $this->patchJson("/api/tickets/{$resolvedTicket->id}", ['status' => 'CLOSED'])
            ->assertForbidden()
            ->assertJson(['message' => 'No tiene permisos para realizar esta acción.']);

        Sanctum::actingAs($this->admin());
        $this->patchJson("/api/tickets/{$waitingTicket->id}", ['status' => 'IN_PROGRESS'])->assertOk();
        $this->patchJson("/api/tickets/{$resolvedTicket->id}", ['status' => 'CLOSED'])->assertOk();
    }

    public function test_unknown_status_returns_422(): void
    {
        $ticket = $this->createTicket();

        Sanctum::actingAs($this->admin());

        $this->patchJson("/api/tickets/{$ticket->id}", ['status' => 'REOPENED'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status' => 'El estado indicado no existe o no está activo.']);
    }

    public function test_cancellation_requires_notes_and_is_terminal(): void
    {
        $ticket = $this->createTicket();

        Sanctum::actingAs($this->admin());

        $this->patchJson("/api/tickets/{$ticket->id}", ['status' => 'CANCELLED'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['notes' => 'Debe indicar el motivo del cambio de estado.']);

        $this->patchJson("/api/tickets/{$ticket->id}", [
            'status' => 'CANCELLED',
            'notes' => 'Duplicado de TCK-2026-000001.',
        ])->assertOk();

        $ticket->refresh();
        $this->assertSame('CANCELLED', $ticket->status->code);

        $event = $ticket->events()->where('event_type', 'status_changed')->firstOrFail();
        $this->assertSame($this->statusId('CANCELLED'), $event->to_status_id);
        $this->assertSame('Duplicado de TCK-2026-000001.', $event->notes);

        $this->patchJson("/api/tickets/{$ticket->id}", ['subject' => 'Reapertura'])
            ->assertStatus(409)
            ->assertJson(['message' => 'Los tickets cerrados o cancelados no pueden modificarse.']);
    }

    public function test_status_change_on_terminal_ticket_returns_409_before_transition_check(): void
    {
        $ticket = $this->createTicket(['status_id' => $this->statusId('CANCELLED')]);

        Sanctum::actingAs($this->admin());

        $this->patchJson("/api/tickets/{$ticket->id}", ['status' => 'IN_PROGRESS'])
            ->assertStatus(409)
            ->assertJson(['message' => 'Los tickets cerrados o cancelados no pueden modificarse.']);
    }

    public function test_resolution_and_closure_timestamps_are_set_once(): void
    {
        $technician = $this->tech();
        $ticket = $this->createTicket([
            'assigned_to_id' => $technician->id,
            'status_id' => $this->statusId('IN_PROGRESS'),
            'started_at' => now()->subHour(),
        ]);

        Sanctum::actingAs($technician);

        $this->patchJson("/api/tickets/{$ticket->id}", [
            'status' => 'RESOLVED',
            'resolution_notes' => 'Equipo reparado.',
        ])->assertOk();

        $ticket->refresh();
        $this->assertNotNull($ticket->resolved_at);
        $this->assertNull($ticket->closed_at);
        $this->assertNull($ticket->first_response_at, 'first_response_at lo define la fase de SLA/comentarios.');

        Sanctum::actingAs($this->admin());
        $this->patchJson("/api/tickets/{$ticket->id}", ['status' => 'CLOSED'])->assertOk();

        $ticket->refresh();
        $this->assertNotNull($ticket->closed_at);
        $this->assertTrue(
            $ticket->resolved_at->lessThanOrEqualTo($ticket->closed_at),
            'resolved_at no se pisa al cerrar.',
        );
    }

    public function test_status_change_is_denied_outside_the_role_scope(): void
    {
        $owner = $this->requester();
        $intruder = $this->requester();
        $ticket = $this->createTicket(['requester_id' => $owner->id]);

        Sanctum::actingAs($intruder);

        $this->patchJson("/api/tickets/{$ticket->id}", ['status' => 'CANCELLED', 'notes' => 'x'])
            ->assertNotFound()
            ->assertJson(['message' => 'Ticket no encontrado.']);
    }
}
