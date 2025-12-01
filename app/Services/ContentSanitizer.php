<?php

namespace App\Services;

use HTMLPurifier;
use HTMLPurifier_Config;

class ContentSanitizer
{
    private $purifier;
    
    public function __construct()
    {
        $config = HTMLPurifier_Config::createDefault();
        
        // Строгие настройки безопасности
        $config->set('HTML.Allowed', 'p,img[src|alt|title|width|height],br,strong,em,u,code,pre,blockquote,ul,ol,li,a[href],h1,h2,h3,h4,h5,h6');
        $config->set('HTML.ForbiddenElements', 'script,object,embed,applet,form,input,textarea,button,select,option,iframe,frame');
       $config->set('HTML.ForbiddenAttributes', 'on*,style,class,id,onclick,onload,onerror');
       $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'data' => true]);
        $config->set('URI.DisableExternalResources', false);
        $config->set('HTML.Nofollow', true);
        $config->set('HTML.TargetBlank', true);
        
        $this->purifier = new HTMLPurifier($config);
    }
    
    public function sanitize($content)
    {
        return $this->purifier->purify($content);
    }
    
    public function sanitizeTitle($title)
    {
        // Для заголовков убираем все HTML
        return strip_tags($title);
    }
}