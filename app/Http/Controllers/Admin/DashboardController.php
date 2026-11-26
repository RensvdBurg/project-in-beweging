<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\AuditLog;
use App\Models\Challenge;
use App\Models\ChallengeCompletion;
use App\Models\User;
use Illuminate\View\View;

/**
 * Startpagina van het admin-dashboard: totalen en de nieuwste logregels.
 */
class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'stats' => [
                ['label' => 'Gebruikers', 'value' => User::query()->count(), 'route' => 'admin.users.index'],
                ['label' => 'Challenges', 'value' => Challenge::query()->count(), 'route' => 'admin.challenges.index'],
                ['label' => 'Activiteiten', 'value' => Activity::query()->count(), 'route' => 'admin.activities.index'],
                ['label' => 'Voltooid vandaag', 'value' => ChallengeCompletion::query()->where('created_at', '>=', today())->count(), 'route' => 'admin.logs.index'],
            ],
            'newUsersThisWeek' => User::query()->where('created_at', '>=', now()->subDays(7))->count(),
            'totalXp' => (int) User::query()->sum('xp'),
            'latestUsers' => User::query()->latest()->latest('id')->limit(5)->get(['id', 'username', 'full_name', 'email', 'created_at']),
            'latestLogs' => AuditLog::query()->with('user:id,username')->latest()->latest('id')->limit(8)->get(),
        ]);
    }
}
