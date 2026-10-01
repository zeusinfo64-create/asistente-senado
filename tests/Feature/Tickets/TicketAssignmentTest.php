<?php

namespace Tests\Feature\Tickets;

use Laravel\Sanctum\Sanctum;

/**
 * Pruebas de asignación/reasignación de tickets (FASE 6.2 §22, §38.4).
 */
class TicketAssignmentTest extends TicketTestCase
{
    public function test_admin_assigns_a_registered_ticket_with_a_single_combined_event(): void
    {
        $admin = $this->admin();
        $technician = $this->tech();
        $ticket = $this->createTicket();

        Sanctum::actingAs($admin);

        $this->patchJson("/api/tickets/{$ticket->id}", ['assigned_technician_id' => $technician->id])
            ->assertOk()
            ->assertJsonPath('data.assigned_technician.id', $technician->id);

        $ticket->refresh();

        $this->assertSame($technician->id, $ticket->assigned_to_id);
        $this->assertNotNull($ticket->assigned_at);
        $this->assertSame('ASSIGNED', $ticket->status->code);

        $assignment = $ticket->events()->where('event_type', 'assigned')->firstOrFail();

        $this->assertNull($assignment->from_assignee_id);
        $this->assertSame($technician->id, $assignment->to_assignee_id);
        $this->assertSame($this->statusId('REGISTERED'), $assignment->from_status_id);
        $this->assertSame($this->statusId('ASSIGNED'), $assignment->to_status_id);
        $this->assertSame(2, $ticket->events()->count(), 'Creado + un único evento assigned.');
    }

    public function test_reassignment_keeps_assigned_at_and_status(): void
    {
        $admin = $this->admin();
        $first = $this->tech();
        $second = $this->tech();
        $ticket = $this->createTicket([
            'assigned_to_id' => $first->id,
            'assigned_at' => now()->subDay(),
            'status_id' => $this->statusId('ASSIGNED'),
        ]);

        $originalAssignedAt = $ticket->assigned_at;

        Sanctum::actingAs($admin);

        $this->patchJson("/api/tickets/{$ticket->id}", ['assigned_technician_id' => $second->id])
            ->assertOk();

        $ticket->refresh();

        $this->assertSame($second->id, $ticket->assigned_to_id);
        $this->assertTrue($originalAssignedAt->equalTo($ticket->assigned_at), 'assigned_at no se reinicia.');
        $this->assertSame('ASSIGNED', $ticket->status->code, 'La reasignación no altera el estado.');

        $assignment = $ticket->events()->where('event_type', 'assigned')->firstOrFail();

        $this->assertSame($first->id, $assignment->from_assignee_id);
        $this->assertSame($second->id, $assignment->to_assignee_id);
        $this->assertNull($assignment->from_status_id);
        $this->assertNull($assignment->to_status_id);
    }

    public function test_assignment_moves_registered_tickets_to_assigned(): void
    {
        $admin = $this->admin();
        $technician = $this->tech();
        $ticket = $this->createTicket();

        Sanctum::actingAs($admin);

        $this->patchJson("/api/tickets/{$ticket->id}", ['assigned_technician_id' => $technician->id])->assertOk();

        $this->assertSame('ASSIGNED', $ticket->refresh()->status->code);
        $this->assertNull($ticket->started_at);
        $this->assertNull($ticket->resolved_at);
    }

    public function test_only_admin_can_assign(): void
    {
        $technician = $this->tech();
        $requester = $this->requester();
        $ticket = $this->createTicket(['requester_id' => $requester->id]);

        Sanctum::actingAs($technician);

        $this->patchJson("/api/tickets/{$ticket->id}", ['assigned_technician_id' => $technician->id])
            ->assertNotFound();

        $otherTicket = $this->createTicket(['assigned_to_id' => $technician->id]);

        $this->patchJson("/api/tickets/{$otherTicket->id}", ['assigned_technician_id' => $technician->id])
            ->assertForbidden()
            ->assertJson(['message' => 'No tiene permisos para modificar este campo.']);

        Sanctum::actingAs($requester);

        $this->patchJson("/api/tickets/{$ticket->id}", ['assigned_technician_id' => $technician->id])
            ->assertForbidden()
            ->assertJson(['message' => 'No tiene permisos para modificar este campo.']);
    }

    public function test_assignee_must_be_an_active_technician(): void
    {
        $admin = $this->admin();
        $requester = $this->requester();
        $ticket = $this->createTicket();

        Sanctum::actingAs($admin);

        $this->patchJson("/api/tickets/{$ticket->id}", ['assigned_technician_id' => $requester->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'assigned_technician_id' => 'El usuario indicado no tiene el rol TECH.',
            ]);

        $this->patchJson("/api/tickets/{$ticket->id}", ['assigned_technician_id' => 999999])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['assigned_technician_id']);
    }

    public function test_assignment_outside_the_role_scope_returns_404(): void
    {
        $owner = $this->requester();
        $intruder = $this->requester();
        $ticket = $this->createTicket(['requester_id' => $owner->id]);

        Sanctum::actingAs($intruder);

        $this->patchJson("/api/tickets/{$ticket->id}", ['assigned_technician_id' => 1])
            ->assertNotFound()
            ->assertJson(['message' => 'Ticket no encontrado.']);
    }

    public function test_assignment_on_terminal_ticket_returns_409(): void
    {
        $technician = $this->tech();
        $ticket = $this->createTicket(['status_id' => $this->statusId('CLOSED')]);

        Sanctum::actingAs($this->admin());

        $this->patchJson("/api/tickets/{$ticket->id}", ['assigned_technician_id' => $technician->id])
            ->assertStatus(409)
            ->assertJson(['message' => 'Los tickets cerrados o cancelados no pueden modificarse.']);
    }

    public function test_admin_can_unassign(): void
    {
        $technician = $this->tech();
        $ticket = $this->createTicket(['assigned_to_id' => $technician->id]);

        Sanctum::actingAs($this->admin());

        $this->patchJson("/api/tickets/{$ticket->id}", ['assigned_technician_id' => null])
            ->assertOk()
            ->assertJsonPath('data.assigned_technician', null);

        $ticket->refresh();

        $this->assertNull($ticket->assigned_to_id);

        $assignment = $ticket->events()->where('event_type', 'assigned')->firstOrFail();

        $this->assertSame($technician->id, $assignment->from_assignee_id);
        $this->assertNull($assignment->to_assignee_id);
    }

    public function test_assignment_without_changes_does_not_record_events(): void
    {
        $technician = $this->tech();
        $ticket = $this->createTicket(['assigned_to_id' => $technician->id]);

        Sanctum::actingAs($this->admin());

        $this->patchJson("/api/tickets/{$ticket->id}", ['assigned_technician_id' => $technician->id])
            ->assertOk();

        $this->assertSame(1, $ticket->events()->count());
        $this->assertSame(0, $ticket->events()->where('event_type', 'assigned')->count());
    }
}
