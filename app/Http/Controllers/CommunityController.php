<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Services\Api\ApiException;
use App\Services\Api\UpMoveApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\View;

/**
 * "Samen in beweging": activiteiten van alle gebruikers, met likes en reacties (via de API).
 * De eerste vijf staan op home; bij meer dan vijf is er een eigen pagina.
 */
class CommunityController extends Controller
{
    public const PER_PAGE = 5;

    public function __construct(private readonly UpMoveApi $api) {}

    public function index(Request $request): View|RedirectResponse
    {
        $activities = $this->api->feed(max(1, $request->integer('page', 1)), self::PER_PAGE);

        if ($activities->total() <= self::PER_PAGE) {
            return redirect()->route('home');
        }

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
    public function like(Request $request, int $activity): RedirectResponse|JsonResponse
    {
        $updated = $this->api->toggleLike($activity);

        if ($request->expectsJson()) {
            // Stond het reactiepaneel open? Dan blijft het open na het vernieuwen van de kaart.
            return $this->cardResponse($request, $updated, $request->boolean('open_comments'));
        }

        return $this->backToActivity($activity);
    }

    public function comment(Request $request, int $activity): RedirectResponse|JsonResponse
    {
        $body = trim((string) $request->input('body', ''));

        try {
            $updated = $this->api->comment($activity, $body);
        } catch (ApiException $exception) {
            if (! $exception->isValidationError()) {
                throw $exception;
            }

            $error = $exception->firstError('body') ?: 'Schrijf eerst een reactie.';

            if ($request->expectsJson()) {
                return $this->cardResponse($request, $this->api->activity($activity), true, $error, 422);
            }

            return $this->backToActivity($activity)
                ->withInput($request->only('body'))
                ->with(['open_comments' => $activity, 'comment_error' => $error]);
        }

        if ($request->expectsJson()) {
            return $this->cardResponse($request, $updated, true);
        }

        return $this->backToActivity($activity)->with('open_comments', $activity);
    }

    /**
     * Verwijdert een reactie; alleen de schrijver mag dat (de server controleert het).
     */
    public function destroyComment(Request $request, int $comment): RedirectResponse|JsonResponse
    {
        try {
            $activity = $this->api->deleteComment($comment);
        } catch (ApiException $exception) {
            abort_if($exception->status === 403, 403, $exception->getMessage());
            abort_if($exception->status === 404, 404);

            throw $exception;
        }

        if ($request->expectsJson()) {
            return $this->cardResponse($request, $activity, true);
        }

        return $this->backToActivity($activity->id)->with('open_comments', $activity->id);
    }

    /**
     * Zonder herladen (fetch vanuit resources/js/community.js): de bijgewerkte kaart als HTML,
     * zodat het script die op dezelfde plek kan vervangen.
     */
    private function cardResponse(Request $request, Activity $activity, bool $openComments, string $commentError = '', int $status = 200): JsonResponse
    {
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
    private function backToActivity(int $activityId): RedirectResponse
    {
        return redirect()->back(fallback: route('home'))->withFragment('activiteit-'.$activityId);
    }
}
