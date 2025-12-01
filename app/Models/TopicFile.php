<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Controllers\FileController;

class TopicFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'topic_id', 'filename', 'original_name', 'file_path', 'file_size', 'mime_type'
    ];

    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }

    public function getDownloadUrlAttribute()
    {
        // Генерируем безопасную хешированную ссылку
        return FileController::generateDownloadUrl($this->id, 'topic');
    }

    public function getFileSizeHumanAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getFormattedSizeAttribute()
    {
        return $this->getFileSizeHumanAttribute();
    }
}