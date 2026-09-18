<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageQueue extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'project_client_id',
        'client_id',
        'type',
        'payload',
        'status',
        'retry_count',
        'error_message',
        'synced_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'synced_at' => 'datetime',
        'retry_count' => 'integer',
    ];

    /**
     * Relationship: message belongs to an optional project tenant.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(ProjectClient::class, 'project_client_id');
    }

    /**
     * Relationship: message belongs to an optional hardware client.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Scope for pending messages.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for synced messages.
     */
    public function scopeSynced($query)
    {
        return $query->where('status', 'synced');
    }

    /**
     * Scope for failed messages.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Mark message as synced.
     */
    public function markAsSynced(): void
    {
        $this->update([
            'status' => 'synced',
            'synced_at' => now(),
            'error_message' => null,
        ]);
    }

    /**
     * Mark message as failed.
     */
    public function markAsFailed(?string $errorMessage = null): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'retry_count' => $this->retry_count + 1,
        ]);
    }
}
