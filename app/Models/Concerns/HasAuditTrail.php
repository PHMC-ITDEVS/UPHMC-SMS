<?php

namespace App\Models\Concerns;

use App\Models\AuditTrail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait HasAuditTrail
{
    protected array $auditTrailIgnored = [
        'created_at',
        'updated_at',
        'deleted_at',
        'remember_token',
        'password',
    ];

    public static function bootHasAuditTrail(): void
    {
        static::created(function ($model) {
            $model->writeAuditTrail('created', null, $model->auditTrailNewValues());
        });

        static::updated(function ($model) {
            $oldValues = [];
            $newValues = [];

            foreach ($model->getChanges() as $attribute => $value) {
                if ($model->shouldIgnoreAuditTrailAttribute($attribute)) {
                    continue;
                }

                $oldValues[$attribute] = $model->getOriginal($attribute);
                $newValues[$attribute] = $value;
            }

            if (! empty($newValues)) {
                $model->writeAuditTrail('updated', $oldValues, $newValues);
            }
        });

        static::deleted(function ($model) {
            $model->writeAuditTrail('deleted', $model->auditTrailOldValues(), null);
        });
    }

    public function auditTrails()
    {
        return $this->morphMany(AuditTrail::class, 'auditable');
    }

    protected function writeAuditTrail(string $event, ?array $oldValues, ?array $newValues): void
    {
        AuditTrail::create([
            'user_id' => Auth::id(),
            'event' => $event,
            'auditable_type' => $this->getMorphClass(),
            'auditable_id' => $this->getKey(),
            'old_values' => empty($oldValues) ? null : $oldValues,
            'new_values' => empty($newValues) ? null : $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    protected function auditTrailNewValues(): array
    {
        return collect($this->getAttributes())
            ->reject(fn ($value, $attribute) => $this->shouldIgnoreAuditTrailAttribute($attribute))
            ->toArray();
    }

    protected function auditTrailOldValues(): array
    {
        return collect($this->getOriginal())
            ->reject(fn ($value, $attribute) => $this->shouldIgnoreAuditTrailAttribute($attribute))
            ->toArray();
    }

    protected function shouldIgnoreAuditTrailAttribute(string $attribute): bool
    {
        return in_array($attribute, $this->auditTrailIgnored, true);
    }
}
