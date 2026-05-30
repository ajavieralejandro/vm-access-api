<?php

namespace App\Models;

use App\Enums\AccessPassStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccessPass extends Model
{
    protected $fillable = [
        'access_zone_id',
        'code',
        'vmserver_user_id',
        'dni',
        'holder_name',
        'source_service',
        'source_type',
        'source_reference',
        'status',
        'valid_from',
        'valid_until',
        'used_at',
        'revoked_at',
        'metadata',
    ];

    protected $casts = [
        'status'     => AccessPassStatus::class,
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'used_at'    => 'datetime',
        'revoked_at' => 'datetime',
        'metadata'   => 'array',
    ];

    public function accessZone(): BelongsTo
    {
        return $this->belongsTo(AccessZone::class);
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(AccessLog::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', AccessPassStatus::Active->value);
    }

    public function scopeForCode(Builder $query, string $code): Builder
    {
        return $query->where('code', $code);
    }
}
