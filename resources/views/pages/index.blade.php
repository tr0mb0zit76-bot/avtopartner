@extends('layouts.main')

@section('title', 'Автопартнер Поволжья - Главная')

@section('content')
    <!-- Затемняющий оверлей -->
    <div class="chat-overlay" id="chatOverlay" onclick="hideMessages()"></div>

    <!-- Навигация -->
    <div class="section-nav">
        <span class="nav-dot active" onclick="scrollToPage(0)"></span>
        <span class="nav-dot" onclick="scrollToPage(1)"></span>
        <span class="nav-dot" onclick="scrollToPage(2)"></span>
        <span class="nav-dot" onclick="scrollToPage(3)"></span>
        <span class="nav-dot" onclick="scrollToPage(4)"></span>
        <span class="nav-dot" onclick="scrollToPage(5)"></span>
    </div>

    <!-- Горизонтальные страницы -->
    <div class="horizontal-sections" id="horizontalSections">
        <!-- Главная -->
        <div class="page">
            <div class="logo-container">
                <div class="logo-icon">
                    <span>АП</span>
                </div>
                <div class="logo-text">
                    <div class="logo-main">
                        <h1>АВТОПАРТНЕР</h1>
                        <span class="logo-region">ПОВОЛЖЬЕ</span>
                    </div>
                    <div class="logo-divider"></div>
                    <div class="logo-tagline">НАДЕЖНОСТЬ НА КАЖДОМ ЭТАПЕ</div>
                </div>
            </div>

            <h1 style="font-size: 48px; color: var(--primary-dark); margin: 40px 0 20px;">
                Перевозки по России
            </h1>
            <p style="color: var(--text-soft); font-size: 18px;">Автопарк, ЖД, негабарит — с поддержкой нейросети</p>

            <div class="cards-grid">
                <div class="card">
                    <h3>🚛 Автоперевозки</h3>
                    <p>1500+ рейсов в месяц. Фуры, тенты, рефрижераторы</p>
                </div>
                <div class="card">
                    <h3>🚆 ЖД перевозки</h3>
                    <p>Вагоны от 3 суток. Полувагоны, платформы</p>
                </div>
                <div class="card">
                    <h3>🏗️ Негабарит</h3>
                    <p>Сложные маршруты, разрешения, сопровождение</p>
                </div>
            </div>
        </div>

        <!-- О компании -->
        <div class="page">
            <div class="logo-container" style="margin-bottom: 20px;">
                <div class="logo-icon" style="width: 50px; height: 50px; font-size: 22px;">
                    <span>АП</span>
                </div>
                <div class="logo-text">
                    <div class="logo-main">
                        <h1 style="font-size: 28px;">АВТОПАРТНЕР</h1>
                        <span class="logo-region">ПОВОЛЖЬЕ</span>
                    </div>
                </div>
            </div>
            <h2 style="color: var(--primary-dark);">О компании</h2>
            <p>12 лет на рынке, 500+ клиентов, 98% доставок в срок</p>
        </div>

        <!-- Услуги -->
        <div class="page">
            <div class="logo-container" style="margin-bottom: 20px;">
                <div class="logo-icon" style="width: 50px; height: 50px; font-size: 22px;">
                    <span>АП</span>
                </div>
                <div class="logo-text">
                    <div class="logo-main">
                        <h1 style="font-size: 28px;">АВТОПАРТНЕР</h1>
                        <span class="logo-region">ПОВОЛЖЬЕ</span>
                    </div>
                </div>
            </div>
            <h2 style="color: var(--primary-dark);">Услуги</h2>
            <div class="cards-grid">
                <div class="card"><h3>🚛 Авто</h3><p>Фуры, тенты</p></div>
                <div class="card"><h3>🚆 ЖД</h3><p>Полувагоны</p></div>
                <div class="card"><h3>🏗️ Негабарит</h3><p>Тяжеловесные</p></div>
            </div>
        </div>

        <!-- Опыт -->
        <div class="page">
            <div class="logo-container" style="margin-bottom: 20px;">
                <div class="logo-icon" style="width: 50px; height: 50px; font-size: 22px;">
                    <span>АП</span>
                </div>
                <div class="logo-text">
                    <div class="logo-main">
                        <h1 style="font-size: 28px;">АВТОПАРТНЕР</h1>
                        <span class="logo-region">ПОВОЛЖЬЕ</span>
                    </div>
                </div>
            </div>
            <h2 style="color: var(--primary-dark);">Наш опыт</h2>
            <div class="cards-grid">
                <div class="card"><h3>Буровые</h3><p>40т, Самара → Сургут</p></div>
                <div class="card"><h3>Ритейл</h3><p>Поставки для "Магнит"</p></div>
            </div>
        </div>

        <!-- SLA -->
        <div class="page">
            <div class="logo-container" style="margin-bottom: 20px;">
                <div class="logo-icon" style="width: 50px; height: 50px; font-size: 22px;">
                    <span>АП</span>
                </div>
                <div class="logo-text">
                    <div class="logo-main">
                        <h1 style="font-size: 28px;">АВТОПАРТНЕР</h1>
                        <span class="logo-region">ПОВОЛЖЬЕ</span>
                    </div>
                </div>
            </div>
            <h2 style="color: var(--primary-dark);">Гарантии</h2>
            <div class="cards-grid">
                <div class="card"><h3>⏱️ Подача от 2ч</h3></div>
                <div class="card"><h3>📍 GPS контроль</h3></div>
            </div>
        </div>

        <!-- Контакты -->
        <div class="page">
            <div class="logo-container" style="margin-bottom: 20px;">
                <div class="logo-icon" style="width: 50px; height: 50px; font-size: 22px;">
                    <span>АП</span>
                </div>
                <div class="logo-text">
                    <div class="logo-main">
                        <h1 style="font-size: 28px;">АВТОПАРТНЕР</h1>
                        <span class="logo-region">ПОВОЛЖЬЕ</span>
                    </div>
                </div>
            </div>
            <h2 style="color: var(--primary-dark);">Контакты</h2>
            <p>📞 +7 (800) 123-45-67</p>
        </div>
    </div>

    <!-- ПОЛЕ ВВОДА -->
    <div class="input-fixed">
        <div class="panel-header">
            <span class="panel-title">💬 Цифровой диспетчер</span>
            <a href="{{ route('cabinet.dashboard') }}" class="cabinet-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                Личный кабинет
            </a>
        </div>

        <div class="input-wrapper">
            <input type="text" class="chat-input" id="chatInput" 
                   placeholder="Станок Самара Москва..."
                   onfocus="showMessages()"
                   onkeypress="onEnterPress(event)">
            <button class="send-btn" onclick="sendMessage()">Отправить</button>
        </div>

        <div class="chips-wrapper">
            <span class="chip" onclick="quickMessage('Негабарит Москва Владивосток')">🚛 Негабарит</span>
            <span class="chip" onclick="quickMessage('Сборный груз Казань')">📦 Сборный</span>
            <span class="chip" onclick="quickMessage('Срочно Самара Москва')">⚡ Срочно</span>
            <span class="chip" onclick="quickMessage('ЖД вагон Пермь')">🚆 ЖД</span>
        </div>
    </div>

    <!-- СООБЩЕНИЯ -->
    <div class="messages-container">
        <div class="chat-messages" id="chatMessages">
            <div class="message bot">👋 Задайте вопрос цифровому диспетчеру</div>
        </div>
    </div>
@endsection

@section('scripts')
    // Весь ваш JavaScript код из <script> вставляем сюда
    const horizontalSections = document.getElementById('horizontalSections');
    const navDots = document.querySelectorAll('.nav-dot');
    const chatMessages = document.getElementById('chatMessages');
    const chatInput = document.getElementById('chatInput');
    const chatOverlay = document.getElementById('chatOverlay');

    function scrollToPage(index) {
        const pageWidth = window.innerWidth;
        horizontalSections.scrollTo({
            left: index * pageWidth,
            behavior: 'smooth'
        });
    }

    horizontalSections.addEventListener('scroll', () => {
        const scrollPosition = horizontalSections.scrollLeft;
        const pageWidth = window.innerWidth;
        const activeIndex = Math.round(scrollPosition / pageWidth);
        
        navDots.forEach((dot, index) => {
            dot.classList.toggle('active', index === activeIndex);
        });
    });

    function showMessages() {
        chatMessages.classList.add('visible');
        chatOverlay.classList.add('active');
        horizontalSections.classList.add('blurred');
    }

    function hideMessages() {
        chatMessages.classList.remove('visible');
        chatOverlay.classList.remove('active');
        horizontalSections.classList.remove('blurred');
        chatInput.blur();
    }

    document.addEventListener('click', function(event) {
        const isClickOnInput = event.target.closest('.input-fixed');
        const isClickOnMessages = event.target.closest('.chat-messages');
        const isClickOnOverlay = event.target === chatOverlay;
        
        if (!isClickOnInput && !isClickOnMessages && !isClickOnOverlay) {
            hideMessages();
        }
    });

    function onEnterPress(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            sendMessage();
        }
    }

    function addMessage(type, text) {
        const msgDiv = document.createElement('div');
        msgDiv.className = 'message ' + type;
        msgDiv.textContent = text;
        chatMessages.appendChild(msgDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
        
        while (chatMessages.children.length > 10) {
            chatMessages.removeChild(chatMessages.children[0]);
        }
    }

    function sendMessage() {
        const query = chatInput.value.trim();
        if (!query) return;

        addMessage('user', query);
        showMessages();
        
        setTimeout(() => {
            const responses = [
                "Из базы: перевозка станка Самара → Москва ~47 000₽, срок 2-3 дня. Эксперт — Иван.",
                "Сборный груз Казань: от 45₽/кг, отправка дважды в неделю. Связаться с Марией?",
                "Негабарит Москва-Владивосток: нужен спецтранспорт. Рекомендую Алексея.",
                "По вашему запросу нашёл 12 похожих перевозок. Лучший менеджер по этому направлению — Елена."
            ];
            addMessage('bot', responses[Math.floor(Math.random() * responses.length)]);
        }, 600);

        chatInput.value = '';
        chatInput.focus();
    }

    function quickMessage(text) {
        chatInput.value = text;
        showMessages();
        sendMessage();
    }

    window.onload = function() {
        navDots[0].classList.add('active');
    };
@endsection