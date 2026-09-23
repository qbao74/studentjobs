<?php

use App\Http\Controllers\Api\ApplicationController;
use App\Http\Controllers\Api\BookmarkController;
use App\Http\Controllers\Api\CvController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CvDownloadController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:10,1')->name('register.store');

    Route::get('/register/employer', [RegisterController::class, 'createEmployer'])->name('register.employer');
    Route::post('/register/employer', [RegisterController::class, 'store'])->middleware('throttle:10,1')->name('register.employer.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('student.or.guest')->group(function () {
    Route::view('/', 'home')->name('home');
    Route::view('/explore', 'explore')->name('explore');
    Route::view('/jobs', 'jobs')->name('jobs');
    Route::view('/companies', 'companies')->name('companies');
});

/*
| API cho JavaScript trang sinh viên. Nằm trong nhóm web để dùng chung session + CSRF (header X-CSRF-TOKEN).
| Chưa đăng nhập → 401 JSON, JS sẽ mở popup đăng nhập.
*/
Route::prefix('api')->name('api.')->middleware(['auth', 'role:student', 'throttle:120,1'])->group(function () {
    Route::post('/jobs/{job}/apply', [ApplicationController::class, 'store'])->name('jobs.apply');
    Route::delete('/applications/{application}', [ApplicationController::class, 'destroy'])->name('applications.destroy');

    Route::post('/jobs/{job}/save', [BookmarkController::class, 'toggleJob'])->name('jobs.save');
    Route::post('/companies/{company:slug}/follow', [BookmarkController::class, 'toggleCompany'])->name('companies.follow');

    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/skills', [ProfileController::class, 'skills'])->name('profile.skills');
    Route::post('/profile/avatar', [ProfileController::class, 'avatar'])->name('profile.avatar');
    Route::post('/profile/cv', [CvController::class, 'store'])->middleware('throttle:10,1')->name('profile.cv.store');
    Route::delete('/profile/cv', [CvController::class, 'destroy'])->name('profile.cv.destroy');
});

// Tin nhắn dùng chung cho sinh viên và nhà tuyển dụng; quyền kiểm tra bằng ApplicationPolicy::message.
Route::prefix('api')->name('api.')->middleware(['auth', 'throttle:120,1'])->group(function () {
    Route::get('/applications/{application}/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::post('/applications/{application}/messages', [MessageController::class, 'store'])->middleware('throttle:30,1')->name('messages.store');
});

Route::get('/cvs/{cv}/download', CvDownloadController::class)->middleware('auth')->name('cvs.download');

Route::middleware(['auth', 'role:student'])->group(function () {
    Route::view('/match', 'match')->name('match');
    Route::view('/applications', 'applications')->name('applications');
    Route::view('/profile', 'profile')->name('profile');
    Route::view('/chat', 'chat')->name('chat');
});
