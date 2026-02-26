<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Кабинет') - {{ current_site()->name ?? 'Автопартнер' }}</title>
    
    <!-- Handsontable CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/handsontable@14.2.0/dist/handsontable.full.min.css">
    
    <!-- Базовые стили -->
    <link rel="stylesheet" href="{{ asset('css/cabinet.css') }}">
    
    <!-- Тема пользователя -->
    @php
        $theme = session('user_theme', 'light');
    @endphp
    <link rel="stylesheet" href="{{ asset('css/themes/' . $theme . '.css') }}">
    
    @stack('styles')
</head>
<body class="theme-{{ $theme }}">
    <div class="app-wrapper">
        <!-- Шапка фиксированная -->
        <header class="app-header">
            <div class="header-left">
                <div class="logo">
                    <img src="{{ asset('images/logo.png') }}" alt="{{ current_site()->name ?? 'Логотип' }}" onerror="this.style.display='none'">
                    <span class="site-name">{{ current_site()->name ?? 'Кабинет' }}</span>
                </div>
                
                <!-- Верхнее меню кабинета -->
                <nav class="main-nav">
                    @auth
                        <a href="{{ route('cabinet.dashboard') }}" class="nav-item {{ request()->routeIs('cabinet.dashboard') ? 'active' : '' }}">
                            📊 Дашборд
                        </a>
                        
                        <a href="{{ route('cabinet.orders.index') }}" class="nav-item {{ request()->routeIs('cabinet.orders.*') ? 'active' : '' }}">
                            📋 Заявки
                        </a>
                        
                        @if(auth()->user()->hasPermission('reports.view'))
                            <a href="#" class="nav-item" onclick="alert('В разработке')">
                                📊 Отчёты
                            </a>
                        @endif
                        
                        @if(auth()->user()->hasPermission('users.view'))
                            <a href="{{ route('cabinet.users.index') }}" class="nav-item {{ request()->routeIs('cabinet.users.*') ? 'active' : '' }}">
                                👥 Пользователи
                            </a>
                        @endif
                        
                        @if(auth()->user()->hasPermission('settings.view'))
                            <a href="#" class="nav-item" onclick="alert('В разработке')">
                                ⚙️ Настройки
                            </a>
                        @endif
                    @endauth
                </nav>
            </div>
            
            <div class="header-right">
                @auth
                <div class="user-info">
                    <span class="user-name">{{ auth()->user()->name }}</span>
                    <span class="user-role">{{ auth()->user()->role->display_name ?? 'Пользователь' }}</span>
                </div>
                
                <!-- Кнопка выхода -->
                <form method="POST" action="{{ route('cabinet.logout') }}" style="display: inline;">
                    @csrf
                    <button type="submit" class="logout-btn">
                        🚪 Выйти
                    </button>
                </form>
                @endauth
                
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
                
                <!-- Кнопка возврата на главный сайт -->
                <a href="{{ current_site()->home_url ?? '/' }}" class="back-to-site">
                    ← На главный сайт
                </a>
            </div>
        </header>

        <!-- Контейнер для контента и поля ввода -->
        <div class="content-wrapper">
            <!-- Основной контент с прокруткой -->
            <main class="app-main">
                @yield('content')
            </main>

            <!-- Поле ввода для ИИ ассистента (всегда внизу) -->
            <div class="ai-input-container">
                <div class="ai-input-wrapper">
                    <input type="text" class="ai-input" placeholder="💬 Задайте вопрос цифровому ассистенту..." id="aiChatInput">
                    <button class="ai-send-btn" onclick="sendAiMessage()">Отправить</button>
                </div>
                <div class="ai-suggestions">
                    <span class="suggestion-chip" onclick="setAiPrompt('Покажи мои заявки')">📋 Мои заявки</span>
                    <span class="suggestion-chip" onclick="setAiPrompt('Какая дельта по заявкам?')">💰 Дельта</span>
                    <span class="suggestion-chip" onclick="setAiPrompt('Расчёт зарплаты')">📊 Зарплата</span>
                    <span class="suggestion-chip" onclick="setAiPrompt('Помоги создать заявку')">➕ Новая заявка</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно для ответов ИИ -->
    <div class="ai-modal" id="aiModal">
        <div class="ai-modal-content">
            <div class="ai-modal-header">
                <h3>Цифровой ассистент</h3>
                <button class="ai-modal-close" onclick="closeAiModal()">✕</button>
            </div>
            <div class="ai-modal-body" id="aiModalBody">
                <div class="ai-message bot">
                    👋 Здравствуйте! Я цифровой ассистент. Чем могу помочь?
                </div>
            </div>
            <div class="ai-modal-footer">
                <input type="text" class="ai-modal-input" placeholder="Введите сообщение..." id="aiModalInput">
                <button class="ai-modal-send" onclick="sendAiModalMessage()">➤</button>
            </div>
        </div>
    </div>

    <!-- Handsontable JS -->
    <script src="https://cdn.jsdelivr.net/npm/handsontable@14.2.0/dist/handsontable.full.min.js"></script>
    <script src="{{ asset('js/cabinet.js') }}"></script>
    
    <script>
        // Функции для работы с ИИ
        const aiModal = document.getElementById('aiModal');
        const aiModalBody = document.getElementById('aiModalBody');
        const aiModalInput = document.getElementById('aiModalInput');
        
        function sendAiMessage() {
            const input = document.getElementById('aiChatInput');
            const query = input.value.trim();
            if (!query) return;
            
            // Открываем модальное окно
            openAiModal();
            
            // Добавляем сообщение пользователя
            addAiMessage('user', query);
            
            // Имитация ответа (здесь будет интеграция с API)
            setTimeout(() => {
                addAiMessage('bot', 'Запрос обрабатывается. Функция в разработке.');
            }, 500);
            
            input.value = '';
        }
        
        function openAiModal() {
            aiModal.classList.add('active');
            // Блокируем прокрутку основной страницы
            document.body.style.overflow = 'hidden';
        }
        
        function closeAiModal() {
            aiModal.classList.remove('active');
            // Возвращаем прокрутку
            document.body.style.overflow = '';
            // Очищаем модальное окно при закрытии (кроме приветствия)
            const messages = aiModalBody.querySelectorAll('.ai-message:not(.bot:first-child)');
            messages.forEach(msg => msg.remove());
        }
        
        function addAiMessage(type, text) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `ai-message ${type}`;
            messageDiv.textContent = text;
            aiModalBody.appendChild(messageDiv);
            aiModalBody.scrollTop = aiModalBody.scrollHeight;
        }
        
        function sendAiModalMessage() {
            const query = aiModalInput.value.trim();
            if (!query) return;
            
            addAiMessage('user', query);
            
            // Имитация ответа (здесь будет интеграция с API)
            setTimeout(() => {
                addAiMessage('bot', 'Запрос обрабатывается. Функция в разработке.');
            }, 500);
            
            aiModalInput.value = '';
        }
        
        function setAiPrompt(text) {
            document.getElementById('aiChatInput').value = text;
            sendAiMessage();
        }
        
        // Закрытие по клику вне модального окна
        aiModal.addEventListener('click', function(event) {
            if (event.target === aiModal) {
                closeAiModal();
            }
        });
        
        // Закрытие по Escape
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && aiModal.classList.contains('active')) {
                closeAiModal();
            }
        });
        
        // Отправка по Enter в модальном окне
        aiModalInput.addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                sendAiModalMessage();
            }
        });
    </script>
    
    @stack('scripts')
</body>
</html>