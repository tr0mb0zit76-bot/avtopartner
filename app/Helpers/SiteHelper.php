<?php

use App\Models\Site;

if (!function_exists('current_site')) {
    function current_site()
    {
        // Пробуем получить из контейнера
        if (app()->has('current_site')) {
            return app('current_site');
        }
        
        // Если нет, возвращаем заглушку
        return (object)[
            'name' => 'Кабинет',
            'home_url' => '/',
            'domain' => request()->getHost(),
        ];
    }
}