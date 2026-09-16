<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\ProjectClient;
use Illuminate\Database\Seeder;

class ProjectClientSeeder extends Seeder
{
    /**
     * Seed project clients and link existing printer clients.
     */
    public function run(): void
    {
        // 1. Project 1: RS Permata Hati - Prima CSSD
        $cssdProject = ProjectClient::firstOrCreate(
            ['code' => 'RSPH-CSSD'],
            [
                'name' => 'RS Permata Hati - Prima CSSD',
                'api_key' => 'proj_rsph_cssd_testkey1234567890',
                'max_printers' => 4,
                'status' => 'active',
                'description' => 'Aplikasi Sterilisasi Medis CSSD (4 Printer Lokasi Berbeda: Dekontaminasi, Packing, Autoclave, Distribusi)',
            ]
        );

        // 2. Project 2: RS Permata Hati - Prima Inventaris
        $invProject = ProjectClient::firstOrCreate(
            ['code' => 'RSPH-INV'],
            [
                'name' => 'RS Permata Hati - Prima Inventaris',
                'api_key' => 'proj_rsph_inv_testkey1234567890',
                'max_printers' => 2,
                'status' => 'active',
                'description' => 'Aplikasi Manajemen Aset & Inventaris (2 Printer: Gudang & Meja Admin)',
            ]
        );

        // Link existing client "aplikasi 1" (pc-msi) if exists to CSSD project
        $app1 = Client::where('slug', 'pc-msi')->first();
        if ($app1 && !$app1->project_client_id) {
            $app1->update(['project_client_id' => $cssdProject->id]);
        }

        // Link default Loket Pendaftaran 1 to CSSD or Inventaris
        $loket1 = Client::where('slug', 'Loket-Pendaftaran-1')->first();
        if ($loket1 && !$loket1->project_client_id) {
            $loket1->update(['project_client_id' => $invProject->id]);
        }
    }
}
