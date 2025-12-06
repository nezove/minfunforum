/**
 * Модуль для работы со смайликами в Quill Editor
 */

class QuillEmojis {
    constructor() {
        this.emojis = [];
        this.categories = [];
        this.isOpen = false;
        this.currentEditor = null;
        this.loadEmojis();
    }

    /**
     * Загрузка смайликов с сервера
     */
    async loadEmojis() {
        try {
            const response = await fetch('/api/emojis/categories');
            const data = await response.json();

            if (data.success) {
                this.categories = data.categories;
                this.emojis = data.categories.flatMap(cat => cat.emojis);
            }
        } catch (error) {
            console.error('Ошибка загрузки смайликов:', error);
        }
    }

    /**
     * Инициализация кнопки смайликов для редактора
     */
    initButton(quillEditor, toolbarSelector) {
        this.currentEditor = quillEditor;

        // Добавляем кнопку смайликов в тулбар
        // toolbarSelector может быть строкой (селектор) или DOM элементом
        const toolbar = typeof toolbarSelector === 'string'
            ? document.querySelector(toolbarSelector)
            : toolbarSelector;

        if (!toolbar) {
            return;
        }

        // Создаем кнопку
        const emojiBtn = document.createElement('button');
        emojiBtn.type = 'button';
        emojiBtn.className = 'ql-emoji';
        emojiBtn.innerHTML = '<i class="bi bi-emoji-smile"></i>';
        emojiBtn.title = 'Вставить смайлик';

        // Добавляем кнопку в тулбар (после кнопки изображения)
        const imageBtn = toolbar.querySelector('.ql-image');
        if (imageBtn) {
            imageBtn.parentNode.insertBefore(emojiBtn, imageBtn.nextSibling);
        } else {
            toolbar.appendChild(emojiBtn);
        }

        // Создаем панель смайликов
        this.createEmojiPanel();

        // Обработчик клика на кнопку
        emojiBtn.addEventListener('click', (e) => {
            e.preventDefault();
            this.togglePanel();
        });
    }

    /**
     * Создание панели со смайликами
     */
    createEmojiPanel() {
        // Удаляем старую панель если есть
        const oldPanel = document.getElementById('quill-emoji-panel');
        if (oldPanel) oldPanel.remove();

        const panel = document.createElement('div');
        panel.id = 'quill-emoji-panel';
        panel.className = 'quill-emoji-panel';
        panel.style.display = 'none';

        // Поиск
        const searchBox = document.createElement('input');
        searchBox.type = 'text';
        searchBox.className = 'form-control form-control-sm mb-2';
        searchBox.placeholder = 'Поиск смайликов...';
        searchBox.addEventListener('input', (e) => this.searchEmojis(e.target.value));

        panel.appendChild(searchBox);

        // Табы категорий
        if (this.categories.length > 0) {
            const tabs = document.createElement('div');
            tabs.className = 'emoji-tabs mb-2';

            this.categories.forEach((category, index) => {
                const tab = document.createElement('button');
                tab.type = 'button';
                tab.className = 'btn btn-sm btn-outline-secondary' + (index === 0 ? ' active' : '');
                tab.textContent = category.icon || category.name;
                tab.title = category.name;
                tab.dataset.categoryId = category.id;

                tab.addEventListener('click', () => {
                    document.querySelectorAll('.emoji-tabs button').forEach(b => b.classList.remove('active'));
                    tab.classList.add('active');
                    this.showCategory(category.id);
                });

                tabs.appendChild(tab);
            });

            panel.appendChild(tabs);
        }

        // Контейнер для смайликов
        const emojiContainer = document.createElement('div');
        emojiContainer.id = 'emoji-container';
        emojiContainer.className = 'emoji-container';
        panel.appendChild(emojiContainer);

        // НЕ показываем категорию здесь - она покажется при открытии панели

        document.body.appendChild(panel);

        // Закрываем при клике вне панели
        document.addEventListener('click', (e) => {
            if (this.isOpen &&
                !panel.contains(e.target) &&
                !e.target.classList.contains('ql-emoji') &&
                !e.target.closest('.ql-emoji')) {
                this.closePanel();
            }
        });
    }

    /**
     * Показать смайлики категории
     */
    showCategory(categoryId) {
        const container = document.getElementById('emoji-container');
        if (!container) return;

        const category = this.categories.find(c => c.id === categoryId);
        if (!category) return;

        container.innerHTML = '';

        category.emojis.forEach(emoji => {
            const emojiEl = this.createEmojiElement(emoji);
            container.appendChild(emojiEl);
        });
    }

    /**
     * Создать элемент смайлика
     */
    createEmojiElement(emoji) {
        const wrapper = document.createElement('div');
        wrapper.className = 'emoji-item';
        wrapper.title = emoji.name;

        const img = document.createElement('img');
        img.src = emoji.url;
        img.alt = emoji.name;
        img.width = emoji.width || 24;
        img.height = emoji.height || 24;
        img.className = 'custom-emoji';
        img.dataset.emojiId = emoji.id;

        wrapper.appendChild(img);

        wrapper.addEventListener('click', () => {
            this.insertEmoji(emoji);
        });

        return wrapper;
    }

    /**
     * Вставить смайлик в редактор
     */
    insertEmoji(emoji) {
        if (!this.currentEditor) return;

        const range = this.currentEditor.getSelection(true);
        const index = range ? range.index : this.currentEditor.getLength();

        // Вставляем изображение
        this.currentEditor.insertEmbed(index, 'image', emoji.url, 'user');

        // Добавляем класс и атрибуты
        setTimeout(() => {
            const imgs = this.currentEditor.root.querySelectorAll('img[src="' + emoji.url + '"]');
            if (imgs.length > 0) {
                const img = imgs[imgs.length - 1];
                img.className = 'custom-emoji';
                img.width = emoji.width || 24;
                img.height = emoji.height || 24;
                img.style.width = (emoji.width || 24) + 'px';
                img.style.height = (emoji.height || 24) + 'px';
                img.style.verticalAlign = 'middle';
                img.style.display = 'inline';
                img.dataset.emojiId = emoji.id;
            }
        }, 10);

        // Перемещаем курсор
        this.currentEditor.setSelection(index + 1, 0);

        // Увеличиваем счетчик использования
        this.incrementUsage(emoji.id);

        this.closePanel();
    }

    /**
     * Поиск смайликов
     */
    async searchEmojis(query) {
        if (query.length < 2) {
            // Показываем первую категорию
            if (this.categories.length > 0) {
                this.showCategory(this.categories[0].id);
            }
            return;
        }

        try {
            const response = await fetch(`/api/emojis/search?q=${encodeURIComponent(query)}`);
            const data = await response.json();

            if (data.success) {
                const container = document.getElementById('emoji-container');
                if (!container) return;

                container.innerHTML = '';

                if (data.emojis.length === 0) {
                    container.innerHTML = '<div class="text-center text-muted py-3">Ничего не найдено</div>';
                    return;
                }

                data.emojis.forEach(emoji => {
                    const emojiEl = this.createEmojiElement(emoji);
                    container.appendChild(emojiEl);
                });
            }
        } catch (error) {
            console.error('Ошибка поиска смайликов:', error);
        }
    }

    /**
     * Увеличить счетчик использования
     */
    async incrementUsage(emojiId) {
        try {
            await fetch('/api/emojis/increment-usage', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ emoji_id: emojiId })
            });
        } catch (error) {
            console.error('Ошибка обновления счетчика:', error);
        }
    }

    /**
     * Открыть/закрыть панель
     */
    togglePanel() {
        const panel = document.getElementById('quill-emoji-panel');
        if (!panel) return;

        if (this.isOpen) {
            this.closePanel();
        } else {
            this.openPanel();
        }
    }

    /**
     * Открыть панель
     */
    openPanel() {
        const panel = document.getElementById('quill-emoji-panel');
        if (!panel) return;

        // Показываем первую категорию при открытии
        if (this.categories.length > 0) {
            this.showCategory(this.categories[0].id);
        }

        // Позиционируем панель рядом с кнопкой
        const emojiBtn = document.querySelector('.ql-emoji');
        if (emojiBtn) {
            const rect = emojiBtn.getBoundingClientRect();
            panel.style.top = (rect.bottom + window.scrollY + 5) + 'px';
            panel.style.left = rect.left + 'px';
        }

        panel.style.display = 'block';
        this.isOpen = true;
    }

    /**
     * Закрыть панель
     */
    closePanel() {
        const panel = document.getElementById('quill-emoji-panel');
        if (panel) {
            panel.style.display = 'none';
        }
        this.isOpen = false;
    }

    /**
     * Автоподстановка по ключевым словам
     */
    initAutocomplete(quillEditor) {
        let typingTimeout;

        quillEditor.on('text-change', (delta, oldDelta, source) => {
            if (source !== 'user') return;

            clearTimeout(typingTimeout);
            typingTimeout = setTimeout(() => {
                this.checkForKeywords(quillEditor);
            }, 500);
        });
    }

    /**
     * Проверка текста на ключевые слова
     */
    async checkForKeywords(quillEditor) {
        const text = quillEditor.getText().trim();

        // Разбиваем на слова и берём последнее непустое
        const words = text.split(/\s+/).filter(w => w.length > 0);
        const lastWord = words.length > 0 ? words[words.length - 1].toLowerCase().trim() : '';

        if (lastWord.length < 2) {
            // Скрываем подсказку если слово слишком короткое
            this.hideSuggestion();
            return;
        }

        // Ищем ВСЕ смайлики по ключевому слову (может быть несколько)
        const matchedEmojis = this.emojis.filter(emoji => {
            return emoji.keywords && emoji.keywords.some(keyword =>
                keyword.toLowerCase().trim().includes(lastWord)
            );
        });

        if (matchedEmojis.length > 0) {
            this.showSuggestions(quillEditor, matchedEmojis, lastWord);
        } else {
            this.hideSuggestion();
        }
    }

    /**
     * Показать подсказки с несколькими смайликами
     */
    showSuggestions(quillEditor, emojis, keyword) {
        // Удаляем старую панель если есть
        const existing = document.getElementById('emoji-suggestion');
        if (existing) existing.remove();

        const tooltip = document.createElement('div');
        tooltip.id = 'emoji-suggestion';
        tooltip.className = 'emoji-suggestion card shadow-sm';

        const emojisHtml = emojis.map(emoji => `
            <img src="${emoji.url}"
                 width="${emoji.width || 24}"
                 height="${emoji.height || 24}"
                 alt="${emoji.name}"
                 title="${emoji.name}"
                 class="emoji-suggestion-item"
                 data-emoji-id="${emoji.id}"
                 data-emoji-url="${emoji.url}"
                 data-emoji-width="${emoji.width || 24}"
                 data-emoji-height="${emoji.height || 24}"
                 style="cursor: pointer; margin: 2px; border-radius: 4px; padding: 2px;">
        `).join('');

        tooltip.innerHTML = `
            <div class="card-body p-2">
                <small class="text-muted d-block mb-2">Выберите смайлик:</small>
                <div class="d-flex flex-wrap gap-1">
                    ${emojisHtml}
                </div>
            </div>
        `;

        document.body.appendChild(tooltip);

        // Позиционируем около курсора
        const selection = quillEditor.getSelection();
        if (selection) {
            const bounds = quillEditor.getBounds(selection.index);
            const editorRect = quillEditor.root.getBoundingClientRect();
            tooltip.style.top = (editorRect.top + bounds.bottom + window.scrollY) + 'px';
            tooltip.style.left = (editorRect.left + bounds.left) + 'px';
        }

        // Обработчики клика на смайлики
        tooltip.querySelectorAll('.emoji-suggestion-item').forEach(img => {
            img.addEventListener('click', () => {
                const emojiData = {
                    id: img.dataset.emojiId,
                    url: img.dataset.emojiUrl,
                    width: parseInt(img.dataset.emojiWidth),
                    height: parseInt(img.dataset.emojiHeight)
                };
                this.insertEmojiWithoutDeletingKeyword(quillEditor, emojiData);
                tooltip.remove();
            });

            // Эффект при наведении
            img.addEventListener('mouseenter', () => {
                img.style.backgroundColor = '#f0f0f0';
            });
            img.addEventListener('mouseleave', () => {
                img.style.backgroundColor = 'transparent';
            });
        });

        // Автоматически скрыть через 10 секунд
        setTimeout(() => {
            if (tooltip.parentNode) {
                tooltip.remove();
            }
        }, 10000);
    }

    /**
     * Вставить смайлик БЕЗ удаления ключевого слова
     */
    insertEmojiWithoutDeletingKeyword(quillEditor, emoji) {
        const range = quillEditor.getSelection(true);
        const index = range ? range.index : quillEditor.getLength();

        // Вставляем изображение на текущую позицию курсора
        quillEditor.insertEmbed(index, 'image', emoji.url, 'user');

        // Добавляем класс и атрибуты
        setTimeout(() => {
            const imgs = quillEditor.root.querySelectorAll('img[src="' + emoji.url + '"]');
            if (imgs.length > 0) {
                const img = imgs[imgs.length - 1];
                img.className = 'custom-emoji';
                img.width = emoji.width || 24;
                img.height = emoji.height || 24;
                img.style.width = (emoji.width || 24) + 'px';
                img.style.height = (emoji.height || 24) + 'px';
                img.style.verticalAlign = 'middle';
                img.style.display = 'inline';
                img.dataset.emojiId = emoji.id;
            }
        }, 10);

        // Перемещаем курсор после смайлика
        quillEditor.setSelection(index + 1, 0);

        // Увеличиваем счетчик использования
        this.incrementUsage(emoji.id);
    }

    /**
     * Скрыть подсказки
     */
    hideSuggestion() {
        const existing = document.getElementById('emoji-suggestion');
        if (existing) {
            existing.remove();
        }
    }
}

// Глобальный экземпляр
window.quillEmojis = new QuillEmojis();
