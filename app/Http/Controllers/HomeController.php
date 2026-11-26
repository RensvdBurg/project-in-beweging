<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Challenge;
use App\Models\User;
use App\Services\ChallengeService;
use App\Services\PhoneNotifications;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Request $request, ChallengeService $challenges, PhoneNotifications $notifications): View
    {
        $user = $request->user()->refresh();

        // Meldingen op de telefoon aanvullen (doet niets als er niets veranderd is).
        $notifications->sync($user);
        $current = $challenges->current($user);

        return view('home', [
            'user' => $user,
            'reminderMinutes' => $user->preferences()['frequency'],
            'selectedChallenge' => $current['challenge'],
            'challengeCompleted' => $current['completed'],
            'noDifferentChallengeAvailable' => $current['noDifferentAvailable'],
            'challengeChangeAt' => $challenges->nextChangeAt(),
            'challengeChangesRemaining' => $challenges->changesRemaining($user),
            'challengeNotice' => session('challenge_notice', ''),
            'challengeCompletionNotice' => session('challenge_completion_notice', ''),
            'communityActivities' => Activity::query()->forFeed($user)->limit(CommunityController::PER_PAGE)->get(),
            'communityActivityCount' => Activity::query()->count(),
            'openComments' => (int) session('open_comments', 0),
            'commentError' => session('comment_error', ''),
            // Ranglijst: top 10 en de eigen positie (die kan buiten de top 10 vallen).
            'rankings' => User::query()->leaderboard()->limit(10)->get(['id', 'username', 'full_name', 'xp']),
            'currentUserRank' => $user->leaderboardRank(),
            // Leren: alle challenges op volgorde van ID.
            'challenges' => Challenge::query()->orderBy('id')->get(['id', 'title', 'description', 'img', 'kcal_burned', 'difficulty', 'xp_reward']),
            'screen' => $request->query('screen', 'home'),
            // Terug van een beschrijvingspagina: de slide-overgang de andere kant op.
            'learnTransitionBack' => $request->query('screen') === 'leren' && $request->query('transition') === 'back',
        ]);
    }
}
