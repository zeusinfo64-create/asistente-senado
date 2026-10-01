<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\TicketController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:login')
    ->name('auth.login');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::get('/me', [AuthController::class, 'me'])->name('auth.me');

    Route::prefix('catalogs')->name('catalogs.')->group(function (): void {
        Route::get('/roles', [CatalogController::class, 'roles'])->name('roles');
        Route::get('/organizational-units', [CatalogController::class, 'organizationalUnits'])
            ->name('organizational-units');
        Route::get('/ticket-types', [CatalogController::class, 'ticketTypes'])->name('ticket-types');
        Route::get('/ticket-statuses', [CatalogController::class, 'ticketStatuses'])->name('ticket-statuses');
        Route::get('/ticket-priorities', [CatalogController::class, 'ticketPriorities'])
            ->name('ticket-priorities');
        Route::get('/categories', [CatalogController::class, 'categories'])->name('categories');
        Route::get('/intervention-types', [CatalogController::class, 'interventionTypes'])
            ->name('intervention-types');
        Route::get('/asset-types', [CatalogController::class, 'assetTypes'])->name('asset-types');
        Route::get('/asset-states', [CatalogController::class, 'assetStates'])->name('asset-states');
    });

    Route::prefix('tickets')->name('tickets.')->group(function (): void {
        Route::get('/', [TicketController::class, 'index'])->name('index');
        Route::post('/', [TicketController::class, 'store'])->name('store');
        Route::get('/{ticket}', [TicketController::class, 'show'])
            ->missing(fn (): JsonResponse => response()->json(['message' => 'Ticket no encontrado.'], 404))
            ->name('show');
        Route::patch('/{ticket}', [TicketController::class, 'update'])
            ->missing(fn (): JsonResponse => response()->json(['message' => 'Ticket no encontrado.'], 404))
            ->name('update');
    });
});
