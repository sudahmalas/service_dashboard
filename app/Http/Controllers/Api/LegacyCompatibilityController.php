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
use Illuminate\Support\Str;

class LegacyCompatibilityController extends Controller
{
    /**
     * Provide connection info for printer_service (matching managementStore.ts).
     * POST /api/print-service/connect-info
     */
    public function connectInfo(Request $request): JsonResponse
    {
        $apiKey = $request->input('api_key') ?: $request->header('X-Client-Key') ?: $request->bearerToken();

        if (!$apiKey) {
            return response()->json(['success' => false, 'message' => 'API Key wajib diisi'], 400);
        }

        $client = Client::where('api_key', $apiKey)->first();

        if (!$client) {
            return response()->json(['success' => false, 'message' => 'API Key tidak valid atau client belum terdaftar'], 401);
        }

        $client->update([
            'ip_address' => $request->ip(),
            'last_seen_at' => now(),
            'status' => 'active',
        ]);

        $channelName = 'printer.' . $client->slug;
        $reverbKey = env('REVERB_APP_KEY', 'za2zx1fb5ugbw2kcdtyb');
        $reverbHost = env('REVERB_HOST', '127.0.0.1');
        $reverbPort = (int) env('REVERB_PORT', 8090);
        $reverbScheme = env('REVERB_SCHEME', 'http');

        broadcast(new DashboardActivityEvent(
            type: 'printer_service',
            clientName: $client->name,
            status: 'online',
            message: "Client '{$client->name}' terhubung ke WebSocket Reverb via connect-info"
        ));

        return response()->json([
            'success' => true,
            'data' => [
                'service_id' => $client->id,
                'service_name' => $client->name,
                'channel' => $channelName,
                'pusher' => [
                    'key' => $reverbKey,
                    'host' => $reverbHost,
                    'cluster' => 'mt1',
                    'scheme' => $reverbScheme,
                    'port' => $reverbPort,
                    'encrypted' => $reverbScheme === 'https',
                ],
                'reverb' => [
                    'key' => $reverbKey,
                    'host' => $reverbHost,
                    'port' => $reverbPort,
                    'scheme' => $reverbScheme,
                ],
            ],
        ]);
    }

    /**
     * Register client details from printer_service (matching managementStore.ts).
     * POST /api/print-service/register
     */
    public function register(Request $request): JsonResponse
    {
        $apiKey = $request->input('api_key') ?: $request->header('X-Client-Key');

        if (!$apiKey) {
            return response()->json(['success' => false, 'message' => 'API Key wajib diisi'], 400);
        }

        $client = Client::where('api_key', $apiKey)->first();

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Registrasi gagal: API Key tidak valid atau belum terdaftar di WebHost.',
            ], 401);
        }

        $client->update([
            'machine_name' => $request->input('machine_name', $client->machine_name),
            'ip_address' => $request->input('ip_address', $request->ip()),
            'mac_address' => $request->input('mac_address', $client->mac_address),
            'printers' => $request->input('printers', $client->printers),
            'last_seen_at' => now(),
            'status' => 'active',
        ]);

        broadcast(new DashboardActivityEvent(
            type: 'printer_service',
            clientName: $client->name,
            status: 'registered',
            message: "Sinkronisasi profil & printer OS berhasil untuk '{$client->name}'"
        ));

        return response()->json([
            'success' => true,
            'message' => 'Printer service berhasil diregistrasi dan sinkron dengan WebHost.',
            'data' => [
                'id' => $client->id,
                'service_name' => $client->name,
                'slug' => $client->slug,
                'status' => $client->status,
                'printers' => $client->printers,
            ],
        ]);
    }

    /**
     * Unregister client from printer_service.
     * POST /api/print-service/unregister
     */
    public function unregister(Request $request): JsonResponse
    {
        $apiKey = $request->input('api_key') ?: $request->header('X-Client-Key');

        if ($apiKey) {
            $client = Client::where('api_key', $apiKey)->first();
            if ($client) {
                $client->update(['status' => 'inactive']);
                return response()->json(['success' => true, 'message' => 'Printer service dinonaktifkan dari WebHost']);
            }
        }

        return response()->json(['success' => false, 'message' => 'Service tidak ditemukan'], 404);
    }

    /**
     * Dispatch print jobs from Prima (matching Prima Plan B dispatch).
     * POST /api/print-service/dispatch
     */
    public function dispatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'jobs' => 'required|array',
        ]);

        $dispatchedCount = 0;
        $failedJobs = [];

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

        // 2. Group jobs by target client / machine (Restricted to project if authenticated)
        $clients = $project ? $project->printers()->active()->get() : Client::active()->get();

        if ($clients->isEmpty()) {
            $msg = $project
                ? "Tidak ada Print Service yang aktif atau dialokasikan untuk project '{$project->name}'."
                : 'Tidak ada Print Service yang terdaftar atau aktif di WebHost.';
            return response()->json([
                'success' => false,
                'message' => $msg,
            ], 422);
        }

        $clientJobs = [];

        foreach ($validated['jobs'] as $job) {
            $serviceName = $job['service_name'] ?? null;
            $printerName = $job['printer_name'] ?? null;
            $macAddress = $job['mac_address'] ?? null;
            $category = $job['category'] ?? null;

            $foundClient = null;

            // Jika printer_name mengandung format 'service_name|printer_name', pisahkan
            if ($printerName && str_contains($printerName, '|')) {
                $parts = explode('|', $printerName, 2);
                if (!$serviceName || $serviceName === 'Local' || $serviceName === 'Network') {
                    $serviceName = trim($parts[0]);
                }
                $printerName = trim($parts[1]);
            }

            // 1. By service_name
            if ($serviceName && $serviceName !== 'Local' && $serviceName !== 'Network') {
                $foundClient = $clients->first(function ($c) use ($serviceName) {
                    return strcasecmp(trim($c->name), trim($serviceName)) === 0
                        || strcasecmp(trim($c->slug), trim(str_replace(' ', '-', $serviceName))) === 0;
                });
            }

            // 1.5. Direct match if printerName matches client name or slug
            if (!$foundClient && $printerName) {
                $foundClient = $clients->first(function ($c) use ($printerName) {
                    return strcasecmp(trim($c->name), trim($printerName)) === 0
                        || strcasecmp(trim($c->slug), trim(str_replace(' ', '-', $printerName))) === 0;
                });
            }

            // 2. By MAC Address
            if (!$foundClient && $macAddress) {
                $foundClient = $clients->first(function ($c) use ($macAddress) {
                    return strcasecmp(trim($c->mac_address ?? ''), trim($macAddress)) === 0;
                });
            }

            // 3. By Printer Target Labels / OS Printer Name
            if (!$foundClient) {
                foreach ($clients as $client) {
                    if (is_array($client->printers)) {
                        foreach ($client->printers as $p) {
                            $pName = is_array($p) ? ($p['printer_name'] ?? $p['os_printer_name'] ?? '') : '';
                            $targetLabels = is_array($p) ? ($p['target_labels'] ?? []) : [];

                            if ($printerName && strcasecmp(trim($pName), trim($printerName)) === 0) {
                                $foundClient = $client;
                                break 2;
                            }
                            if ($category && is_array($targetLabels) && in_array($category, $targetLabels)) {
                                $foundClient = $client;
                                break 2;
                            }
                        }
                    }
                }
            }

            // 4. Default to first active client only if no explicit target service/printer/mac was specified
            $hasExplicitTarget = (!empty($serviceName) && !in_array($serviceName, ['Local', 'Network']))
                || !empty($printerName)
                || !empty($macAddress);

            if (!$foundClient && !$hasExplicitTarget && $clients->count() === 1) {
                $foundClient = $clients->first();
            }

            if ($foundClient) {
                $clientId = $foundClient->id;
                if (!isset($clientJobs[$clientId])) {
                    $clientJobs[$clientId] = [
                        'client' => $foundClient,
                        'jobs' => [],
                    ];
                }
                $clientJobs[$clientId]['jobs'][] = $job;
                $dispatchedCount++;
            } else {
                $failedJobs[] = $job;
            }
        }

        // Store and dispatch to each matched client
        foreach ($clientJobs as $data) {
            /** @var Client $client */
            $client = $data['client'];
            $jobs = $data['jobs'];

            // Save to buffer queue
            $message = MessageQueue::create([
                'client_id' => $client->id,
                'type' => 'print_label',
                'payload' => ['jobs' => $jobs],
                'status' => 'pending',
            ]);

            // Broadcast via Reverb
            broadcast(new DispatchedClientPayloadEvent($client, $message));

            $projectNameNotice = $project ? " [{$project->name}]" : '';

            broadcast(new DashboardActivityEvent(
                type: 'print_label',
                clientName: $client->name,
                status: 'dispatched',
                message: "Dispatch " . count($jobs) . " dokumen cetak ke {$client->name}{$projectNameNotice}"
            ));
        }

        return response()->json([
            'success' => true,
            'message' => 'Print jobs telah di-queue dan di-relay via Reverb',
            'data' => [
                'dispatched' => $dispatchedCount,
                'failed' => count($failedJobs),
                'failed_jobs' => $failedJobs,
            ],
        ]);
    }
}
