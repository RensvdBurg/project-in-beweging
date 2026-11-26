<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChallengeRequest;
use App\Models\AuditLog;
use App\Models\Challenge;
use App\Services\ChallengeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Challenges bekijken, toevoegen, bewerken en verwijderen.
 */
class ChallengeController extends Controller
{
    private const PER_PAGE = 15;

    public function __construct(private readonly ChallengeService $challenges) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $challenges = Challenge::query()
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.mb_strtolower($search).'%';
                $query->where(fn ($query) => $query
                    ->whereRaw('LOWER(title) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(description_extra) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(difficulty) LIKE ?', [$like]));
            })
            ->withCount('completions')
            ->orderBy('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.challenges.index', ['challenges' => $challenges, 'search' => $search]);
    }

    public function create(): View
    {
        return view('admin.challenges.form', [
            'challenge' => new Challenge(['difficulty' => 'Beginner', 'kcal_burned' => 5, 'xp_reward' => 5]),
            'difficulties' => Challenge::DIFFICULTIES,
        ]);
    }

    public function store(ChallengeRequest $request): RedirectResponse
    {
        $challenge = Challenge::query()->create($request->challengeData());

        AuditLog::record('admin_challenge_created', "{$request->user()->username} heeft challenge \"{$challenge->title}\" toegevoegd.", $request->user());

        return redirect()->route('admin.challenges.index')->with('admin_status', "Challenge \"{$challenge->title}\" is toegevoegd.");
    }

    public function edit(Challenge $challenge): View
    {
        return view('admin.challenges.form', [
            'challenge' => $challenge,
            'difficulties' => Challenge::DIFFICULTIES,
        ]);
    }

    public function update(ChallengeRequest $request, Challenge $challenge): RedirectResponse
    {
        $challenge->update($request->challengeData());

        AuditLog::record('admin_challenge_updated', "{$request->user()->username} heeft challenge \"{$challenge->title}\" bewerkt.", $request->user());

        return redirect()->route('admin.challenges.index')->with('admin_status', "Challenge \"{$challenge->title}\" is bijgewerkt.");
    }

    public function destroy(Request $request, Challenge $challenge): RedirectResponse
    {
        $title = $challenge->title;
        $this->challenges->delete($challenge);

        AuditLog::record('admin_challenge_deleted', "{$request->user()->username} heeft challenge \"{$title}\" verwijderd.", $request->user());

        return redirect()->route('admin.challenges.index')->with('admin_status', "Challenge \"{$title}\" is verwijderd.");
    }
}
