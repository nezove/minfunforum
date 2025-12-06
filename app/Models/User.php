<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\UserSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use App\Models\Post;
use App\Models\Topic; 
use App\Models\Like;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'name',
        'email',
        'password',
        'avatar',
        'bio',
        'location',
        'website',
        'role',
        'is_banned',
        'banned_at',
        'banned_until',
        'ban_reason',
        'banned_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'last_activity_at' => 'datetime',
        'banned_at' => 'datetime',
        'banned_until' => 'datetime',
        'is_banned' => 'boolean',
    ];

    // === РОЛИ И ПРАВА ===
    
    /**
     * Проверка роли пользователя
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Проверка наличия одной из ролей
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles);
    }

    /**
     * Является ли пользователь администратором
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Является ли пользователь модератором
     */
    public function isModerator(): bool
    {
        return $this->role === 'moderator';
    }

    /**
     * Является ли пользователь модератором или админом
     */
    public function isStaff(): bool
    {
        return $this->hasAnyRole(['moderator', 'admin']);
    }

    /**
     * Может ли пользователь модерировать
     */
    public function canModerate(): bool
    {
        return $this->isStaff() && !$this->isBanned();
    }

    // === СИСТЕМА БАНОВ ===

    /**
     * Проверка, заблокирован ли пользователь
     */
    public function isBanned(): bool
{
    if (!$this->is_banned) {
        return false;
    }

    // Если бан временный, проверяем не истек ли он
    if ($this->banned_until && $this->banned_until->isPast()) {
        $this->unban();
        return false;
    }

    return true;
}



    /**
     * Заблокировать пользователя
     */
    public function ban(string $reason = null, Carbon $until = null, User $bannedBy = null): void
    {
        $this->update([
            'is_banned' => true,
            'banned_at' => now(),
            'banned_until' => $until,
            'ban_reason' => $reason,
            'banned_by' => $bannedBy?->id,
        ]);
    }

    /**
     * Разблокировать пользователя
     */
    public function unban(): void
{
    $this->update([
        'is_banned' => false,
        'banned_at' => null,
        'banned_until' => null,
        'ban_reason' => null,
        'banned_by' => null,
    ]);
    
    // НОВОЕ: Автоматически удаляем уведомления о блокировке
    $this->notifications()
        ->whereIn('type', ['temporary_ban', 'permanent_ban'])
        ->delete();
}


    /**
     * Получить тип бана (временный/постоянный)
     */
    public function getBanType(): string
    {
        if (!$this->isBanned()) {
            return 'none';
        }

        return $this->banned_until ? 'temporary' : 'permanent';
    }

    /**
     * Получить время до разбана
     */
    public function getBanTimeRemaining(): ?string
    {
        if (!$this->isBanned() || !$this->banned_until) {
            return null;
        }

        return $this->banned_until->diffForHumans();
    }

    /**
     * Проверка, может ли пользователь выполнять действия
     */
    public function canPerformActions(): bool
    {
        return !$this->isBanned();
    }

    // === СВЯЗИ ===

    public function topics()
    {
        return $this->hasMany(Topic::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function sentNotifications()
    {
        return $this->hasMany(Notification::class, 'from_user_id');
    }

    public function notificationSettings()
    {
        return $this->hasOne(NotificationSettings::class);
    }

    /**
     * Награды пользователя
     */
    public function achievements()
    {
        return $this->belongsToMany(Achievement::class, 'user_achievements')
            ->withPivot('awarded_at', 'awarded_by')
            ->orderBy('user_achievements.awarded_at', 'desc');
    }

    /**
     * Пользователь, который заблокировал этого пользователя
     */
    public function bannedByUser()
    {
        return $this->belongsTo(User::class, 'banned_by');
    }

    /**
     * Пользователи, заблокированные этим пользователем
     */
    public function bannedUsers()
    {
        return $this->hasMany(User::class, 'banned_by');
    }

    // === АКСЕССОРЫ ===

    public function getAvatarUrlAttribute()
{
    if ($this->avatar && Storage::disk('public')->exists($this->avatar)) {
        return Storage::disk('public')->url($this->avatar);
    }
    
    return '/storage/avatars/default-avatar.jpg';
}

    /**
     * Получить цвет роли для отображения
     */
    public function getRoleColorAttribute(): string
    {
        return match($this->role) {
            'admin' => 'danger',
            'moderator' => 'warning',
            default => 'secondary'
        };
    }

    /**
     * Получить название роли на русском
     */
    public function getRoleNameAttribute(): string
    {
        return match($this->role) {
            'admin' => 'Администратор',
            'moderator' => 'Модератор',
            default => 'Пользователь'
        };
    }

    // === СКОУПЫ ===

    /**
     * Скоуп для получения онлайн пользователей
     */
    public function scopeOnline($query)
    {
        return $query->where('last_activity_at', '>=', now()->subMinutes(5));
    }

    /**
     * Скоуп для получения не заблокированных пользователей
     */
    public function scopeNotBanned($query)
    {
        return $query->where(function($q) {
            $q->where('is_banned', false)
              ->orWhere(function($sq) {
                  $sq->where('is_banned', true)
                     ->where('banned_until', '<=', now());
              });
        });
    }

    /**
     * Скоуп для получения заблокированных пользователей
     */
    public function scopeBanned($query)
    {
        return $query->where('is_banned', true)
                    ->where(function($q) {
                        $q->whereNull('banned_until')
                          ->orWhere('banned_until', '>', now());
                    });
    }

    /**
     * Скоуп для получения модераторов и админов
     */
    public function scopeStaff($query)
    {
        return $query->whereIn('role', ['moderator', 'admin']);
    }

    // === СОБЫТИЯ МОДЕЛИ ===

    protected static function booted()
    {
        // Автоматически снимаем бан, если время истекло
        static::retrieved(function ($user) {
            if ($user->is_banned && $user->banned_until && $user->banned_until->isPast()) {
                $user->unban();
            }
        });
    }
    
    public function getBanInfo(): array
{
    return [
        'reason' => $this->ban_reason,
        'until' => $this->banned_until ? $this->banned_until->format('d.m.Y H:i') : null,
        'banned_by' => $this->banned_by,
        'banned_at' => $this->banned_at,
        'type' => $this->getBanType(),
        'time_remaining' => $this->getBanTimeRemaining()
    ];
}

/**
 * Получить сессии пользователя
 */
public function sessions()
{
    return $this->hasMany(UserSession::class);
}

/**
 * Получить последние сессии
 */
public function recentSessions($limit = 10)
{
    return $this->sessions()
        ->orderBy('created_at', 'desc')
        ->limit($limit)
        ->get();
}


/**
 * Понравившиеся посты
 */
public function likedPosts()
{
    return $this->belongsToMany(Post::class, 'likes', 'user_id', 'likeable_id')
                ->where('likes.likeable_type', Post::class)
                ->withTimestamps()
                ->orderBy('likes.created_at', 'desc');
}

/**
 * Понравившиеся темы
 */
public function likedTopics()
{
    return $this->belongsToMany(Topic::class, 'likes', 'user_id', 'likeable_id')
                ->where('likes.likeable_type', Topic::class)
                ->withTimestamps()
                ->orderBy('likes.created_at', 'desc');
}

/**
 * Количество полученных лайков (аттрибут)
 */
public function getReceivedLikesCountAttribute()
{
    return Like::whereHasMorph('likeable', [Post::class, Topic::class], function ($query) {
        $query->where('user_id', $this->id);
    })->count();
}

/**
 * Количество поставленных лайков (аттрибут)
 */
public function getGivenLikesCountAttribute()
{
    return $this->likes()->count();
}

/**
 * Получить стилизованный никнейм (username) с CSS
 */
public function getStyledUsernameAttribute(): string
{
    if ($this->username_style_enabled && $this->username_style) {
        return '<span style="' . e($this->username_style) . '">' . e($this->username) . '</span>';
    }

    return e($this->username);
}

/**
 * Лайки, полученные пользователем (на его посты и темы)
 */
public function receivedLikes()
{
    return $this->hasManyThrough(
        Like::class,
        Post::class,
        'user_id', // Foreign key on posts table
        'likeable_id', // Foreign key on likes table
        'id', // Local key on users table
        'id' // Local key on posts table
    )->where('likes.likeable_type', Post::class)
     ->union(
         $this->hasManyThrough(
             Like::class,
             Topic::class,
             'user_id',
             'likeable_id',
             'id',
             'id'
         )->where('likes.likeable_type', Topic::class)
     );
}

/**
 * Альтернативный способ получения лайков через морфинг
 */
public function getAllReceivedLikes()
{
    return Like::whereHasMorph('likeable', [Post::class, Topic::class], function($query) {
        $query->where('user_id', $this->id);
    });
}

// ТАКЖЕ ДОБАВИТЬ команду для обновления счётчиков постов:

/**
 * Обновить счётчик постов пользователя
 */
public function updatePostsCount()
{
    $this->posts_count = $this->posts()->count();
    $this->save();
}
/**
     * Все диалоги пользователя
     */
    public function conversations()
    {
        return Conversation::where('user_one_id', $this->id)
            ->orWhere('user_two_id', $this->id)
            ->orderBy('last_message_at', 'desc')
            ->get();
    }

    /**
     * Отправленные сообщения
     */
    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    /**
     * Количество непрочитанных сообщений
     */
    public function unreadMessagesCount()
    {
        return Message::whereHas('conversation', function ($query) {
            $query->where('user_one_id', $this->id)
                  ->orWhere('user_two_id', $this->id);
        })
        ->where('sender_id', '!=', $this->id)
        ->where('is_read', false)
        ->count();
    }
}