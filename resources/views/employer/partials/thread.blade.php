<div class="hr-thread">
    <div class="hr-thread-log">
        @forelse ($thread as $message)
            @php $mine = $message->user_id === auth()->id(); @endphp
            <div class="msg-group {{ $mine ? 'msg-group--out' : 'msg-group--in' }}">
                <div class="bubble {{ $mine ? 'bubble--out' : 'bubble--in' }}">{{ $message->body }}</div>
                <span class="msg-time">{{ $message->created_at->format('d/m H:i') }}</span>
            </div>
        @empty
            <p class="muted hr-thread-empty">Chưa có tin nhắn. Gửi lời chào để bắt đầu hội thoại về đơn này.</p>
        @endforelse
    </div>
    <form class="hr-compose" method="POST" action="{{ route('employer.applications.messages.store', $application) }}">
        @csrf
        <input type="text" name="body" value="{{ old('body') }}" maxlength="2000" placeholder="Nhắn cho ứng viên..." required autocomplete="off">
        <button class="btn btn-primary btn-sm" type="submit"><i data-lucide="send"></i> Gửi</button>
    </form>
    @error('body')
        <p class="form-error">{{ $message }}</p>
    @enderror
</div>
