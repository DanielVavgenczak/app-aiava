<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IsTeacher
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        //dd('caindo nesse carda', Auth::guard('teacher')->user());
        if (Auth::guard('teacher')->check()) {
            return $next($request);
        }
        return redirect()->route('login')->with('error', 'Acesso restrito');
    }
}
