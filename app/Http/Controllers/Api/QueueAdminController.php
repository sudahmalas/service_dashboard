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
     * List message queues with filtering by client, status, and type.
     */
    public function index(Request $request): JsonResponse
    {
        $query = MessageQueue::with('client')->orderBy('created_at', 'desc');

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->input('client_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
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
            ],
        ]);
    }

    /**
     * View details of a specific queue message including raw payload.
     */
    public function show(string $id): JsonResponse
    {
        $queue = MessageQueue::with('client')->findOrFail($id);

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
