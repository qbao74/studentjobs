<?php

namespace App\Providers;

use App\Enums\ApplicationStatus;
use App\Enums\JobStatus;
use App\Enums\Role;
use App\Models\Application;
use App\Models\Company;
use App\Models\Employer;
use App\Models\JobPost;
use App\Models\Message;
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
                Role::Admin => $this->adminPanel(),
                default => ['panelLabel' => $user->role->label(), 'panelSubtitle' => $user->email, 'nav' => []],
            });
        });
    }

    /** @return array{panelLabel: string, panelSubtitle: string, nav: list<array<string, mixed>>} */
    private function adminPanel(): array
    {
        return [
            'panelLabel' => 'Quản trị',
            'panelSubtitle' => 'Toàn hệ thống',
            'nav' => [
                ['route' => 'admin.dashboard', 'active' => 'admin.dashboard', 'label' => 'Tổng quan', 'icon' => 'layout-dashboard'],
                ['route' => 'admin.users.index', 'active' => 'admin.users.*', 'label' => 'Tài khoản', 'icon' => 'users'],
                ['route' => 'admin.companies.index', 'active' => 'admin.companies.*', 'label' => 'Công ty', 'icon' => 'building-2', 'badge' => Company::query()->where('verified', false)->count()],
                ['route' => 'admin.jobs.index', 'active' => 'admin.jobs.*', 'label' => 'Tin tuyển dụng', 'icon' => 'briefcase', 'badge' => JobPost::query()->where('status', JobStatus::Hidden)->count()],
                ['route' => 'admin.applications.index', 'active' => 'admin.applications.*', 'label' => 'Đơn ứng tuyển', 'icon' => 'inbox', 'badge' => Application::query()->where('status', ApplicationStatus::Pending)->count()],
            ],
        ];
    }

    private function employerPanel(Employer $employer): array
    {
        $pending = Application::where('status', ApplicationStatus::Pending)
            ->whereHas('jobPost', fn ($q) => $q->where('company_id', $employer->company_id))
            ->count();

        $unread = Message::query()
            ->whereNull('read_at')
            ->where('user_id', '!=', $employer->user_id)
            ->whereHas('application.jobPost', fn ($q) => $q->where('company_id', $employer->company_id))
            ->count();

        return [
            'panelLabel' => 'Nhà tuyển dụng',
            'panelSubtitle' => $employer->company->name,
            'nav' => [
                ['route' => 'employer.dashboard', 'active' => 'employer.dashboard', 'label' => 'Tổng quan', 'icon' => 'layout-dashboard'],
                ['route' => 'employer.jobs.index', 'active' => 'employer.jobs.*', 'label' => 'Tin tuyển dụng', 'icon' => 'briefcase'],
                ['route' => 'employer.applications.index', 'active' => 'employer.applications.*', 'label' => 'Đơn ứng tuyển', 'icon' => 'users', 'badge' => $pending],
                ['route' => 'employer.chat', 'active' => 'employer.chat', 'label' => 'Tin nhắn', 'icon' => 'message-circle', 'badge' => $unread],
            ],
        ];
    }
}
