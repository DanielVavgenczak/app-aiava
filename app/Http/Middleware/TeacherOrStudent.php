<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TeacherOrStudent
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('teacher')->check() || Auth::guard('student')->check()) {
            return $next($request);
        }
        // Se for uma requisição AJAX/Livewire
        if ($request->expectsJson() || $request->header('X-Livewire')) {
            abort(403, 'Acesso negado. Apenas professores e estudantes podem acessar esta área.');
        }
        //dd(Auth::guard('teacher')->check(), Auth::guard('student')->check());
        // Para requisições normais, redireciona com mensagem
        return redirect()->route('login')
            ->with('error', 'Você precisa estar logado como professor ou estudante para acessar esta área.');
    }
}
