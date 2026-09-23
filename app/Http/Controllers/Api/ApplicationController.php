<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\JobPost;
use App\Services\Frontend\StudentPresenter;
use App\Services\Student\ApplicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ApplicationController extends Controller
{
    public function __construct(
        private ApplicationService $applications,
        private StudentPresenter $presenter,
    ) {}

    public function store(Request $request, JobPost $job): JsonResponse
    {
        $application = $this->applications->apply($request->user()->student, $job);

        return response()->json([
            'message' => 'Đã gửi hồ sơ tới '.$job->company->name.'.',
            'application' => $this->presenter->application($application->refresh()),
        ], 201);
    }

    public function destroy(Application $application): JsonResponse
    {
        Gate::authorize('withdraw', $application);

        $this->applications->withdraw($application);

        return response()->json(['message' => 'Đã rút đơn ứng tuyển.']);
    }
}
