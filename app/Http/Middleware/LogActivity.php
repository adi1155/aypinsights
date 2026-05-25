<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->user() && $request->isMethod('POST', 'PUT', 'PATCH', 'DELETE')) {
            ActivityLog::create([
                'user_id' => $request->user()->id,
                'action' => $request->method().' '.$request->path(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'properties' => ['input' => $request->except(['password', '_token'])],
            ]);
        }

        return $response;
    }
}
