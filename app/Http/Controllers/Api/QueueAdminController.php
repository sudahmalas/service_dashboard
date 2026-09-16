<?php

namespace App\Http\Controllers\Api;

use App\Events\DashboardActivityEvent;
use App\Events\DispatchedClientPayloadEvent;
use App\Http\Controllers\Controller;
use App\Models\MessageQueue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QueueAdminController extends Controller
{
    /**
     * List message queues with filtering by project, client, status, type, and search query.
     */
    public function index(Request $request): JsonResponse
    {
        $query = MessageQueue::with(['client.project'])->orderBy('created_at', 'desc');

        if ($request->filled('project_id')) {
            $projectId = $request->input('project_id');
            if ($projectId === 'none') {
                $query->whereHas('client', function ($q) {
                    $q->whereNull('project_client_id');
                });
            } else {
                $query->whereHas('client', function ($q) use ($projectId) {
                    $q->where('project_client_id', $projectId);
                });
            }
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->input('client_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%")
                            ->orWhere('slug', 'like', "%{$search}%")
                            ->orWhere('machine_name', 'like', "%{$search}%")
                            ->orWhereHas('project', function ($pq) use ($search) {
                                $pq->where('name', 'like', "%{$search}%")
                                    ->orWhere('code', 'like', "%{$search}%");
                            });
                    });
            });
        }

        $queues = $query->paginate(25);

        return response()->json([
            'success' => true,
            'data' => $queues->items(),
            'meta' => [
                'current_page' => $queues->currentPage(),
                'last_page' => $queues->lastPage(),
                'total' => $queues->total(),
                'per_page' => $queues->perPage(),
                'stats' => [
                    'pending' => MessageQueue::where('status', 'pending')->count(),
                    'dispatched' => MessageQueue::where('status', 'dispatched')->count(),
                    'synced' => MessageQueue::where('status', 'synced')->count(),
                    'failed' => MessageQueue::where('status', 'failed')->count(),
                    'total' => MessageQueue::count(),
                ],
            ],
        ]);
    }

    /**
     * View details of a specific queue message including raw payload.
     */
    public function show(string $id): JsonResponse
    {
        $queue = MessageQueue::with(['client.project'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $queue,
        ]);
    }

    /**
     * Resend/re-dispatch a queue message.
     */
    public function resend(string $id): JsonResponse
    {
        $queue = MessageQueue::with('client')->findOrFail($id);

        if (!$queue->client || $queue->client->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Client target tidak aktif atau tidak ditemukan.',
            ], 422);
        }

        // Reset status to pending
        $queue->update([
            'status' => 'pending',
            'error_message' => null,
            'retry_count' => $queue->retry_count + 1,
        ]);

        // Broadcast again
        broadcast(new DispatchedClientPayloadEvent($queue->client, $queue));

        broadcast(new DashboardActivityEvent(
            type: $queue->type,
            clientName: $queue->client->name,
            status: 'resubmitted',
            message: "Manual Resend: Antrian {$queue->id} dikirim ulang ke {$queue->client->name}"
        ));

        return response()->json([
            'success' => true,
            'message' => 'Pesan antrian berhasil di-dispatch ulang via Reverb.',
            'data' => $queue,
        ]);
    }

    /**
     * Clear / delete a queue item.
     */
    public function destroy(string $id): JsonResponse
    {
        $queue = MessageQueue::findOrFail($id);
        $queue->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item antrian berhasil dihapus.',
        ]);
    }
}
