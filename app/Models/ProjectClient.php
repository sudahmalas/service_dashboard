<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectClient extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'code',
        'api_key',
        'max_printers',
        'status',
        'description',
    ];

    protected $casts = [
        'max_printers' => 'integer',
    ];

    /**
     * Relationship: project client has many assigned printer clients.
     */
    public function clients(): HasMany
    {
        return $this->hasMany(Client::class, 'project_client_id');
    }

    /**
     * Helper alias for printer clients.
     */
    public function printers(): HasMany
    {
        return $this->hasMany(Client::class, 'project_client_id');
    }

    /**
     * Generate secure Project API Key with prefix proj_
     */
    public static function generateKey(): string
    {
        return 'proj_' . bin2hex(random_bytes(20));
    }

    /**
     * Scope for active projects.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Check if project has printer quota available.
     */
    public function hasQuotaAvailable(): bool
    {
        return $this->printers()->count() < $this->max_printers;
    }
}
