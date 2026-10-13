<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employer\RecruiterNoteRequest;
use App\Models\Application;
use App\Models\RecruiterNote;
use App\Services\Recruiter\RecruiterNoteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class RecruiterNoteController extends Controller
{
    public function __construct(private RecruiterNoteService $notes) {}

    public function store(RecruiterNoteRequest $request, Application $application): RedirectResponse
    {
        Gate::authorize('create', [RecruiterNote::class, $application]);

        $this->notes->create($request->user()->employer, $application, trim($request->validated('body')));

        return back()->with('status', 'Đã lưu ghi chú nội bộ. Sinh viên không nhìn thấy nội dung này.');
    }

    public function update(RecruiterNoteRequest $request, RecruiterNote $note): RedirectResponse
    {
        Gate::authorize('update', $note);

        $this->notes->update($note, trim($request->validated('body')));

        return back()->with('status', 'Đã cập nhật ghi chú.');
    }

    public function destroy(RecruiterNote $note): RedirectResponse
    {
        Gate::authorize('delete', $note);

        $this->notes->delete($note);

        return back()->with('status', 'Đã xoá ghi chú.');
    }
}
