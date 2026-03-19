<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * One-time setup command for the Python SMS modem dependency.
 *
 * Usage:
 *   php artisan sms:setup-modem
 *   php artisan sms:setup-modem --check   (verify only, no install)
 */
class SetupModem extends Command
{
    protected $signature   = 'sms:setup-modem {--check : Only verify, do not install}';
    protected $description = 'Install and verify Python dependencies for the GSM modem SMS driver';

    public function handle(): int
    {
        $this->info("=== UPHMC SMS — Modem Setup ===");
        $this->newLine();

        $checkOnly = (bool) $this->option('check');
        $allGood   = true;

        // ── 1. Python availability ────────────────────────────────────────────
        $this->comment("1. Python");
        exec('python --version 2>&1', $pyOut, $pyCode);
        $pythonVersion = implode('', $pyOut);

        if ($pyCode !== 0 || ! str_contains($pythonVersion, 'Python')) {
            $this->error("   Python not found. Install Python 3.8+ from https://python.org");
            $this->error("   Make sure 'python' is on your PATH.");
            return self::FAILURE;
        }

        $this->line("   {$pythonVersion} ✓");

        // ── 2. pip availability ───────────────────────────────────────────────
        $this->comment("2. pip");
        exec('pip --version 2>&1', $pipOut, $pipCode);
        $pipVersion = implode('', $pipOut);

        if ($pipCode !== 0) {
            $this->error("   pip not found. Ensure pip is installed with Python.");
            return self::FAILURE;
        }

        $this->line("   " . substr($pipVersion, 0, 40) . " ✓");

        // ── 3. pyserial ───────────────────────────────────────────────────────
        $this->comment("3. pyserial");
        exec('python -c "import serial; print(serial.__version__)" 2>&1', $serOut, $serCode);
        $serialVersion = implode('', $serOut);

        if ($serCode !== 0 || ! is_numeric(substr($serialVersion, 0, 1))) {
            if ($checkOnly) {
                $this->error("   pyserial NOT installed.");
                $this->warn("   Run: pip install -r scripts/sms/requirements.txt");
                $allGood = false;
            } else {
                $this->warn("   pyserial not installed — installing now...");

                $requirementsPath = base_path('scripts/sms/requirements.txt');
                $installCmd       = "pip install -r " . escapeshellarg($requirementsPath) . " 2>&1";

                exec($installCmd, $installOut, $installCode);

                if ($installCode !== 0) {
                    $this->error("   Install failed: " . implode("\n", $installOut));
                    return self::FAILURE;
                }

                $this->info("   pyserial installed ✓");
            }
        } else {
            $this->line("   pyserial {$serialVersion} ✓");
        }

        // ── 4. Script existence ───────────────────────────────────────────────
        $this->comment("4. sms_send.py");
        $scriptPath = base_path('scripts/sms/sms_send.py');

        if (! file_exists($scriptPath)) {
            $this->error("   Script not found at: {$scriptPath}");
            $allGood = false;
        } else {
            $this->line("   Found at: {$scriptPath} ✓");
        }

        // ── 5. Gateway DB config ──────────────────────────────────────────────
        $this->comment("5. Gateway config");
        try {
            $gateway = \App\Models\SmsGateway::where('is_active', true)->orderBy('priority')->first();
            if ($gateway) {
                $port = $gateway->config['port'] ?? 'NOT SET';
                $baud = $gateway->config['baud_rate'] ?? 'NOT SET';
                $this->line("   Name:      {$gateway->name}");
                $this->line("   Port:      {$port}");
                $this->line("   Baud rate: {$baud}");

                // ── 6. Quick Python port open test ────────────────────────────
                if ($allGood && ! $checkOnly && $port !== 'NOT SET') {
                    $this->comment("6. Port open test via Python");

                    // Use a proper Python script string with correct quoting.
                    // Pass port as a command-line argument to avoid shell quoting issues.
                    $cmd = sprintf(
                        'python -c "import sys,serial; s=serial.Serial(sys.argv[1],%d,timeout=3,dsrdtr=True); s.close(); print(\'Port OK\')" %s 2>&1',
                        (int) $baud,
                        escapeshellarg($port)
                    );

                    exec($cmd, $testOut, $testCode);
                    $testResult = implode('', $testOut);

                    if ($testCode === 0 && str_contains($testResult, 'Port OK')) {
                        $this->info("   Port {$port} accessible via pyserial ✓");
                    } else {
                        $this->error("   Port test failed: {$testResult}");
                        $this->warn("   Ensure modem is connected and no other application has {$port} open.");
                        $allGood = false;
                    }
                }
            } else {
                $this->warn("   No active gateway found in database.");
                $allGood = false;
            }
        } catch (\Throwable $e) {
            $this->error("   DB error: " . $e->getMessage());
            $allGood = false;
        }

        $this->newLine();

        if ($allGood) {
            $this->info("=== All checks passed. SMS modem is ready. ===");
            $this->newLine();
            $this->line("Next steps:");
            $this->line("  1. Test directly:  python scripts/sms/sms_send.py --port {$port} --baud {$baud} --to 09XXXXXXXXX --message \"Test\"");
            $this->line("  2. Start worker:   php artisan queue:work --queue=sms --sleep=3 --tries=3 --timeout=120");
        } else {
            $this->warn("=== Some checks failed. Review output above. ===");
        }

        return $allGood ? self::SUCCESS : self::FAILURE;
    }
}