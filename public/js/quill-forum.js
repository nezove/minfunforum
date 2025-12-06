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

            ForumQuill.uploadImage(file, quill);
        };
    },

    // Функция загрузки изображения на сервер
    uploadImage: function(file, quill) {
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

        // Получаем позицию курсора
        const range = quill.getSelection(true);
        const cursorPosition = range ? range.index : quill.getLength();

        // Вставляем текстовый индикатор загрузки
        quill.insertText(cursorPosition, '[Загружается изображение...]\n', 'user');
        quill.setSelection(cursorPosition + 27);

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
                // Находим позицию текста индикатора
                const currentText = quill.getText();
                const loaderText = '[Загружается изображение...]\n';
                const loaderIndex = currentText.indexOf(loaderText);

                if (loaderIndex !== -1) {
                    // Удаляем текст индикатора
                    quill.deleteText(loaderIndex, loaderText.length);

                    // Вставляем изображение используя insertEmbed
                    quill.insertEmbed(loaderIndex, 'image', data.imageUrl, 'user');

                    // Добавляем атрибуты к изображению
                    setTimeout(() => {
                        const img = quill.root.querySelector(`img[src="${data.imageUrl}"]`);
                        if (img) {
                            img.setAttribute('title', 'Нажмите для увеличения');
                            img.setAttribute('data-original', data.originalUrl);
                            img.setAttribute('loading', 'lazy');
                            img.classList.add('img-fluid', 'rounded', 'shadow-sm', 'clickable-image');
                            img.style.maxWidth = '100%';
                        }
                    }, 10);

                    // Добавляем перенос строки после изображения
                    quill.insertText(loaderIndex + 1, '\n', 'user');

                    // Перемещаем курсор после изображения и переноса
                    quill.setSelection(loaderIndex + 2, 'user');
                }

                if (typeof showToast === 'function') {
                    showToast('success', 'Готово!', 'Изображение успешно загружено');
                }
            } else {
                // Удаляем индикатор загрузки в случае ошибки
                const currentText = quill.getText();
                const loaderText = '[Загружается изображение...]\n';
                const loaderIndex = currentText.indexOf(loaderText);

                if (loaderIndex !== -1) {
                    quill.deleteText(loaderIndex, loaderText.length, 'user');
                }

                if (typeof showToast === 'function') {
                    showToast('error', 'Ошибка!', data.message || 'Ошибка загрузки изображения');
                } else {
                    alert(data.message || 'Ошибка загрузки изображения');
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);

            // Удаляем индикатор загрузки в случае ошибки
            const currentText = quill.getText();
            const loaderText = '[Загружается изображение...]\n';
            const loaderIndex = currentText.indexOf(loaderText);

            if (loaderIndex !== -1) {
                quill.deleteText(loaderIndex, loaderText.length, 'user');
            }

            if (typeof showToast === 'function') {
                showToast('error', 'Ошибка!', 'Произошла ошибка при загрузке изображения');
            } else {
                alert('Произошла ошибка при загрузке изображения');
            }
        });
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
                },
                clipboard: {
                    matchVisual: false,
                    matchers: [
                        ['IMG', function(node, delta) {
                            // Блокируем вставку base64 изображений
                            return { ops: [] };
                        }]
                    ]
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
                },
                clipboard: {
                    matchVisual: false,
                    matchers: [
                        ['IMG', function(node, delta) {
                            // Блокируем вставку base64 изображений
                            return { ops: [] };
                        }]
                    ]
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

        // Добавляем обработчик вставки изображений через Ctrl+V
        window.mainQuill.root.addEventListener('paste', function(e) {
            const clipboardData = e.clipboardData || window.clipboardData;
            const items = clipboardData.items;

            if (!items) return;

            // Проверяем, есть ли изображение в буфере обмена
            let hasImage = false;
            for (let i = 0; i < items.length; i++) {
                if (items[i].type.indexOf('image') !== -1) {
                    hasImage = true;
                    break;
                }
            }

            // Если есть изображение, полностью предотвращаем стандартную вставку
            if (hasImage) {
                e.preventDefault();
                e.stopPropagation();

                // Загружаем каждое изображение
                for (let i = 0; i < items.length; i++) {
                    const item = items[i];

                    if (item.type.indexOf('image') !== -1) {
                        const file = item.getAsFile();
                        if (file) {
                            ForumQuill.uploadImage(file, window.mainQuill);
                        }
                    }
                }
            }
        }, true); // Используем capturing phase для раннего перехвата
    }

    if (document.getElementById('quill-reply-editor')) {
        window.replyQuill = ForumQuill.initReplyEditor();

        // Добавляем обработчик вставки изображений через Ctrl+V
        window.replyQuill.root.addEventListener('paste', function(e) {
            const clipboardData = e.clipboardData || window.clipboardData;
            const items = clipboardData.items;

            if (!items) return;

            // Проверяем, есть ли изображение в буфере обмена
            let hasImage = false;
            for (let i = 0; i < items.length; i++) {
                if (items[i].type.indexOf('image') !== -1) {
                    hasImage = true;
                    break;
                }
            }

            // Если есть изображение, полностью предотвращаем стандартную вставку
            if (hasImage) {
                e.preventDefault();
                e.stopPropagation();

                // Загружаем каждое изображение
                for (let i = 0; i < items.length; i++) {
                    const item = items[i];

                    if (item.type.indexOf('image') !== -1) {
                        const file = item.getAsFile();
                        if (file) {
                            ForumQuill.uploadImage(file, window.replyQuill);
                        }
                    }
                }
            }
        }, true); // Используем capturing phase для раннего перехвата

        // Инициализация кастомных смайликов для редактора ответов
        if (window.quillEmojis && window.replyQuill) {
            // Ищем toolbar внутри контейнера редактора
            const replyContainer = document.getElementById('quill-reply-editor').parentElement;
            const replyToolbar = replyContainer.querySelector('.ql-toolbar');
            if (replyToolbar) {
                window.quillEmojis.initButton(window.replyQuill, replyToolbar);
                window.quillEmojis.initAutocomplete(window.replyQuill);
            }
        }
    }

    // Инициализация кастомных смайликов для главного редактора
    if (window.quillEmojis && window.mainQuill) {
        // Ищем toolbar внутри контейнера редактора
        const mainContainer = document.getElementById('quill-editor').parentElement;
        const mainToolbar = mainContainer.querySelector('.ql-toolbar');
        if (mainToolbar) {
            window.quillEmojis.initButton(window.mainQuill, mainToolbar);
            window.quillEmojis.initAutocomplete(window.mainQuill);
        }
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