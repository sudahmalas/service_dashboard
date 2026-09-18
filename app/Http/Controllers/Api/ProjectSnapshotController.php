<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MessageQueue;
use App\Models\ProjectClient;
use App\Models\PublicItemSnapshot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProjectSnapshotController extends Controller
{
    /**
     * Push / Synchronize Public Item Snapshots from Prima (Asset, CSSD, Serial Number).
     * POST /api/v1/project/items/sync
     */
    public function sync(Request $request): JsonResponse
    {
        /** @var ProjectClient $project */
        $project = $request->get('project') ?: $request->user();

        // Support single item or batch array of items
        $items = $request->input('items');
        if (!is_array($items)) {
            $items = [$request->all()];
        }

        $synced = [];
        foreach ($items as $itemData) {
            $identifier = trim($itemData['identifier'] ?? $itemData['code'] ?? $itemData['token'] ?? '');
            if (empty($identifier)) {
                continue;
            }

            $snapshot = PublicItemSnapshot::updateOrCreate(
                [
                    'project_client_id' => $project->id,
                    'identifier' => $identifier,
                ],
                [
                    'category' => $itemData['category'] ?? 'asset',
                    'title' => $itemData['title'] ?? $itemData['name'] ?? 'Item Tanpa Nama',
                    'subtitle' => $itemData['subtitle'] ?? null,
                    'code' => $itemData['code'] ?? $identifier,
                    'location' => $itemData['location'] ?? $itemData['room'] ?? null,
                    'status_label' => strtoupper($itemData['status_label'] ?? $itemData['status'] ?? 'SIAP PAKAI'),
                    'status_color' => $itemData['status_color'] ?? 'emerald',
                    'meta_data' => $itemData['meta_data'] ?? $itemData['meta'] ?? null,
                    'is_active' => $itemData['is_active'] ?? true,
                ]
            );

            $synced[] = [
                'id' => $snapshot->id,
                'identifier' => $snapshot->identifier,
                'title' => $snapshot->title,
                'category' => $snapshot->category,
                'status_label' => $snapshot->status_label,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Berhasil menyinkronkan ' . count($synced) . ' item snapshot publik.',
            'project' => [
                'id' => $project->id,
                'code' => $project->code,
            ],
            'count' => count($synced),
            'data' => $synced,
        ]);
    }

    /**
     * Retrieve pending form submissions for this project.
     * GET /api/v1/project/queues/form-submissions
     */
    public function getFormSubmissions(Request $request): JsonResponse
    {
        /** @var ProjectClient $project */
        $project = $request->get('project') ?: $request->user();

        $queues = MessageQueue::where('project_client_id', $project->id)
            ->where('type', 'form_submission')
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'count' => $queues->count(),
            'data' => $queues->map(function ($q) {
                return [
                    'id' => $q->id,
                    'type' => $q->type,
                    'status' => $q->status,
                    'payload' => $q->payload,
                    'retry_count' => $q->retry_count,
                    'created_at' => $q->created_at?->toIso8601String(),
                ];
            }),
        ]);
    }

    /**
     * Acknowledge that Prima has processed and saved the form submission locally.
     * POST /api/v1/project/queues/{id}/ack
     */
    public function ackFormSubmission(Request $request, string $id): JsonResponse
    {
        /** @var ProjectClient $project */
        $project = $request->get('project') ?: $request->user();

        $queue = MessageQueue::where('project_client_id', $project->id)
            ->where('id', $id)
            ->first();

        if (!$queue) {
            return response()->json([
                'success' => false,
                'message' => 'Antrian tidak ditemukan atau tidak milik project ini.',
            ], 404);
        }

        $queue->update([
            'status' => 'synced',
            'synced_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Antrian laporan form submission berhasil di-ACK.',
            'data' => [
                'id' => $queue->id,
                'status' => $queue->status,
                'synced_at' => $queue->synced_at?->toIso8601String(),
            ],
        ]);
    }
}
