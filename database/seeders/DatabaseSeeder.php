<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\MessageQueue;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed default Admin User
        User::firstOrCreate(
            ['email' => 'admin@idgrow.test'],
            [
                'name' => 'Administrator',
                'password' => bcrypt('password'),
            ]
        );

        // 2. Seed Default Printer Service Client (matching printer_service defaults)
        $printerClient = Client::firstOrCreate(
            ['slug' => 'Loket-Pendaftaran-1'],
            [
                'name' => 'Loket Pendaftaran 1',
                'slug' => 'Loket-Pendaftaran-1',
                'api_key' => 'ps_counter01_testkey99887766554433221100',
                'scope' => 'printer_service',
                'machine_name' => 'PC-LOKET-01',
                'ip_address' => '127.0.0.1',
                'mac_address' => '00:1B:44:11:3A:B7',
                'status' => 'active',
                'last_seen_at' => now(),
                'printers' => [
                    [
                        'printer_name' => 'Zebra ZD230',
                        'target_labels' => ['Gelang Pasien', 'Etiket Obat', 'cetak_serial'],
                    ],
                    [
                        'printer_name' => 'EPSON L3110',
                        'target_labels' => ['Surat Rujukan', 'cetak_asset'],
                    ],
                ],
            ]
        );

        // 3. Seed Default Prima Client (Input Application)
        $primaClient = Client::firstOrCreate(
            ['slug' => 'prima-pos-kasir-01'],
            [
                'name' => 'Prima POS Kasir 01',
                'slug' => 'prima-pos-kasir-01',
                'api_key' => 'ps_prima01_testkey11223344556677889900',
                'scope' => 'prima',
                'machine_name' => 'PC-KASIR-01',
                'ip_address' => '127.0.0.1',
                'status' => 'active',
                'last_seen_at' => now(),
            ]
        );

        // 4. Seed a sample pending message queue
        MessageQueue::firstOrCreate(
            ['client_id' => $printerClient->id, 'status' => 'pending'],
            [
                'type' => 'print_label',
                'payload' => [
                    'jobs' => [
                        [
                            'category' => 'Gelang Pasien',
                            'printer_name' => 'Zebra ZD230',
                            'lebar_mm' => 50,
                            'tinggi_mm' => 25,
                            'qty' => 1,
                            'url' => 'data:text/html;charset=utf-8,%3Ch1%3EAntrian%20A-001%3C%2Fh1%3E',
                        ],
                    ],
                ],
                'status' => 'pending',
                'retry_count' => 0,
            ]
        );
    }
}
