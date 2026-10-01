<?php

namespace Tests\Feature\Catalogs;

use App\Models\Category;
use App\Models\TicketType;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Auth\AuthTestCase;

/**
 * Pruebas de los catálogos READ-ONLY de FASE 6.1.
 *
 * Reutiliza la base de pruebas de Fase 5 (transacción MySQL sobre helpdesk_v11)
 * para no duplicar infraestructura.
 */
class CatalogsTest extends AuthTestCase
{
    /**
     * @var array<string, string>
     */
    private const CATALOGS = [
        'roles' => '/api/catalogs/roles',
        'organizational-units' => '/api/catalogs/organizational-units',
        'ticket-types' => '/api/catalogs/ticket-types',
        'ticket-statuses' => '/api/catalogs/ticket-statuses',
        'ticket-priorities' => '/api/catalogs/ticket-priorities',
        'categories' => '/api/catalogs/categories',
        'intervention-types' => '/api/catalogs/intervention-types',
        'asset-types' => '/api/catalogs/asset-types',
        'asset-states' => '/api/catalogs/asset-states',
    ];

    public function test_catalogs_require_authentication(): void
    {
        foreach (self::CATALOGS as $uri) {
            $this->getJson($uri)->assertUnauthorized();
        }
    }

    public function test_catalogs_reject_inactive_users(): void
    {
        Sanctum::actingAs($this->createUserWithRole('TECH', isActive: false));

        foreach (self::CATALOGS as $uri) {
            $this->getJson($uri)
                ->assertForbidden()
                ->assertJson(['message' => 'El usuario está inactivo.']);
        }
    }

    public function test_catalogs_are_available_to_every_role(): void
    {
        foreach (['ADMIN', 'TECH', 'REQUESTER'] as $role) {
            Sanctum::actingAs($this->createUserWithRole($role));

            foreach (self::CATALOGS as $uri) {
                $this->getJson($uri)->assertOk()->assertJsonStructure(['data']);
            }
        }
    }

    public function test_catalogs_do_not_accept_write_methods(): void
    {
        Sanctum::actingAs($this->createUserWithRole('ADMIN'));

        $this->postJson('/api/catalogs/roles')->assertStatus(405);
        $this->putJson('/api/catalogs/ticket-types', ['name' => 'Otro'])->assertStatus(405);
        $this->deleteJson('/api/catalogs/categories', [1])->assertStatus(405);
    }

    public function test_roles_catalog_contains_the_functional_roles(): void
    {
        Sanctum::actingAs($this->createUserWithRole('ADMIN'));

        $response = $this->getJson('/api/catalogs/roles')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'code', 'name']]]);

        $this->assertSame(['ADMIN', 'REQUESTER', 'TECH'], $this->codes($response->json('data')));
    }

    public function test_ticket_types_catalog_matches_v11_and_excludes_repair(): void
    {
        Sanctum::actingAs($this->createUserWithRole('REQUESTER'));

        $response = $this->getJson('/api/catalogs/ticket-types')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'code', 'name', 'description', 'sort_order']]]);

        $codes = $this->codes($response->json('data'));

        $this->assertSame(['INCIDENT', 'MAINTENANCE', 'REQUEST', 'SUPPORT'], $codes);
        $this->assertNotContains('REPAIR', $codes);
    }

    public function test_ticket_statuses_catalog_contains_exactly_the_seven_v11_statuses(): void
    {
        Sanctum::actingAs($this->createUserWithRole('TECH'));

        $response = $this->getJson('/api/catalogs/ticket-statuses')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'code', 'name', 'color', 'sort_order', 'is_initial', 'is_resolved', 'is_terminal']]]);

        $data = $response->json('data');
        $codes = $this->codes($data);

        $this->assertCount(7, $data);
        $this->assertSame(
            ['ASSIGNED', 'CANCELLED', 'CLOSED', 'IN_PROGRESS', 'REGISTERED', 'RESOLVED', 'WAITING'],
            $codes,
        );
        $this->assertNotContains('REOPENED', $codes);

        $this->assertSame('REGISTERED', $data[0]['code']);
        $this->assertTrue((bool) $data[0]['is_initial']);
        $this->assertTrue((bool) $this->statusByCode($data, 'RESOLVED')['is_resolved']);
        $this->assertTrue((bool) $this->statusByCode($data, 'CLOSED')['is_terminal']);
    }

    public function test_ticket_priorities_catalog_contains_exactly_the_four_v11_priorities(): void
    {
        Sanctum::actingAs($this->createUserWithRole('REQUESTER'));

        $response = $this->getJson('/api/catalogs/ticket-priorities')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'code', 'name', 'color', 'sort_order']]]);

        $this->assertSame(
            ['CRITICAL', 'HIGH', 'LOW', 'MEDIUM'],
            $this->codes($response->json('data')),
        );
    }

    public function test_intervention_types_catalog_includes_repair(): void
    {
        Sanctum::actingAs($this->createUserWithRole('TECH'));

        $response = $this->getJson('/api/catalogs/intervention-types')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'code', 'name', 'description', 'sort_order']]]);

        $codes = $this->codes($response->json('data'));

        $this->assertCount(10, $codes);
        $this->assertContains('REPAIR', $codes);
        $this->assertContains('DIAGNOSIS', $codes);
        $this->assertContains('INSTALLATION', $codes);
        $this->assertContains('CONFIGURATION', $codes);
        $this->assertContains('PREVENTIVE_MAINTENANCE', $codes);
    }

    public function test_asset_catalogs_return_active_records(): void
    {
        Sanctum::actingAs($this->createUserWithRole('ADMIN'));

        $types = $this->getJson('/api/catalogs/asset-types')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'code', 'name', 'sort_order']]])
            ->json('data');

        $states = $this->getJson('/api/catalogs/asset-states')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'code', 'name', 'color', 'sort_order']]])
            ->json('data');

        $this->assertCount(8, $types);
        $this->assertCount(5, $states);
        $this->assertContains('PRINTER', array_column($types, 'code'));
        $this->assertContains('IN_REPAIR', array_column($states, 'code'));
    }

    public function test_organizational_units_catalog_includes_type_and_parent(): void
    {
        Sanctum::actingAs($this->createUserWithRole('ADMIN'));

        $response = $this->getJson('/api/catalogs/organizational-units')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'code', 'name', 'description', 'parent_id', 'type', 'parent']],
            ]);

        $unit = collect($response->json('data'))->firstWhere('code', 'DEV-UNIT');

        $this->assertNotNull($unit);
        $this->assertNull($unit['parent_id']);
        $this->assertNull($unit['parent']);
        $this->assertSame('unidad', $unit['type']['code']);
        $this->assertSame('Unidad', $unit['type']['name']);
    }

    public function test_organizational_units_can_be_filtered_by_type(): void
    {
        Sanctum::actingAs($this->createUserWithRole('ADMIN'));

        $filtered = $this->getJson('/api/catalogs/organizational-units?type=unidad')
            ->assertOk()
            ->json('data');

        $this->assertCount(1, $filtered);
        $this->assertSame('DEV-UNIT', $filtered[0]['code']);

        $empty = $this->getJson('/api/catalogs/organizational-units?type=camara')
            ->assertOk()
            ->json('data');

        $this->assertSame([], $empty);
    }

    public function test_organizational_units_reject_invalid_type_filter(): void
    {
        Sanctum::actingAs($this->createUserWithRole('ADMIN'));

        $this->getJson('/api/catalogs/organizational-units?type=inexistente')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type']);

        $this->getJson('/api/catalogs/organizational-units?type=')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    public function test_categories_catalog_returns_nested_roots_and_subcategories(): void
    {
        Sanctum::actingAs($this->createUserWithRole('REQUESTER'));

        $response = $this->getJson('/api/catalogs/categories')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'code', 'name', 'description', 'sort_order', 'subcategories' => [['id', 'code', 'name']]]],
            ]);

        $roots = $response->json('data');
        $children = 0;

        $this->assertCount(9, $roots);

        foreach ($roots as $root) {
            $this->assertArrayHasKey('subcategories', $root);

            foreach ($root['subcategories'] as $subcategory) {
                $children++;
                $this->assertArrayNotHasKey('subcategories', $subcategory);
            }
        }

        $this->assertSame(45, $children);
        $this->assertSame(0, $this->categoriesBelowSecondLevel());
    }

    public function test_categories_catalog_filters_by_root_name(): void
    {
        Sanctum::actingAs($this->createUserWithRole('REQUESTER'));

        $data = $this->getJson('/api/catalogs/categories?search=Computadoras')
            ->assertOk()
            ->json('data');

        $this->assertCount(1, $data);
        $this->assertSame('COMPUTERS', $data[0]['code']);
        $this->assertCount(8, $data[0]['subcategories']);
    }

    public function test_categories_catalog_filters_by_subcategory_name(): void
    {
        Sanctum::actingAs($this->createUserWithRole('REQUESTER'));

        $data = $this->getJson('/api/catalogs/categories?search=No%20enciende')
            ->assertOk()
            ->json('data');

        $this->assertCount(1, $data);
        $this->assertSame('COMPUTERS', $data[0]['code']);
        $this->assertCount(1, $data[0]['subcategories']);
        $this->assertSame('COMPUTERS_NO_ENCIENDE', $data[0]['subcategories'][0]['code']);
    }

    public function test_categories_catalog_returns_empty_list_when_search_has_no_matches(): void
    {
        Sanctum::actingAs($this->createUserWithRole('REQUESTER'));

        $data = $this->getJson('/api/catalogs/categories?search=sin-coincidencias')
            ->assertOk()
            ->json('data');

        $this->assertSame([], $data);
    }

    public function test_categories_catalog_rejects_invalid_search(): void
    {
        Sanctum::actingAs($this->createUserWithRole('REQUESTER'));

        $this->getJson('/api/catalogs/categories?search=')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['search']);

        $this->getJson('/api/catalogs/categories?search='.str_repeat('a', 101))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['search']);
    }

    public function test_catalogs_only_return_active_records(): void
    {
        $inactive = new TicketType;
        $inactive->code = 'TEST_INACTIVE';
        $inactive->name = 'Tipo inactivo de prueba';
        $inactive->is_active = false;
        $inactive->save();

        Sanctum::actingAs($this->createUserWithRole('REQUESTER'));

        $codes = $this->codes($this->getJson('/api/catalogs/ticket-types')->assertOk()->json('data'));

        $this->assertNotContains('TEST_INACTIVE', $codes);
        $this->assertCount(4, $codes);
    }

    /**
     * Códigos ordenados de los elementos de un catálogo.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, string>
     */
    private function codes(array $items): array
    {
        $codes = array_column($items, 'code');
        sort($codes);

        return $codes;
    }

    /**
     * @param  array<int, array<string, mixed>>  $statuses
     * @return array<string, mixed>
     */
    private function statusByCode(array $statuses, string $code): array
    {
        foreach ($statuses as $status) {
            if ($status['code'] === $code) {
                return $status;
            }
        }

        $this->fail("El estado {$code} no existe en la respuesta.");
    }

    /**
     * Categorías por debajo del segundo nivel (debe ser 0 en V1.1).
     */
    private function categoriesBelowSecondLevel(): int
    {
        return Category::query()
            ->whereNotNull('parent_id')
            ->whereHas('parent', fn (Builder $query): Builder => $query->whereNotNull('parent_id'))
            ->count();
    }
}
