<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\JobStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Company;
use App\Models\JobPost;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'stats' => [
                'users' => User::query()->count(),
                'unverified' => Company::query()->where('verified', false)->count(),
                'openJobs' => JobPost::query()->where('status', JobStatus::Open)->count(),
                'pending' => Application::query()->where('status', ApplicationStatus::Pending)->count(),
            ],
            'recentUsers' => User::query()->latest()->limit(6)->get(),
            'recentJobs' => JobPost::query()->with('company')->withCount('applications')->latest()->limit(5)->get(),
            'roles' => [
                Role::Student->value => User::query()->where('role', Role::Student)->count(),
                Role::Employer->value => User::query()->where('role', Role::Employer)->count(),
                Role::Admin->value => User::query()->where('role', Role::Admin)->count(),
            ],
        ]);
    }
}
