<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicItemSnapshot extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'project_client_id',
        'identifier',
        'category',
        'title',
        'subtitle',
        'code',
        'location',
        'status_label',
        'status_color',
        'meta_data',
        'is_active',
    ];

    protected $casts = [
        'meta_data' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Relationship: snapshot belongs to a ProjectClient (tenant).
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(ProjectClient::class, 'project_client_id');
    }
}
