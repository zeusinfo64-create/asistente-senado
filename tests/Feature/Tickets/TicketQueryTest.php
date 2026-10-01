<?php

namespace Tests\Feature\Tickets;

use App\Models\User;
use Laravel\Sanctum\Sanctum;

/**
 * Pruebas de listado, filtros, paginación y detalle (FASE 6.2 §34, §38.2).
 */
class TicketQueryTest extends TicketTestCase
{
    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/tickets')->assertUnauthorized();
        $this->getJson('/api/tickets/1')->assertUnauthorized();
    }

    public function test_admin_sees_every_ticket(): void
    {
        $firstRequester = $this->requester();
        $secondRequester = $this->requester();

        $this->createTicket(['requester_id' => $firstRequester->id]);
        $this->createTicket(['requester_id' => $firstRequester->id]);
        $this->createTicket(['requester_id' => $secondRequester->id]);

        Sanctum::actingAs($this->admin());

        $this->getJson('/api/tickets')
            ->assertOk()
            ->assertJson(['meta' => ['total' => 3]]);
    }

    public function test_requester_sees_only_own_tickets(): void
    {
        $owner = $this->requester();
        $other = $this->requester();

        $mine1 = $this->createTicket(['requester_id' => $owner->id]);
        $mine2 = $this->createTicket(['requester_id' => $owner->id]);
        $this->createTicket(['requester_id' => $other->id]);

        Sanctum::actingAs($owner);

        $response = $this->getJson('/api/tickets')
            ->assertOk()
            ->assertJson(['meta' => ['total' => 2]]);

        $ids = array_column($response->json('data'), 'id');
        sort($ids);

        $this->assertSame([$mine1->id, $mine2->id], $ids);
    }

    public function test_technician_sees_only_assigned_tickets(): void
    {
        $technician = $this->tech();
        $otherTechnician = $this->tech();

        $assigned = $this->createTicket(['assigned_to_id' => $technician->id]);
        $this->createTicket(['assigned_to_id' => $otherTechnician->id]);
        $this->createTicket();

        Sanctum::actingAs($technician);

        $response = $this->getJson('/api/tickets')
            ->assertOk()
            ->assertJson(['meta' => ['total' => 1]]);

        $this->assertSame($assigned->id, $response->json('data.0.id'));
    }

    public function test_user_without_role_sees_an_empty_list(): void
    {
        $this->createTicket();

        Sanctum::actingAs(User::factory()->create(['is_active' => true]));

        $this->getJson('/api/tickets')
            ->assertOk()
            ->assertJson(['data' => [], 'meta' => ['total' => 0]]);
    }

    public function test_status_and_priority_filters(): void
    {
        $registered = $this->createTicket();
        $resolved = $this->createTicket([
            'status_id' => $this->statusId('RESOLVED'),
            'priority_id' => $this->priorityId('CRITICAL'),
        ]);

        Sanctum::actingAs($this->admin());

        $this->getJson('/api/tickets?status=RESOLVED')
            ->assertOk()
            ->assertJson(['meta' => ['total' => 1], 'data' => [['id' => $resolved->id]]]);

        $this->getJson('/api/tickets?status=REGISTERED')
            ->assertOk()
            ->assertJson(['meta' => ['total' => 1], 'data' => [['id' => $registered->id]]]);

        $this->getJson('/api/tickets?priority=CRITICAL')
            ->assertOk()
            ->assertJson(['meta' => ['total' => 1], 'data' => [['id' => $resolved->id]]]);

        $this->getJson('/api/tickets?status=NOPE')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    public function test_type_category_subcategory_and_unit_filters(): void
    {
        [$root, $child] = $this->categoryPair();

        $incident = $this->createTicket(['ticket_type_id' => $this->typeId('INCIDENT')]);
        $categorized = $this->createTicket([
            'category_id' => $root->id,
            'subcategory_id' => $child->id,
        ]);

        Sanctum::actingAs($this->admin());

        $this->getJson('/api/tickets?ticket_type=INCIDENT')
            ->assertOk()
            ->assertJson(['meta' => ['total' => 1], 'data' => [['id' => $incident->id]]]);

        $this->getJson('/api/tickets?category='.$root->id)
            ->assertOk()
            ->assertJson(['meta' => ['total' => 1], 'data' => [['id' => $categorized->id]]]);

        $this->getJson('/api/tickets?subcategory='.$child->id)
            ->assertOk()
            ->assertJson(['meta' => ['total' => 1], 'data' => [['id' => $categorized->id]]]);

        $this->getJson('/api/tickets?organizational_unit='.$this->unit()->id)
            ->assertOk()
            ->assertJson(['meta' => ['total' => 2]]);

        $this->getJson('/api/tickets?category=999999')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category']);
    }

    public function test_assigned_technician_filter(): void
    {
        $technician = $this->tech();
        $assigned = $this->createTicket(['assigned_to_id' => $technician->id]);
        $this->createTicket();

        Sanctum::actingAs($this->admin());

        $this->getJson('/api/tickets?assigned_technician='.$technician->id)
            ->assertOk()
            ->assertJson(['meta' => ['total' => 1], 'data' => [['id' => $assigned->id]]]);
    }

    public function test_search_filter_matches_number_subject_and_description(): void
    {
        $ticket = $this->createTicket([
            'subject' => 'Monitor LED dañado',
            'description' => 'El monitor parpadea constantemente.',
        ]);
        $this->createTicket([
            'subject' => 'Falla de red',
            'description' => 'El switch del piso se reinicia solo.',
        ]);

        Sanctum::actingAs($this->admin());

        $this->getJson('/api/tickets?search='.urlencode('Monitor'))
            ->assertOk()
            ->assertJson(['meta' => ['total' => 1], 'data' => [['id' => $ticket->id]]]);

        $this->getJson('/api/tickets?search='.urlencode($ticket->ticket_number))
            ->assertOk()
            ->assertJson(['meta' => ['total' => 1], 'data' => [['id' => $ticket->id]]]);

        $this->getJson('/api/tickets?search=parpadea')
            ->assertOk()
            ->assertJson(['meta' => ['total' => 1], 'data' => [['id' => $ticket->id]]]);

        $this->getJson('/api/tickets?search=sin-resultados')
            ->assertOk()
            ->assertJson(['data' => [], 'meta' => ['total' => 0]]);
    }

    public function test_date_range_filter(): void
    {
        $this->createTicket();
        $this->createTicket();

        Sanctum::actingAs($this->admin());

        $today = now()->format('Y-m-d');

        $this->getJson("/api/tickets?date_from={$today}&date_to={$today}")
            ->assertOk()
            ->assertJson(['meta' => ['total' => 2]]);

        $tomorrow = now()->addDay()->format('Y-m-d');

        $this->getJson("/api/tickets?date_from={$tomorrow}")
            ->assertOk()
            ->assertJson(['meta' => ['total' => 0]]);

        $yesterday = now()->subDay()->format('Y-m-d');

        $this->getJson("/api/tickets?date_from={$today}&date_to={$yesterday}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'date_from' => 'La fecha desde no puede ser posterior a la fecha hasta.',
            ]);

        $this->getJson('/api/tickets?date_from=no-es-fecha')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['date_from']);
    }

    public function test_pagination_and_per_page_validation(): void
    {
        $this->createTicket();
        $this->createTicket();
        $this->createTicket();

        Sanctum::actingAs($this->admin());

        $firstPage = $this->getJson('/api/tickets?per_page=2')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'per_page', 'total', 'last_page']])
            ->assertJson(['meta' => [
                'current_page' => 1,
                'per_page' => 2,
                'total' => 3,
                'last_page' => 2,
            ]]);

        $this->assertCount(2, $firstPage->json('data'));

        $secondPage = $this->getJson('/api/tickets?per_page=2&page=2')->assertOk();

        $this->assertCount(1, $secondPage->json('data'));
        $this->assertSame(2, $secondPage->json('meta.current_page'));

        $this->getJson('/api/tickets?per_page=101')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page']);

        $this->getJson('/api/tickets?per_page=0')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page']);

        $this->getJson('/api/tickets?page=0')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['page']);
    }

    public function test_filters_never_widen_the_role_scope(): void
    {
        $owner = $this->requester();
        $technician = $this->tech();
        $other = $this->requester();

        $mine = $this->createTicket([
            'requester_id' => $owner->id,
            'assigned_to_id' => $technician->id,
        ]);
        $this->createTicket(['requester_id' => $other->id]);

        Sanctum::actingAs($owner);

        $this->getJson('/api/tickets?assigned_technician='.$technician->id)
            ->assertOk()
            ->assertJson(['meta' => ['total' => 1], 'data' => [['id' => $mine->id]]]);

        $this->getJson('/api/tickets?assigned_technician='.$owner->id)
            ->assertOk()
            ->assertJson(['data' => [], 'meta' => ['total' => 0]]);
    }

    public function test_show_returns_detail_with_ordered_events(): void
    {
        $owner = $this->requester();
        $ticket = $this->createTicket(['requester_id' => $owner->id]);

        Sanctum::actingAs($owner);

        $response = $this->getJson("/api/tickets/{$ticket->id}")
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'ticket_number',
                    'subject',
                    'description',
                    'type',
                    'status',
                    'priority',
                    'requester',
                    'created_by',
                    'assigned_technician',
                    'organizational_unit',
                    'dates',
                    'sla',
                    'events',
                ],
            ]);

        $this->assertSame('TCK', substr($response->json('data.ticket_number'), 0, 3));
        $this->assertSame(['created'], array_column($response->json('data.events'), 'event_type'));

        $event = $response->json('data.events.0');
        $this->assertSame('REGISTERED', $event['to_status']);
        $this->assertSame($owner->id, $event['actor']['id']);
        $this->assertIsString($event['created_at']);
    }

    public function test_show_returns_404_outside_the_role_scope(): void
    {
        $owner = $this->requester();
        $intruder = $this->requester();
        $ticket = $this->createTicket(['requester_id' => $owner->id]);

        Sanctum::actingAs($intruder);

        $this->getJson("/api/tickets/{$ticket->id}")
            ->assertNotFound()
            ->assertJson(['message' => 'Ticket no encontrado.']);
    }

    public function test_show_returns_404_for_unknown_ticket(): void
    {
        Sanctum::actingAs($this->admin());

        $this->getJson('/api/tickets/999999')
            ->assertNotFound()
            ->assertJson(['message' => 'Ticket no encontrado.']);
    }
}
