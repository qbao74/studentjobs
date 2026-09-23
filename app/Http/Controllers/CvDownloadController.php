<?php

namespace App\Http\Controllers;

use App\Models\Cv;
use App\Services\Cv\CvService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** CV nằm trong disk riêng tư: chỉ tải được qua đây, sau khi CvPolicy cho phép. */
class CvDownloadController extends Controller
{
    public function __invoke(Cv $cv): StreamedResponse
    {
        Gate::authorize('download', $cv);

        abort_unless(Storage::disk(CvService::DISK)->exists($cv->path), 404, 'File CV không còn tồn tại.');

        return Storage::disk(CvService::DISK)->download($cv->path, $cv->original_name, [
            'Content-Type' => $cv->mime_type,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
