<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ManualAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->session()->has('user_id')) {
            return redirect()->route('login')->with('error', 'Silakan login dulu.');
        }
        return $next($request);
    }
}
