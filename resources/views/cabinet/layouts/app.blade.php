<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Кабинет') - {{ current_site()->name ?? 'Автопартнер' }}</title>
    
    <!-- Базовые стили -->
    <link rel="stylesheet" href="{{ asset('css/cabinet.css') }}">
    
    <!-- Handsontable CSS - правильная версия -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/handsontable@14.2.0/dist/handsontable.full.min.css">
    
    <!-- Тема пользователя (светлая/тёмная) -->
    @php
        $theme = session('user_theme', 'light');
    @endphp
    <link rel="stylesheet" href="{{ asset('css/themes/' . $theme . '.css') }}">
    
    @stack('styles')
</head>
<body class="theme-{{ $theme }}">
    <div class="cabinet-container">
        <!-- Шапка -->
        <header class="cabinet-header">
            <div class="logo">
                <img src="{{ asset('images/logo.png') }}" alt="{{ current_site()->name ?? 'Логотип' }}" onerror="this.style.display='none'">
                <span class="site-name">{{ current_site()->name ?? 'Кабинет' }}</span>
            </div>
            
            <div class="header-actions">
                <!-- Информация о пользователе -->
                @auth
                <div class="user-info">
                    <span class="user-name">{{ auth()->user()->name }}</span>
                    <span class="user-role">{{ auth()->user()->role->display_name ?? 'Пользователь' }}</span>
                </div>
                @endauth
                
                <!-- Меню навигации -->
                <div class="nav-menu">
                    <a href="{{ route('cabinet.orders.index') }}" class="nav-link {{ request()->routeIs('cabinet.orders.*') ? 'active' : '' }}">
                        📋 Заявки
                    </a>
                    @if(auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isSupervisor()))
                        <a href="{{ route('cabinet.reports.index') }}" class="nav-link {{ request()->routeIs('cabinet.reports.*') ? 'active' : '' }}">
                            📊 Отчёты
                        </a>
                    @endif
                    @if(auth()->check() && auth()->user()->isAdmin())
                        <a href="{{ route('cabinet.users.index') }}" class="nav-link {{ request()->routeIs('cabinet.users.*') ? 'active' : '' }}">
                            👥 Пользователи
                        </a>
                    @endif
                </div>
                
                <!-- Переключатель темы -->
                @if(isset($theme))
                <div class="theme-switcher">
                    <form method="POST" action="{{ route('cabinet.theme.switch') }}" class="theme-form" id="themeForm">
                        @csrf
                        <input type="hidden" name="theme" value="{{ $theme === 'light' ? 'dark' : 'light' }}">
                        <button type="submit" class="theme-btn">
                            @if($theme === 'light')
                                🌙 Тёмная тема
                            @else
                                ☀️ Светлая тема
                            @endif
                        </button>
                    </form>
                </div>
                @endif
                
                <!-- Кнопка возврата на сайт -->
                <a href="{{ current_site()->home_url ?? '/' }}" class="back-to-site">
                    ← На главный сайт
                </a>
            </div>
        </header>

        <!-- Основной контент -->
        <main class="cabinet-content">
            @yield('content')
        </main>
    </div>

    <!-- Handsontable JS - правильная версия -->
    <script src="https://cdn.jsdelivr.net/npm/handsontable@14.2.0/dist/handsontable.full.min.js"></script>
    
    <!-- Общие скрипты -->
    <script src="{{ asset('js/cabinet.js') }}"></script>
    
    @stack('scripts')
</body>
</html>