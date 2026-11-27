<?php

use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\ChallengeController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
| Admin-dashboard. Geladen in bootstrap/app.php met prefix /admin, routenamen admin.*
| en middleware web + auth + admin (alleen gebruikers met users.is_admin).
*/

Route::get('/', DashboardController::class)->name('dashboard');

Route::resource('gebruikers', UserController::class)
    ->except('show')
    ->parameters(['gebruikers' => 'user'])
    ->names('users');

Route::resource('challenges', ChallengeController::class)->except('show');

Route::get('/activiteiten', [ActivityController::class, 'index'])->name('activities.index');
Route::delete('/activiteiten/{activity}', [ActivityController::class, 'destroy'])->name('activities.destroy');

Route::get('/logs', [LogController::class, 'index'])->name('logs.index');
