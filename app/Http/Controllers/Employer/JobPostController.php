<?php

namespace App\Http\Controllers\Employer;

use App\Enums\ApplicationStatus;
use App\Enums\JobStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employer\JobPostRequest;
use App\Models\JobPost;
use App\Services\Employer\JobPostService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class JobPostController extends Controller
{
    public function __construct(private JobPostService $jobs) {}

    public function index(Request $request): View
    {
        $status = $request->query('status');

        $jobs = $request->user()->employer->company->jobPosts()
            ->when(in_array($status, array_column(JobStatus::cases(), 'value'), true), fn ($q) => $q->where('status', $status))
            ->withCount([
                'applications',
                'applications as pending_count' => fn ($q) => $q->where('status', ApplicationStatus::Pending),
            ])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('employer.jobs.index', compact('jobs', 'status'));
    }

    public function create(): View
    {
        Gate::authorize('create', JobPost::class);

        return view('employer.jobs.form', ['job' => new JobPost(['type' => 'Part-time'])]);
    }

    public function store(JobPostRequest $request): RedirectResponse
    {
        Gate::authorize('create', JobPost::class);

        $job = $this->jobs->create($request->user()->employer->company, $request->jobData());

        return redirect()->route('employer.jobs.index')->with('status', "Đã đăng tin \"{$job->title}\". Hệ thống đã tính điểm khớp với các sinh viên.");
    }

    public function edit(JobPost $job): View
    {
        Gate::authorize('update', $job);

        return view('employer.jobs.form', ['job' => $job->load('skills')]);
    }

    public function update(JobPostRequest $request, JobPost $job): RedirectResponse
    {
        Gate::authorize('update', $job);

        $this->jobs->update($job, $request->jobData());

        return redirect()->route('employer.jobs.index')->with('status', "Đã cập nhật tin \"{$job->title}\".");
    }

    public function updateStatus(Request $request, JobPost $job): RedirectResponse
    {
        Gate::authorize('update', $job);

        $data = $request->validate([
            'status' => ['required', Rule::in([JobStatus::Open->value, JobStatus::Closed->value])],
        ]);
        $status = JobStatus::from($data['status']);

        $this->jobs->setStatus($job, $status);

        return back()->with('status', $status === JobStatus::Open ? "Đã mở lại tin \"{$job->title}\"." : "Đã đóng tin \"{$job->title}\".");
    }

    public function destroy(JobPost $job): RedirectResponse
    {
        Gate::authorize('delete', $job);

        try {
            $this->jobs->delete($job);
        } catch (UnprocessableEntityHttpException $e) {
            return back()->withErrors(['job' => $e->getMessage()]);
        }

        return redirect()->route('employer.jobs.index')->with('status', "Đã xoá tin \"{$job->title}\".");
    }
}
