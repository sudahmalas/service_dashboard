<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Client extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'project_client_id',
        'name',
        'slug',
        'api_key',
        'scope',
        'machine_name',
        'ip_address',
        'mac_address',
        'printers',
        'status',
        'last_seen_at',
    ];

    protected $casts = [
        'printers' => 'array',
        'last_seen_at' => 'datetime',
    ];

    protected $appends = [
        'is_online',
    ];

    /**
     * Relationship: client belongs to a project client (tenant).
     */
    public function project()
    {
        return $this->belongsTo(ProjectClient::class, 'project_client_id');
    }

    public function projectClient()
    {
        return $this->project();
    }

    /**
     * Relationship: client has many message queues.
     */
    public function messageQueues(): HasMany
    {
        return $this->hasMany(MessageQueue::class);
    }

    /**
     * Check if client was seen in the last 2 minutes.
     */
    public function getIsOnlineAttribute(): bool
    {
        if (!$this->last_seen_at) {
            return false;
        }

        return $this->last_seen_at->greaterThanOrEqualTo(now()->subMinutes(2));
    }

    /**
     * Scope for active clients.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Generate new secure API key with prefix ps_ (compatible with Prima & PrinterService).
     */
    public static function generateKey(): string
    {
        return 'ps_' . bin2hex(random_bytes(20));
    }

    /**
     * Touch last seen timestamp.
     */
    public function touchLastSeen(): void
    {
        $this->update(['last_seen_at' => now()]);
    }
}
