<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApplicationController;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/apply/{jobId}', [ApplicationController::class, 'apply'])->name('apply');
Route::get('/my-applications', [ApplicationController::class, 'history'])->name('applications.history');
Route::get('/jobs', [ApplicationController::class, 'listJobs'])->name('jobs.list');
