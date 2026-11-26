<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Alle activiteiten van alle gebruikers bekijken en verwijderen.
 */
class ActivityController extends Controller
{
    private const PER_PAGE = 15;

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $activities = Activity::query()
            ->with('user:id,username,full_name')
            ->withCount(['likes', 'comments'])
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.mb_strtolower($search).'%';
                $query->where(fn ($query) => $query
                    ->whereRaw('LOWER(title) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(description) LIKE ?', [$like])
                    ->orWhereHas('user', fn ($query) => $query->whereRaw('LOWER(username) LIKE ?', [$like])));
            })
            ->latest()
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.activities.index', ['activities' => $activities, 'search' => $search]);
    }

    public function destroy(Request $request, Activity $activity): RedirectResponse
    {
        $activity->loadMissing('user:id,username');
        $title = $activity->title;
        $owner = $activity->user->username ?? 'onbekend';

        // Activity::booted() verwijdert ook de afbeelding; reacties en likes gaan via cascade.
        $activity->delete();

        AuditLog::record('admin_activity_deleted', "{$request->user()->username} heeft de activiteit \"{$title}\" van {$owner} verwijderd.", $request->user());

        return back()->with('admin_status', "Activiteit \"{$title}\" is verwijderd.");
    }
}
