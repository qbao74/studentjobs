<?php

namespace App\Providers;

use App\Services\Frontend\JoblyPayload;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Layout trang sinh viên luôn có window.JOBLY với dữ liệu thật từ database.
        View::composer('layout.app', function ($view) {
            $view->with('jobly', app(JoblyPayload::class)->build(auth()->user()));
        });
    }
}
