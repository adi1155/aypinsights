<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;

class AuditLogController extends Controller
{
    public function index()
    {
        $logs = ActivityLog::with('user')->latest()->paginate(50);

        return view('admin.audit-logs', compact('logs'));
    }
}
