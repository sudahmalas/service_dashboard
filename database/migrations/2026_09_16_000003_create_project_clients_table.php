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
        Schema::create('project_clients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name'); // e.g. "RS Permata Hati - Prima CSSD"
            $table->string('code')->unique()->index(); // e.g. "RSPH-CSSD"
            $table->string('api_key')->unique()->index(); // e.g. "proj_..."
            $table->unsignedInteger('max_printers')->default(5); // printer quota limit
            $table->enum('status', ['active', 'inactive'])->default('active')->index();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->foreignUuid('project_client_id')
                ->nullable()
                ->after('id')
                ->constrained('project_clients')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_client_id');
        });

        Schema::dropIfExists('project_clients');
    }
};
