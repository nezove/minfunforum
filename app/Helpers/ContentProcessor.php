<?php

namespace App\Helpers;

class ContentProcessor
{
    /**
     * Обработка контента без лишних <br> тегов
     */
    public static function processContent($content)
    {
        if (empty($content)) {
            return $content;
        }

        try {
            // Применяем обработку упоминаний
            $processedContent = MentionHelper::parseUserMentions($content);
            
            // Проверяем, является ли контент уже HTML
            if (strip_tags($processedContent) !== $processedContent) {
                // Это уже HTML - только очищаем
                $cleanContent = \Mews\Purifier\Facades\Purifier::clean($processedContent, 'forum');
            } else {
                // Это markdown - конвертируем в HTML
                $markdownContent = \Illuminate\Support\Str::markdown($processedContent);
                $cleanContent = \Mews\Purifier\Facades\Purifier::clean($markdownContent, 'forum');
            }

            // Обрабатываем изображения (без скриптов)
            return self::processImages($cleanContent);
            
        } catch (\Exception $e) {
            \Log::error('Content processing error: ' . $e->getMessage(), [
                'content_length' => strlen($content),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Fallback - безопасная обработка
            return self::fallbackProcessing($content);
        }
    }

    /**
     * Обработка изображений в контенте с поддержкой кликабельности
     */
    private static function processImages($content)
    {
        return preg_replace_callback(
            '/<img([^>]*?)src=["\']([^"\']+)["\']([^>]*?)>/i',
            function ($matches) {
                $fullMatch = $matches[0];
                $beforeSrc = $matches[1];
                $srcUrl = $matches[2];
                $afterSrc = $matches[3];

                // Если это наше оригинальное изображение - заменяем на превью с кликабельностью
                if (strpos($srcUrl, '/storage/images/originals/') !== false && strpos($srcUrl, '_original.jpg') !== false) {
                    $thumbnailUrl = str_replace('_original.jpg', '_thumb.jpg', $srcUrl);
                    $thumbnailUrl = str_replace('/originals/', '/thumbnails/', $thumbnailUrl);

                    return sprintf(
                         '<img%s src="%s"%s class="img-fluid rounded shadow-sm clickable-image" loading="lazy" style="max-width: 100%%; height: auto; cursor: pointer;" data-original="%s" title="Нажмите для увеличения">',
                        $beforeSrc,
                        $thumbnailUrl,
                        $afterSrc,
                        $srcUrl,
                        $srcUrl,
                        'Изображение'
                    );
                }
                
                // Если это уже превью - добавляем кликабельность
                if (strpos($srcUrl, '/storage/images/thumbnails/') !== false && strpos($srcUrl, '_thumb.jpg') !== false) {
                    $originalUrl = str_replace('_thumb.jpg', '_original.jpg', $srcUrl);
                    $originalUrl = str_replace('/thumbnails/', '/originals/', $originalUrl);

                    return sprintf(
                         '<img%s src="%s"%s class="img-fluid rounded shadow-sm clickable-image" loading="lazy" style="max-width: 100%%; height: auto; cursor: pointer;" data-original="%s" title="Нажмите для увеличения">',
                        $beforeSrc,
                        $srcUrl,
                        $afterSrc,
                        $originalUrl,
                        $originalUrl,
                        'Изображение'
                    );
                }
                
                // Для внешних изображений добавляем стили и кликабельность
                if (!strpos($fullMatch, 'class=')) {
                    return sprintf(
                         '<img%s src="%s"%s class="img-fluid rounded shadow-sm clickable-image" loading="lazy" style="max-width: 100%%; height: auto; cursor: pointer;" data-original="%s" title="Нажмите для увеличения">',
                        $beforeSrc,
                        $srcUrl,
                        $afterSrc,
                        $srcUrl,
                        'Изображение'
                    );
                }
                
                return $fullMatch;
            },
            $content
        );
    }

    /**
     * Добавление JavaScript для модального окна изображений
     */
    private static function addImageModalScript($content)
    {
        // Проверяем, есть ли в контенте кликабельные изображения
        if (strpos($content, 'clickable-image') === false) {
            return $content;
        }

        $modalScript = '
<script>
if (typeof showImageModal === "undefined") {
    // Создаем модальное окно только один раз
    function createImageModal() {
        if (document.getElementById("imageModal")) return;
        
        const modal = document.createElement("div");
        modal.id = "imageModal";
        modal.className = "modal fade";
        modal.tabIndex = -1;
        modal.setAttribute("aria-labelledby", "imageModalLabel");
        modal.setAttribute("aria-hidden", "true");
        
        modal.innerHTML = `
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content bg-dark">
                    <div class="modal-header border-secondary">
                        <h5 class="modal-title text-white" id="imageModalLabel">Изображение</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                    </div>
                    <div class="modal-body text-center p-2">
                        <img id="modalImage" src="" alt="" class="img-fluid rounded" style="max-height: 80vh; max-width: 100%;">
                    </div>
                    <div class="modal-footer border-secondary justify-content-center">
                        <a id="downloadImageBtn" href="" download class="btn btn-outline-light btn-sm">
                            <i class="bi bi-download me-1"></i>Скачать
                        </a>
                        <a id="openImageBtn" href="" target="_blank" class="btn btn-outline-light btn-sm">
                            <i class="bi bi-box-arrow-up-right me-1"></i>Открыть в новой вкладке
                        </a>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
    }

    // Функция показа модального окна
    function showImageModal(imageSrc, imageAlt) {
        createImageModal();
        
        const modal = new bootstrap.Modal(document.getElementById("imageModal"));
        const modalImage = document.getElementById("modalImage");
        const modalTitle = document.getElementById("imageModalLabel");
        const downloadBtn = document.getElementById("downloadImageBtn");
        const openBtn = document.getElementById("openImageBtn");
        
        modalImage.src = imageSrc;
        modalImage.alt = imageAlt || "Изображение";
        modalTitle.textContent = imageAlt || "Изображение";
        downloadBtn.href = imageSrc;
        openBtn.href = imageSrc;
        
        // Добавляем загрузчик
        modalImage.style.opacity = "0.5";
        modalImage.onload = function() {
            this.style.opacity = "1";
        };
        
        modal.show();
    }

    // Добавляем обработчики для всех кликабельных изображений
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll(".clickable-image").forEach(function(img) {
            if (!img.onclick) {
                img.style.cursor = "pointer";
                img.addEventListener("click", function() {
                    const originalSrc = this.getAttribute("data-original") || this.src;
                    const alt = this.alt || "Изображение";
                    showImageModal(originalSrc, alt);
                });
            }
        });
    });

    // Клавиши для модального окна
    document.addEventListener("keydown", function(e) {
        const modal = document.getElementById("imageModal");
        if (modal && modal.classList.contains("show")) {
            if (e.key === "Escape") {
                bootstrap.Modal.getInstance(modal).hide();
            }
        }
    });
}
</script>';

        return $content . $modalScript;
    }

    /**
     * Fallback обработка при ошибках
     */
    private static function fallbackProcessing($content)
    {
        // Минимальная безопасная обработка
        $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
        $content = nl2br($content);
        
        return $content;
    }

    /**
     * Обработка только текста без HTML (для заголовков)
     */
    public static function processPlainText($text)
    {
        if (empty($text)) {
            return $text;
        }

        // Убираем все HTML теги
        $text = strip_tags($text);
        
        // Обрабатываем упоминания
        $text = MentionHelper::parseUserMentions($text);
        
        // Экранируем HTML сущности
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Быстрая обработка без Purifier (для случаев ошибок)
     */
    public static function processContentSimple($content)
    {
        if (empty($content)) {
            return $content;
        }

        try {
            // Применяем обработку упоминаний
            $processedContent = MentionHelper::parseUserMentions($content);
            
            // Если это HTML - оставляем как есть, если markdown - конвертируем
            if (strip_tags($processedContent) === $processedContent) {
                $processedContent = \Illuminate\Support\Str::markdown($processedContent);
            }

            // Обрабатываем изображения (без скриптов)
            return self::processImages($processedContent);
            
        } catch (\Exception $e) {
            \Log::error('Simple content processing error: ' . $e->getMessage());
            return self::fallbackProcessing($content);
        }
    }
}