<?php

use App\Http\Controllers\Api\V1\ActivityController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ChallengeController;
use App\Http\Controllers\Api\V1\LeaderboardController;
use App\Http\Controllers\Api\V1\MeController;
use Illuminate\Support\Facades\Route;

/*
| API voor de app (prefix /api/v1, routenamen api.v1.*). De app logt in met POST /login
| en stuurt daarna het token mee als "Authorization: Bearer <token>".
| Overzicht van alle endpoints: docs/08-api.md.
*/

Route::prefix('v1')->name('api.v1.')->middleware('throttle:api')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('/me', [MeController::class, 'show'])->name('me');
        Route::put('/me/profile', [MeController::class, 'updateProfile'])->name('me.profile');
        Route::put('/me/about', [MeController::class, 'updateAbout'])->name('me.about');
        Route::put('/me/password', [MeController::class, 'updatePassword'])->name('me.password');
        Route::put('/me/settings', [MeController::class, 'updateSettings'])->name('me.settings');
        Route::delete('/me', [MeController::class, 'destroy'])->name('me.destroy');
        Route::get('/me/activities', [ActivityController::class, 'mine'])->name('me.activities');

        Route::get('/challenges', [ChallengeController::class, 'index'])->name('challenges.index');
        Route::get('/challenges/current', [ChallengeController::class, 'current'])->name('challenges.current');
        Route::post('/challenges/next', [ChallengeController::class, 'next'])->name('challenges.next');
        Route::post('/challenges/complete', [ChallengeController::class, 'complete'])->name('challenges.complete');
        Route::get('/challenges/{challenge}', [ChallengeController::class, 'show'])->whereNumber('challenge')->name('challenges.show');

        Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard');
        Route::get('/users/{user}', [LeaderboardController::class, 'profile'])->whereNumber('user')->name('users.show');
        Route::get('/users/{user}/activities', [LeaderboardController::class, 'activities'])->whereNumber('user')->name('users.activities');

        Route::get('/activities', [ActivityController::class, 'feed'])->name('activities.index');
        Route::post('/activities', [ActivityController::class, 'store'])->name('activities.store');
        Route::get('/activities/{activity}', [ActivityController::class, 'show'])->whereNumber('activity')->name('activities.show');
        Route::post('/activities/{activity}/like', [ActivityController::class, 'like'])->whereNumber('activity')->name('activities.like');
        Route::post('/activities/{activity}/comments', [ActivityController::class, 'comment'])->whereNumber('activity')->name('activities.comment');
        Route::delete('/comments/{comment}', [ActivityController::class, 'destroyComment'])->whereNumber('comment')->name('comments.destroy');
    });
});
