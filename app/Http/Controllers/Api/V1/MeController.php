<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\AuditLog;
use App\Support\UserSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * De ingelogde gebruiker: profiel, "over mij", wachtwoord, instellingen en account verwijderen.
 */
class MeController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => new UserResource($user),
            'rank' => $user->leaderboardRank(),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'username' => ['bail', 'required', 'string', 'regex:/\A[A-Za-z0-9_.-]{3,30}\z/', AuthController::uniqueIgnoringCase('username', $user->id)],
            'email' => ['bail', 'required', 'string', 'email', 'max:254', AuthController::uniqueIgnoringCase('email', $user->id)],
        ], [
            'full_name.*' => 'Vul een naam van maximaal 150 tekens in.',
            'username.required' => AuthController::USERNAME_MESSAGE,
            'username.string' => AuthController::USERNAME_MESSAGE,
            'username.regex' => AuthController::USERNAME_MESSAGE,
            'email.required' => 'Vul een geldig e-mailadres in.',
            'email.string' => 'Vul een geldig e-mailadres in.',
            'email.email' => 'Vul een geldig e-mailadres in.',
            'email.max' => 'Vul een geldig e-mailadres in.',
        ]);

        $user->update([
            'full_name' => trim($data['full_name']),
            'username' => trim($data['username']),
            'email' => trim($data['email']),
        ]);

        return response()->json(['message' => 'Je profiel is bijgewerkt.', 'user' => new UserResource($user)]);
    }

    public function updateAbout(Request $request): JsonResponse
    {
        $request->validate(
            ['about_me' => ['nullable', 'string', 'max:500']],
            ['about_me.*' => 'Je stukje over jezelf mag maximaal 500 tekens bevatten.'],
        );

        $user = $request->user();
        $user->forceFill(['about_me' => trim((string) $request->input('about_me', ''))])->save();

        return response()->json(['message' => 'Je stukje over jezelf is opgeslagen.', 'user' => new UserResource($user)]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();
        $newPassword = (string) $request->input('new_password', '');

        $message = match (true) {
            ! Hash::check((string) $request->input('current_password', ''), $user->password) => ['current_password', 'Je huidige wachtwoord is onjuist.'],
            strlen($newPassword) < 8 || strlen($newPassword) > 72 => ['new_password', 'Je nieuwe wachtwoord moet minimaal 8 en maximaal 72 letters bevatten.'],
            $newPassword !== (string) $request->input('new_password_confirmation', '') => ['new_password_confirmation', 'De nieuwe wachtwoorden komen niet overeen.'],
            default => null,
        };

        if ($message !== null) {
            throw ValidationException::withMessages([$message[0] => $message[1]]);
        }

        $user->update(['password' => $newPassword]);

        // Andere telefoons uitloggen; deze blijft ingelogd.
        $user->tokens()->whereKeyNot($user->currentAccessToken()->getKey())->delete();

        return response()->json(['message' => 'Je wachtwoord is gewijzigd.']);
    }

    public function updateSettings(Request $request): JsonResponse
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
            'workSchedule' => ['sometimes', 'array', 'size:7'],
            'workSchedule.*.start' => ['required_with:workSchedule', 'date_format:H:i'],
            'workSchedule.*.end' => ['required_with:workSchedule', 'date_format:H:i'],
        ]);

        $settings = UserSettings::normalize($validated);
        $request->user()->forceFill(['settings' => $settings])->save();

        return response()->json(['settings' => $settings]);
    }

    public function destroy(Request $request): JsonResponse
    {
        if (! $request->boolean('confirm_delete')) {
            throw ValidationException::withMessages(['confirm_delete' => 'Bevestig dat je je account permanent wilt verwijderen.']);
        }

        $user = $request->user();
        AuditLog::record('account_deleted', "{$user->username} heeft het eigen account verwijderd.");
        $user->deleteAccount();

        return response()->json(['message' => 'Je account is verwijderd.']);
    }
}
