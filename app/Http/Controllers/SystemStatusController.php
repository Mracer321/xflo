<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

/**
 * Phase 8 — production monitoring.
 *
 * `health()` is a lightweight, unauthenticated JSON probe for uptime monitors
 * and load balancers. `index()` renders the same checks (plus a few operational
 * counters) as an admin-only status page.
 */
class SystemStatusController extends Controller
{
    /**
     * JSON health probe: database, cache, queue and storage.
     *
     * Returns HTTP 200 when every critical check passes, 503 otherwise, so an
     * external monitor can alert on a single status code.
     */
    public function health(): JsonResponse
    {
        $checks = $this->runChecks();

        $ok = collect($checks)->every(fn ($c) => $c['ok']);

        return response()->json([
            'status' => $ok ? 'ok' : 'degraded',
            'checks' => $checks,
        ], $ok ? 200 : 503);
    }

    /**
     * Admin-only system status page (route-gated to super_admin).
     */
    public function index(): View
    {
        return view('system.status', [
            'checks' => $this->runChecks(),
            'metrics' => $this->metrics(),
        ]);
    }

    /**
     * Run each critical infrastructure check.
     *
     * @return array<string, array{ok: bool, detail: string}>
     */
    private function runChecks(): array
    {
        return [
            'database' => $this->check(function () {
                DB::select('select 1');

                return 'Connected ('.config('database.default').')';
            }),
            'cache' => $this->check(function () {
                Cache::put('health:ping', 1, 5);

                if (Cache::get('health:ping') !== 1) {
                    throw new \RuntimeException('Cache read-back failed');
                }

                return 'Working ('.config('cache.default').')';
            }),
            'queue' => $this->check(function () {
                // For the database queue, confirm the jobs table is reachable.
                $pending = config('queue.default') === 'database'
                    ? DB::table('jobs')->count()
                    : 0;

                return config('queue.default')." ({$pending} pending)";
            }),
            'storage' => $this->check(function () {
                if (! Storage::disk('public')->exists('.')) {
                    // Touch a probe file to confirm the disk is writable.
                    Storage::disk('public')->put('health/.probe', (string) time());
                }

                return 'Writable';
            }),
        ];
    }

    /**
     * Operational counters shown on the admin status page.
     *
     * @return array<string, int|string>
     */
    private function metrics(): array
    {
        $failedJobs = $this->safe(fn () => DB::table('failed_jobs')->count());
        $pendingJobs = $this->safe(fn () => DB::table('jobs')->count());

        $freeBytes = @disk_free_space(storage_path());

        return [
            'pending_jobs' => $pendingJobs,
            'failed_jobs' => $failedJobs,
            'cache_store' => config('cache.default'),
            'queue_connection' => config('queue.default'),
            'mail_mailer' => config('mail.default'),
            'app_env' => config('app.env'),
            'debug' => config('app.debug') ? 'on' : 'off',
            'storage_free' => $freeBytes ? $this->humanBytes($freeBytes) : 'unknown',
        ];
    }

    /**
     * Run a check callback, capturing success/failure into the result shape.
     *
     * @return array{ok: bool, detail: string}
     */
    private function check(callable $callback): array
    {
        try {
            return ['ok' => true, 'detail' => (string) $callback()];
        } catch (Throwable $e) {
            return ['ok' => false, 'detail' => $e->getMessage()];
        }
    }

    /**
     * Run a callback, returning a dash on failure (for non-critical counters).
     */
    private function safe(callable $callback): int|string
    {
        try {
            return $callback();
        } catch (Throwable) {
            return '—';
        }
    }

    /**
     * Format a byte count as a human-readable string.
     */
    private function humanBytes(float $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 1).' '.$units[$i];
    }
}
