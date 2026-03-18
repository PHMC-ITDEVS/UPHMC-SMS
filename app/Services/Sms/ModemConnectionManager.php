<?php

namespace App\Services\Sms;

use App\Services\Sms\Exceptions\ModemException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Manages serial port lifecycle and prevents concurrent modem access
 * via a Redis-backed distributed lock.
 *
 * Lock key : sms:modem:lock:{port}
 * Lock TTL : 60s
 */
class ModemConnectionManager
{
    private const LOCK_TTL_SECONDS = 60;
    private const LOCK_PREFIX = 'sms:modem:lock:';

    private ?AtCommandRunner $runner = null;

    public function __construct(
        private readonly string $port,
        private readonly int $baudRate = 9600,
    ) {}

    /**
     * Acquire modem lock and open connection.
     * Returns false if modem is busy — caller should requeue job.
     *
     * @throws ModemException if port cannot be opened
     */
    public function acquire(): bool
    {
        $lock = Cache::lock($this->lockKey(), self::LOCK_TTL_SECONDS);

        if (! $lock->get()) {
            Log::info("[Modem] Port {$this->port} is busy — lock not acquired.");
            return false;
        }

        try {
            $this->runner = new AtCommandRunner($this->port, $this->baudRate);
            $this->runner->open();
            Log::info("[Modem] Connection acquired on {$this->port}");
            return true;
        } catch (ModemException $e) {
            $lock->release();
            throw $e;
        }
    }

    /**
     * Release lock and close connection.
     * Always call in a finally block.
     */
    public function release(): void
    {
        try {
            $this->runner?->close();
        } finally {
            Cache::lock($this->lockKey())->forceRelease();
            $this->runner = null;
            Log::info("[Modem] Connection released on {$this->port}");
        }
    }

    public function getRunner(): AtCommandRunner
    {
        if ($this->runner === null) {
            throw new ModemException('Connection not acquired. Call acquire() first.');
        }

        return $this->runner;
    }

    public function isLocked(): bool
    {
        return ! Cache::lock($this->lockKey(), 1)->get();
    }

    private function lockKey(): string
    {
        $normalized = preg_replace('/[^a-zA-Z0-9]/', '_', $this->port);
        return self::LOCK_PREFIX . $normalized;
    }
}