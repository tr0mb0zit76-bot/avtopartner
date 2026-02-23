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