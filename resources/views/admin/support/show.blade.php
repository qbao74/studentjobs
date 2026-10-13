@extends('layout.panel')
@section('title', $ticket->subject)

@section('content')
    <header class="page-head">
        <div>
            <p class="page-sub"><a href="{{ route('admin.support.index') }}">Hỗ trợ</a></p>
            <h1 class="page-title">{{ $ticket->subject }}</h1>
            <p class="page-sub">{{ $ticket->user->name }} ({{ $ticket->user->email }}) · {{ $ticket->category->label() }}</p>
        </div>
        <form method="POST" action="{{ route('admin.support.status', $ticket) }}">
            @csrf
            @method('PATCH')
            <label class="inbox-filter">
                <span class="muted">Trạng thái</span>
                <select name="status" data-autosubmit>
                    @foreach (\App\Enums\SupportTicketStatus::cases() as $case)
                        <option value="{{ $case->value }}" @selected($ticket->status === $case)>{{ $case->label() }}</option>
                    @endforeach
                </select>
            </label>
        </form>
    </header>

    @foreach ($ticket->replies as $reply)
        <article class="card section">
            <strong>{{ $reply->user->name }}</strong>
            <small class="muted"> · {{ $reply->user->role->label() }} · {{ $reply->created_at->format('d/m/Y H:i') }}</small>
            <p>{{ $reply->body }}</p>
        </article>
    @endforeach

    @if (! $ticket->isClosed())
        <form method="POST" action="{{ route('admin.support.reply', $ticket) }}" class="card section panel-form">
            @csrf
            <label class="form-field form-field--wide">Phản hồi
                <textarea name="body" rows="5" required minlength="3" maxlength="5000">{{ old('body') }}</textarea>
                @error('body') <span class="form-error">{{ $message }}</span> @enderror
            </label>
            <button class="btn btn-primary" type="submit">Gửi phản hồi</button>
        </form>
    @endif
@endsection
