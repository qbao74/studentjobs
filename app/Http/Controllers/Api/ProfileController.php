<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\UpdateProfileRequest;
use App\Services\Frontend\JoblyPayload;
use App\Services\Student\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Sau mỗi thay đổi hồ sơ, điểm khớp của mọi tin đều có thể đổi,
 * nên trả về toàn bộ state mới để giao diện vẽ lại một lần cho đúng.
 */
class ProfileController extends Controller
{
    public function __construct(
        private ProfileService $profiles,
        private JoblyPayload $payload,
    ) {}

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $this->profiles->update($request->user()->student, $request->validated());

        return $this->state($request, 'Đã lưu hồ sơ.');
    }

    public function skills(Request $request): JsonResponse
    {
        $data = $request->validate([
            'skills' => ['present', 'array', 'max:30'],
            'skills.*' => ['string', 'min:1', 'max:50'],
        ], [], ['skills' => 'kỹ năng', 'skills.*' => 'kỹ năng']);

        $this->profiles->syncSkills($request->user()->student, $data['skills']);

        return $this->state($request, 'Đã cập nhật kỹ năng.');
    }

    public function avatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4000,max_height=4000'],
        ], [], ['avatar' => 'ảnh đại diện']);

        $this->profiles->updateAvatar($request->user()->student, $request->file('avatar'));

        return $this->state($request, 'Đã đổi ảnh đại diện.');
    }

    private function state(Request $request, string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'state' => $this->payload->build($request->user()->fresh()),
        ]);
    }
}
