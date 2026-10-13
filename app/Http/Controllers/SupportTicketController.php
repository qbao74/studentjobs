<?php

namespace App\Http\Controllers;

use App\Enums\SupportCategory;
use App\Http\Requests\StoreSupportReplyRequest;
use App\Http\Requests\StoreSupportTicketRequest;
use App\Models\SupportTicket;
use App\Services\Support\SupportTicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
    public function __construct(private SupportTicketService $tickets) {}

    public function index(Request $request): View
    {
        $tickets = $request->user()
            ->supportTickets()
            ->with('latestReply')
            ->latest()
            ->paginate(10);

        return view('support.index', compact('tickets'));
    }

    public function create(): View
    {
        Gate::authorize('create', SupportTicket::class);

        return view('support.create', ['categories' => SupportCategory::cases()]);
    }

    public function store(StoreSupportTicketRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $ticket = $this->tickets->open(
            $request->user(),
            SupportCategory::from($data['category']),
            trim($data['subject']),
            trim($data['body']),
        );

        return redirect()
            ->route('support.show', $ticket)
            ->with('status', 'Đã gửi yêu cầu hỗ trợ. Bạn có thể theo dõi phản hồi tại đây.');
    }

    public function show(Request $request, SupportTicket $ticket): View
    {
        Gate::authorize('view', $ticket);

        $ticket->load(['replies.user', 'user']);

        return view('support.show', compact('ticket'));
    }

    public function reply(StoreSupportReplyRequest $request, SupportTicket $ticket): RedirectResponse
    {
        $this->tickets->reply($ticket, $request->user(), trim($request->validated('body')));

        return back()->with('status', 'Đã gửi phản hồi.');
    }

    public function close(Request $request, SupportTicket $ticket): RedirectResponse
    {
        Gate::authorize('close', $ticket);

        $this->tickets->close($ticket);

        return back()->with('status', 'Đã đóng yêu cầu hỗ trợ.');
    }
}
