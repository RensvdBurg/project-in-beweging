<?php

namespace App\Http\Controllers;

use App\Auth\ApiSessionGuard;
use App\Services\Api\ApiException;
use App\Services\Api\UpMoveApi;
use App\Services\PhoneNotifications;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Accountinstellingen via de API: profiel, "over mij", wachtwoord en account verwijderen.
 */
class AccountController extends Controller
{
    public function __construct(private readonly UpMoveApi $api) {}

    public function show(Request $request): View
    {
        return view('settings.account', [
            'accountUser' => $request->user(),
            'statusMessage' => session('status_message', ''),
            'statusType' => session('status_type', 'success'),
            'passwordFormOpen' => (bool) session('password_form_open', false),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        try {
            $result = $this->api->updateProfile(
                trim((string) $request->input('name', '')),
                trim((string) $request->input('username', '')),
                trim((string) $request->input('email', '')),
            );
        } catch (ApiException $exception) {
            return $this->failed($exception)->withInput();
        }

        $this->guard()->remember($result['user']);

        return $this->back($result['message']);
    }

    /**
     * "Over mij": zichtbaar op het profiel (via de ranglijst of een activiteit). Mag leeg zijn.
     */
    public function updateAboutMe(Request $request): RedirectResponse
    {
        try {
            $result = $this->api->updateAbout(trim((string) $request->input('about_me', '')));
        } catch (ApiException $exception) {
            return $this->failed($exception)->withInput($request->only('about_me'));
        }

        $this->guard()->remember($result['user']);

        return $this->back($result['message']);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        try {
            $message = $this->api->updatePassword(
                (string) $request->input('current_password', ''),
                (string) $request->input('new_password', ''),
                (string) $request->input('confirm_password', ''),
            );
        } catch (ApiException $exception) {
            return $this->failed($exception)->with('password_form_open', true);
        }

        return $this->back($message)->with('password_form_open', true);
    }

    public function destroy(Request $request, PhoneNotifications $notifications): RedirectResponse
    {
        if ($request->input('confirm_delete') !== '1') {
            return $this->back('Bevestig dat je je account permanent wilt verwijderen.', 'error');
        }

        $this->api->deleteAccount();

        $notifications->clear();
        $this->guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Fout van de API als melding op de accountpagina. Geen verbinding of uitgelogd
     * gaat naar de algemene afhandeling (bootstrap/app.php).
     */
    private function failed(ApiException $exception): RedirectResponse
    {
        if ($exception->isUnauthenticated() || $exception->isConnectionError()) {
            throw $exception;
        }

        return $this->back($exception->firstError(), 'error');
    }

    private function guard(): ApiSessionGuard
    {
        /** @var ApiSessionGuard $guard */
        $guard = Auth::guard();

        return $guard;
    }

    private function back(string $message, string $type = 'success'): RedirectResponse
    {
        return redirect()->route('account')->with([
            'status_message' => $message,
            'status_type' => $type,
        ]);
    }
}
