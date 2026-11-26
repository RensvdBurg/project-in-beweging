<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChallengeController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::post('/register', [AuthController::class, 'register'])->name('register');
});

// Afbeelding van een activiteit: ondertekende URL zonder sessie/cookies, zodat elke
// afbeelding een zo licht mogelijk request is (de app kan storage/ niet direct serveren).
Route::get('/activiteiten/{activity}/afbeelding', [ActivityController::class, 'image'])
    ->name('activities.image')
    ->whereNumber('activity')
    ->middleware('signed')
    ->withoutMiddleware('web');

Route::middleware('auth')->group(function () {
    Route::get('/', HomeController::class)->name('home');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/leren/{challenge}', [ChallengeController::class, 'show'])->name('challenges.show');
    Route::post('/challenges/next', [ChallengeController::class, 'next'])->name('challenges.next');
    Route::post('/challenges/complete', [ChallengeController::class, 'complete'])->name('challenges.complete');

    Route::get('/activiteiten', [ActivityController::class, 'index'])->name('activities.index');
    Route::post('/activiteiten', [ActivityController::class, 'store'])->name('activities.store');
    Route::get('/activiteiten/overzicht', [ActivityController::class, 'all'])->name('activities.all');

    Route::get('/community', [CommunityController::class, 'index'])->name('community.index');
    Route::post('/activiteiten/{activity}/like', [CommunityController::class, 'like'])->name('activities.like');
    Route::post('/activiteiten/{activity}/reacties', [CommunityController::class, 'comment'])->name('activities.comment');
    Route::delete('/reacties/{comment}', [CommunityController::class, 'destroyComment'])->name('comments.destroy');

    Route::view('/statistieken', 'statistics.index')->name('statistics');

    Route::get('/profiel/{user}', [ProfileController::class, 'show'])->name('profile.show');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::get('/settings/account', [AccountController::class, 'show'])->name('account');
    Route::put('/settings/account/profile', [AccountController::class, 'updateProfile'])->name('account.profile');
    Route::put('/settings/account/about', [AccountController::class, 'updateAboutMe'])->name('account.about');
    Route::put('/settings/account/password', [AccountController::class, 'updatePassword'])->name('account.password');
    Route::delete('/settings/account', [AccountController::class, 'destroy'])->name('account.destroy');
});
