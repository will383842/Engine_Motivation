<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

class HealthController extends Controller
{
    /** Queue backlog thresholds for alerting */
    private const QUEUE_WARNING_THRESHOLD = 1000;
    private const QUEUE_CRITICAL_THRESHOLD = 5000;

    public function __invoke(): JsonResponse
    {
        $checks = [];

        // Database
        try {
            DB::select('SELECT 1');
            $checks['database'] = 'ok';
        } catch (\Throwable $e) {
            $checks['database'] = 'error';
        }

        // Redis
        try {
            Redis::ping();
            $checks['redis'] = 'ok';
        } catch (\Throwable $e) {
            $checks['redis'] = 'error';
        }

        // Queue sizes with detailed status
        $queues = ['critical', 'high', 'webhooks', 'default', 'whatsapp', 'low'];
        $queueDetails = [];
        $queueHealthy = true;
        foreach ($queues as $queue) {
            try {
                $size = Queue::size($queue);
                $status = match (true) {
                    $size >= self::QUEUE_CRITICAL_THRESHOLD => 'critical',
                    $size >= self::QUEUE_WARNING_THRESHOLD => 'warning',
                    default => 'ok',
                };
                $queueDetails[$queue] = ['size' => $size, 'status' => $status];
                if ($status !== 'ok') {
                    $queueHealthy = false;
                }
            } catch (\Throwable) {
                $queueDetails[$queue] = ['size' => -1, 'status' => 'error'];
                $queueHealthy = false;
            }
        }
        $checks['queues'] = $queueDetails;

        // Failed jobs count
        try {
            $failedJobs = DB::table('failed_jobs')->count();
            $checks['failed_jobs'] = $failedJobs;
        } catch (\Throwable) {
            $checks['failed_jobs'] = -1;
        }

        // Disk usage
        try {
            $diskFree = disk_free_space('/');
            $diskTotal = disk_total_space('/');
            $diskUsedPercent = $diskTotal > 0 ? round((1 - $diskFree / $diskTotal) * 100, 1) : 0;
            $checks['disk'] = [
                'used_percent' => $diskUsedPercent,
                'free_gb' => round($diskFree / 1073741824, 2),
                'status' => $diskUsedPercent > 90 ? 'critical' : ($diskUsedPercent > 80 ? 'warning' : 'ok'),
            ];
        } catch (\Throwable) {
            $checks['disk'] = ['status' => 'unknown'];
        }

        // Memory usage
        try {
            $memUsage = memory_get_usage(true);
            $memPeak = memory_get_peak_usage(true);
            $memLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
            $memPercent = $memLimit > 0 ? round($memUsage / $memLimit * 100, 1) : 0;
            $checks['memory'] = [
                'used_mb' => round($memUsage / 1048576, 1),
                'peak_mb' => round($memPeak / 1048576, 1),
                'limit_mb' => round($memLimit / 1048576, 1),
                'used_percent' => $memPercent,
                'status' => $memPercent > 90 ? 'critical' : ($memPercent > 75 ? 'warning' : 'ok'),
            ];
        } catch (\Throwable) {
            $checks['memory'] = ['status' => 'unknown'];
        }

        // WhatsApp circuit breaker (global)
        try {
            $waState = Redis::get('wa_pool:circuit_breaker:state') ?? 'open';
            $checks['whatsapp'] = [
                'circuit_breaker' => $waState,
                'active_numbers' => (int) Redis::get('wa_pool:active_count') ?: 0,
            ];
        } catch (\Throwable) {
            $checks['whatsapp'] = ['circuit_breaker' => 'unknown'];
        }

        // Twilio status
        try {
            $twilioSuspended = Redis::get('twilio:suspended');
            $checks['twilio'] = $twilioSuspended ? 'suspended' : 'ok';
        } catch (\Throwable) {
            $checks['twilio'] = 'unknown';
        }

        $healthy = $checks['database'] === 'ok'
            && $checks['redis'] === 'ok'
            && $queueHealthy
            && ($checks['disk']['status'] ?? 'ok') !== 'critical'
            && ($checks['memory']['status'] ?? 'ok') !== 'critical';

        return response()->json(array_merge(
            ['status' => $healthy ? 'healthy' : 'degraded'],
            $checks
        ), $healthy ? 200 : 503);
    }

    private function parseMemoryLimit(string $limit): int
    {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $value = (int) $limit;

        return match ($last) {
            'g' => $value * 1073741824,
            'm' => $value * 1048576,
            'k' => $value * 1024,
            default => $value,
        };
    }
}
