<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ProjectClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_prima_can_fetch_its_own_allocated_printers_with_project_key(): void
    {
        // 1. Create Project 1 (CSSD) with 2 printers
        $projectCssd = ProjectClient::create([
            'name' => 'RS Permata Hati - Prima CSSD',
            'code' => 'RSPH-CSSD',
            'api_key' => 'proj_cssd_key_111',
            'max_printers' => 4,
            'status' => 'active',
        ]);

        $printer1 = Client::create([
            'project_client_id' => $projectCssd->id,
            'name' => 'CSSD - Packing',
            'slug' => 'cssd-packing',
            'api_key' => 'ps_cssd_packing',
            'scope' => 'printer_service',
            'status' => 'active',
        ]);

        $printer2 = Client::create([
            'project_client_id' => $projectCssd->id,
            'name' => 'CSSD - Autoclave',
            'slug' => 'cssd-autoclave',
            'api_key' => 'ps_cssd_autoclave',
            'scope' => 'printer_service',
            'status' => 'active',
        ]);

        // 2. Create Project 2 (Inventaris) with 1 printer
        $projectInv = ProjectClient::create([
            'name' => 'RS Permata Hati - Prima Inventaris',
            'code' => 'RSPH-INV',
            'api_key' => 'proj_inv_key_222',
            'max_printers' => 2,
            'status' => 'active',
        ]);

        $printer3 = Client::create([
            'project_client_id' => $projectInv->id,
            'name' => 'Inventaris - Gudang',
            'slug' => 'inv-gudang',
            'api_key' => 'ps_inv_gudang',
            'scope' => 'printer_service',
            'status' => 'active',
        ]);

        // 3. Request printers using CSSD Project Key
        $response = $this->withHeaders([
            'X-Project-Key' => 'proj_cssd_key_111',
        ])->getJson('/api/v1/project/printers');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('project.code', 'RSPH-CSSD');
        $response->assertJsonCount(2, 'data');

        // Verify that Inventaris printer is NOT present
        $printerNames = collect($response->json('data'))->pluck('name');
        $this->assertTrue($printerNames->contains('CSSD - Packing'));
        $this->assertTrue($printerNames->contains('CSSD - Autoclave'));
        $this->assertFalse($printerNames->contains('Inventaris - Gudang'));
    }

    public function test_dispatch_with_project_key_rejects_cross_project_printer(): void
    {
        $projectCssd = ProjectClient::create([
            'name' => 'RS Permata Hati - CSSD',
            'code' => 'RSPH-CSSD',
            'api_key' => 'proj_cssd_key_111',
            'max_printers' => 4,
            'status' => 'active',
        ]);

        $projectInv = ProjectClient::create([
            'name' => 'RS Permata Hati - Inventaris',
            'code' => 'RSPH-INV',
            'api_key' => 'proj_inv_key_222',
            'max_printers' => 2,
            'status' => 'active',
        ]);

        $invPrinter = Client::create([
            'project_client_id' => $projectInv->id,
            'name' => 'Inventaris - Gudang',
            'slug' => 'inv-gudang',
            'api_key' => 'ps_inv_gudang',
            'scope' => 'printer_service',
            'status' => 'active',
        ]);

        // CSSD tries to dispatch to Inventaris printer -> Rejected
        $response = $this->withHeaders([
            'X-Project-Key' => 'proj_cssd_key_111',
        ])->postJson('/api/v1/relay/dispatch', [
            'target_client_id' => $invPrinter->id,
            'type' => 'print_label',
            'payload' => ['jobs' => [['category' => 'Label Steril']]],
        ]);

        $response->assertStatus(404); // Not found within project scope
    }

    public function test_project_quota_enforcement_when_assigning_clients(): void
    {
        $project = ProjectClient::create([
            'name' => 'Klinik Kecil',
            'code' => 'KLINIK-01',
            'api_key' => 'proj_small_123',
            'max_printers' => 1, // Only 1 printer allowed
            'status' => 'active',
        ]);

        $printer1 = Client::create([
            'name' => 'Printer 1',
            'slug' => 'p-1',
            'api_key' => 'ps_1',
            'status' => 'active',
        ]);

        $printer2 = Client::create([
            'name' => 'Printer 2',
            'slug' => 'p-2',
            'api_key' => 'ps_2',
            'status' => 'active',
        ]);

        // Assign 1st printer -> Allowed
        $res1 = $this->postJson("/api/v1/admin/projects/{$project->id}/assign-client", [
            'client_id' => $printer1->id,
        ]);
        $res1->assertStatus(200);

        // Assign 2nd printer -> Exceeds quota (422)
        $res2 = $this->postJson("/api/v1/admin/projects/{$project->id}/assign-client", [
            'client_id' => $printer2->id,
        ]);
        $res2->assertStatus(422);
        $res2->assertJsonPath('success', false);
    }

    public function test_can_remove_client_from_project(): void
    {
        $project = ProjectClient::create([
            'name' => 'RS Test',
            'code' => 'RS-TEST',
            'api_key' => 'proj_test_key',
            'max_printers' => 2,
            'status' => 'active',
        ]);

        $printer = Client::create([
            'project_client_id' => $project->id,
            'name' => 'Printer Lab',
            'slug' => 'printer-lab',
            'api_key' => 'ps_lab',
            'status' => 'active',
        ]);

        $this->assertEquals($project->id, $printer->fresh()->project_client_id);

        $res = $this->postJson("/api/v1/admin/projects/{$project->id}/remove-client", [
            'client_id' => $printer->id,
        ]);

        $res->assertStatus(200);
        $this->assertNull($printer->fresh()->project_client_id);
    }

    public function test_legacy_dispatch_respects_project_isolation(): void
    {
        $projectA = ProjectClient::create([
            'name' => 'Project A',
            'code' => 'PROJ-A',
            'api_key' => 'proj_key_aaa',
            'max_printers' => 2,
            'status' => 'active',
        ]);

        $projectB = ProjectClient::create([
            'name' => 'Project B',
            'code' => 'PROJ-B',
            'api_key' => 'proj_key_bbb',
            'max_printers' => 2,
            'status' => 'active',
        ]);

        $printerA = Client::create([
            'project_client_id' => $projectA->id,
            'name' => 'Printer A Only',
            'slug' => 'printer-a',
            'api_key' => 'ps_a',
            'status' => 'active',
        ]);

        $printerB = Client::create([
            'project_client_id' => $projectB->id,
            'name' => 'Printer B Only',
            'slug' => 'printer-b',
            'api_key' => 'ps_b',
            'status' => 'active',
        ]);

        // Dispatch via legacy endpoint with Project A key
        $res = $this->withHeaders([
            'X-Project-Key' => 'proj_key_aaa',
        ])->postJson('/api/print-service/dispatch', [
            'jobs' => [
                ['printer_name' => 'Printer A Only', 'payload' => 'job-1'],
            ],
        ]);

        $res->assertStatus(200);
        $res->assertJsonPath('data.dispatched', 1);

        // Try dispatching to Printer B with Project A key -> fails (not found in project A)
        $resFail = $this->withHeaders([
            'X-Project-Key' => 'proj_key_aaa',
        ])->postJson('/api/print-service/dispatch', [
            'jobs' => [
                ['service_name' => 'Printer B Only', 'payload' => 'job-2'],
            ],
        ]);

        $resFail->assertStatus(200);
        $resFail->assertJsonPath('data.dispatched', 0);
        $resFail->assertJsonPath('data.failed', 1);
    }
}
