<?php

namespace App\Http\Controllers\Employer;

use App\Enums\ApplicationStatus;
use App\Enums\JobStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $company = $request->user()->employer->company;
        $applications = Application::whereHas('jobPost', fn ($q) => $q->where('company_id', $company->id));

        $stats = [
            'openJobs' => $company->jobPosts()->where('status', JobStatus::Open)->count(),
            'applications' => (clone $applications)->count(),
            'pending' => (clone $applications)->where('status', ApplicationStatus::Pending)->count(),
            'interview' => (clone $applications)->where('status', ApplicationStatus::Interview)->count(),
        ];

        $recent = (clone $applications)
            ->with(['student.user', 'jobPost'])
            ->latest()
            ->limit(6)
            ->get();

        $jobs = $company->jobPosts()
            ->withCount([
                'applications',
                'applications as pending_count' => fn ($q) => $q->where('status', ApplicationStatus::Pending),
            ])
            ->latest()
            ->limit(5)
            ->get();

        return view('employer.dashboard', compact('company', 'stats', 'recent', 'jobs'));
    }
}
