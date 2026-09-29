<?php

namespace App\Http\Controllers\Admin;

use App\Enums\JobStatus;
use App\Http\Controllers\Controller;
use App\Models\JobPost;
use App\Services\Employer\JobPostService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class JobController extends Controller
{
    public function __construct(private JobPostService $jobs) {}

    public function index(Request $request): View
    {
        $status = $request->query('status');
        $q = trim((string) $request->query('q', ''));

        $jobs = JobPost::query()
            ->with('company')
            ->withCount('applications')
            ->when(in_array($status, array_column(JobStatus::cases(), 'value'), true), fn ($query) => $query->where('status', $status))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('title', 'like', "%{$q}%")
                        ->orWhereHas('company', fn ($company) => $company->where('name', 'like', "%{$q}%"));
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.jobs.index', compact('jobs', 'status', 'q'));
    }

    public function show(JobPost $job): View
    {
        $job->load(['company', 'skills'])->loadCount('applications');

        return view('admin.jobs.show', compact('job'));
    }

    public function updateStatus(Request $request, JobPost $job): RedirectResponse
    {
        Gate::authorize('moderate', JobPost::class);

        $data = $request->validate([
            'status' => ['required', Rule::enum(JobStatus::class)],
        ]);

        $status = JobStatus::from($data['status']);
        $this->jobs->moderate($job, $status);

        return back()->with('status', "Đã chuyển \"{$job->title}\" sang {$status->label()}.");
    }
}
