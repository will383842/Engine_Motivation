<?php
declare(strict_types=1);
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Chatter;
use Illuminate\Http\JsonResponse;
class ChatterStatsController extends Controller {
    public function show(string $firebaseUid): JsonResponse {
        $chatter = Chatter::where("firebase_uid", $firebaseUid)->firstOrFail();
        return response()->json($chatter->only(["display_name", "level", "total_xp", "current_streak", "badges_count", "lifecycle_state"]));
    }
    public function streaks(string $firebaseUid): JsonResponse {
        $chatter = Chatter::where("firebase_uid", $firebaseUid)->firstOrFail();
        return response()->json($chatter->streak);
    }
    public function missions(string $firebaseUid): JsonResponse {
        $chatter = Chatter::where("firebase_uid", $firebaseUid)->firstOrFail();
        return response()->json($chatter->missions()->with("mission")->whereIn("chatter_missions.status", ["assigned","in_progress"])->get());
    }
}