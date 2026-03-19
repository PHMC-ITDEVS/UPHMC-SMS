<?php

namespace App\Jobs;

use App\Enums\RecipientStatus;
use App\Enums\SmsStatus;
use App\Models\SmsRecipient;
use App\Services\Sms\Exceptions\NoAvailableGatewayException;
use App\Services\Sms\GatewayRouter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * SendSmsJob
 *
 * Processes a single SMS recipient. One job = one recipient = one SMS.
 *
 * RETRY STRATEGY:
 *   - modem_busy        → re-release to queue with 30s delay (not a failure attempt)
 *   - modem_disconnected→ permanently mark failed, do NOT retry
 *   - send failure      → Laravel retries up to $tries times with backoff
 *   - max retries hit   → failed() hook marks recipient as permanently failed
 *
 * QUEUE:
 *   Use a dedicated 'sms' queue so SMS jobs don't compete with other jobs.
 *   Run worker with: php artisan queue:work --queue=sms --sleep=3 --tries=3
 *
 * CONCURRENCY:
 *   Modem handles one SMS at a time via Redis lock in ModemConnectionManager.
 *   Run only ONE worker process for the sms queue to avoid lock contention.
 *   php artisan queue:work --queue=sms --sleep=3 --tries=3 --max-jobs=50
 */
class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum attempts before job is marked as failed.
     */
    public int $tries = 3;

    /**
     * Seconds between retry attempts (exponential: 60, 120, 240).
     */
    public array $backoff = [60, 120, 240];

    /**
     * Seconds before job is considered timed out.
     * Must be longer than AT command timeout (10s) + lock wait + stty config.
     */
    public int $timeout = 120;

    public function __construct(
        private readonly int $recipientId
    ) {}

    public function handle(GatewayRouter $router): void
    {
        $recipient = $this->loadRecipient();

        if ($recipient === null) {
            // Record deleted — nothing to do, discard job silently
            return;
        }

        if ($this->shouldSkip($recipient)) {
            return;
        }

        $message = $recipient->smsMessage;

        // Resolve gateway driver from DB config
        try {
            $driver = $router->resolve();
        } catch (NoAvailableGatewayException $e) {
            Log::error("[SendSmsJob] No gateway available for recipient #{$this->recipientId}");
            $this->fail($e);
            return;
        }

        // Attempt send
        $result = $driver->send($recipient->phone_number, $message->message_body);

        // Modem was busy — release back to queue, don't count as failure attempt
        if (! $result['success'] && $result['error'] === 'modem_busy') {
            Log::info("[SendSmsJob] Modem busy — requeuing recipient #{$this->recipientId} in 30s");
            $this->release(30);
            return;
        }

        // Modem physically disconnected — don't retry, mark immediately failed
        if (! $result['success'] && $result['error'] === 'modem_disconnected') {
            Log::warning("[SendSmsJob] Modem not connected for recipient #{$this->recipientId}");
            $recipient->markFailed(
                gatewayUsed: $result['gateway'],
                errorMessage: 'Modem device not connected or port unavailable.'
            );
            return;
        }

        // FIX: markSent() expects ($gatewayUsed, $gatewayResponse)
        // Previously this called markSent(gateway: ..., reference: ...) which
        // caused a PHP named argument mismatch error, failing the job silently.
        if ($result['success']) {
            $recipient->markSent(
                gatewayUsed:     $result['gateway'],
                gatewayResponse: $result['reference'],
            );

            Log::info("[SendSmsJob] SMS sent to {$recipient->phone_number}", [
                'recipient_id' => $this->recipientId,
                'message_id'   => $message->id,
                'gateway'      => $result['gateway'],
                'reference'    => $result['reference'],
            ]);

            return;
        }

        // Failure — let Laravel retry via backoff
        Log::warning("[SendSmsJob] Send failed for recipient #{$this->recipientId}: {$result['error']}", [
            'attempt' => $this->attempts(),
            'gateway' => $result['gateway'],
        ]);

        // On final attempt, this exception triggers failed()
        if ($this->attempts() >= $this->tries) {
            $this->fail(new \RuntimeException($result['error']));
            return;
        }

        // Re-throw to trigger Laravel retry with backoff
        throw new \RuntimeException("SMS send failed: {$result['error']}");
    }

    /**
     * Called by Laravel when all retry attempts are exhausted.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("[SendSmsJob] Permanently failed for recipient #{$this->recipientId}: {$exception->getMessage()}");

        $recipient = $this->loadRecipient();

        if ($recipient === null) {
            return;
        }

        $recipient->markFailed(
            gatewayUsed:  'system',
            errorMessage: $exception->getMessage(),
        );
    }

    // ── Private Helpers ───────────────────────────────────────────────────────

    private function loadRecipient(): ?SmsRecipient
    {
        return SmsRecipient::with('smsMessage')->find($this->recipientId);
    }

    /**
     * Skip if already processed (guards against duplicate job dispatch).
     */
    private function shouldSkip(SmsRecipient $recipient): bool
    {
        if ($recipient->status !== RecipientStatus::PENDING) {
            Log::info("[SendSmsJob] Skipping recipient #{$this->recipientId} — status: {$recipient->status->value}");
            return true;
        }

        if ($recipient->smsMessage->status === SmsStatus::FAILED) {
            Log::info("[SendSmsJob] Skipping recipient #{$this->recipientId} — parent message cancelled/failed");
            return true;
        }

        return false;
    }
}