<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * "Samen in beweging": activiteiten van alle gebruikers, met likes en reacties.
 * De eerste vijf staan op home; bij meer dan vijf is er een eigen pagina.
 */
class CommunityController extends Controller
{
    public const PER_PAGE = 5;

    public function index(Request $request): View|RedirectResponse
    {
        if (Activity::query()->count() <= self::PER_PAGE) {
            return redirect()->route('home');
        }

        $activities = Activity::query()->forFeed($request->user())->paginate(self::PER_PAGE);

        if ($activities->isEmpty() && $activities->currentPage() > 1) {
            return redirect()->route('community.index', ['page' => $activities->lastPage()]);
        }

        return view('community.index', [
            'activities' => $activities,
            'openComments' => (int) session('open_comments', 0),
            'commentError' => session('comment_error', ''),
        ]);
    }

    /**
     * Like aan- of uitzetten.
     */
    public function like(Request $request, Activity $activity): RedirectResponse|JsonResponse
    {
        $activity->likes()->toggle($request->user()->id);

        if ($request->expectsJson()) {
            // Stond het reactiepaneel open? Dan blijft het open na het vernieuwen van de kaart.
            return $this->cardResponse($request, $activity, $request->boolean('open_comments'));
        }

        return $this->backToActivity($activity);
    }

    public function comment(Request $request, Activity $activity): RedirectResponse|JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'body' => ['required', 'string', 'max:1000'],
        ], [
            'body.required' => 'Schrijf eerst een reactie.',
            'body.max' => 'Een reactie mag maximaal 1000 tekens bevatten.',
            'body.*' => 'Schrijf eerst een reactie.',
        ]);

        if ($validator->fails() || trim((string) $request->input('body')) === '') {
            $error = $validator->errors()->first('body') ?: 'Schrijf eerst een reactie.';

            if ($request->expectsJson()) {
                return $this->cardResponse($request, $activity, true, $error, 422);
            }

            return $this->backToActivity($activity)
                ->withInput($request->only('body'))
                ->with(['open_comments' => $activity->id, 'comment_error' => $error]);
        }

        $activity->comments()->create([
            'user_id' => $request->user()->id,
            'body' => trim((string) $request->input('body')),
        ]);

        if ($request->expectsJson()) {
            return $this->cardResponse($request, $activity, true);
        }

        return $this->backToActivity($activity)->with('open_comments', $activity->id);
    }

    /**
     * Verwijdert een reactie; alleen de schrijver mag dat.
     */
    public function destroyComment(Request $request, ActivityComment $comment): RedirectResponse|JsonResponse
    {
        abort_unless($comment->user_id === $request->user()->id, 403);

        $comment->delete();

        if ($request->expectsJson()) {
            return $this->cardResponse($request, $comment->activity, true);
        }

        return $this->backToActivity($comment->activity)->with('open_comments', $comment->activity_id);
    }

    /**
     * Zonder herladen (fetch vanuit resources/js/community.js): de bijgewerkte kaart als HTML,
     * zodat het script die op dezelfde plek kan vervangen.
     */
    private function cardResponse(Request $request, Activity $activity, bool $openComments, string $commentError = '', int $status = 200): JsonResponse
    {
        $activity = Activity::query()->forFeed($request->user())->findOrFail($activity->id);

        $html = Blade::render(
            '<x-community-card :activity="$activity" :open-comments="$openComments" :comment-error="$commentError" :profile-from="$profileFrom" />',
            [
                'activity' => $activity,
                'openComments' => $openComments ? $activity->id : 0,
                'commentError' => $commentError,
                'profileFrom' => $request->input('profile_from') === 'ranglijst' ? 'ranglijst' : 'home',
            ],
        );

        return response()->json(['html' => $html, 'error' => $commentError], $status);
    }

    /**
     * Terug naar de pagina waar op de knop is gedrukt (home of community), bij dezelfde card.
     */
    private function backToActivity(Activity $activity): RedirectResponse
    {
        return redirect()->back(fallback: route('home'))->withFragment('activiteit-'.$activity->id);
    }
}
