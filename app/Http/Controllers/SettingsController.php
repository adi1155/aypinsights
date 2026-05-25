<?php

namespace App\Http\Controllers;

use App\Models\DashboardPreference;
use App\Models\ScheduledReport;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $prefs = DashboardPreference::firstOrCreate(['user_id' => $request->user()->id]);

        return view('settings.index', compact('prefs'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'default_company' => 'nullable|string',
            'default_branch' => 'nullable|string',
            'theme' => 'in:light,dark',
            'currency' => 'nullable|string|max:10',
        ]);

        DashboardPreference::updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated
        );

        return back()->with('success', 'Preferences saved.');
    }

    public function scheduledReports(Request $request)
    {
        $reports = ScheduledReport::where('user_id', $request->user()->id)->get();

        return view('settings.scheduled-reports', compact('reports'));
    }

    public function storeScheduledReport(Request $request)
    {
        $validated = $request->validate([
            'report_type' => 'required|in:ceo,daily_closing,ap,ar,expense',
            'format' => 'required|in:pdf,csv',
            'frequency' => 'required|in:daily,weekly',
            'delivery_time' => 'required',
            'recipients' => 'required|string',
        ]);

        ScheduledReport::create([
            'user_id' => $request->user()->id,
            'report_type' => $validated['report_type'],
            'format' => $validated['format'],
            'frequency' => $validated['frequency'],
            'delivery_time' => $validated['delivery_time'],
            'recipients' => array_map('trim', explode(',', $validated['recipients'])),
            'is_active' => true,
        ]);

        return back()->with('success', 'Scheduled report created.');
    }
}
