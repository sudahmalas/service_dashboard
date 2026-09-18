<?php

use App\Http\Controllers\Api\ClientAdminController;
use App\Http\Controllers\Api\ClientSyncController;
use App\Http\Controllers\Api\DashboardStatsController;
use App\Http\Controllers\Api\IngestController;
use App\Http\Controllers\Api\LegacyCompatibilityController;
use App\Http\Controllers\Api\ProjectAdminController;
use App\Http\Controllers\Api\ProjectAppController;
use App\Http\Controllers\Api\QueueAdminController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\ProjectSnapshotController;
use App\Http\Controllers\Api\PublicPortalController;

/*
|--------------------------------------------------------------------------
| WebHost API Routes
|--------------------------------------------------------------------------
*/

// --- 1. Production v1 API ---
Route::prefix('v1')->group(function () {
    // Ingestion endpoint (Supports optional X-Project-Key for tenant isolation)
    Route::post('/relay/dispatch', [IngestController::class, 'dispatch']);

    // Public Portal endpoints (Scan QR from smartphone without auth)
    Route::prefix('public')->group(function () {
        Route::get('/item/{identifier}', [PublicPortalController::class, 'getItem']);
        Route::post('/maintenance/submit', [PublicPortalController::class, 'submitMaintenance']);
    });

    // Endpoints for Prima applications authenticated with Project API Key
    Route::middleware('auth.project_key')->prefix('project')->group(function () {
        Route::get('/printers', [ProjectAppController::class, 'printers']);
        Route::post('/items/sync', [ProjectSnapshotController::class, 'sync']);
        Route::get('/queues/form-submissions', [ProjectSnapshotController::class, 'getFormSubmissions']);
        Route::post('/queues/{id}/ack', [ProjectSnapshotController::class, 'ackFormSubmission']);
    });

    // Endpoints for authenticated connected clients (PrinterService)
    Route::middleware('auth.client_key')->prefix('client')->group(function () {
        Route::get('/sync', [ClientSyncController::class, 'sync']);
        Route::post('/ack', [ClientSyncController::class, 'ack']);
        Route::post('/heartbeat', [ClientSyncController::class, 'heartbeat']);
    });

    // Endpoints for Web Management Dashboard UI
    Route::prefix('admin')->group(function () {
        Route::get('/stats', [DashboardStatsController::class, 'index']);

        // Project Clients (Tenants)
        Route::get('/projects', [ProjectAdminController::class, 'index']);
        Route::post('/projects', [ProjectAdminController::class, 'store']);
        Route::put('/projects/{id}', [ProjectAdminController::class, 'update']);
        Route::post('/projects/{id}/regenerate-key', [ProjectAdminController::class, 'regenerateKey']);
        Route::post('/projects/{id}/assign-client', [ProjectAdminController::class, 'assignClient']);
        Route::post('/projects/{id}/remove-client', [ProjectAdminController::class, 'removeClient']);
        Route::delete('/projects/{id}', [ProjectAdminController::class, 'destroy']);

        // Printer Clients (Hardware nodes)
        Route::get('/clients', [ClientAdminController::class, 'index']);
        Route::post('/clients', [ClientAdminController::class, 'store']);
        Route::put('/clients/{id}', [ClientAdminController::class, 'update']);
        Route::post('/clients/{id}/regenerate-key', [ClientAdminController::class, 'regenerateKey']);
        Route::delete('/clients/{id}', [ClientAdminController::class, 'destroy']);

        // Buffer Queues
        Route::get('/queues', [QueueAdminController::class, 'index']);
        Route::get('/queues/{id}', [QueueAdminController::class, 'show']);
        Route::post('/queues/{id}/resend', [QueueAdminController::class, 'resend']);
        Route::delete('/queues/{id}', [QueueAdminController::class, 'destroy']);
    });
});

// --- 2. Legacy Compatibility Layer for PrinterService & Prima ---
Route::prefix('print-service')->group(function () {
    Route::post('/connect-info', [LegacyCompatibilityController::class, 'connectInfo']);
    Route::post('/register', [LegacyCompatibilityController::class, 'register']);
    Route::post('/unregister', [LegacyCompatibilityController::class, 'unregister']);
    Route::post('/dispatch', [LegacyCompatibilityController::class, 'dispatch']);
});
