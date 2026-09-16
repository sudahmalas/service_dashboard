<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ProjectClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProjectAdminController extends Controller
{
    /**
     * List all projects with printer quota information.
     */
    public function index(): JsonResponse
    {
        $projects = ProjectClient::with('printers')->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $projects->map(function ($proj) {
                return [
                    'id' => $proj->id,
                    'name' => $proj->name,
                    'code' => $proj->code,
                    'api_key' => $proj->api_key,
                    'max_printers' => $proj->max_printers,
                    'printers_count' => $proj->printers->count(),
                    'quota_used' => $proj->printers->count(),
                    'status' => $proj->status,
                    'description' => $proj->description,
                    'created_at' => $proj->created_at->toISOString(),
                    'printers' => $proj->printers->map(function ($p) {
                        return [
                            'id' => $p->id,
                            'name' => $p->name,
                            'slug' => $p->slug,
                            'machine_name' => $p->machine_name,
                            'ip_address' => $p->ip_address,
                            'mac_address' => $p->mac_address,
                            'is_online' => $p->is_online,
                            'status' => $p->status,
                            'last_seen_at' => $p->last_seen_at?->diffForHumans() ?? 'Belum pernah',
                        ];
                    }),
                ];
            }),
        ]);
    }

    /**
     * Provision a new Project Client (Tenant).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:project_clients,code',
            'max_printers' => 'required|integer|min:1|max:100',
            'description' => 'nullable|string',
        ]);

        $project = ProjectClient::create([
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'api_key' => ProjectClient::generateKey(),
            'max_printers' => $validated['max_printers'],
            'status' => 'active',
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Project client berhasil didaftarkan.',
            'data' => $project,
        ], 201);
    }

    /**
     * Update project settings or quota.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $project = ProjectClient::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'max_printers' => 'sometimes|required|integer|min:1|max:100',
            'status' => 'sometimes|required|string|in:active,inactive',
            'description' => 'nullable|string',
        ]);

        $project->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Project berhasil diperbarui.',
            'data' => $project,
        ]);
    }

    /**
     * Regenerate Project API Key.
     */
    public function regenerateKey(string $id): JsonResponse
    {
        $project = ProjectClient::findOrFail($id);
        $newKey = ProjectClient::generateKey();

        $project->update(['api_key' => $newKey]);

        return response()->json([
            'success' => true,
            'message' => 'Project API Key baru berhasil digenerate.',
            'data' => [
                'id' => $project->id,
                'api_key' => $newKey,
            ],
        ]);
    }

    /**
     * Assign or transfer a printer client to a project.
     */
    public function assignClient(Request $request, string $id): JsonResponse
    {
        $project = ProjectClient::findOrFail($id);

        $validated = $request->validate([
            'client_id' => 'required|string|exists:clients,id',
        ]);

        $client = Client::findOrFail($validated['client_id']);

        // Check quota if assigning a new client to this project
        if ($client->project_client_id !== $project->id) {
            if (!$project->hasQuotaAvailable()) {
                return response()->json([
                    'success' => false,
                    'message' => "Kuota printer untuk project ini sudah penuh (Maksimal: {$project->max_printers} printer). Tingkatkan kuota di pengaturan project.",
                ], 422);
            }
        }

        $client->update(['project_client_id' => $project->id]);

        return response()->json([
            'success' => true,
            'message' => "Printer '{$client->name}' berhasil dialokasikan ke project '{$project->name}'.",
        ]);
    }

    /**
     * Remove / detach a printer client from a project.
     */
    public function removeClient(Request $request, string $id): JsonResponse
    {
        $project = ProjectClient::findOrFail($id);

        $validated = $request->validate([
            'client_id' => 'required|string|exists:clients,id',
        ]);

        $client = Client::where('id', $validated['client_id'])
            ->where('project_client_id', $project->id)
            ->firstOrFail();

        $client->update(['project_client_id' => null]);

        return response()->json([
            'success' => true,
            'message' => "Printer '{$client->name}' berhasil dilepas dari project '{$project->name}'.",
        ]);
    }

    /**
     * Delete project.
     */
    public function destroy(string $id): JsonResponse
    {
        $project = ProjectClient::findOrFail($id);
        $project->delete();

        return response()->json([
            'success' => true,
            'message' => 'Project berhasil dihapus.',
        ]);
    }
}
