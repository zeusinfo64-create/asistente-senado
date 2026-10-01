<?php

namespace Tests\Feature\Tickets;

use App\Models\Ticket;
use Laravel\Sanctum\Sanctum;

/**
 * Pruebas de creación de tickets (FASE 6.2 §35-§36, §38.1).
 */
class TicketCreationTest extends TicketTestCase
{
    public function test_ticket_creation_requires_authentication(): void
    {
        $this->postJson('/api/tickets', [])->assertUnauthorized();
    }

    public function test_technician_cannot_create_tickets(): void
    {
        Sanctum::actingAs($this->tech());

        $this->postJson('/api/tickets', $this->validPayload())
            ->assertForbidden()
            ->assertJson(['message' => 'No tiene permisos para realizar esta acción.']);
    }

    public function test_requester_creates_ticket_with_server_defaults(): void
    {
        $requester = $this->requester();
        Sanctum::actingAs($requester);

        $response = $this->postJson('/api/tickets', $this->validPayload())
            ->assertCreated()
            ->assertJson(['message' => 'Ticket creado correctamente.']);

        $this->assertMatchesRegularExpression('/^TCK-\d{4}-\d{6}$/', $response->json('data.ticket_number'));
        $this->assertSame('REGISTERED', $response->json('data.status.code'));
        $this->assertSame('web', $response->json('data.channel'));
        $this->assertSame($requester->id, $response->json('data.requester.id'));
        $this->assertSame($requester->id, $response->json('data.created_by.id'));
        $this->assertSame($this->unit()->id, $response->json('data.organizational_unit.id'));
        $this->assertNull($response->json('data.assigned_technician'));
        $this->assertNull($response->json('data.asset'));

        /** @var Ticket $ticket */
        $ticket = Ticket::query()->where('ticket_number', $response->json('data.ticket_number'))->firstOrFail();

        $this->assertSame('REGISTERED', $ticket->status->code);
        $this->assertSame(1, $ticket->events()->count());
        $this->assertSame('created', $ticket->events()->first()->event_type);
        $this->assertSame($ticket->status_id, $ticket->events()->first()->to_status_id);
    }

    public function test_admin_creates_ticket_on_behalf_of_another_requester(): void
    {
        $admin = $this->admin();
        $otherUnit = $this->createUnit('UNIT-B');
        $requester = $this->requester();
        $requester->organizational_unit_id = $otherUnit->id;
        $requester->save();

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/tickets', $this->validPayload([
            'requester_id' => $requester->id,
            'organizational_unit_id' => $otherUnit->id,
        ]))->assertCreated();

        $this->assertSame($requester->id, $response->json('data.requester.id'));
        $this->assertSame($admin->id, $response->json('data.created_by.id'));
        $this->assertSame($otherUnit->id, $response->json('data.organizational_unit.id'));
    }

    public function test_admin_unit_fallback_uses_requester_unit(): void
    {
        $admin = $this->admin();
        $otherUnit = $this->createUnit('UNIT-C');
        $requester = $this->requester();
        $requester->organizational_unit_id = $otherUnit->id;
        $requester->save();

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/tickets', $this->validPayload([
            'requester_id' => $requester->id,
        ]))->assertCreated();

        $this->assertSame($otherUnit->id, $response->json('data.organizational_unit.id'));
    }

    public function test_missing_required_fields_return_spanish_validation_errors(): void
    {
        Sanctum::actingAs($this->requester());

        $this->postJson('/api/tickets', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'subject' => 'El asunto es obligatorio.',
                'description' => 'La descripción es obligatoria.',
                'ticket_type' => 'El tipo de ticket es obligatorio.',
                'priority' => 'La prioridad es obligatoria.',
            ]);
    }

    public function test_subject_length_limit_is_enforced(): void
    {
        Sanctum::actingAs($this->requester());

        $this->postJson('/api/tickets', $this->validPayload([
            'subject' => str_repeat('a', 201),
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['subject' => 'El asunto no debe superar los 200 caracteres.']);
    }

    public function test_unknown_catalog_codes_return_422(): void
    {
        Sanctum::actingAs($this->requester());

        $this->postJson('/api/tickets', $this->validPayload([
            'ticket_type' => 'NOPE',
            'priority' => 'NOPE',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['ticket_type', 'priority']);
    }

    public function test_category_requires_its_subcategory(): void
    {
        [$root] = $this->categoryPair();

        Sanctum::actingAs($this->requester());

        $this->postJson('/api/tickets', $this->validPayload([
            'category_id' => $root->id,
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'subcategory_id' => 'Debe indicar la categoría y la subcategoría juntas.',
            ]);
    }

    public function test_subcategory_must_belong_to_the_category(): void
    {
        [$root] = $this->categoryPair();
        $foreignChild = $this->foreignSubcategory($root);

        Sanctum::actingAs($this->requester());

        $this->postJson('/api/tickets', $this->validPayload([
            'category_id' => $root->id,
            'subcategory_id' => $foreignChild->id,
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'subcategory_id' => 'La subcategoría seleccionada no pertenece a la categoría.',
            ]);
    }

    public function test_valid_category_pair_is_persisted(): void
    {
        [$root, $child] = $this->categoryPair();

        Sanctum::actingAs($this->requester());

        $response = $this->postJson('/api/tickets', $this->validPayload([
            'category_id' => $root->id,
            'subcategory_id' => $child->id,
        ]))->assertCreated();

        $this->assertSame($root->id, $response->json('data.category.id'));
        $this->assertSame($child->id, $response->json('data.subcategory.id'));
    }

    public function test_decommissioned_asset_is_rejected(): void
    {
        $asset = $this->createDecommissionedAsset();

        Sanctum::actingAs($this->requester());

        $this->postJson('/api/tickets', $this->validPayload([
            'asset_id' => $asset->id,
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'asset_id' => 'El activo indicado está dado de baja y no puede asociarse al ticket.',
            ]);
    }

    public function test_active_asset_is_accepted(): void
    {
        $asset = $this->activeAsset();

        Sanctum::actingAs($this->requester());

        $response = $this->postJson('/api/tickets', $this->validPayload([
            'asset_id' => $asset->id,
        ]))->assertCreated();

        $this->assertSame($asset->id, $response->json('data.asset.id'));
    }

    public function test_missing_asset_returns_422(): void
    {
        Sanctum::actingAs($this->requester());

        $this->postJson('/api/tickets', $this->validPayload(['asset_id' => 999999]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['asset_id' => 'El activo indicado no existe.']);
    }

    public function test_requester_cannot_send_backend_or_forbidden_fields(): void
    {
        Sanctum::actingAs($this->requester());

        $forbidden = [
            ['status' => 'ASSIGNED'],
            ['ticket_number' => 'TCK-2026-999999'],
            ['assigned_technician_id' => 1],
            ['channel' => 'email'],
            ['created_by_id' => 2],
        ];

        foreach ($forbidden as $payload) {
            $this->postJson('/api/tickets', $this->validPayload($payload))
                ->assertForbidden()
                ->assertJson(['message' => 'No tiene permisos para modificar este campo.']);
        }
    }

    public function test_requester_cannot_create_for_another_unit(): void
    {
        $otherUnit = $this->createUnit('UNIT-D');
        $requester = $this->requester();

        Sanctum::actingAs($requester);

        $this->postJson('/api/tickets', $this->validPayload([
            'organizational_unit_id' => $otherUnit->id,
        ]))
            ->assertForbidden()
            ->assertJson(['message' => 'No puede crear tickets para otra unidad organizacional.']);
    }

    public function test_requester_without_unit_must_send_one(): void
    {
        $requester = $this->createUserWithRole('REQUESTER');

        Sanctum::actingAs($requester);

        $this->postJson('/api/tickets', $this->validPayload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'organizational_unit_id' => 'Debe indicar la unidad organizacional.',
            ]);

        $this->postJson('/api/tickets', $this->validPayload([
            'organizational_unit_id' => $this->unit()->id,
        ]))->assertCreated();
    }

    public function test_requester_id_must_reference_a_requester_role_user(): void
    {
        $technician = $this->tech();

        Sanctum::actingAs($this->admin());

        $this->postJson('/api/tickets', $this->validPayload([
            'requester_id' => $technician->id,
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'requester_id' => 'El usuario indicado no tiene el rol REQUESTER.',
            ]);
    }

    public function test_inactive_requester_id_returns_422(): void
    {
        $inactive = $this->createUserWithRole('REQUESTER', isActive: false);

        Sanctum::actingAs($this->admin());

        $this->postJson('/api/tickets', $this->validPayload([
            'requester_id' => $inactive->id,
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['requester_id']);
    }

    public function test_ticket_numbers_are_sequential(): void
    {
        Sanctum::actingAs($this->admin());

        $first = $this->postJson('/api/tickets', $this->validPayload())->assertCreated();
        $second = $this->postJson('/api/tickets', $this->validPayload())->assertCreated();

        $firstNumber = (int) substr($first->json('data.ticket_number'), -6);
        $secondNumber = (int) substr($second->json('data.ticket_number'), -6);

        $this->assertSame($firstNumber + 1, $secondNumber);
    }

    public function test_write_methods_are_not_allowed_on_collection(): void
    {
        Sanctum::actingAs($this->admin());

        $this->putJson('/api/tickets', [])->assertStatus(405);
        $this->deleteJson('/api/tickets/1')->assertStatus(405);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'subject' => 'Impresora no responde en el piso 3',
            'description' => 'La impresora compartida del piso 3 no responde desde ayer.',
            'ticket_type' => 'SUPPORT',
            'priority' => 'MEDIUM',
        ], $overrides);
    }
}
