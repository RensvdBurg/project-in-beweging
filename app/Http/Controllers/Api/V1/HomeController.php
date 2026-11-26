<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityResource;
use App\Http\Resources\ChallengeResource;
use App\Http\Resources\UserResource;
use App\Models\Activity;
use App\Models\Challenge;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Alles voor het home-scherm in één verzoek (scheelt vier rondes over mobiel internet):
 * eigen gegevens, challenge van het moment, de eerste 5 van de community, de
 * ranglijst en alle challenges voor het scherm "Leren".
 */
class HomeController extends Controller
{
    private const FEED_SIZE = 5;

    private const TOP = 10;

    public function __invoke(Request $request, ChallengeController $challenges): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => new UserResource($user),
            'rank' => $user->leaderboardRank(),
            'challenge' => $challenges->current($request)->getData(true),
            'feed' => [
                'data' => ActivityResource::collection(Activity::query()->forFeed($user)->limit(self::FEED_SIZE)->get()),
                'total' => Activity::query()->count(),
            ],
            'rankings' => UserResource::collection(User::query()->leaderboard()->limit(self::TOP)->get()),
            'challenges' => ChallengeResource::collection(Challenge::query()->orderBy('id')->get()),
        ]);
    }
}
