<?php
declare(strict_types=1);
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Chatter;
use App\Services\LeaderboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaderboardController extends Controller {
    public function __construct(private readonly LeaderboardService $service) {}

    public function index(): JsonResponse { return response()->json($this->service->getTopChatters("weekly")); }

    public function weekly(): JsonResponse { return response()->json($this->service->getTopChatters("weekly")); }

    public function monthly(): JsonResponse { return response()->json($this->service->getTopChatters("monthly")); }

    /**
     * Show a specific leaderboard by type and period.
     *
     * GET /api/leaderboards/{type}/{period}
     * e.g. /api/leaderboards/xp/weekly, /api/leaderboards/xp/alltime
     */
    public function show(string $type, string $period, Request $request): JsonResponse
    {
        $validTypes = ['xp', 'sales', 'streak', 'engagement'];
        $validPeriods = ['weekly', 'monthly', 'alltime'];

        if (!in_array($type, $validTypes, true)) {
            return response()->json([
                'error' => 'Invalid leaderboard type.',
                'valid_types' => $validTypes,
            ], 422);
        }

        if (!in_array($period, $validPeriods, true)) {
            return response()->json([
                'error' => 'Invalid period.',
                'valid_periods' => $validPeriods,
            ], 422);
        }

        $limit = min((int) $request->query('limit', '50'), 200);
        $offset = max((int) $request->query('offset', '0'), 0);
        $country = $request->query('country');

        $entries = $this->service->getTopChatters($type, $period, $limit);

        // Build enriched response with chatter details
        $ranked = [];
        $rank = $offset;

        foreach (array_slice($entries, $offset, $limit, true) as $chatterId => $score) {
            $rank++;
            $chatter = Chatter::select(['id', 'firebase_uid', 'display_name', 'country', 'level'])
                ->find($chatterId);

            if (!$chatter) {
                continue;
            }

            if ($country && $chatter->country !== $country) {
                continue;
            }

            $ranked[] = [
                'rank' => $rank,
                'chatter_id' => $chatter->id,
                'display_name' => $chatter->display_name,
                'country' => $chatter->country,
                'level' => $chatter->level,
                'score' => (int) $score,
            ];
        }

        return response()->json([
            'type' => $type,
            'period' => $period,
            'total' => count($ranked),
            'entries' => $ranked,
        ]);
    }
}