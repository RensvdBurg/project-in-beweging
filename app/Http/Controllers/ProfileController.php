<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Profiel van een gebruiker, te openen via de ranglijst of een activiteit op home.
 */
class ProfileController extends Controller
{
    public const PER_PAGE = 5;

    public function show(Request $request, User $user): View|RedirectResponse
    {
        $from = $request->query('from') === 'home' ? 'home' : 'ranglijst';

        $activities = Activity::query()
            ->whereBelongsTo($user)
            ->forFeed($request->user())
            ->paginate(self::PER_PAGE, pageName: 'activity_page')
            ->withQueryString()
            ->fragment('profile-activities-title');

        // Te hoog paginanummer? Dan naar de laatste pagina (zoals vroeger).
        if ($activities->isEmpty() && $activities->currentPage() > 1) {
            return redirect()->route('profile.show', [
                'user' => $user,
                'from' => $from,
                'activity_page' => $activities->lastPage(),
            ]);
        }

        return view('profile.show', [
            'profile' => $user,
            'profileRank' => $user->leaderboardRank(),
            'activities' => $activities,
            'returnToHome' => $from === 'home',
            'from' => $from,
            'openComments' => (int) session('open_comments', 0),
            'commentError' => session('comment_error', ''),
        ]);
    }
}
