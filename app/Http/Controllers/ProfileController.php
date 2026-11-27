<?php

namespace App\Http\Controllers;

use App\Services\Api\UpMoveApi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Profiel van een gebruiker, te openen via de ranglijst of een activiteit op home (via de API).
 */
class ProfileController extends Controller
{
    public const PER_PAGE = 5;

    public function __construct(private readonly UpMoveApi $api) {}

    public function show(Request $request, int $user): View|RedirectResponse
    {
        $from = $request->query('from') === 'home' ? 'home' : 'ranglijst';
        $profile = $this->api->profile($user);

        $activities = $this->api
            ->userActivities($user, max(1, $request->integer('activity_page', 1)), self::PER_PAGE, 'activity_page')
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
            'profile' => $profile['user'],
            'profileRank' => $profile['rank'],
            'activities' => $activities,
            'returnToHome' => $from === 'home',
            'from' => $from,
            'openComments' => (int) session('open_comments', 0),
            'commentError' => session('comment_error', ''),
        ]);
    }
}
