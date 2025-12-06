<?php

// Создай файл app/Services/MarkdownService.php

namespace App\Services;

use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\CommonMark\Renderer\Inline\ImageRenderer;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;

class BootstrapImageRenderer implements NodeRendererInterface
{
    public function render($node, ChildNodeRendererInterface $childRenderer)
    {
        if (!($node instanceof Image)) {
            throw new \InvalidArgumentException('Incompatible node type: ' . get_class($node));
        }

        $attrs = [
            'src' => $node->getUrl(),
            'alt' => $childRenderer->renderNodes($node->children()),
            'class' => 'img-fluid rounded shadow-sm', // Bootstrap классы
            'loading' => 'lazy', // Ленивая загрузка
            'style' => 'max-width: 100%; height: auto;' // Дополнительная защита
        ];

        if ($node->getTitle()) {
            $attrs['title'] = $node->getTitle();
        }

        return new HtmlElement('img', $attrs, '', true);
    }
}

class MarkdownService
{
    protected $converter;

    public function __construct()
    {
        $environment = new Environment();
        $environment->addExtension(new CommonMarkCoreExtension());
        
        // Заменяем стандартный рендерер изображений на наш
        $environment->addRenderer(Image::class, new BootstrapImageRenderer());

        $this->converter = new CommonMarkConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ], $environment);
    }

    public function toHtml($markdown)
    {
        return $this->converter->convert($markdown)->getContent();
    }
}