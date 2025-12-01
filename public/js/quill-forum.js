// Конфигурация Quill для форума
window.ForumQuill = {
    // Общие настройки тулбара
    toolbarOptions: [
        [{ 'header': [1, 2, 3, false] }],
        ['bold', 'italic', 'underline', 'strike'],
        [{ 'color': [] }, { 'background': [] }],
        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
        [{ 'indent': '-1'}, { 'indent': '+1' }],
        ['blockquote', 'code-block'],
        ['link', 'image'],
        ['clean']
    ],

    // Функция загрузки изображений
    imageHandler: function() {
        const input = document.createElement('input');
        input.setAttribute('type', 'file');
        input.setAttribute('accept', 'image/*');
        input.click();

        const quill = this.quill;
        
        input.onchange = function() {
            const file = input.files[0];
            
            if (!file) return;

            // Проверка размера файла
            if (file.size > 10 * 1024 * 1024) {
                if (typeof showToast === 'function') {
                    showToast('error', 'Ошибка!', 'Размер изображения не должен превышать 10MB');
                } else {
                    alert('Размер изображения не должен превышать 10MB');
                }
                return;
            }

            // Проверка типа файла
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                if (typeof showToast === 'function') {
                    showToast('error', 'Ошибка!', 'Разрешены только изображения (JPEG, PNG, GIF, WebP)');
                } else {
                    alert('Разрешены только изображения (JPEG, PNG, GIF, WebP)');
                }
                return;
            }

            // Подготовка данных для отправки
            const formData = new FormData();
            formData.append('image', file);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

            // Показываем индикатор загрузки
            const range = quill.getSelection() || { index: 0, length: 0 };
            quill.insertText(range.index, '[Загружается изображение...]');
            
            // Отправляем на сервер
            fetch('/images/upload', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Удаляем текст загрузки
                    quill.deleteText(range.index, '[Загружается изображение...]'.length);
                    
                    // Создаем кастомный HTML для изображения
                   const imageHtml = `<p><img title="Нажмите для увеличения" data-original="${data.originalUrl}" style="max-width: 100%;" loading="lazy" class="img-fluid rounded shadow-sm clickable-image" alt="Изображение" src="${data.imageUrl}"></p>`;

                    // Вставляем как HTML блок
                    quill.clipboard.dangerouslyPasteHTML(range.index, imageHtml);
                    
                    if (typeof showToast === 'function') {
                        showToast('success', 'Готово!', 'Изображение успешно загружено');
                    }
                } else {
                    // Удаляем текст загрузки в случае ошибки
                    quill.deleteText(range.index, '[Загружается изображение...]'.length);
                    if (typeof showToast === 'function') {
                        showToast('error', 'Ошибка!', data.message || 'Ошибка загрузки изображения');
                    } else {
                        alert(data.message || 'Ошибка загрузки изображения');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Удаляем текст загрузки в случае ошибки
                quill.deleteText(range.index, '[Загружается изображение...]'.length);
                if (typeof showToast === 'function') {
                    showToast('error', 'Ошибка!', 'Произошла ошибка при загрузке изображения');
                } else {
                    alert('Произошла ошибка при загрузке изображения');
                }
            });
        };
    },

    // Инициализация основного редактора (создание/редактирование тем)
    initMainEditor: function() {
        if (!document.getElementById('quill-editor')) return;

        const quill = new Quill('#quill-editor', {
            theme: 'snow',
            placeholder: 'Опишите вашу тему подробно...',
            modules: {
                toolbar: {
                    container: this.toolbarOptions,
                    handlers: {
                        image: this.imageHandler
                    }
                }
            }
        });

        // Загружаем существующий контент (для редактирования)
        const hiddenInput = document.getElementById('content');
        if (hiddenInput && hiddenInput.value.trim()) {
            quill.root.innerHTML = hiddenInput.value;
        }

        // Синхронизация с скрытым полем
        quill.on('text-change', () => {
            if (hiddenInput) {
                hiddenInput.value = quill.root.innerHTML;
            }
            this.updateCharCount('char-count', quill);
        });

        // Горячие клавиши
        quill.keyboard.addBinding({
            key: 'B',
            ctrlKey: true
        }, () => {
            quill.format('bold', !quill.getFormat().bold);
        });

        quill.keyboard.addBinding({
            key: 'I',
            ctrlKey: true
        }, () => {
            quill.format('italic', !quill.getFormat().italic);
        });

        quill.keyboard.addBinding({
            key: 'Enter',
            ctrlKey: true
        }, () => {
            const form = document.getElementById('topic-form');
            if (form) {
                form.submit();
            }
        });

        // Обновляем счетчик при инициализации
        this.updateCharCount('char-count', quill);

        return quill;
    },

    // Инициализация редактора ответов
    initReplyEditor: function() {
        if (!document.getElementById('quill-reply-editor')) return;

        const quill = new Quill('#quill-reply-editor', {
            theme: 'snow',
            placeholder: 'Введите ваш ответ...\n\nИспользуйте панель инструментов для форматирования',
            modules: {
                toolbar: {
                    container: this.toolbarOptions,
                    handlers: {
                        image: this.imageHandler
                    }
                }
            }
        });

        // Синхронизация с скрытым полем
        const hiddenInput = document.getElementById('content');
        quill.on('text-change', () => {
            if (hiddenInput) {
                hiddenInput.value = quill.root.innerHTML;
            }
            this.updateCharCount('reply-char-count', quill);
        });

        // Горячие клавиши
        quill.keyboard.addBinding({
            key: 'B',
            ctrlKey: true
        }, () => {
            quill.format('bold', !quill.getFormat().bold);
        });

        quill.keyboard.addBinding({
            key: 'I',
            ctrlKey: true
        }, () => {
            quill.format('italic', !quill.getFormat().italic);
        });

        // Обновляем счетчик при инициализации
        this.updateCharCount('reply-char-count', quill);

        return quill;
    },

    // Обновление счетчика символов
    updateCharCount: function(elementId, quill) {
        const counter = document.getElementById(elementId);
        if (!counter) return;

        const text = quill.getText();
        const length = text.trim().length;
        counter.textContent = `${length} символов`;

        // Цветовая индикация
        if (length > 10000) {
            counter.style.color = '#dc3545'; // danger
        } else if (length > 5000) {
            counter.style.color = '#fd7e14'; // warning
        } else {
            counter.style.color = '#6c757d'; // muted
        }
    },

    // Функция для ответа на пост (добавляет @упоминание)
    replyToUser: function(username, quill) {
        if (!quill) return;
        
        const mentionText = `@${username} `;
        const range = quill.getSelection() || { index: 0, length: 0 };
        
        quill.insertText(range.index, mentionText, 'bold', true);
        quill.setSelection(range.index + mentionText.length);
        quill.focus();
    }
};

// Автоинициализация при загрузке DOM
document.addEventListener('DOMContentLoaded', function() {
    // Инициализируем соответствующий редактор
    if (document.getElementById('quill-editor')) {
        window.mainQuill = ForumQuill.initMainEditor();
    }
    
    if (document.getElementById('quill-reply-editor')) {
        window.replyQuill = ForumQuill.initReplyEditor();
    }

    // Обработка кликов на изображения для модального окна
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('clickable-image')) {
            e.preventDefault(); // Предотвращаем навигацию
            const originalUrl = e.target.getAttribute('data-original');
            if (originalUrl) {
                // Используем существующий модальный код
                const modalImage = document.getElementById('modalImage');
                const imageModal = document.getElementById('imageModal');
                if (modalImage && imageModal) {
                    modalImage.src = originalUrl;
                    const modal = new bootstrap.Modal(imageModal);
                    modal.show();
                }
            }
        }
    });
});