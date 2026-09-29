<div class="panel-actions">
    @if ($job->status !== \App\Enums\JobStatus::Hidden)
        <form method="POST" action="{{ route('admin.jobs.status', $job) }}" data-confirm="Ẩn tin này? Sinh viên sẽ không thấy, và nhà tuyển dụng không tự mở lại được.">
            @csrf
            @method('PATCH')
            <input type="hidden" name="status" value="hidden">
            <button class="btn btn-ghost btn-sm is-danger" type="submit"><i data-lucide="eye-off"></i> Ẩn</button>
        </form>
    @endif
    @if ($job->status !== \App\Enums\JobStatus::Open)
        <form method="POST" action="{{ route('admin.jobs.status', $job) }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="status" value="open">
            <button class="btn btn-ghost btn-sm" type="submit"><i data-lucide="lock-open"></i> Mở lại</button>
        </form>
    @endif
    @if ($job->status === \App\Enums\JobStatus::Open)
        <form method="POST" action="{{ route('admin.jobs.status', $job) }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="status" value="closed">
            <button class="btn btn-ghost btn-sm" type="submit"><i data-lucide="lock"></i> Đóng</button>
        </form>
    @endif
</div>
