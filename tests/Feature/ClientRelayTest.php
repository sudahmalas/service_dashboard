<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\MessageQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientRelayTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_dispatch_job_to_client_and_store_in_queue(): void
    {
        $client = Client::create([
            'name' => 'Printer Loket 1',
            'slug' => 'loket-1',
            'api_key' => 'ps_test_key_123',
            'scope' => 'printer_service',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/relay/dispatch', [
            'target_client_id' => $client->id,
            'type' => 'print_label',
            'payload' => [
                'jobs' => [
                    ['category' => 'Gelang Pasien', 'qty' => 1]
                ],
            ],
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.client_slug', 'loket-1');

        $this->assertDatabaseHas('message_queues', [
            'client_id' => $client->id,
            'type' => 'print_label',
            'status' => 'pending',
        ]);
    }

    public function test_client_can_sync_pending_jobs_with_api_key(): void
    {
        $client = Client::create([
            'name' => 'Printer Loket 1',
            'slug' => 'loket-1',
            'api_key' => 'ps_test_key_sync_456',
            'scope' => 'printer_service',
            'status' => 'active',
        ]);

        MessageQueue::create([
            'client_id' => $client->id,
            'type' => 'print_label',
            'payload' => ['jobs' => [['category' => 'Gelang Pasien']]],
            'status' => 'pending',
        ]);

        $response = $this->withHeaders([
            'X-Client-Key' => 'ps_test_key_sync_456',
        ])->getJson('/api/v1/client/sync');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('count', 1);
    }

    public function test_client_can_ack_job(): void
    {
        $client = Client::create([
            'name' => 'Printer Loket 1',
            'slug' => 'loket-1',
            'api_key' => 'ps_test_key_ack_789',
            'scope' => 'printer_service',
            'status' => 'active',
        ]);

        $message = MessageQueue::create([
            'client_id' => $client->id,
            'type' => 'print_label',
            'payload' => ['jobs' => [['category' => 'Gelang Pasien']]],
            'status' => 'pending',
        ]);

        $response = $this->withHeaders([
            'X-Client-Key' => 'ps_test_key_ack_789',
        ])->postJson('/api/v1/client/ack', [
            'message_id' => $message->id,
            'status' => 'synced',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $this->assertDatabaseHas('message_queues', [
            'id' => $message->id,
            'status' => 'synced',
        ]);
    }

    public function test_legacy_connect_info_endpoint_returns_reverb_config(): void
    {
        $client = Client::create([
            'name' => 'Loket Pendaftaran 1',
            'slug' => 'Loket-Pendaftaran-1',
            'api_key' => 'ps_legacy_connect_key',
            'scope' => 'printer_service',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/print-service/connect-info', [
            'api_key' => 'ps_legacy_connect_key',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.channel', 'printer.Loket-Pendaftaran-1');
        $response->assertJsonPath('data.reverb.port', (int) env('REVERB_PORT', 8090));
    }

    public function test_legacy_dispatch_from_prima_queues_jobs(): void
    {
        $client = Client::create([
            'name' => 'Loket Pendaftaran 1',
            'slug' => 'Loket-Pendaftaran-1',
            'api_key' => 'ps_legacy_prima_key',
            'scope' => 'printer_service',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/print-service/dispatch', [
            'jobs' => [
                [
                    'service_name' => 'Loket Pendaftaran 1',
                    'category' => 'Gelang Pasien',
                    'printer_name' => 'Zebra ZD230',
                    'qty' => 1,
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.dispatched', 1);

        $this->assertDatabaseHas('message_queues', [
            'client_id' => $client->id,
            'type' => 'print_label',
            'status' => 'pending',
        ]);
    }

    public function test_client_heartbeat_updates_last_seen(): void
    {
        $client = Client::create([
            'name' => 'Printer Loket 2',
            'slug' => 'loket-2',
            'api_key' => 'ps_heartbeat_test_key',
            'scope' => 'printer_service',
            'status' => 'active',
            'last_seen_at' => now()->subMinutes(10),
        ]);

        $response = $this->withHeaders([
            'X-Client-Key' => 'ps_heartbeat_test_key',
        ])->postJson('/api/v1/client/heartbeat');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('is_online', true);

        $client->refresh();
        $this->assertTrue($client->is_online);
    }

    public function test_admin_stats_and_client_crud(): void
    {
        // 1. Create client via Admin API
        $createRes = $this->postJson('/api/v1/admin/clients', [
            'name' => 'Printer Loket VIP',
            'scope' => 'printer_service',
        ]);

        $createRes->assertStatus(201);
        $createRes->assertJsonPath('success', true);
        $clientId = $createRes->json('data.id');

        // 2. Regenerate Key
        $regenRes = $this->postJson("/api/v1/admin/clients/{$clientId}/regenerate-key");
        $regenRes->assertStatus(200);
        $this->assertNotNull($regenRes->json('data.api_key'));

        // 3. Admin stats
        $statsRes = $this->getJson('/api/v1/admin/stats');
        $statsRes->assertStatus(200);
        $statsRes->assertJsonPath('success', true);
    }
}
