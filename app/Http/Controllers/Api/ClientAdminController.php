<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ProjectClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ClientAdminController extends Controller
{
    /**
     * List all registered clients with status and hardware details.
     */
    public function index(): JsonResponse
    {
        $clients = Client::with('project')->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $clients->map(function ($c) {
                return [
                    'id' => $c->id,
                    'project_client_id' => $c->project_client_id,
                    'project' => $c->project ? [
                        'id' => $c->project->id,
                        'name' => $c->project->name,
                        'code' => $c->project->code,
                    ] : null,
                    'name' => $c->name,
                    'slug' => $c->slug,
                    'api_key' => $c->api_key,
                    'scope' => $c->scope,
                    'status' => $c->status,
                    'machine_name' => $c->machine_name,
                    'ip_address' => $c->ip_address,
                    'mac_address' => $c->mac_address,
                    'printers' => $c->printers,
                    'is_online' => $c->is_online,
                    'last_seen_at' => $c->last_seen_at?->diffForHumans() ?? 'Belum pernah',
                    'last_seen_raw' => $c->last_seen_at?->toISOString(),
                    'created_at' => $c->created_at->toISOString(),
                ];
            }),
        ]);
    }

    /**
     * Provision a new client.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:clients,slug',
            'scope' => 'required|string|in:printer_service,prima,all',
            'project_client_id' => 'nullable|string|exists:project_clients,id',
        ]);

        if (!empty($validated['project_client_id'])) {
            $project = ProjectClient::findOrFail($validated['project_client_id']);
            if (!$project->hasQuotaAvailable()) {
                return response()->json([
                    'success' => false,
                    'message' => "Kuota printer untuk project '{$project->name}' sudah penuh (Maksimal: {$project->max_printers} printer).",
                ], 422);
            }
        }

        $slug = !empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        // Ensure unique slug
        $baseSlug = $slug;
        $counter = 1;
        while (Client::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        $apiKey = Client::generateKey();

        $client = Client::create([
            'project_client_id' => $validated['project_client_id'] ?? null,
            'name' => $validated['name'],
            'slug' => $slug,
            'api_key' => $apiKey,
            'scope' => $validated['scope'],
            'status' => 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Client baru berhasil didaftarkan.',
            'data' => $client->load('project'),
        ], 201);
    }

    /**
     * Regenerate API key for a client.
     */
    public function regenerateKey(string $id): JsonResponse
    {
        $client = Client::findOrFail($id);
        $newKey = Client::generateKey();

        $client->update(['api_key' => $newKey]);

        return response()->json([
            'success' => true,
            'message' => 'API Key baru berhasil digenerate.',
            'data' => [
                'id' => $client->id,
                'api_key' => $newKey,
            ],
        ]);
    }

    /**
     * Update client status, scope, or assigned project.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $client = Client::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'scope' => 'sometimes|required|string|in:printer_service,prima,all',
            'status' => 'sometimes|required|string|in:active,inactive',
            'project_client_id' => 'nullable|string|exists:project_clients,id',
        ]);

        if (array_key_exists('project_client_id', $validated)) {
            $newProjectId = $validated['project_client_id'];
            if ($newProjectId && $newProjectId !== $client->project_client_id) {
                $project = ProjectClient::findOrFail($newProjectId);
                if (!$project->hasQuotaAvailable()) {
                    return response()->json([
                        'success' => false,
                        'message' => "Kuota printer untuk project '{$project->name}' sudah penuh (Maksimal: {$project->max_printers} printer).",
                    ], 422);
                }
            }
        }

        $client->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data client berhasil diperbarui.',
            'data' => $client->load('project'),
        ]);
    }

    /**
     * Delete client and its associated message queues.
     */
    public function destroy(string $id): JsonResponse
    {
        $client = Client::findOrFail($id);
        $client->delete();

        return response()->json([
            'success' => true,
            'message' => 'Client berhasil dihapus beserta antriannya.',
        ]);
    }
}
