<?php

namespace App\Http\Controllers;

use App\Services\PhoneNotifications;
use App\Support\UserSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * De instellingen staan per gebruiker in de database (users.settings);
     * resources/js/settings-page.js laadt ze uit data-settings en slaat elke
     * wijziging op via settings.update.
     */
    public function index(Request $request, PhoneNotifications $notifications): View
    {
        $user = $request->user();

        return view('settings.index', [
            'user' => $user,
            // null = nog nooit opgeslagen: de pagina neemt dan de instellingen uit
            // localStorage over (van vóór de database) en slaat ze direct op.
            'settings' => $user->settings !== null ? $user->preferences() : null,
            // Meldingen: null in de browser, anders 'granted' / 'denied' / … van de telefoon.
            'phoneNotifications' => $notifications->isAvailable(),
            'notificationPermission' => $notifications->permissionStatus(),
        ]);
    }

    public function update(Request $request, PhoneNotifications $notifications): JsonResponse
    {
        $validated = $request->validate([
            'frequency' => ['required', 'integer', Rule::in(UserSettings::FREQUENCIES)],
            'dailyGoal' => ['required', 'integer', 'between:1,30'],
            'darkMode' => ['required', 'boolean:strict'],
            'workOnly' => ['required', 'boolean:strict'],
            'workStart' => ['required', 'date_format:H:i'],
            'workEnd' => ['required', 'date_format:H:i'],
            'workDays' => ['required', 'array', 'min:1', 'max:7'],
            'workDays.*' => ['integer', 'between:0,6', 'distinct'],
            // Tijden per dag (0 = zondag … 6 = zaterdag). Optioneel voor oudere pagina's.
            'workSchedule' => ['sometimes', 'array', 'size:7'],
            'workSchedule.*.start' => ['required_with:workSchedule', 'date_format:H:i'],
            'workSchedule.*.end' => ['required_with:workSchedule', 'date_format:H:i'],
        ]);

        $settings = UserSettings::normalize($validated);

        $request->user()->forceFill(['settings' => $settings])->save();

        // Direct opnieuw plannen (vraagt zo nodig toestemming voor meldingen).
        $notifications->sync($request->user(), force: true);

        return response()->json(['settings' => $settings]);
    }
}
