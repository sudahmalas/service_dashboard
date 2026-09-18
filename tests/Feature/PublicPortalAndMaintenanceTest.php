<?php

namespace Tests\Feature;

use App\Models\MessageQueue;
use App\Models\ProjectClient;
use App\Models\PublicItemSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPortalAndMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_public_portal_and_maintenance_store_and_forward_flow(): void
    {
        // 1. Create a Project Tenant in WebHost (e.g. Prima Inventaris)
        $project = ProjectClient::create([
            'name' => 'RS Permata Hati - Prima Inventaris',
            'code' => 'RSPH-INV',
            'api_key' => 'proj_rsph_secret_123',
            'max_printers' => 5,
            'status' => 'active',
        ]);

        // 2. Prima pushes public item snapshots via POST /api/v1/project/items/sync
        $syncPayload = [
            'items' => [
                [
                    'identifier' => 'SUCTION-01',
                    'category' => 'asset',
                    'title' => 'Suction Pump Medis YX-930D',
                    'subtitle' => 'Alat Elektromedis Bedah',
                    'code' => 'ASSET-2026-0091',
                    'location' => 'Ruang ICU Lt. 2',
                    'status_label' => 'SIAP PAKAI',
                    'status_color' => 'emerald',
                    'meta_data' => [
                        'serial_number' => 'SN-SP-998231',
                        'brand' => 'GE Healthcare',
                        'model' => 'YX-930D',
                    ],
                ],
                [
                    'identifier' => 'CSSD-SET-01',
                    'category' => 'cssd',
                    'title' => 'Set Bedah Minor Standard A',
                    'subtitle' => 'Paket Instrumen Steril',
                    'code' => 'CSSD-2026-0084',
                    'location' => 'Depo Steril CSSD',
                    'status_label' => 'STERIL',
                    'status_color' => 'emerald',
                    'meta_data' => [
                        'sterilized_at' => '2026-09-15 08:30:00',
                        'expired_at' => '2026-09-29 08:30:00',
                        'operator_name' => 'Siti Rahma',
                        'method' => 'Steam Autoclave 134°C',
                    ],
                ],
            ],
        ];

        $syncRes = $this->withHeaders([
            'X-Project-Key' => 'proj_rsph_secret_123',
        ])->postJson('/api/v1/project/items/sync', $syncPayload);

        $syncRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'count' => 2,
            ]);

        $this->assertDatabaseHas('public_item_snapshots', [
            'identifier' => 'SUCTION-01',
            'project_client_id' => $project->id,
            'title' => 'Suction Pump Medis YX-930D',
        ]);

        $this->assertDatabaseHas('public_item_snapshots', [
            'identifier' => 'CSSD-SET-01',
            'project_client_id' => $project->id,
            'category' => 'cssd',
        ]);

        // 3. Smartphone scans QR and loads public item data via GET /api/v1/public/item/{identifier}
        $publicRes = $this->getJson('/api/v1/public/item/SUCTION-01');
        $publicRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'identifier' => 'SUCTION-01',
                    'title' => 'Suction Pump Medis YX-930D',
                    'status_label' => 'SIAP PAKAI',
                ],
            ]);

        // 4. Technician submits maintenance report via POST /api/v1/public/maintenance/submit
        $maintenancePayload = [
            'identifier' => 'SUCTION-01',
            'performer_name' => 'Budi Santoso (IPSRS)',
            'job_type' => 'perbaikan',
            'operational_status' => 'bisa_digunakan',
            'problem' => 'Kabel suction longgar dan filter berdebu.',
            'resolution' => 'Kencangkan konektor dan bersihkan filter HEPA.',
            'checklist' => ['Cek fisik kabel', 'Uji daya hisap'],
            'notes' => 'Alat telah diuji coba 15 menit, berjalan lancar.',
        ];

        $submitRes = $this->postJson('/api/v1/public/maintenance/submit', $maintenancePayload);
        $submitRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'pending',
                ],
            ]);

        $queueId = $submitRes->json('data.queue_id');
        $this->assertNotNull($queueId);

        // Verify message queue created
        $this->assertDatabaseHas('message_queues', [
            'id' => $queueId,
            'project_client_id' => $project->id,
            'type' => 'form_submission',
            'status' => 'pending',
        ]);

        // 5. Prima on local PC fetches pending form submissions via GET /api/v1/project/queues/form-submissions
        $fetchRes = $this->withHeaders([
            'X-Project-Key' => 'proj_rsph_secret_123',
        ])->getJson('/api/v1/project/queues/form-submissions');

        $fetchRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'count' => 1,
            ]);

        $this->assertEquals($queueId, $fetchRes->json('data.0.id'));
        $this->assertEquals('Budi Santoso (IPSRS)', $fetchRes->json('data.0.payload.performer_name'));

        // 6. Prima processes locally and sends ACK via POST /api/v1/project/queues/{id}/ack
        $ackRes = $this->withHeaders([
            'X-Project-Key' => 'proj_rsph_secret_123',
        ])->postJson("/api/v1/project/queues/{$queueId}/ack");

        $ackRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'synced',
                ],
            ]);

        $this->assertDatabaseHas('message_queues', [
            'id' => $queueId,
            'status' => 'synced',
        ]);
    }
}
