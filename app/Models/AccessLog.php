<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessLog extends Model
{
    protected $fillable = [
        'access_pass_id',
        'access_zone_id',
        'vmserver_user_id',
        'dni',
        'direction',
        'allowed',
        'reason',
        'scanner_device_id',
        'scanned_by',
        'request_payload',
        'decision_payload',
        'scanned_at',
    ];

    protected $casts = [
        'allowed'          => 'boolean',
        'request_payload'  => 'array',
        'decision_payload' => 'array',
        'scanned_at'       => 'datetime',
    ];

    public function accessPass(): BelongsTo
    {
        return $this->belongsTo(AccessPass::class);
    }

    public function accessZone(): BelongsTo
    {
        return $this->belongsTo(AccessZone::class);
    }
}
