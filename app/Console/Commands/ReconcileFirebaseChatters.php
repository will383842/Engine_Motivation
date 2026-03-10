<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Chatter;
use App\Services\AdminNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReconcileFirebaseChatters extends Command
{
    protected $signature = 'chatters:reconcile {--dry-run : Show discrepancies without fixing}';
    protected $description = 'Compare local chatters with Firebase and flag discrepancies';

    public function handle(AdminNotifier $notifier): int
    {
        $dryRun = $this->option('dry-run');
        $this->info('Starting Firebase reconciliation' . ($dryRun ? ' (DRY RUN)' : '') . '...');

        $firebaseUrl = config('services.firebase.reconcile_url');
        if (!$firebaseUrl) {
            $this->error('FIREBASE_RECONCILE_URL not configured. Set services.firebase.reconcile_url in config.');
            Log::warning('ReconcileFirebaseChatters: FIREBASE_RECONCILE_URL not configured');
            return Command::FAILURE;
        }

        // Step 1: Fetch active chatters from Firebase
        try {
            $response = Http::timeout(30)->withHeaders([
                'X-Webhook-Signature' => hash_hmac('sha256', 'reconcile', config('services.firebase.webhook_secret', '')),
            ])->get($firebaseUrl);

            if (!$response->successful()) {
                $this->error("Firebase API returned {$response->status()}");
                Log::error('ReconcileFirebaseChatters: Firebase API error', ['status' => $response->status()]);
                return Command::FAILURE;
            }

            $firebaseChatters = collect($response->json('chatters', []));
        } catch (\Throwable $e) {
            $this->error("Failed to fetch from Firebase: {$e->getMessage()}");
            Log::error('ReconcileFirebaseChatters: fetch failed', ['error' => $e->getMessage()]);
            return Command::FAILURE;
        }

        $this->info("  Firebase: {$firebaseChatters->count()} active chatters");

        // Step 2: Get local chatters
        $localChatters = Chatter::where('is_active', true)
            ->pluck('firebase_uid')
            ->filter()
            ->unique();

        $this->info("  Local DB: {$localChatters->count()} active chatters");

        // Step 3: Compare
        $firebaseUids = $firebaseChatters->pluck('uid')->filter()->unique();

        $missingLocally = $firebaseUids->diff($localChatters);    // In Firebase, not in PG
        $missingInFirebase = $localChatters->diff($firebaseUids);  // In PG, not in Firebase
        $totalDiscrepancies = $missingLocally->count() + $missingInFirebase->count();

        // Step 4: Report
        if ($missingLocally->count() > 0) {
            $this->warn("  Missing locally (in Firebase but not PG): {$missingLocally->count()}");
            foreach ($missingLocally->take(20) as $uid) {
                $fbData = $firebaseChatters->firstWhere('uid', $uid);
                $this->line("    - {$uid} ({$fbData['email'] ?? 'no email'})");

                if (!$dryRun) {
                    // Auto-create from Firebase data
                    try {
                        Chatter::create([
                            'firebase_uid' => $uid,
                            'email' => $fbData['email'] ?? '',
                            'display_name' => $fbData['displayName'] ?? $fbData['firstName'] ?? 'Unknown',
                            'language' => $fbData['language'] ?? 'fr',
                            'country' => $fbData['country'] ?? 'unknown',
                            'timezone' => $fbData['timezone'] ?? 'UTC',
                            'affiliate_code_client' => $fbData['affiliateCodeClient'] ?? null,
                            'affiliate_code_recruitment' => $fbData['affiliateCodeRecruitment'] ?? null,
                            'is_active' => true,
                            'lifecycle_state' => 'active',
                            'level' => $fbData['level'] ?? 1,
                            'total_xp' => 0,
                            'balance_cents' => $fbData['balanceCents'] ?? 0,
                            'lifetime_earnings_cents' => $fbData['lifetimeEarningsCents'] ?? 0,
                        ]);
                        Log::info("ReconcileFirebaseChatters: auto-created {$uid}");
                    } catch (\Throwable $e) {
                        Log::error("ReconcileFirebaseChatters: failed to create {$uid}", ['error' => $e->getMessage()]);
                    }
                }
            }
        }

        if ($missingInFirebase->count() > 0) {
            $this->warn("  Missing in Firebase (in PG but not Firebase): {$missingInFirebase->count()}");
            foreach ($missingInFirebase->take(20) as $uid) {
                $this->line("    - {$uid}");
            }
            // Don't auto-delete — could be recently deleted in Firebase, flag for admin review
        }

        // Step 5: Alert if discrepancy is significant
        $totalActive = max($firebaseUids->count(), $localChatters->count(), 1);
        $discrepancyRate = $totalDiscrepancies / $totalActive;

        if ($discrepancyRate > 0.05) {
            $notifier->alert('critical', 'reconciliation', "Firebase reconciliation: {$totalDiscrepancies} discrepancies (" . round($discrepancyRate * 100, 1) . "%). Missing locally: {$missingLocally->count()}, Missing in Firebase: {$missingInFirebase->count()}", ['telegram', 'email', 'dashboard']);
        } elseif ($totalDiscrepancies > 0) {
            $notifier->alert('info', 'reconciliation', "Firebase reconciliation: {$totalDiscrepancies} minor discrepancies (" . round($discrepancyRate * 100, 1) . "%)", ['dashboard']);
        }

        $this->info("Reconciliation complete: {$totalDiscrepancies} discrepancies ({$missingLocally->count()} missing locally, {$missingInFirebase->count()} missing in Firebase)");
        Log::info('ReconcileFirebaseChatters completed', [
            'firebase_count' => $firebaseUids->count(),
            'local_count' => $localChatters->count(),
            'missing_locally' => $missingLocally->count(),
            'missing_in_firebase' => $missingInFirebase->count(),
            'discrepancy_rate' => round($discrepancyRate * 100, 2) . '%',
        ]);

        return Command::SUCCESS;
    }
}
