<?php

namespace App\Services\Sms;

use App\Enums\SmsStatus;
use App\Jobs\SendSmsJob;
use App\Models\Contact;
use App\Models\ContactGroup;
use App\Models\SmsMessage;
use App\Models\SmsRecipient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SmsService
 *
 * Orchestrates SMS message creation and job dispatch.
 * Controllers call this — never dispatch jobs directly from controllers.
 *
 * Responsibilities:
 *   - Create SmsMessage record
 *   - Expand recipients (individual numbers, contacts, groups)
 *   - Create SmsRecipient records
 *   - Dispatch one SendSmsJob per recipient
 *   - Handle scheduled messages (dispatch deferred)
 */
class SmsService
{
    /**
     * Send SMS to resolved recipients immediately.
     *
     * Each recipient item must contain:
     *   - phone_number
     *   - contact_id (nullable)
     */
    public function sendToRecipients(
        string $body,
        array $recipients,
        int $senderId,
        ?int $gatewayId = null,
        ?int $apiClientId = null,
        string $source = 'web'
    ): SmsMessage {
        return DB::transaction(function () use ($body, $recipients, $senderId, $gatewayId, $apiClientId, $source) {
            $message = $this->createMessage($body, $senderId, $gatewayId, count($recipients), null, SmsStatus::PROCESSING, $apiClientId, $source);

            foreach ($recipients as $recipientData) {
                $recipient = SmsRecipient::create([
                    'sms_message_id' => $message->id,
                    'phone_number'   => $this->normalizeNumber($recipientData['phone_number']),
                    'contact_id'     => $recipientData['contact_id'] ?? null,
                ]);

                SendSmsJob::dispatch($recipient->id)->onQueue('sms');
            }

            $message->update(['status' => SmsStatus::QUEUED]);

            Log::info("[SmsService] Queued {$message->total_recipients} SMS jobs", [
                'message_id' => $message->id,
                'sender_id'  => $senderId,
            ]);

            return $message;
        });
    }

    /**
     * Send SMS to one or more phone numbers immediately.
     *
     * @param  string    $body        Message text
     * @param  array     $numbers     Raw phone numbers e.g. ['09171234567', '09181234567']
     * @param  int       $senderId    User ID who triggered the send
     * @param  int|null  $gatewayId   Pin to specific gateway (null = auto-resolve)
     */
    public function sendToNumbers(
        string $body,
        array $numbers,
        int $senderId,
        ?int $gatewayId = null
    ): SmsMessage {
        return $this->sendToRecipients(
            body: $body,
            recipients: array_map(fn ($number) => [
                'phone_number' => $number,
                'contact_id' => null,
            ], $numbers),
            senderId: $senderId,
            gatewayId: $gatewayId,
        );
    }

    /**
     * Send SMS to all members of one or more contact groups.
     *
     * @param  string  $body       Message text
     * @param  array   $groupIds   ContactGroup IDs
     * @param  int     $senderId   User ID
     */
    public function sendToGroups(
        string $body,
        array $groupIds,
        int $senderId,
        ?int $gatewayId = null
    ): SmsMessage {
        // Load all unique contacts across all groups
        $contacts = Contact::whereHas('groups', fn ($q) => $q->whereIn('contact_groups.id', $groupIds))
            ->select(['id', 'phone_number'])
            ->distinct()
            ->get();

        return DB::transaction(function () use ($body, $contacts, $senderId, $gatewayId) {
            $message = $this->createMessage($body, $senderId, $gatewayId, $contacts->count());

            foreach ($contacts as $contact) {
                $recipient = SmsRecipient::create([
                    'sms_message_id' => $message->id,
                    'phone_number'   => $contact->phone_number,
                    'contact_id'     => $contact->id,
                ]);

                SendSmsJob::dispatch($recipient->id)->onQueue('sms');
            }

            $message->update(['status' => SmsStatus::QUEUED]);

            return $message;
        });
    }

    /**
     * Schedule SMS for future delivery.
     * Jobs are dispatched with a delay — queue worker handles timing.
     *
     * @param  string             $body         Message text
     * @param  array              $numbers      Phone numbers
     * @param  int                $senderId     User ID
     * @param  \Carbon\Carbon     $scheduledAt  When to send
     */
    public function schedule(
        string $body,
        array $numbers,
        int $senderId,
        \Carbon\Carbon $scheduledAt,
        ?int $gatewayId = null
    ): SmsMessage {
        return $this->scheduleRecipients(
            body: $body,
            recipients: array_map(fn ($number) => [
                'phone_number' => $number,
                'contact_id' => null,
            ], $numbers),
            senderId: $senderId,
            scheduledAt: $scheduledAt,
            gatewayId: $gatewayId,
        );
    }

    public function scheduleRecipients(
        string $body,
        array $recipients,
        int $senderId,
        \Carbon\Carbon $scheduledAt,
        ?int $gatewayId = null,
        ?int $apiClientId = null,
        string $source = 'web'
    ): SmsMessage {
        return DB::transaction(function () use ($body, $recipients, $senderId, $scheduledAt, $gatewayId, $apiClientId, $source) {
            $message = $this->createMessage(
                body: $body,
                senderId: $senderId,
                gatewayId: $gatewayId,
                totalRecipients: count($recipients),
                scheduledAt: $scheduledAt,
                status: SmsStatus::DRAFT,   // stays draft until dispatch time
                apiClientId: $apiClientId,
                source: $source
            );

            $delay = now()->diffInSeconds($scheduledAt);

            foreach ($recipients as $recipientData) {
                $recipient = SmsRecipient::create([
                    'sms_message_id' => $message->id,
                    'phone_number'   => $this->normalizeNumber($recipientData['phone_number']),
                    'contact_id'     => $recipientData['contact_id'] ?? null,
                ]);

                SendSmsJob::dispatch($recipient->id)
                    ->onQueue('sms')
                    ->delay($delay);
            }

            Log::info("[SmsService] Scheduled {$message->total_recipients} SMS jobs for {$scheduledAt}", [
                'message_id' => $message->id,
            ]);

            return $message;
        });
    }

    public function updateScheduledDraft(
        SmsMessage $message,
        string $body,
        array $recipients,
        \Carbon\Carbon $scheduledAt
    ): SmsMessage {
        return DB::transaction(function () use ($message, $body, $recipients, $scheduledAt) {
            $message->recipients()->delete();

            $message->update([
                'message_body' => $body,
                'type' => 'scheduled',
                'status' => SmsStatus::DRAFT,
                'scheduled_at' => $scheduledAt,
                'total_recipients' => count($recipients),
                'sent_count' => 0,
                'failed_count' => 0,
            ]);

            $delay = max(0, now()->diffInSeconds($scheduledAt, false));

            foreach ($recipients as $recipientData) {
                $recipient = SmsRecipient::create([
                    'sms_message_id' => $message->id,
                    'phone_number'   => $this->normalizeNumber($recipientData['phone_number']),
                    'contact_id'     => $recipientData['contact_id'] ?? null,
                ]);

                SendSmsJob::dispatch($recipient->id)
                    ->onQueue('sms')
                    ->delay($delay);
            }

            Log::info("[SmsService] Updated scheduled draft SMS #{$message->id}", [
                'scheduled_at' => $scheduledAt,
                'total_recipients' => count($recipients),
            ]);

            return $message->fresh(['recipients', 'recipients.contact']);
        });
    }

    // ── Private Helpers ───────────────────────────────────────────────────────

    private function createMessage(
        string $body,
        int $senderId,
        ?int $gatewayId,
        int $totalRecipients,
        ?\Carbon\Carbon $scheduledAt = null,
        SmsStatus $status = SmsStatus::PROCESSING,
        ?int $apiClientId = null,
        string $source = 'web'
    ): SmsMessage {
        return SmsMessage::create([
            'api_client_id'     => $apiClientId,
            'message_body'      => $body,
            'sender_id'         => $senderId,
            'type'              => $scheduledAt ? 'scheduled' : 'immediate',
            'gateway_id'        => $gatewayId,
            'status'            => $status,
            'source'            => $source,
            'total_recipients'  => $totalRecipients,
            'sent_count'        => 0,
            'failed_count'      => 0,
            'scheduled_at'      => $scheduledAt,
        ]);
    }

    /**
     * Normalize PH mobile numbers to a consistent format.
     * Accepts: 09171234567, 9171234567, +639171234567
     * Returns: 09171234567
     *
     * Adjust this logic if you need E.164 format for API gateways later.
     */
    private function normalizeNumber(string $number): string
    {
        $stripped = preg_replace('/\D/', '', $number);

        // +63XXXXXXXXXX or 63XXXXXXXXXX → 0XXXXXXXXX
        if (str_starts_with($stripped, '63') && strlen($stripped) === 12) {
            return '0' . substr($stripped, 2);
        }

        // 9XXXXXXXXX (missing leading 0) → 09XXXXXXXXX
        if (strlen($stripped) === 10 && str_starts_with($stripped, '9')) {
            return '0' . $stripped;
        }

        return $stripped;
    }
}
