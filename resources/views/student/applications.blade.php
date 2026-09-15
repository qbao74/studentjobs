<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Lịch sử ứng tuyển</title>
</head>
<body>
    <h1>Lịch sử ứng tuyển của tôi</h1>

    @if(session('success'))
        <p style="color: green">{{ session('success') }}</p>
    @endif

    @if($applications->isEmpty())
        <p>Bạn chưa ứng tuyển công việc nào.</p>
    @else
        <table border="1" cellpadding="10">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Công việc</th>
                    <th>CV</th>
                    <th>Trạng thái</th>
                    <th>Ngày nộp</th>
                </tr>
            </thead>
            <tbody>
                @foreach($applications as $index => $app)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $app->job->title ?? 'N/A' }}</td>
                    <td>{{ $app->cv->title ?? 'N/A' }}</td>
                    <td>{{ $app->status }}</td>
                    <td>{{ $app->created_at->format('d/m/Y H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>