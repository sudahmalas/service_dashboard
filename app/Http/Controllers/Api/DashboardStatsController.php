<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\MessageQueue;
use Illuminate\Http\JsonResponse;

class DashboardStatsController extends Controller
{
    /**
     * Get real-time system metrics for the dashboard overview.
     */
    public function index(): JsonResponse
    {
        $allClients = Client::all();
        $onlineClients = $allClients->filter->is_online;

        $pendingCount = MessageQueue::pending()->count();
        $syncedTodayCount = MessageQueue::synced()
            ->whereDate('synced_at', now()->toDateString())
            ->count();
        $failedCount = MessageQueue::failed()->count();

        $recentQueues = MessageQueue::with('client')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($q) {
                return [
                    'id' => $q->id,
                    'client_id' => $q->client_id,
                    'client_name' => $q->client?->name ?? 'Unknown',
                    'type' => $q->type,
                    'status' => $q->status,
                    'retry_count' => $q->retry_count,
                    'created_at' => $q->created_at->toISOString(),
                    'synced_at' => $q->synced_at?->toISOString(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'kpis' => [
                    'total_clients' => $allClients->count(),
                    'online_clients' => $onlineClients->count(),
                    'pending_queues' => $pendingCount,
                    'synced_today' => $syncedTodayCount,
                    'failed_queues' => $failedCount,
                ],
                'clients' => $allClients->map(function ($c) {
                    return [
                        'id' => $c->id,
                        'name' => $c->name,
                        'slug' => $c->slug,
                        'scope' => $c->scope,
                        'is_online' => $c->is_online,
                        'status' => $c->status,
                        'last_seen_at' => $c->last_seen_at?->diffForHumans() ?? 'Belum pernah',
                        'machine_name' => $c->machine_name,
                        'ip_address' => $c->ip_address,
                    ];
                }),
                'recent_queues' => $recentQueues,
            ],
        ]);
    }
}
