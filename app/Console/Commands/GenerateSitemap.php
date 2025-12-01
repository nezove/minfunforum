<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class GenerateSitemap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitemap:generate {--submit : Submit sitemap to search engines}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Generate and optionally submit sitemap to search engines';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Generating sitemap...');

        try {
            // Генерируем основной sitemap index
            $this->generateSitemapFile('sitemap.xml', route('sitemap.index'));
            
            // Генерируем отдельные sitemap файлы
            $this->generateSitemapFile('sitemap-main.xml', route('sitemap.main'));
            $this->generateSitemapFile('sitemap-categories.xml', route('sitemap.categories'));
            $this->generateSitemapFile('sitemap-topics.xml', route('sitemap.topics'));

            $this->info('Sitemap files generated successfully!');

            // Если указана опция --submit, отправляем в поисковые системы
            if ($this->option('submit')) {
                $this->submitToSearchEngines();
            }

            $this->info('Sitemap generation completed!');

        } catch (\Exception $e) {
            $this->error('Error generating sitemap: ' . $e->getMessage());
            Log::error('Sitemap generation failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Генерирует отдельный sitemap файл
     */
    private function generateSitemapFile($filename, $url)
    {
        $this->line("Generating {$filename}...");
        
        $response = Http::get($url);
        
        if ($response->successful()) {
            Storage::disk('public')->put($filename, $response->body());
            $this->line("✓ {$filename} saved to storage/app/public/{$filename}");
        } else {
            throw new \Exception("Failed to generate {$filename}: " . $response->status());
        }
    }

    /**
     * Отправляет sitemap в поисковые системы
     */
    private function submitToSearchEngines()
    {
        $this->info('Submitting sitemap to search engines...');
        
        $sitemapUrl = url('/sitemap.xml');
        
        // Google
        $this->submitToGoogle($sitemapUrl);
        
        // Bing
        $this->submitToBing($sitemapUrl);
        
        // Yandex
        $this->submitToYandex($sitemapUrl);
    }

    /**
     * Отправляет sitemap в Google
     */
    private function submitToGoogle($sitemapUrl)
    {
        try {
            $response = Http::get("https://www.google.com/ping?sitemap={$sitemapUrl}");
            
            if ($response->successful()) {
                $this->info('✓ Successfully submitted to Google');
            } else {
                $this->warn('⚠ Failed to submit to Google: ' . $response->status());
            }
        } catch (\Exception $e) {
            $this->warn('⚠ Error submitting to Google: ' . $e->getMessage());
        }
    }

    /**
     * Отправляет sitemap в Bing
     */
    private function submitToBing($sitemapUrl)
    {
        try {
            $response = Http::get("https://www.bing.com/ping?sitemap={$sitemapUrl}");
            
            if ($response->successful()) {
                $this->info('✓ Successfully submitted to Bing');
            } else {
                $this->warn('⚠ Failed to submit to Bing: ' . $response->status());
            }
        } catch (\Exception $e) {
            $this->warn('⚠ Error submitting to Bing: ' . $e->getMessage());
        }
    }

    /**
     * Отправляет sitemap в Yandex
     */
    private function submitToYandex($sitemapUrl)
    {
        try {
            $response = Http::get("https://webmaster.yandex.ru/ping?sitemap={$sitemapUrl}");
            
            if ($response->successful()) {
                $this->info('✓ Successfully submitted to Yandex');
            } else {
                $this->warn('⚠ Failed to submit to Yandex: ' . $response->status());
            }
        } catch (\Exception $e) {
            $this->warn('⚠ Error submitting to Yandex: ' . $e->getMessage());
        }
    }
}