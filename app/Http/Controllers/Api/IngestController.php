<?php

namespace App\Http\Controllers\Api;

use App\Events\DashboardActivityEvent;
use App\Events\DispatchedClientPayloadEvent;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\MessageQueue;
use App\Models\ProjectClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IngestController extends Controller
{
    /**
     * Dispatch payload to a target client via Store & Forward queue + Reverb broadcast.
     * Supports Tenant Project Isolation (X-Project-Key).
     * POST /api/v1/relay/dispatch
     */
    public function dispatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'target_client_id' => 'nullable|string',
            'target_slug' => 'nullable|string',
            'target_service_name' => 'nullable|string',
            'type' => 'required|string|in:print_label,form_submission,sync_command',
            'payload' => 'required|array',
        ]);

        // 1. Optional Tenant/Project Identification
        $project = null;
        $projectKey = $request->header('X-Project-Key')
            ?: ($request->bearerToken() && str_starts_with($request->bearerToken(), 'proj_') ? $request->bearerToken() : null)
            ?: $request->input('project_key');

        if ($projectKey) {
            $project = ProjectClient::where('api_key', $projectKey)->active()->first();
            if (!$project) {
                return response()->json([
                    'success' => false,
                    'message' => 'Project API Key tidak valid atau project sedang non-aktif.',
                ], 401);
            }
        }

        // 2. Locate target client (Restricted to Project's printers if project is authenticated)
        $targetClient = null;
        $clientQuery = $project ? $project->printers()->active() : Client::active();

        if (!empty($validated['target_client_id'])) {
            $targetClient = $clientQuery->where('id', $validated['target_client_id'])->first();
        }

        if (!$targetClient && !empty($validated['target_slug'])) {
            $targetClient = $clientQuery->where('slug', $validated['target_slug'])->first();
        }

        if (!$targetClient && !empty($validated['target_service_name'])) {
            $targetName = $validated['target_service_name'];
            $targetClient = $clientQuery->where(function ($q) use ($targetName) {
                $q->where('name', $targetName)
                  ->orWhere('slug', str_replace(' ', '-', $targetName));
            })->first();
        }

        // Fallback: If only 1 client is available in this project scope, auto-target
        if (!$targetClient) {
            $availableClients = $clientQuery->get();
            if ($availableClients->count() === 1) {
                $targetClient = $availableClients->first();
            }
        }

        if (!$targetClient) {
            $scopeNotice = $project ? " pada project '{$project->name}'" : '';
            return response()->json([
                'success' => false,
                'message' => "Target printer/client tidak ditemukan atau belum dialokasikan{$scopeNotice}.",
            ], 404);
        }

        // 3. Security Isolation Check: Ensure target client does not belong to another project
        if ($project && $targetClient->project_client_id && $targetClient->project_client_id !== $project->id) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: Printer ini dialokasikan untuk project atau rumah sakit lain.',
            ], 403);
        }

        // 4. Persist to message_queues buffer (status: pending)
        $message = MessageQueue::create([
            'client_id' => $targetClient->id,
            'type' => $validated['type'],
            'payload' => $validated['payload'],
            'status' => 'pending',
            'retry_count' => 0,
        ]);

        // 5. Immediately broadcast via Laravel Reverb (zero-latency)
        broadcast(new DispatchedClientPayloadEvent($targetClient, $message));

        // 6. Notify live dashboard
        $summary = match ($validated['type']) {
            'print_label' => 'Perintah cetak baru diterima',
            'form_submission' => 'Form data baru diterima',
            default => 'Perintah sinkronisasi baru diterima',
        };

        $projectNameNotice = $project ? " [{$project->name}]" : '';

        broadcast(new DashboardActivityEvent(
            type: $validated['type'],
            clientName: $targetClient->name,
            status: 'dispatched',
            message: "{$summary}{$projectNameNotice} untuk {$targetClient->name} (Queue ID: " . substr($message->id, 0, 8) . "...)"
        ));

        return response()->json([
            'success' => true,
            'message' => 'Payload berhasil disimpan ke antrian dan di-broadcast ke target client.',
            'data' => [
                'message_id' => $message->id,
                'client_id' => $targetClient->id,
                'client_name' => $targetClient->name,
                'client_slug' => $targetClient->slug,
                'project_name' => $project?->name,
                'type' => $message->type,
                'status' => $message->status,
                'created_at' => $message->created_at->toISOString(),
            ],
        ], 201);
    }
}
