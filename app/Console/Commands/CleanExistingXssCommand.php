<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Topic;
use App\Models\Post;
use App\Models\User;
use HTMLPurifier;
use HTMLPurifier_Config;

class CleanExistingXssCommand extends Command
{
    protected $signature = 'forum:clean-xss';
    protected $description = 'Очистить существующий контент от XSS';

    public function handle()
    {
        $this->info('Начинаю очистку существующего контента от XSS...');
        
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'p,br,strong,em,u,s,code,pre,blockquote,ul,ol,li,a[href],img[src|alt],h1,h2,h3,h4,h5,h6');
        $config->set('HTML.ForbiddenElements', 'script,object,embed,applet,form,input,textarea,button,select,option');
        $config->set('HTML.ForbiddenAttributes', 'on*,style');
        $config->set('URI.AllowedSchemes', array('http' => true, 'https' => true, 'mailto' => true));
        $config->set('AutoFormat.Linkify', true);
        
        $purifier = new HTMLPurifier($config);
        
        // Очистка тем
        $this->info('Очистка тем...');
        $topics = Topic::all();
        $topicsUpdated = 0;
        
        foreach ($topics as $topic) {
            $originalTitle = $topic->title;
            $originalContent = $topic->content;
            
            $cleanTitle = $purifier->purify($originalTitle);
            $cleanContent = $purifier->purify($originalContent);
            
            if ($originalTitle !== $cleanTitle || $originalContent !== $cleanContent) {
                $topic->update([
                    'title' => $cleanTitle,
                    'content' => $cleanContent
                ]);
                $topicsUpdated++;
                $this->line("Очищена тема ID: {$topic->id}");
            }
        }
        
        // Очистка постов
        $this->info('Очистка постов...');
        $posts = Post::all();
        $postsUpdated = 0;
        
        foreach ($posts as $post) {
            $originalContent = $post->content;
            $cleanContent = $purifier->purify($originalContent);
            
            if ($originalContent !== $cleanContent) {
                $post->update(['content' => $cleanContent]);
                $postsUpdated++;
                $this->line("Очищен пост ID: {$post->id}");
            }
        }
        
        // Очистка био пользователей
        $this->info('Очистка профилей пользователей...');
        $users = User::whereNotNull('bio')->get();
        $usersUpdated = 0;
        
        foreach ($users as $user) {
            $originalBio = $user->bio;
            $cleanBio = $purifier->purify($originalBio);
            
            if ($originalBio !== $cleanBio) {
                $user->update(['bio' => $cleanBio]);
                $usersUpdated++;
                $this->line("Очищен профиль пользователя ID: {$user->id}");
            }
        }
        
        $this->info("Очистка завершена!");
        $this->info("Обновлено тем: {$topicsUpdated}");
        $this->info("Обновлено постов: {$postsUpdated}");
        $this->info("Обновлено профилей: {$usersUpdated}");
        
        return 0;
    }
}