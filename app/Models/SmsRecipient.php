<?php

namespace App\Models;

use App\Library\Helper;
use App\Enums\RecipientStatus;
use App\Models\Concerns\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class SmsRecipient extends Model
{
    use HasFactory, SoftDeletes, HasAuditTrail;

    protected $fillable = [
        'sms_message_id',
        'contact_id',
        'phone_number',
        'status',
        'gateway_response',
        'error_message',
        'gateway_used',
        'sent_at',
        'dispatch_at',
    ];

    protected $casts = [
        'status'  => RecipientStatus::class,
        'sent_at' => 'datetime',
        'dispatch_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function smsMessage(): BelongsTo
    {
        return $this->belongsTo(SmsMessage::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo( Contact::class, 'contact_id' , 'id' );
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopePending($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', RecipientStatus::PENDING->value);
    }

    public function scopeFailed($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', RecipientStatus::FAILED->value);
    }

    // -------------------------------------------------------------------------
    // State transitions — called by SendSmsJob after AT command / API response
    // -------------------------------------------------------------------------

    public function markSent(string $gatewayUsed, ?string $gatewayResponse = null): void
    {
        $this->update([
            'status'           => RecipientStatus::SENT,
            'gateway_used'     => $gatewayUsed,
            'gateway_response' => $gatewayResponse,
            'sent_at'          => now(),
            'error_message'    => null,
        ]);

        $this->smsMessage->incrementSentCount();
        $this->broadcastStatus();
    }

    public function markFailed(string $gatewayUsed, string $errorMessage): void
    {
        $this->update([
            'status'        => RecipientStatus::FAILED,
            'gateway_used'  => $gatewayUsed,
            'error_message' => $errorMessage,
        ]);
        Log::info('asdasdas info mark failed');
        $this->smsMessage->incrementFailedCount();
        $this->broadcastStatus();
    }

    private function broadcastStatus(): void
    {
        $message = $this->smsMessage()->with('recipients')->first();

        if (! $message) {
            return;
        }

        Helper::useRedis('sms_status', [
            'message_id' => $message->id,
            'recipient_id' => $this->id,
            'recipient_status' => $this->status->value,
            'message_status' => $message->status->value,
            'sent_count' => $message->sent_count,
            'failed_count' => $message->failed_count,
            'total_recipients' => $message->total_recipients,
        ]);
    }
}
