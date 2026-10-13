<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Http\Requests\StoreCompanyReviewRequest;
use App\Models\Company;
use App\Models\Student;
use App\Services\Review\CompanyReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class CompanyReviewController extends Controller
{
    public function __construct(private CompanyReviewService $reviews) {}

    public function index(Company $company): View
    {
        $reviews = $company->reviews()
            ->with('student.user')
            ->latest()
            ->paginate(10);

        $student = auth()->user()?->student;
        $canReview = $student ? $this->reviews->canReview($student, $company) : false;
        $myReview = $student?->reviews()->where('company_id', $company->id)->first();

        return view('reviews.index', compact('company', 'reviews', 'canReview', 'myReview'));
    }

    public function create(Request $request): View
    {
        $student = $request->user()->student;
        $companies = $this->reviewableCompanies($student);

        return view('reviews.create', compact('companies'));
    }

    public function store(StoreCompanyReviewRequest $request, Company $company): RedirectResponse
    {
        $data = $request->validated();

        try {
            $this->reviews->create(
                $request->user()->student,
                $company,
                (int) $data['rating'],
                trim($data['comment']),
            );
        } catch (ConflictHttpException $e) {
            return back()->withErrors(['comment' => $e->getMessage()])->withInput();
        } catch (UnprocessableEntityHttpException $e) {
            return back()->withErrors(['comment' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('companies.reviews.index', $company)
            ->with('status', 'Đã gửi đánh giá. Cảm ơn bạn đã chia sẻ trải nghiệm thật.');
    }

    /** @return Collection<int, Company> */
    private function reviewableCompanies(Student $student): Collection
    {
        return Company::query()
            ->whereHas('jobPosts.applications', function ($q) use ($student): void {
                $q->where('student_id', $student->id)
                    ->where('status', ApplicationStatus::Hired);
            })
            ->whereDoesntHave('reviews', fn ($q) => $q->where('student_id', $student->id))
            ->orderBy('name')
            ->get();
    }
}
