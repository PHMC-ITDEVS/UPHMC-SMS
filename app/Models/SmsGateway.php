<?php

namespace App\Models;

use App\Enums\GatewayType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmsGateway extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'is_active',
        'priority',
        'config',
    ];

    protected $casts = [
        'config'    => 'array',
        'is_active' => 'boolean',
        'priority'  => 'integer',
        'type'      => GatewayType::class,
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function smsMessages(): HasMany
    {
        return $this->hasMany(SmsMessage::class, 'gateway_id');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /**
     * Returns the highest-priority active gateway.
     * Used by GatewayRouter to decide which gateway to use.
     */
    public function scopeActivePrimary($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true)->orderBy('priority');
    }

    public function scopeOfType($query, GatewayType $type): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('type', $type->value);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isModem(): bool
    {
        return $this->type === GatewayType::MODEM;
    }

    public function isApi(): bool
    {
        return $this->type === GatewayType::API;
    }

    /**
     * Safely get a config value with a default.
     */
    public function getConfig(string $key, mixed $default = null): mixed
    {
        return data_get($this->config, $key, $default);
    }
}