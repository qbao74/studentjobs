<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Services\Frontend\StudentPresenter;
use App\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MessageController extends Controller
{
    public function __construct(
        private MessageService $messages,
        private StudentPresenter $presenter,
    ) {}

    /** ?after=<id> để JS chỉ lấy tin mới khi hỏi lại định kỳ. */
    public function index(Request $request, Application $application): JsonResponse
    {
        Gate::authorize('message', $application);

        $after = $request->integer('after') ?: null;
        $viewer = $request->user();

        return response()->json([
            'messages' => $this->messages->thread($application, $viewer, $after)
                ->map(fn ($m) => $this->presenter->message($m, $viewer->id))
                ->all(),
        ]);
    }

    public function store(Request $request, Application $application): JsonResponse
    {
        Gate::authorize('message', $application);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ], [], ['body' => 'tin nhắn']);

        $body = trim($data['body']);
        abort_if($body === '', 422, 'Tin nhắn không được để trống.');

        $message = $this->messages->send($application, $request->user(), $body);

        return response()->json([
            'message' => $this->presenter->message($message, $request->user()->id),
        ], 201);
    }
}
