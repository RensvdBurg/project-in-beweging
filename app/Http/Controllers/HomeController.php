<?php

namespace App\Http\Controllers;

use App\Auth\ApiSessionGuard;
use App\Services\Api\UpMoveApi;
use App\Services\PhoneNotifications;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Request $request, UpMoveApi $api, PhoneNotifications $notifications): View
    {
        // Eén API-verzoek voor het hele scherm (home, ranglijst en leren).
        $home = $api->home();

        /** @var ApiSessionGuard $guard */
        $guard = Auth::guard();
        $user = $guard->remember($home['user']);

        // Meldingen op de telefoon aanvullen (doet niets als er niets veranderd is).
        $notifications->sync($user);

        return view('home', [
            'user' => $user,
            'reminderMinutes' => $user->preferences()['frequency'],
            'selectedChallenge' => $home['state']['challenge'],
            'challengeCompleted' => $home['state']['completed'],
            'noDifferentChallengeAvailable' => $home['state']['no_different_available'],
            'challengeChangeAt' => $home['state']['next_change_at'],
            'challengeChangesRemaining' => $home['state']['changes_remaining'],
            'challengeNotice' => session('challenge_notice', ''),
            'challengeCompletionNotice' => session('challenge_completion_notice', ''),
            'communityActivities' => $home['feed'],
            'communityActivityCount' => $home['feedTotal'],
            'openComments' => (int) session('open_comments', 0),
            'commentError' => session('comment_error', ''),
            // Ranglijst: top 10 en de eigen positie (die kan buiten de top 10 vallen).
            'rankings' => $home['rankings'],
            'currentUserRank' => $home['rank'],
            // Leren: alle challenges op volgorde van ID.
            'challenges' => $home['challenges'],
            'screen' => $request->query('screen', 'home'),
            // Terug van een beschrijvingspagina: de slide-overgang de andere kant op.
            'learnTransitionBack' => $request->query('screen') === 'leren' && $request->query('transition') === 'back',
        ]);
    }
}
