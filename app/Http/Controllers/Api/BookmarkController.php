<?php

namespace App\Http\Controllers\Api;

use App\Enums\JobStatus;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\JobPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Lưu việc và theo dõi công ty: bấm lần nữa là bỏ (toggle). */
class BookmarkController extends Controller
{
    public function toggleJob(Request $request, JobPost $job): JsonResponse
    {
        $savedJobs = $request->user()->student->savedJobs();

        // Tin đã đóng/ẩn: vẫn cho bỏ lưu, không cho lưu mới.
        if ($job->status !== JobStatus::Open && ! $savedJobs->whereKey($job->id)->exists()) {
            return response()->json(['message' => 'Tin này đã ngừng tuyển.'], 422);
        }

        $result = $request->user()->student->savedJobs()->toggle($job->id);
        $saved = $result['attached'] !== [];

        return response()->json([
            'saved' => $saved,
            'message' => $saved ? 'Đã lưu việc.' : 'Đã bỏ lưu việc.',
        ]);
    }

    public function toggleCompany(Request $request, Company $company): JsonResponse
    {
        $result = $request->user()->student->followedCompanies()->toggle($company->id);
        $following = $result['attached'] !== [];

        return response()->json([
            'following' => $following,
            'followers' => $company->followers()->count(),
            'message' => $following ? "Đang theo dõi {$company->name}." : "Đã bỏ theo dõi {$company->name}.",
        ]);
    }
}
