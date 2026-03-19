<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Diagnostic command for modem port detection on Windows.
 *
 * Usage:
 *   php artisan sms:diagnose-modem
 *   php artisan sms:diagnose-modem --port=COM5
 */
class DiagnoseModem extends Command
{
    protected $signature = 'sms:diagnose-modem {--port=COM3 : The COM port to test}';
    protected $description = 'Diagnose modem port availability and PHP capabilities on Windows';

    public function handle(): int
    {
        $port = strtoupper(trim((string) $this->option('port')));
        $uncPort = '\\\\.\\'  . $port;

        $this->info("=== UPHMC SMS — Modem Diagnostic ===");
        $this->line("Port: {$port}");
        $this->line("UNC:  {$uncPort}");
        $this->line("OS:   " . PHP_OS_FAMILY . " (" . PHP_OS . ")");
        $this->line("PHP:  " . PHP_VERSION);
        $this->newLine();

        // ── 1. file_exists ────────────────────────────────────────────────────
        $this->comment("1. file_exists() tests");
        $this->line("   file_exists('{$port}'):     " . ($this->yesNo(file_exists($port))));
        $this->line("   file_exists('{$uncPort}'): " . ($this->yesNo(file_exists($uncPort))));

        // ── 2. exec() availability ────────────────────────────────────────────
        $this->newLine();
        $this->comment("2. exec() / shell_exec() availability");
        $execDisabled = in_array('exec', array_map('trim', explode(',', ini_get('disable_functions'))));
        $shellDisabled = in_array('shell_exec', array_map('trim', explode(',', ini_get('disable_functions'))));
        $this->line("   exec() disabled:       " . $this->yesNo($execDisabled, true));
        $this->line("   shell_exec() disabled: " . $this->yesNo($shellDisabled, true));

        // ── 3. mode.exe probe ─────────────────────────────────────────────────
        $this->newLine();
        $this->comment("3. mode.exe probe");
        if (! $execDisabled) {
            $modeCmd = "mode {$port} 2>&1";
            exec($modeCmd, $modeOutput, $modeCode);
            $this->line("   Command:     {$modeCmd}");
            $this->line("   Exit code:   {$modeCode}");
            $this->line("   Output:      " . implode(' | ', $modeOutput));
            $this->line("   Port exists: " . $this->yesNo($modeCode === 0));
        } else {
            $this->warn("   Skipped — exec() is disabled.");
        }

        // ── 4. PowerShell probe ───────────────────────────────────────────────
        $this->newLine();
        $this->comment("4. PowerShell [System.IO.Ports.SerialPort]::GetPortNames() probe");
        if (! $execDisabled) {
            $psCmd = 'powershell -NoProfile -Command "[System.IO.Ports.SerialPort]::GetPortNames()" 2>&1';
            exec($psCmd, $psOutput, $psCode);
            $this->line("   Exit code: {$psCode}");
            $this->line("   Ports:     " . implode(', ', $psOutput));
            $portFoundInPs = collect($psOutput)->contains(fn ($p) => strtoupper(trim($p)) === $port);
            $this->line("   {$port} listed: " . $this->yesNo($portFoundInPs));
        } else {
            $this->warn("   Skipped — exec() is disabled.");
        }

        // ── 5. fopen() attempt ────────────────────────────────────────────────
        $this->newLine();
        $this->comment("5. fopen() attempt on {$uncPort}");
        $openError = null;
        set_error_handler(function (int $errno, string $errstr) use (&$openError): bool {
            $openError = $errstr;
            return true;
        });
        $handle = fopen($uncPort, 'r+b');
        restore_error_handler();

        if ($handle !== false) {
            $this->info("   fopen() SUCCESS — port is accessible");
            fclose($handle);
        } else {
            $this->error("   fopen() FAILED: " . ($openError ?? 'Unknown error'));
        }

        // ── 6. PHP process info ───────────────────────────────────────────────
        $this->newLine();
        $this->comment("6. PHP process context");
        $this->line("   Running as user: " . (function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : exec('whoami')));
        $this->line("   SAPI:            " . PHP_SAPI);
        $this->line("   CWD:             " . getcwd());

        // ── 7. Gateway DB config ──────────────────────────────────────────────
        $this->newLine();
        $this->comment("7. Gateway config from database");
        try {
            $gateway = \App\Models\SmsGateway::where('is_active', true)->orderBy('priority')->first();
            if ($gateway) {
                $this->line("   Name:      " . $gateway->name);
                $this->line("   Type:      " . $gateway->type->value);
                $this->line("   Active:    " . $this->yesNo($gateway->is_active));
                $this->line("   Priority:  " . $gateway->priority);
                $this->line("   Config:    " . json_encode($gateway->config));
            } else {
                $this->warn("   No active gateway found in database.");
            }
        } catch (\Throwable $e) {
            $this->error("   DB error: " . $e->getMessage());
        }

        // ── 8. Redis lock status ──────────────────────────────────────────────
        $this->newLine();
        $this->comment("8. Redis modem lock status");
        try {
            $normalized = preg_replace('/[^a-zA-Z0-9]/', '_', $port);
            $lockKey = 'sms:modem:lock:' . $normalized;
            $locked = \Illuminate\Support\Facades\Cache::has($lockKey);
            $this->line("   Lock key: {$lockKey}");
            $this->line("   Locked:   " . $this->yesNo($locked, true));
            if ($locked) {
                $this->warn("   → Run: php artisan tinker --execute=\"Cache::forget('{$lockKey}');\"");
            }
        } catch (\Throwable $e) {
            $this->error("   Redis error: " . $e->getMessage());
        }

        $this->newLine();
        $this->info("=== Diagnostic Complete ===");

        return self::SUCCESS;
    }

    private function yesNo(bool $value, bool $invertColor = false): string
    {
        $yes = $invertColor ? '<error>YES</error>' : '<info>YES</info>';
        $no  = $invertColor ? '<info>NO</info>'  : '<error>NO</error>';
        return $value ? $yes : $no;
    }
}