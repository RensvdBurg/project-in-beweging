<?php

namespace App\Http\Controllers;

use App\Models\Challenge;
use App\Services\ChallengeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChallengeController extends Controller
{
    public function __construct(private readonly ChallengeService $challenges) {}

    /**
     * Beschrijvingspagina van een challenge (vroeger pages/learn/learn-beschrijving.php).
     */
    public function show(Challenge $challenge): View
    {
        return view('challenges.show', ['challenge' => $challenge]);
    }

    public function next(Request $request): RedirectResponse
    {
        return redirect()->route('home')
            ->with('challenge_notice', $this->challenges->next($request->user()));
    }

    public function complete(Request $request): RedirectResponse
    {
        return redirect()->route('home')
            ->with('challenge_completion_notice', $this->challenges->complete($request->user()));
    }
}
