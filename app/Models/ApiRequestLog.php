<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class ApiRequestLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'api_client_id',
        'endpoint',
        'method',
        'ip_address',
        'request_payload',
        'response_code',
        'response_summary',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_summary' => 'array',
    ];

    public function apiClient(): BelongsTo
    {
        return $this->belongsTo(ApiClient::class);
    }

    public function getCreatedAtAttribute($value): string
    {
        return Carbon::parse($value)
            ->timezone(config('app.timezone'))
            ->format('Y-m-d H:i:s');
    }
}
