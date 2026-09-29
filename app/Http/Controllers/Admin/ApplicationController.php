<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $q = trim((string) $request->query('q', ''));

        $applications = Application::query()
            ->with(['student.user', 'jobPost.company'])
            ->when(in_array($status, ApplicationStatus::values(), true), fn ($query) => $query->where('status', $status))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->whereHas('student.user', fn ($user) => $user->where('name', 'like', "%{$q}%"))
                        ->orWhereHas('jobPost', fn ($job) => $job->where('title', 'like', "%{$q}%"));
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.applications.index', compact('applications', 'status', 'q'));
    }

    public function show(Application $application): View
    {
        Gate::authorize('view', $application);

        $application->load(['student.user', 'student.skills', 'student.cv', 'jobPost.company']);
        $thread = $application->messages()->with('sender')->oldest()->limit(200)->get();

        return view('admin.applications.show', compact('application', 'thread'));
    }
}
