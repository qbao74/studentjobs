<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::view('/', 'home')->name('home');
Route::view('/explore', 'explore')->name('explore');
Route::view('/jobs', 'jobs')->name('jobs');
Route::view('/companies', 'companies')->name('companies');

Route::middleware(['auth', 'role:student'])->group(function () {
    Route::view('/match', 'match')->name('match');
    Route::view('/applications', 'applications')->name('applications');
    Route::view('/profile', 'profile')->name('profile');
    Route::view('/chat', 'chat')->name('chat');
});