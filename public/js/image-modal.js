// Функции для работы с модальным окном изображений
class ImageModal {
    constructor() {
        this.modal = null;
        this.createModal();
        this.bindEvents();
    }

    createModal() {
        // Создаем модальное окно
        const modal = document.createElement('div');
        modal.className = 'image-modal';
        modal.id = 'imageModal';
        
        modal.innerHTML = `
            <div class="image-modal-content">
                <span class="image-modal-close">&times;</span>
                <div class="image-modal-loading">Загрузка...</div>
                <img id="modalImage" style="display: none;" alt="Увеличенное изображение">
            </div>
        `;
        
        document.body.appendChild(modal);
        this.modal = modal;
    }

    bindEvents() {
        // Закрытие по клику на крестик
        this.modal.querySelector('.image-modal-close').addEventListener('click', () => {
            this.close();
        });

        // Закрытие по клику вне изображения
        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) {
                this.close();
            }
        });

        // Закрытие по ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.modal.style.display === 'block') {
                this.close();
            }
        });

        // Привязываем клики к изображениям
        this.bindImageClicks();
    }

    bindImageClicks() {
        // Привязываем обработчики к существующим изображениям
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('clickable-image')) {
                const originalUrl = e.target.dataset.original;
                if (originalUrl) {
                    this.open(originalUrl);
                }
            }
        });
    }

    open(imageSrc) {
        const modalImage = this.modal.querySelector('#modalImage');
        const loading = this.modal.querySelector('.image-modal-loading');
        
        // Показываем модальное окно
        this.modal.style.display = 'block';
        
        // Показываем загрузку
        loading.style.display = 'block';
        modalImage.style.display = 'none';
        
        // Загружаем изображение
        modalImage.onload = () => {
            loading.style.display = 'none';
            modalImage.style.display = 'block';
        };
        
        modalImage.onerror = () => {
            loading.innerHTML = 'Ошибка загрузки изображения';
        };
        
        modalImage.src = imageSrc;
    }

    close() {
        this.modal.style.display = 'none';
        const modalImage = this.modal.querySelector('#modalImage');
        modalImage.src = '';
    }
}

// Инициализируем модальное окно после загрузки DOM
document.addEventListener('DOMContentLoaded', () => {
    window.imageModal = new ImageModal();
});

// Функция для обновления изображений в контенте (после AJAX загрузки)
function updateImageClicks() {
    // Добавляем классы и атрибуты к новым изображениям
    document.querySelectorAll('img[data-original]:not(.clickable-image)').forEach(img => {
        img.classList.add('clickable-image');
    });
}