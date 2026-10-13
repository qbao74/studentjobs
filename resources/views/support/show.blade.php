@extends(auth()->user()->hasRole(\App\Enums\Role::Student) ? 'layout.app' : 'layout.panel')
@section('title', $ticket->subject)
@section('page', 'support')

@section('content')
    <header class="page-head">
        <div>
            <p class="page-sub"><a href="{{ route('support.index') }}">Hỗ trợ</a></p>
            <h1 class="page-title">{{ $ticket->subject }}</h1>
            <p class="page-sub">{{ $ticket->category->label() }} · {{ $ticket->created_at->format('d/m/Y H:i') }}</p>
        </div>
        <span class="status-tag" data-status="{{ $ticket->isClosed() ? 'hidden' : 'open' }}">{{ $ticket->status->label() }}</span>
    </header>

    @foreach ($ticket->replies as $reply)
        <article class="card section">
            <strong>{{ $reply->user->name }}</strong>
            <small class="muted"> · {{ $reply->user->role->label() }} · {{ $reply->created_at->format('d/m/Y H:i') }}</small>
            <p>{{ $reply->body }}</p>
        </article>
    @endforeach

    @if (! $ticket->isClosed())
        <form method="POST" action="{{ route('support.reply', $ticket) }}" class="card section panel-form">
            @csrf
            <label class="form-field form-field--wide">Phản hồi thêm
                <textarea name="body" rows="4" required minlength="3" maxlength="5000">{{ old('body') }}</textarea>
                @error('body') <span class="form-error">{{ $message }}</span> @enderror
            </label>
            <div class="panel-actions">
                <button class="btn btn-primary" type="submit">Gửi</button>
            </div>
        </form>
        <form method="POST" action="{{ route('support.close', $ticket) }}">
            @csrf
            @method('PATCH')
            <button class="btn btn-ghost" type="submit">Đóng yêu cầu</button>
        </form>
    @else
        <p class="muted">Yêu cầu đã đóng. Tạo yêu cầu mới nếu bạn cần hỗ trợ tiếp.</p>
    @endif
@endsection
