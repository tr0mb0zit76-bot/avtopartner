<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoadUserTheme
{
    public function handle(Request $request, Closure $next)
    {
        // Для авторизованных пользователей - тема из БД
        if (Auth::check()) {
            $user = Auth::user();
            $theme = $user->theme ?? 'light';
            session(['user_theme' => $theme]);
        } 
        // Для неавторизованных - тема из сессии или светлая по умолчанию
        else {
            $theme = session('user_theme', 'light');
        }
        
        // Делаем тему доступной во всех шаблонах
        view()->share('user_theme', $theme);
        
        return $next($request);
    }
}