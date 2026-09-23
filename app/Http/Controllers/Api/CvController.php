<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Cv\CvService;
use App\Services\Frontend\JoblyPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CvController extends Controller
{
    public function __construct(
        private CvService $cvs,
        private JoblyPayload $payload,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            // Kiểm tra cả đuôi file lẫn nội dung thật; CvService còn kiểm tra lần nữa trước khi lưu.
            'cv' => [
                'required', 'file', 'max:5120', 'extensions:pdf,docx',
                'mimetypes:application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/zip,application/octet-stream',
            ],
        ], [], ['cv' => 'CV']);

        $cv = $this->cvs->upload($request->user()->student, $request->file('cv'));

        $message = match ($cv->parse_status) {
            'parsed' => 'Đã tải CV và đọc được '.count($cv->parsed['skills'] ?? []).' kỹ năng.',
            default => 'Đã lưu CV. '.$cv->parse_error,
        };

        return response()->json([
            'message' => $message,
            'state' => $this->payload->build($request->user()->fresh()),
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $this->cvs->delete($request->user()->student);

        return response()->json([
            'message' => 'Đã xóa CV.',
            'state' => $this->payload->build($request->user()->fresh()),
        ]);
    }
}
