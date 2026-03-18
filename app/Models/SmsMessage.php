<?php

namespace App\Models;

use App\Enums\SmsStatus;
use App\Models\Concerns\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmsMessage extends Model
{
    use HasFactory, SoftDeletes, HasAuditTrail;

    protected $fillable = [
        'sender_id',
        'message_body',
        'type',
        'status',
        'gateway_id',
        'fallback_gateway_id',
        'scheduled_at',
        'total_recipients',
        'sent_count',
        'failed_count',
    ];

    protected $casts = [
        'status'       => SmsStatus::class,
        'scheduled_at' => 'datetime',
        'deleted_at'   => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id')
            ->leftJoin('accounts', 'users.id', '=', 'accounts.user_id')
            ->select('users.id', DB::raw("
                TRIM(
                    REGEXP_REPLACE(
                        CONCAT(
                            COALESCE(accounts.first_name, ''), ' ',
                            COALESCE(accounts.middle_name, ''), ' ',
                            COALESCE(accounts.last_name, '')
                        ),
                        '\s+',
                        ' ',
                        'g'
                    )
                ) AS name
            "));;
    }

    public function gateway(): BelongsTo
    {
        return $this->belongsTo(SmsGateway::class, 'gateway_id');
    }

    public function fallbackGateway(): BelongsTo
    {
        return $this->belongsTo(SmsGateway::class, 'fallback_gateway_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(SmsRecipient::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeScheduledAndDue($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query
            ->where('type', 'scheduled')
            ->where('status', SmsStatus::QUEUED->value)
            ->where('scheduled_at', '<=', now());
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Atomically increment sent or failed counters.
     * Avoids race conditions from concurrent queue workers.
     */
    public function incrementSentCount(): void
    {
        $this->increment('sent_count');
        $this->checkAndMarkDone();
    }

    public function incrementFailedCount(): void
    {
        $this->increment('failed_count');
        $this->checkAndMarkDone();
    }

    protected function checkAndMarkDone(): void
    {
        $this->refresh();

        if (($this->sent_count + $this->failed_count) >= $this->total_recipients) {
            $this->update([
                'status' => $this->failed_count === $this->total_recipients
                    ? SmsStatus::FAILED
                    : SmsStatus::DONE,
            ]);
        }
    }

    public function isScheduled(): bool
    {
        return $this->type === 'scheduled';
    }

    public function getCreatedAtAttribute($value): string
    {
        return \Carbon\Carbon::parse($value)
            ->timezone(config('app.timezone'))
            ->format('Y-m-d H:i:s');
    }

    public function getScheduledAtAttribute($value): ?string
    {
        return $value
            ? \Carbon\Carbon::parse($value)->timezone(config('app.timezone'))->format('Y-m-d H:i:s')
            : null;
    }
}
