<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccessZone extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata'  => 'array',
    ];

    public function accessPasses(): HasMany
    {
        return $this->hasMany(AccessPass::class);
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(AccessLog::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
