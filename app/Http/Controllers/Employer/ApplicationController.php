<?php

namespace App\Http\Controllers\Employer;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\JobPost;
use App\Services\MessageService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function __construct(private MessageService $messages) {}

    public function index(Request $request): View
    {
        $companyId = $request->user()->employer->company_id;
        $status = $request->query('status');
        $jobId = $request->integer('job') ?: null;

        $applications = $this->forCompany($companyId)
            ->with(['student.user', 'jobPost', 'latestMessage'])
            ->when(in_array($status, ApplicationStatus::values(), true), fn (Builder $q) => $q->where('status', $status))
            ->when($jobId, fn (Builder $q) => $q->where('job_post_id', $jobId))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $jobs = JobPost::query()->where('company_id', $companyId)->orderBy('title')->get(['id', 'title']);

        return view('employer.applications.index', compact('applications', 'status', 'jobId', 'jobs'));
    }

    public function show(Request $request, Application $application): View
    {
        Gate::authorize('view', $application);

        if ($application->status === ApplicationStatus::Pending) {
            $application->update(['status' => ApplicationStatus::Viewed]);
        }

        $application->load(['student.user', 'student.skills', 'student.cv', 'jobPost']);
        $thread = $this->messages->thread($application, $request->user());

        return view('employer.applications.show', compact('application', 'thread'));
    }

    public function updateStatus(Request $request, Application $application): RedirectResponse
    {
        Gate::authorize('updateStatus', $application);

        $data = $request->validate([
            'status' => ['required', Rule::in(ApplicationStatus::values())],
        ]);

        $application->update(['status' => ApplicationStatus::from($data['status'])]);

        return back()->with('status', 'Đã cập nhật trạng thái đơn thành '.$application->status->label().'.');
    }

    public function storeMessage(Request $request, Application $application): RedirectResponse
    {
        Gate::authorize('message', $application);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ], [], ['body' => 'tin nhắn']);

        $body = trim($data['body']);

        if ($body === '') {
            return back()->withErrors(['body' => 'Tin nhắn không được để trống.'])->withInput();
        }

        $this->messages->send($application, $request->user(), $body);

        return back()->with('status', 'Đã gửi tin nhắn.');
    }

    public function chat(Request $request): View
    {
        $user = $request->user();
        $conversations = $this->conversations($user->employer->company_id, $user->id);

        $active = null;
        $thread = collect();
        $selected = $request->integer('c') ?: null;

        if ($selected) {
            $active = $conversations->firstWhere('id', $selected) ?? abort(404);
            Gate::authorize('message', $active);
            $thread = $this->messages->thread($active, $user);
            $active->setAttribute('unread_count', 0);
        }

        return view('employer.chat', compact('conversations', 'active', 'thread'));
    }

    /** @return Builder<Application> */
    private function forCompany(int $companyId): Builder
    {
        return Application::query()->whereHas('jobPost', fn (Builder $q) => $q->where('company_id', $companyId));
    }

    /** @return Collection<int, Application> */
    private function conversations(int $companyId, int $userId): Collection
    {
        return $this->forCompany($companyId)
            ->with(['student.user', 'jobPost', 'latestMessage'])
            ->withCount(['messages as unread_count' => fn (Builder $q) => $q->whereNull('read_at')->where('user_id', '!=', $userId)])
            ->get()
            ->sortByDesc(fn (Application $application) => $application->latestMessage?->created_at ?? $application->created_at)
            ->values();
    }
}
