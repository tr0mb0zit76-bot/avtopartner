// public/js/cabinet.js

document.addEventListener('DOMContentLoaded', function() {
    // Переключение темы через AJAX
    const themeForm = document.getElementById('themeForm');
    if (themeForm) {
        themeForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Плавно перезагружаем страницу для применения новой темы
                    document.body.style.opacity = '0.5';
                    setTimeout(() => {
                        location.reload();
                    }, 300);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Если AJAX не сработал, отправляем форму обычным способом
                this.submit();
            });
        });
    }
    
    // Плавное появление страницы
    document.body.style.opacity = '0';
    document.body.style.transition = 'opacity 0.3s';
    setTimeout(() => {
        document.body.style.opacity = '1';
    }, 100);
    
    // Подсветка активного пункта меню (для будущего)
    const currentPath = window.location.pathname;
    document.querySelectorAll('.nav-link').forEach(link => {
        if (link.getAttribute('href') === currentPath) {
            link.classList.add('active');
        }
    });
});