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
        Schema::table('message_queues', function (Blueprint $table) {
            $table->foreignUuid('project_client_id')
                ->nullable()
                ->after('id')
                ->constrained('project_clients')
                ->nullOnDelete();

            $table->foreignUuid('client_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('message_queues', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_client_id');
        });
    }
};
