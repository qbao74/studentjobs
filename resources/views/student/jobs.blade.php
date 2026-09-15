<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Danh sách việc làm</title>
</head>
<body>
    <h1>Danh sách việc làm</h1>

    @if(session('success'))
        <p style="color: green">{{ session('success') }}</p>
    @endif
    @if(session('error'))
        <p style="color: red">{{ session('error') }}</p>
    @endif

    @foreach($jobs as $job)
        <div style="border: 1px solid #ccc; padding: 10px; margin: 10px 0;">
            <h3>{{ $job->title }}</h3>
            <p>Mô tả: {{ $job->description }}</p>
            <p>Lương: {{ $job->salary }}</p>
            <p>Địa điểm: {{ $job->location }}</p>

            <form method="POST" action="{{ route('apply', $job->id) }}">
                @csrf
                <label>Chọn CV:</label>
                <select name="cv_id" required>
                    @foreach(\App\Models\Cv::all() as $cv)
                        <option value="{{ $cv->id }}">{{ $cv->title }}</option>
                    @endforeach
                </select>
                <br><br>
                <label>Thư giới thiệu:</label><br>
                <textarea name="cover_letter" rows="3" cols="50"></textarea>
                <br><br>
                <button type="submit">Ứng tuyển</button>
            </form>
        </div>
    @endforeach
</body>
</html>