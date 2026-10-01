<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalogs\CategoryCatalogRequest;
use App\Http\Requests\Catalogs\OrganizationalUnitCatalogRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\OrganizationalUnitResource;
use App\Models\AssetState;
use App\Models\AssetType;
use App\Models\Category;
use App\Models\InterventionType;
use App\Models\OrganizationalUnit;
use App\Models\Role;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\TicketType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;

/**
 * Catálogos READ-ONLY de consulta para el frontend (FASE 6.1).
 *
 * Solo GET, solo registros activos y solo campos necesarios: la selección de
 * columnas define exactamente qué se expone en la respuesta JSON.
 */
class CatalogController extends Controller
{
    /**
     * Roles funcionales (ADMIN, TECH, REQUESTER).
     */
    public function roles(): JsonResponse
    {
        $roles = Role::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'description']);

        return response()->json(['data' => $roles]);
    }

    /**
     * Unidades organizacionales activas con su tipo y padre.
     *
     * Filtro: ?type={code de organizational_unit_types}
     */
    public function organizationalUnits(OrganizationalUnitCatalogRequest $request): JsonResponse
    {
        $type = $request->validated('type');

        $units = OrganizationalUnit::query()
            ->where('is_active', true)
            ->when(
                $type,
                fn (Builder $query): Builder => $query->whereHas(
                    'type',
                    fn (Builder $typeQuery): Builder => $typeQuery->where('code', $type),
                ),
            )
            ->with(['type:id,code,name', 'parent:id,code,name'])
            ->orderBy('name')
            ->get(['id', 'parent_id', 'organizational_unit_type_id', 'code', 'name', 'description']);

        return response()->json(['data' => OrganizationalUnitResource::collection($units)]);
    }

    /**
     * Tipos de ticket activos (SUPPORT, INCIDENT, REQUEST, MAINTENANCE).
     */
    public function ticketTypes(): JsonResponse
    {
        $types = TicketType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'description', 'sort_order']);

        return response()->json(['data' => $types]);
    }

    /**
     * Estados de ticket activos (7 estados V1.1, sin REOPENED).
     */
    public function ticketStatuses(): JsonResponse
    {
        $statuses = TicketStatus::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get([
                'id',
                'code',
                'name',
                'description',
                'color',
                'sort_order',
                'is_initial',
                'is_resolved',
                'is_terminal',
            ]);

        return response()->json(['data' => $statuses]);
    }

    /**
     * Prioridades de ticket activas (LOW, MEDIUM, HIGH, CRITICAL).
     */
    public function ticketPriorities(): JsonResponse
    {
        $priorities = TicketPriority::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'color', 'sort_order']);

        return response()->json(['data' => $priorities]);
    }

    /**
     * Categorías activas anidadas: raíces con sus subcategorías (máx. 2 niveles).
     *
     * Filtro: ?search={texto} coincide con raíz o subcategoría; una raíz que
     * coincide devuelve todas sus subcategorías, y una raíz incluida solo por
     * una subcategoría devuelve únicamente las subcategorías coincidentes.
     */
    public function categories(CategoryCatalogRequest $request): JsonResponse
    {
        $search = $request->validated('search');

        $roots = Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->when($search !== null, fn (Builder $query): Builder => $query->where(
                fn (Builder $group): Builder => $group
                    ->where(fn (Builder $like): Builder => $like
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%"))
                    ->orWhereHas('children', fn (Builder $children): Builder => $children
                        ->where('is_active', true)
                        ->where(fn (Builder $like): Builder => $like
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%"))),
            ))
            ->with(['children' => fn (Relation $query): Relation => $query
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'parent_id', 'code', 'name', 'description', 'sort_order']);

        if ($search !== null) {
            $roots = $roots->map(function (Category $root) use ($search): Category {
                if (! $this->matchesSearch($root, $search)) {
                    $root->setRelation(
                        'children',
                        $root->children
                            ->filter(fn (Category $child): bool => $this->matchesSearch($child, $search))
                            ->values(),
                    );
                }

                return $root;
            })->values();
        }

        return response()->json(['data' => CategoryResource::collection($roots)]);
    }

    /**
     * Tipos de intervención activos (incluye REPAIR).
     */
    public function interventionTypes(): JsonResponse
    {
        $types = InterventionType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'description', 'sort_order']);

        return response()->json(['data' => $types]);
    }

    /**
     * Tipos de activo activos.
     */
    public function assetTypes(): JsonResponse
    {
        $types = AssetType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'sort_order']);

        return response()->json(['data' => $types]);
    }

    /**
     * Estados de activo activos.
     */
    public function assetStates(): JsonResponse
    {
        $states = AssetState::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'color', 'sort_order']);

        return response()->json(['data' => $states]);
    }

    /**
     * Coincidencia insensible a mayúsculas/acentos del filtro de categorías.
     */
    private function matchesSearch(Category $category, string $search): bool
    {
        $needle = mb_strtolower($search);

        return str_contains(mb_strtolower($category->name), $needle)
            || str_contains(mb_strtolower((string) $category->code), $needle);
    }
}
