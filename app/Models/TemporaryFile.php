<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class TemporaryFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id', 'user_id', 'filename', 'original_name', 'file_path', 'file_size', 
        'mime_type', 'file_type', 'thumbnail_path', 'width', 'height', 'description', 'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'width' => 'integer',
        'height' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired()
    {
        return $this->expires_at < now();
    }

    public function deletePhysicalFile()
    {
        // Удаляем основной файл
        if ($this->file_path && Storage::disk('public')->exists($this->file_path)) {
            Storage::disk('public')->delete($this->file_path);
        }
        
        // Удаляем миниатюру если она есть
        if ($this->thumbnail_path && Storage::disk('public')->exists($this->thumbnail_path)) {
            Storage::disk('public')->delete($this->thumbnail_path);
        }
    }

    public function deleteCompletely()
    {
        $this->deletePhysicalFile();
        $this->delete();
    }

    public static function expired()
    {
        return static::where('expires_at', '<', now());
    }

    public static function forCurrentUser($fileType = null)
    {
        $query = static::where('expires_at', '>', now());

        if ($fileType) {
            $query->where('file_type', $fileType);
        }

        if (auth()->check()) {
            $query->where('user_id', auth()->id());
        } else {
            $query->where('session_id', session()->getId());
        }

        return $query->latest();
    }

    public static function createTempFile($file, $fileType = 'file', $hoursToExpire = 24)
    {
        $filename = \Str::uuid() . '.' . $file->getClientOriginalExtension();
        
        $folderMap = [
            'file' => 'temp/files',
            'image' => 'temp/images', 
            'gallery' => 'temp/gallery'
        ];
        
        $folder = $folderMap[$fileType] ?? 'temp/files';
        $path = $file->storeAs($folder, $filename, 'public');

        return static::create([
            'session_id' => session()->getId(),
            'user_id' => auth()->id(),
            'filename' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'file_type' => $fileType,
            'expires_at' => now()->addHours($hoursToExpire),
        ]);
    }

    public static function createTempImage($imageData, $fileType = 'gallery', $hoursToExpire = 24)
    {
        return static::create([
            'session_id' => session()->getId(),
            'user_id' => auth()->id(),
            'filename' => $imageData['filename'],
            'original_name' => $imageData['original_name'],
            'file_path' => $imageData['original_path'],
            'thumbnail_path' => $imageData['thumbnail_path'] ?? null,
            'file_size' => $imageData['file_size'],
            'mime_type' => $imageData['mime_type'] ?? 'image/jpeg',
            'file_type' => $fileType,
            'width' => $imageData['width'] ?? null,
            'height' => $imageData['height'] ?? null,
            'description' => $imageData['description'] ?? null,
            'expires_at' => now()->addHours($hoursToExpire),
        ]);
    }
}