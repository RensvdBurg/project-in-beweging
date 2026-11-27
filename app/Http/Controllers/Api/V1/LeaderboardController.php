<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityResource;
use App\Http\Resources\UserResource;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Ranglijst en profielen van andere gebruikers.
 */
class LeaderboardController extends Controller
{
    private const TOP = 10;

    private const ACTIVITIES_PER_PAGE = 5;

    /**
     * Top 10 en de eigen positie (die kan buiten de top 10 vallen).
     */
    public function index(Request $request): JsonResponse
    {
        $rankings = User::query()->leaderboard()->limit(self::TOP)->get(['id', 'username', 'full_name', 'xp', 'about_me', 'created_at']);

        return response()->json([
            'rankings' => UserResource::collection($rankings),
            'my_rank' => $request->user()->leaderboardRank(),
            'me' => new UserResource($request->user()),
        ]);
    }

    public function profile(User $user): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($user),
            'rank' => $user->leaderboardRank(),
        ]);
    }

    public function activities(Request $request, User $user): AnonymousResourceCollection
    {
        return ActivityResource::collection(
            Activity::query()->whereBelongsTo($user)->forFeed($request->user())->paginate($this->perPage($request))
        );
    }

    private function perPage(Request $request): int
    {
        return max(1, min(50, $request->integer('per_page', self::ACTIVITIES_PER_PAGE)));
    }
}
