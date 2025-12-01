<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Controllers\FileController;

class PostFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'original_name',
        'file_path',
        'file_size',
        'mime_type',
    ];

    protected $appends = [
        'download_url',
        'formatted_size'
    ];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function getDownloadUrlAttribute()
    {
        // Генерируем безопасную хешированную ссылку
        return FileController::generateDownloadUrl($this->id, 'post');
    }

    public function getFormattedSizeAttribute()
    {
        $bytes = $this->file_size;
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            return $bytes . ' bytes';
        } elseif ($bytes == 1) {
            return $bytes . ' byte';
        } else {
            return '0 bytes';
        }
    }
}