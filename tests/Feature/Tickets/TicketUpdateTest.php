<?php

namespace Tests\Feature\Tickets;

use Laravel\Sanctum\Sanctum;

/**
 * Pruebas de edición controlada de tickets (FASE 6.2 §28-§31, §38.3).
 */
class TicketUpdateTest extends TicketTestCase
{
    public function test_patch_requires_authentication(): void
    {
        $ticket = $this->createTicket();

        $this->patchJson("/api/tickets/{$ticket->id}", ['subject' => 'Nuevo'])
            ->assertUnauthorized();
    }

    public function test_patch_on_unknown_ticket_returns_404(): void
    {
        Sanctum::actingAs($this->admin());

        $this->patchJson('/api/tickets/999999', ['subject' => 'Nuevo'])
            ->assertNotFound()
            ->assertJson(['message' => 'Ticket no encontrado.']);
    }

    public function test_patch_outside_the_role_scope_returns_404(): void
    {
        $owner = $this->requester();
        $intruder = $this->requester();
        $ticket = $this->createTicket(['requester_id' => $owner->id]);

        Sanctum::actingAs($intruder);

        $this->patchJson("/api/tickets/{$ticket->id}", ['subject' => 'Intento de edición'])
            ->assertNotFound()
            ->assertJson(['message' => 'Ticket no encontrado.']);
    }

    public function test_requester_edits_own_subject_and_description(): void
    {
        $owner = $this->requester();
        $ticket = $this->createTicket(['requester_id' => $owner->id]);

        Sanctum::actingAs($owner);

        $this->patchJson("/api/tickets/{$ticket->id}", [
            'subject' => 'Asunto corregido por el solicitante',
            'description' => 'Descripción ampliada con más detalles del incidente.',
        ])
            ->assertOk()
            ->assertJson(['message' => 'Ticket actualizado correctamente.']);

        $ticket->refresh();

        $this->assertSame('Asunto corregido por el solicitante', $ticket->subject);
        $this->assertSame(1, $ticket->events()->count(), 'La edición de texto no genera eventos.');
    }

    public function test_requester_cannot_change_priority_type_unit_or_assignment(): void
    {
        $owner = $this->requester();
        $ticket = $this->createTicket(['requester_id' => $owner->id]);

        Sanctum::actingAs($owner);

        foreach ([
            ['priority' => 'CRITICAL'],
            ['ticket_type' => 'INCIDENT'],
            ['organizational_unit_id' => 1],
            ['assigned_technician_id' => 1],
            ['requester_id' => 1],
        ] as $payload) {
            $this->patchJson("/api/tickets/{$ticket->id}", $payload)
                ->assertForbidden()
                ->assertJson(['message' => 'No tiene permisos para modificar este campo.']);
        }
    }

    public function test_backend_fields_are_rejected_for_every_role(): void
    {
        $owner = $this->requester();
        $technician = $this->tech();

        $forbidden = [
            'ticket_number' => 'TCK-2026-999999',
            'status_id' => 3,
            'assigned_to_id' => 1,
            'created_by_id' => 1,
            'channel' => 'email',
            'classification_source' => 'ai',
            'resolved_at' => '2026-01-01 00:00:00',
            'sla_policy_id' => 1,
            'sla_response_breached' => true,
        ];

        $tickets = [
            'ADMIN' => ['user' => $this->admin(), 'ticket' => $this->createTicket()],
            'REQUESTER' => ['user' => $owner, 'ticket' => $this->createTicket(['requester_id' => $owner->id])],
            'TECH' => ['user' => $technician, 'ticket' => $this->createTicket(['assigned_to_id' => $technician->id])],
        ];

        foreach ($tickets as $context) {
            Sanctum::actingAs($context['user']);

            foreach ($forbidden as $field => $value) {
                $this->patchJson("/api/tickets/{$context['ticket']->id}", [$field => $value])
                    ->assertForbidden()
                    ->assertJson(['message' => 'No tiene permisos para modificar este campo.']);
            }
        }
    }

    public function test_technician_cannot_edit_text_fields(): void
    {
        $technician = $this->tech();
        $ticket = $this->createTicket(['assigned_to_id' => $technician->id]);

        Sanctum::actingAs($technician);

        $this->patchJson("/api/tickets/{$ticket->id}", ['subject' => 'Texto técnico'])
            ->assertForbidden()
            ->assertJson(['message' => 'No tiene permisos para modificar este campo.']);
    }

    public function test_technician_can_save_resolution_notes_without_status(): void
    {
        $technician = $this->tech();
        $ticket = $this->createTicket(['assigned_to_id' => $technician->id]);

        Sanctum::actingAs($technician);

        $this->patchJson("/api/tickets/{$ticket->id}", [
            'resolution_notes' => 'Se reemplaza el módulo de fuente de poder.',
        ])->assertOk();

        $ticket->refresh();

        $this->assertSame('Se reemplaza el módulo de fuente de poder.', $ticket->resolution_notes);
    }

    public function test_notes_require_a_status_change(): void
    {
        $admin = $this->admin();
        $ticket = $this->createTicket();

        Sanctum::actingAs($admin);

        $this->patchJson("/api/tickets/{$ticket->id}", ['notes' => 'Motivo sin cambio'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'notes' => 'El campo notes solo puede enviarse junto con un cambio de estado.',
            ]);
    }

    public function test_priority_change_records_an_event(): void
    {
        $admin = $this->admin();
        $ticket = $this->createTicket();

        Sanctum::actingAs($admin);

        $this->patchJson("/api/tickets/{$ticket->id}", ['priority' => 'CRITICAL'])
            ->assertOk()
            ->assertJsonPath('data.priority.code', 'CRITICAL');

        $event = $ticket->events()->where('event_type', 'priority_changed')->firstOrFail();

        $this->assertSame($this->priorityId('MEDIUM'), $event->from_priority_id);
        $this->assertSame($this->priorityId('CRITICAL'), $event->to_priority_id);
        $this->assertSame(2, $ticket->events()->count());
    }

    public function test_ticket_type_change_records_an_event(): void
    {
        $admin = $this->admin();
        $ticket = $this->createTicket();

        Sanctum::actingAs($admin);

        $this->patchJson("/api/tickets/{$ticket->id}", ['ticket_type' => 'INCIDENT'])
            ->assertOk()
            ->assertJsonPath('data.type.code', 'INCIDENT');

        $event = $ticket->events()->where('event_type', 'type_changed')->firstOrFail();

        $this->assertSame($this->typeId('SUPPORT'), $event->data['from_ticket_type_id']);
        $this->assertSame($this->typeId('INCIDENT'), $event->data['to_ticket_type_id']);
    }

    public function test_category_change_records_an_event_with_subcategories(): void
    {
        [$root, $child] = $this->categoryPair();
        $otherChild = $this->foreignSubcategory($root);

        $admin = $this->admin();
        $ticket = $this->createTicket([
            'category_id' => $root->id,
            'subcategory_id' => $child->id,
        ]);

        Sanctum::actingAs($admin);

        $this->patchJson("/api/tickets/{$ticket->id}", [
            'category_id' => $otherChild->parent_id,
            'subcategory_id' => $otherChild->id,
        ])->assertOk();

        $event = $ticket->events()->where('event_type', 'category_changed')->firstOrFail();

        $this->assertSame($root->id, $event->from_category_id);
        $this->assertSame($otherChild->parent_id, $event->to_category_id);
        $this->assertSame($child->id, $event->data['from_subcategory_id']);
        $this->assertSame($otherChild->id, $event->data['to_subcategory_id']);
    }

    public function test_category_pair_is_required_together_on_update(): void
    {
        [$root] = $this->categoryPair();

        Sanctum::actingAs($this->admin());

        $ticket = $this->createTicket();

        $this->patchJson("/api/tickets/{$ticket->id}", ['category_id' => $root->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'subcategory_id' => 'Debe indicar la categoría y la subcategoría juntas.',
            ]);
    }

    public function test_clearing_the_category_sends_both_nulls(): void
    {
        [$root, $child] = $this->categoryPair();
        $ticket = $this->createTicket([
            'category_id' => $root->id,
            'subcategory_id' => $child->id,
        ]);

        Sanctum::actingAs($this->admin());

        $this->patchJson("/api/tickets/{$ticket->id}", [
            'category_id' => null,
            'subcategory_id' => null,
        ])->assertOk();

        $ticket->refresh();

        $this->assertNull($ticket->category_id);
        $this->assertNull($ticket->subcategory_id);
        $this->assertSame(2, $ticket->events()->count());
    }

    public function test_organizational_unit_can_be_changed_by_admin(): void
    {
        $otherUnit = $this->createUnit('UNIT-E');
        $ticket = $this->createTicket();

        Sanctum::actingAs($this->admin());

        $this->patchJson("/api/tickets/{$ticket->id}", [
            'organizational_unit_id' => $otherUnit->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.organizational_unit.id', $otherUnit->id);

        $this->assertSame($otherUnit->id, $ticket->refresh()->organizational_unit_id);
    }

    public function test_asset_can_be_updated_but_not_to_a_decommissioned_one(): void
    {
        $asset = $this->activeAsset();
        $decommissioned = $this->createDecommissionedAsset();
        $ticket = $this->createTicket();

        Sanctum::actingAs($this->admin());

        $this->patchJson("/api/tickets/{$ticket->id}", ['asset_id' => $asset->id])
            ->assertOk()
            ->assertJsonPath('data.asset.id', $asset->id);

        $this->patchJson("/api/tickets/{$ticket->id}", ['asset_id' => $decommissioned->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'asset_id' => 'El activo indicado está dado de baja y no puede asociarse al ticket.',
            ]);
    }

    public function test_terminal_ticket_is_read_only(): void
    {
        $ticket = $this->createTicket(['status_id' => $this->statusId('CLOSED')]);

        Sanctum::actingAs($this->admin());

        $this->patchJson("/api/tickets/{$ticket->id}", ['subject' => 'Intento sobre cerrado'])
            ->assertStatus(409)
            ->assertJson(['message' => 'Los tickets cerrados o cancelados no pueden modificarse.']);
    }

    public function test_validation_messages_are_in_spanish(): void
    {
        $ticket = $this->createTicket();

        Sanctum::actingAs($this->admin());

        $this->patchJson("/api/tickets/{$ticket->id}", ['subject' => str_repeat('x', 201)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['subject' => 'El asunto no debe superar los 200 caracteres.']);

        $this->patchJson("/api/tickets/{$ticket->id}", ['priority' => 'NOPE'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['priority' => 'La prioridad indicada no existe o no está activa.']);
    }

    public function test_empty_patch_returns_ok_without_changes(): void
    {
        $ticket = $this->createTicket();
        $original = $ticket->subject;

        Sanctum::actingAs($this->admin());

        $this->patchJson("/api/tickets/{$ticket->id}", [])
            ->assertOk()
            ->assertJsonPath('data.subject', $original);

        $this->assertSame(1, $ticket->events()->count());
    }
}
