<?php

namespace App\Http\Controllers;

use App\Services\Api\UpMoveApi;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Challenges via de API; de regels (blokken, wissels, XP) staan op de server in ChallengeService.
 */
class ChallengeController extends Controller
{
    public function __construct(private readonly UpMoveApi $api) {}

    /**
     * Beschrijvingspagina van een challenge (vroeger pages/learn/learn-beschrijving.php).
     */
    public function show(int $challenge): View
    {
        return view('challenges.show', ['challenge' => $this->api->challenge($challenge)]);
    }

    public function next(): RedirectResponse
    {
        return redirect()->route('home')
            ->with('challenge_notice', $this->api->nextChallenge());
    }

    public function complete(): RedirectResponse
    {
        return redirect()->route('home')
            ->with('challenge_completion_notice', $this->api->completeChallenge());
    }
}
