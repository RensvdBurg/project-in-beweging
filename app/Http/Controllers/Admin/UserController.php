<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Gebruikers bekijken, aanmaken, bewerken en verwijderen.
 */
class UserController extends Controller
{
    private const PER_PAGE = 15;

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $users = User::query()
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.mb_strtolower($search).'%';
                $query->where(fn ($query) => $query
                    ->whereRaw('LOWER(username) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(email) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(full_name) LIKE ?', [$like]));
            })
            ->withCount('activities')
            ->latest()
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.users.index', ['users' => $users, 'search' => $search]);
    }

    public function create(): View
    {
        return view('admin.users.form', ['user' => new User(['birth_date' => null])]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $user = new User;
        $this->fill($user, $request);
        $user->save();

        AuditLog::record('admin_user_created', "{$request->user()->username} heeft gebruiker {$user->username} aangemaakt.", $request->user());

        return redirect()->route('admin.users.index')->with('admin_status', "Gebruiker {$user->username} is aangemaakt.");
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', ['user' => $user]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $this->fill($user, $request);
        $changed = array_keys($user->getDirty());
        $user->save();

        $fields = $changed === [] ? 'niets gewijzigd' : implode(', ', array_map(fn (string $field): string => $field === 'password' ? 'wachtwoord' : $field, $changed));
        AuditLog::record('admin_user_updated', "{$request->user()->username} heeft gebruiker {$user->username} bewerkt ({$fields}).", $request->user());

        return redirect()->route('admin.users.index')->with('admin_status', "Gebruiker {$user->username} is bijgewerkt.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        // Jezelf verwijderen kan via Instellingen > Account; zo sluit je jezelf niet per ongeluk buiten.
        if ($user->is($request->user())) {
            return redirect()->route('admin.users.index')
                ->with(['admin_status' => 'Je kunt je eigen account hier niet verwijderen.', 'admin_status_type' => 'error']);
        }

        $username = $user->username;
        $user->deleteAccount();

        AuditLog::record('admin_user_deleted', "{$request->user()->username} heeft gebruiker {$username} verwijderd.", $request->user());

        return redirect()->route('admin.users.index')->with('admin_status', "Gebruiker {$username} is verwijderd.");
    }

    private function fill(User $user, UserRequest $request): void
    {
        $user->forceFill([
            'full_name' => trim($request->string('full_name')),
            'username' => trim($request->string('username')),
            'email' => trim($request->string('email')),
            'birth_date' => $request->date('birth_date')?->toDateString(),
            'xp' => $request->integer('xp'),
            'about_me' => trim((string) $request->input('about_me', '')),
            // Je eigen beheerdersrechten kun je niet weghalen (anders kom je er niet meer in).
            'is_admin' => $user->is($request->user()) ? true : $request->boolean('is_admin'),
        ]);

        if ($request->filled('password')) {
            $user->password = (string) $request->input('password');
        }
    }
}
