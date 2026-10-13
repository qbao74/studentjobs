<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SupportTicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupportReplyRequest;
use App\Models\SupportTicket;
use App\Services\Support\SupportTicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
    public function __construct(private SupportTicketService $tickets) {}

    public function index(Request $request): View
    {
        Gate::authorize('manage', SupportTicket::class);

        $status = $request->query('status');

        $tickets = SupportTicket::query()
            ->with(['user', 'latestReply'])
            ->when(in_array($status, SupportTicketStatus::values(), true), fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.support.index', compact('tickets', 'status'));
    }

    public function show(SupportTicket $ticket): View
    {
        Gate::authorize('view', $ticket);

        $ticket->load(['replies.user', 'user']);

        return view('admin.support.show', compact('ticket'));
    }

    public function reply(StoreSupportReplyRequest $request, SupportTicket $ticket): RedirectResponse
    {
        $this->tickets->reply($ticket, $request->user(), trim($request->validated('body')));

        return back()->with('status', 'Đã gửi phản hồi tới người dùng.');
    }

    public function updateStatus(Request $request, SupportTicket $ticket): RedirectResponse
    {
        Gate::authorize('manage', SupportTicket::class);

        $data = $request->validate([
            'status' => ['required', Rule::enum(SupportTicketStatus::class)],
        ]);

        $this->tickets->setStatus($ticket, SupportTicketStatus::from($data['status']));

        return back()->with('status', 'Đã cập nhật trạng thái yêu cầu.');
    }
}
