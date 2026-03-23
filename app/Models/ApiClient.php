<?php

namespace App\Models;

use App\Models\Concerns\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ApiClient extends Model
{
    use HasFactory, SoftDeletes, HasAuditTrail;

    protected $fillable = [
        'name',
        'client_key',
        'client_secret_hash',
        'department_id',
        'status',
        'allowed_ips',
        'meta',
        'last_used_at',
        'created_by',
    ];

    protected $casts = [
        'allowed_ips' => 'array',
        'meta' => 'array',
        'last_used_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public static function makeCredentials(): array
    {
        $clientKey = strtoupper(Str::random(16));
        $rawSecret = Str::random(40);

        return [
            'client_key' => $clientKey,
            'raw_secret' => $rawSecret,
            'client_secret_hash' => Hash::make($rawSecret),
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function requestLogs(): HasMany
    {
        return $this->hasMany(ApiRequestLog::class);
    }

    public function smsMessages(): HasMany
    {
        return $this->hasMany(SmsMessage::class);
    }

    public function verifySecret(string $secret): bool
    {
        return Hash::check($secret, $this->client_secret_hash);
    }

    public function isActive(): bool
    {
        return strtolower((string) $this->status) === 'active';
    }

    public function allowsIp(?string $ip): bool
    {
        $allowedIps = collect($this->allowed_ips ?? [])
            ->filter()
            ->values();

        if ($allowedIps->isEmpty()) {
            return true;
        }

        return $ip ? $allowedIps->contains($ip) : false;
    }

    public function getCreatedAtAttribute($value): string
    {
        return Carbon::parse($value)
            ->timezone(config('app.timezone'))
            ->format('Y-m-d H:i:s');
    }

    public function getLastUsedAtAttribute($value): ?string
    {
        return $value
            ? Carbon::parse($value)->timezone(config('app.timezone'))->format('Y-m-d H:i:s')
            : null;
    }

    protected function shouldIgnoreAuditTrailAttribute(string $attribute): bool
    {
        return in_array($attribute, [
            'created_at',
            'updated_at',
            'deleted_at',
            'remember_token',
            'password',
            'client_secret_hash',
        ], true);
    }
}
