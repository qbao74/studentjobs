<?php

namespace App\Providers;

use App\Enums\ApplicationStatus;
use App\Enums\Role;
use App\Models\Application;
use App\Models\Employer;
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

        // Khung khu nhà tuyển dụng / admin: menu tuỳ theo vai trò người đang đăng nhập.
        View::composer('layout.panel', function ($view) {
            $user = auth()->user();

            $view->with(match ($user->role) {
                Role::Employer => $this->employerPanel($user->employer),
                default => ['panelLabel' => $user->role->label(), 'panelSubtitle' => $user->email, 'nav' => []],
            });
        });
    }

    private function employerPanel(Employer $employer): array
    {
        $pending = Application::where('status', ApplicationStatus::Pending)
            ->whereHas('jobPost', fn ($q) => $q->where('company_id', $employer->company_id))
            ->count();

        return [
            'panelLabel' => 'Nhà tuyển dụng',
            'panelSubtitle' => $employer->company->name,
            'nav' => [
                ['route' => 'employer.dashboard', 'active' => 'employer.dashboard', 'label' => 'Tổng quan', 'icon' => 'layout-dashboard'],
                ['route' => 'employer.jobs.index', 'active' => 'employer.jobs.*', 'label' => 'Tin tuyển dụng', 'icon' => 'briefcase', 'badge' => $pending],
            ],
        ];
    }
}
