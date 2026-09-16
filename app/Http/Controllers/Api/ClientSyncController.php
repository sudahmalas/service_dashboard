<?php

namespace App\Http\Controllers\Api;

use App\Events\DashboardActivityEvent;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\MessageQueue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientSyncController extends Controller
{
    /**
     * Retrieve all pending messages for the authenticated client.
     * GET /api/v1/client/sync
     */
    public function sync(Request $request): JsonResponse
    {
        /** @var Client $client */
        $client = $request->user();

        $client->touchLastSeen();

        $pendingMessages = MessageQueue::where('client_id', $client->id)
            ->pending()
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'client_id' => $client->id,
            'client_name' => $client->name,
            'count' => $pendingMessages->count(),
            'data' => $pendingMessages->map(function ($msg) {
                return [
                    'id' => $msg->id,
                    'type' => $msg->type,
                    'payload' => $msg->payload,
                    'retry_count' => $msg->retry_count,
                    'created_at' => $msg->created_at->toISOString(),
                ];
            }),
        ]);
    }

    /**
     * Acknowledge execution completion or report error for a queue message.
     * POST /api/v1/client/ack
     */
    public function ack(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message_id' => 'required|string',
            'status' => 'required|string|in:synced,failed',
            'error_message' => 'nullable|string',
        ]);

        /** @var Client $client */
        $client = $request->user();
        $client->touchLastSeen();

        $message = MessageQueue::where('id', $validated['message_id'])
            ->where('client_id', $client->id)
            ->first();

        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Pesan antrian tidak ditemukan untuk client ini.',
            ], 404);
        }

        if ($validated['status'] === 'synced') {
            $message->markAsSynced();

            broadcast(new DashboardActivityEvent(
                type: $message->type,
                clientName: $client->name,
                status: 'synced',
                message: "Tugas selesai dieksekusi oleh {$client->name} (ID: " . substr($message->id, 0, 8) . "...)"
            ));
        } else {
            $message->markAsFailed($validated['error_message'] ?? 'Client reported execution failure.');

            broadcast(new DashboardActivityEvent(
                type: $message->type,
                clientName: $client->name,
                status: 'failed',
                message: "Gagal eksekusi pada {$client->name}: " . ($validated['error_message'] ?? 'Error tidak diketahui')
            ));
        }

        return response()->json([
            'success' => true,
            'message' => 'Acknowledgment berhasil dicatat.',
            'data' => [
                'message_id' => $message->id,
                'status' => $message->status,
                'synced_at' => $message->synced_at?->toISOString(),
                'error_message' => $message->error_message,
            ],
        ]);
    }

    /**
     * Keepalive heartbeat to refresh client's online presence.
     * POST /api/v1/client/heartbeat
     */
    public function heartbeat(Request $request): JsonResponse
    {
        /** @var Client $client */
        $client = $request->user();
        $client->touchLastSeen();

        return response()->json([
            'success' => true,
            'client_id' => $client->id,
            'client_name' => $client->name,
            'status' => $client->status,
            'is_online' => true,
            'last_seen_at' => $client->last_seen_at->toISOString(),
        ]);
    }
}
