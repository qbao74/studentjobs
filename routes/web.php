<?php

use Illuminate\Support\Facades\Route;

Route::view('/','home')->name('home');

Route::view('/explore','explore')->name('explore');

Route::view('/jobs', 'jobs')->name('jobs');

Route::view('/match', 'match')->name('match');

Route::view('/applications', 'applications')->name('applications');

Route::view('/profile', 'profile')->name('profile');

Route::view('/companies', 'companies')->name('companies');

Route::view('/chat', 'chat')->name('chat');