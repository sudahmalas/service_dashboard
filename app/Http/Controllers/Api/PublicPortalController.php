<?php

namespace App\Http\Controllers\Api;

use App\Events\DashboardActivityEvent;
use App\Http\Controllers\Controller;
use App\Models\MessageQueue;
use App\Models\ProjectClient;
use App\Models\PublicItemSnapshot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicPortalController extends Controller
{
    /**
     * Retrieve public verification info for an item (CSSD or Asset/Serial).
     * GET /api/v1/public/item/{identifier}
     */
    public function getItem(string $identifier): JsonResponse
    {
        $cleanId = trim($identifier);

        $item = PublicItemSnapshot::where('identifier', $cleanId)
            ->orWhere('code', $cleanId)
            ->where('is_active', true)
            ->with(['project:id,name,code'])
            ->first();

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => "Item dengan kode '{$cleanId}' belum terdaftar di WebHost.",
                'identifier' => $cleanId,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $item->id,
                'identifier' => $item->identifier,
                'category' => $item->category,
                'title' => $item->title,
                'subtitle' => $item->subtitle,
                'code' => $item->code,
                'location' => $item->location,
                'status_label' => $item->status_label,
                'status_color' => $item->status_color,
                'meta_data' => $item->meta_data,
                'project' => $item->project ? [
                    'name' => $item->project->name,
                    'code' => $item->project->code,
                ] : null,
                'updated_at' => $item->updated_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Submit technician maintenance report from public mobile form.
     * POST /api/v1/public/maintenance/submit
     */
    public function submitMaintenance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'identifier' => 'nullable|string',
            'project_code' => 'nullable|string',
            'performer_name' => 'required|string|max:100',
            'job_type' => 'required|string|in:rutin,perbaikan,kalibrasi',
            'operational_status' => 'required|string|in:bisa_digunakan,tidak_bisa_digunakan',
            'problem' => 'nullable|string',
            'resolution' => 'nullable|string',
            'checklist' => 'nullable|array',
            'notes' => 'nullable|string',
            'images_before' => 'nullable|array',
            'images_after' => 'nullable|array',
        ]);

        // 1. Resolve project_client_id
        $projectId = null;
        $item = null;
        if (!empty($validated['identifier'])) {
            $item = PublicItemSnapshot::where('identifier', $validated['identifier'])
                ->orWhere('code', $validated['identifier'])
                ->first();
            if ($item && $item->project_client_id) {
                $projectId = $item->project_client_id;
            }
        }

        if (!$projectId && !empty($validated['project_code'])) {
            $projectId = ProjectClient::where('code', $validated['project_code'])->value('id');
        }

        if (!$projectId) {
            // Default to first active project if not explicitly bound
            $projectId = ProjectClient::active()->value('id');
        }

        // 2. Insert into Store-and-Forward message_queues
        $queueId = (string) Str::uuid();
        $payload = [
            'form_type' => 'maintenance_report',
            'identifier' => $validated['identifier'] ?? null,
            'item_title' => $item?->title ?? ($validated['identifier'] ?? 'Alat Medis/Aset'),
            'item_category' => $item?->category ?? 'asset',
            'performer_name' => $validated['performer_name'],
            'job_type' => $validated['job_type'],
            'operational_status' => $validated['operational_status'],
            'problem' => $validated['problem'] ?? null,
            'resolution' => $validated['resolution'] ?? null,
            'checklist' => $validated['checklist'] ?? [],
            'notes' => $validated['notes'] ?? null,
            'images_before' => $validated['images_before'] ?? [],
            'images_after' => $validated['images_after'] ?? [],
            'submitted_at' => now()->toIso8601String(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        $queue = MessageQueue::create([
            'id' => $queueId,
            'project_client_id' => $projectId,
            'type' => 'form_submission',
            'status' => 'pending',
            'payload' => $payload,
            'retry_count' => 0,
        ]);

        // 3. Broadcast real-time event to WebHost Dashboard
        try {
            broadcast(new DashboardActivityEvent(
                'form_submission',
                $item?->title ?? ($validated['identifier'] ?? 'Laporan Teknisi'),
                'pending',
                "Laporan maintenance disubmit oleh {$validated['performer_name']} (Menunggu sync Prima)"
            ));
        } catch (\Throwable $e) {
            // Ignore broadcast failure on dev if Reverb is temporarily idle
        }

        return response()->json([
            'success' => true,
            'message' => 'Laporan hasil maintenance berhasil dikirim dan tersimpan di antrian sinkronisasi rumah sakit.',
            'data' => [
                'queue_id' => $queue->id,
                'status' => $queue->status,
                'item' => $item ? [
                    'identifier' => $item->identifier,
                    'title' => $item->title,
                ] : null,
                'submitted_at' => now()->toIso8601String(),
            ],
        ]);
    }
}
