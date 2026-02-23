<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Автопартнер Поволжья')</title>
    
    <!-- Подключаем внешний CSS файл -->
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
    <!-- Если нужны дополнительные стили из конкретной страницы -->
    @yield('styles')
</head>
<body>
    <div class="site-container">
        @yield('content')
    </div>

    <script src="{{ asset('js/main.js') }}"></script>
    @yield('scripts')
</body>
</html>