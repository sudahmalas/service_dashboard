<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('public_item_snapshots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_client_id')
                ->nullable()
                ->constrained('project_clients')
                ->nullOnDelete();
            $table->string('identifier')->index(); // e.g. "CSSD-2026-0012", "ASSET-098", "SN-88123"
            $table->string('category')->default('asset')->index(); // 'cssd', 'asset', 'serial_number'
            $table->string('title'); // e.g. "SET BEDAH MINOR 01", "AUTOCLAVE STEAM 50L"
            $table->string('subtitle')->nullable(); // e.g. "CSSD Sterilisasi Medis", "Alat Elektromedis"
            $table->string('code')->nullable(); // item code or barcode string
            $table->string('location')->nullable(); // e.g. "Ruang Operasi 1 (OK)", "Gedung A Lt 2"
            $table->string('status_label')->default('SIAP PAKAI'); // "STERIL", "KADALUARSA", "SIAP PAKAI", "MAINTENANCE"
            $table->string('status_color')->default('emerald'); // "emerald", "amber", "rose", "cyan"
            $table->json('meta_data')->nullable(); // Specific metadata: expired_at, sterilized_at, operator, sop_checklist, etc.
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['project_client_id', 'identifier']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('public_item_snapshots');
    }
};
