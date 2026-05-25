<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index()
    {
        return view('admin.companies.index', ['companies' => Company::with('branches')->get()]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'erpnext_name' => 'required|unique:companies',
            'abbr' => 'nullable|string|max:10',
            'default_currency' => 'required|string|max:10',
        ]);
        Company::create($validated);

        return back()->with('success', 'Company added.');
    }
}
