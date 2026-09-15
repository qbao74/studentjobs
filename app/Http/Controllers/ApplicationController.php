<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Cv;
use App\Models\Job;
use App\Models\Student;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    /**
     * Sinh viên nộp đơn ứng tuyển vào một Job.
     */
    public function apply(Request $request, $jobId)
    {
        // 1. Kiểm tra dữ liệu gửi lên
        $request->validate([
            'cv_id' => 'required|exists:cvs,id',
            'cover_letter' => 'nullable|string|max:2000',
        ]);

        // 2. Lấy thông tin sinh viên đang đăng nhập (tạm giả lập ID = 1, sau này sửa lại)
        $student = Student::where('user_id', auth()->id() ?? 1)->firstOrFail();

        // 3. Kiểm tra Job có tồn tại không
        $job = Job::findOrFail($jobId);

        // 4. Kiểm tra xem sinh viên đã ứng tuyển job này chưa
        $existing = Application::where('student_id', $student->id)
            ->where('job_id', $job->id)
            ->first();

        if ($existing) {
            return redirect()->back()->with('error', 'Bạn đã ứng tuyển công việc này rồi!');
        }

        // 5. Tạo đơn ứng tuyển mới
        Application::create([
            'student_id' => $student->id,
            'job_id' => $job->id,
            'cv_id' => $request->cv_id,
            'cover_letter' => $request->cover_letter,
            'status' => 'pending',
        ]);

        return redirect()->back()->with('success', 'Ứng tuyển thành công!');
    }

    /**
     * Sinh viên xem lịch sử ứng tuyển của mình.
     */
    public function history()
    {
        $student = Student::where('user_id', auth()->id() ?? 1)->firstOrFail();

        $applications = Application::with(['job', 'cv'])
            ->where('student_id', $student->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('student.applications', compact('applications'));
    }
    public function listJobs()
    {
        $jobs = \App\Models\Job::all();
        return view('student.jobs', compact('jobs'));
    }
}
