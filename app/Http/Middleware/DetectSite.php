<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Site;

class DetectSite
{
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();
        
        // Пробуем найти сайт по домену
        $site = Site::where('domain', $host)->first();
        
        // Если не нашли, создаём объект с данными по умолчанию
        if (!$site) {
            $site = (object)[
                'id' => null,
                'domain' => $host,
                'name' => 'Кабинет',
                'theme' => 'default',
                'home_url' => '/',
                'is_active' => true,
            ];
        }
        
        // Сохраняем в контейнер
        app()->instance('current_site', $site);
        
        // Сохраняем в сессию
        session(['current_site' => $site]);
        
        return $next($request);
    }
}