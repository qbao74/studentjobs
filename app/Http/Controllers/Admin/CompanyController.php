<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(Request $request): View
    {
        $verified = $request->query('verified');
        $q = trim((string) $request->query('q', ''));

        $companies = Company::query()
            ->withCount(['jobPosts', 'employers', 'followers'])
            ->when($verified === '1', fn ($query) => $query->where('verified', true))
            ->when($verified === '0', fn ($query) => $query->where('verified', false))
            ->when($q !== '', fn ($query) => $query->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.companies.index', compact('companies', 'verified', 'q'));
    }

    public function show(Company $company): View
    {
        $company->load([
            'employers.user',
            'jobPosts' => fn ($query) => $query->withCount('applications')->latest(),
        ]);

        return view('admin.companies.show', compact('company'));
    }

    public function updateVerified(Request $request, Company $company): RedirectResponse
    {
        $company->update(['verified' => $request->boolean('verified')]);

        return back()->with('status', $company->verified
            ? "Đã xác minh {$company->name}."
            : "Đã bỏ xác minh {$company->name}.");
    }
}
