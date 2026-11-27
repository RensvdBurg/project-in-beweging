<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChallengeResource;
use App\Models\Challenge;
use App\Models\User;
use App\Services\ChallengeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Challenges en de "challenge van het moment". Alle regels staan in ChallengeService.
 */
class ChallengeController extends Controller
{
    public function __construct(private readonly ChallengeService $challenges) {}

    public function index(): AnonymousResourceCollection
    {
        return ChallengeResource::collection(Challenge::query()->orderBy('id')->get());
    }

    public function show(Challenge $challenge): ChallengeResource
    {
        return new ChallengeResource($challenge);
    }

    /**
     * De challenge voor het huidige blok van 90 minuten, met wissels en aftelmoment.
     */
    public function current(Request $request): JsonResponse
    {
        return response()->json($this->state($request->user()));
    }

    public function next(Request $request): JsonResponse
    {
        $message = $this->challenges->next($request->user());

        return response()->json(['message' => $message] + $this->state($request->user()->refresh()));
    }

    public function complete(Request $request): JsonResponse
    {
        $message = $this->challenges->complete($request->user());

        return response()->json(['message' => $message] + $this->state($request->user()->refresh()));
    }

    /**
     * @return array<string, mixed>
     */
    private function state(User $user): array
    {
        $current = $this->challenges->current($user);

        return [
            'challenge' => $current['challenge'] !== null ? new ChallengeResource($current['challenge']) : null,
            'completed' => $current['completed'],
            'no_different_available' => $current['noDifferentAvailable'],
            'changes_remaining' => $this->challenges->changesRemaining($user),
            'next_change_at' => $this->challenges->nextChangeAt(),
            'xp' => (int) $user->xp,
        ];
    }
}
