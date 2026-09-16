<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProjectClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectAppController extends Controller
{
    /**
     * Retrieve all authorized printers specifically allocated for this Project.
     * GET /api/v1/project/printers
     */
    public function printers(Request $request): JsonResponse
    {
        /** @var ProjectClient $project */
        $project = $request->get('project') ?: $request->user();

        $printers = $project->printers()
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'code' => $project->code,
                'quota' => [
                    'used' => $printers->count(),
                    'max' => $project->max_printers,
                ],
            ],
            'data' => $printers->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'slug' => $p->slug,
                    'channel' => 'printer.' . $p->slug,
                    'is_online' => $p->is_online,
                    'status' => $p->status,
                    'last_seen_at' => $p->last_seen_at?->diffForHumans() ?? 'Offline',
                    'machine_name' => $p->machine_name,
                    'ip_address' => $p->ip_address,
                    'os_printers' => $p->printers,
                ];
            }),
        ]);
    }
}
