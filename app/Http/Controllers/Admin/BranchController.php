<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        return view('admin.branches.index', [
            'branches' => Branch::with('company')->get(),
            'companies' => Company::where('is_active', true)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'erpnext_name' => 'required|unique:branches',
            'name' => 'required|string',
        ]);
        Branch::create($validated);

        return back()->with('success', 'Branch added.');
    }
}
