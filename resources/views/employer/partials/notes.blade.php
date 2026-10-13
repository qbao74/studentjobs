<section class="card section">
    <h2>Ghi chú nội bộ</h2>
    <p class="muted">Chỉ bạn nhìn thấy ghi chú của mình. Sinh viên và nhà tuyển dụng khác không đọc được.</p>

    @forelse ($notes as $note)
        <article class="note-item">
            <p>{{ $note->body }}</p>
            <small class="muted">Tạo {{ $note->created_at->format('d/m/Y H:i') }}@if ($note->updated_at->ne($note->created_at)) · sửa {{ $note->updated_at->format('d/m/Y H:i') }}@endif</small>
            <form method="POST" action="{{ route('employer.notes.update', $note) }}" class="panel-form" style="margin-top:8px">
                @csrf
                @method('PATCH')
                <label class="form-field form-field--wide">
                    <textarea name="body" rows="3" required minlength="3" maxlength="2000">{{ old('body', $note->body) }}</textarea>
                </label>
                <div class="panel-actions">
                    <button class="btn btn-soft btn-sm" type="submit">Lưu sửa</button>
                </div>
            </form>
            <form method="POST" action="{{ route('employer.notes.destroy', $note) }}" data-confirm="Xoá ghi chú này?">
                @csrf
                @method('DELETE')
                <button class="btn btn-ghost btn-sm is-danger" type="submit">Xoá</button>
            </form>
        </article>
    @empty
        <p class="muted">Chưa có ghi chú cho ứng viên này.</p>
    @endforelse

    <form method="POST" action="{{ route('employer.notes.store', $application) }}" class="panel-form" style="margin-top:16px">
        @csrf
        <label class="form-field form-field--wide">Thêm ghi chú
            <textarea name="body" rows="3" required minlength="3" maxlength="2000" placeholder="Ví dụ: giao tiếp tốt, cần hỏi lịch học">{{ old('body') }}</textarea>
            @error('body') <span class="form-error">{{ $message }}</span> @enderror
        </label>
        <button class="btn btn-primary btn-sm" type="submit">Lưu ghi chú</button>
    </form>
</section>
>