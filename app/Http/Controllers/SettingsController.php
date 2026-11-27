<?php

namespace App\Http\Controllers;

use App\Auth\ApiSessionGuard;
use App\Services\Api\ApiException;
use App\Services\Api\UpMoveApi;
use App\Services\PhoneNotifications;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(private readonly UpMoveApi $api) {}

    /**
     * De instellingen staan per gebruiker op de server (users.settings);
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
        $fields = ['frequency', 'dailyGoal', 'darkMode', 'workOnly', 'workStart', 'workEnd', 'workDays', 'workSchedule'];

        try {
            $settings = $this->api->updateSettings($request->only($fields));
        } catch (ApiException $exception) {
            if (! $exception->isValidationError()) {
                throw $exception;
            }

            return response()->json(['message' => $exception->getMessage(), 'errors' => $exception->errors], 422);
        }

        // De nieuwe instellingen ook in de sessie, zodat de layout (dark mode) direct klopt.
        /** @var ApiSessionGuard $guard */
        $guard = Auth::guard();
        $user = $guard->merge(['settings' => $settings]) ?? $request->user();
        $user->settings = $settings;

        // Direct opnieuw plannen (vraagt zo nodig toestemming voor meldingen).
        $notifications->sync($user, force: true);

        return response()->json(['settings' => $settings]);
    }
}
