<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Models\User;

class AdminActionLogger
{
    public static function logAction(User $admin, string $action, array $details = [])
    {
        $logData = [
            'admin_id' => $admin->id,
            'admin_username' => $admin->username,
            'admin_email' => $admin->email,
            'action' => $action,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toDateTimeString(),
            'details' => $details
        ];

        // Логируем в специальный канал для административных действий
        Log::channel('admin')->info('Admin Action', $logData);

        // Также можно сохранить в базу данных для лучшего контроля
        // AdminLog::create($logData);
    }

    public static function logUserBan(User $admin, User $targetUser, string $reason, $duration)
    {
        self::logAction($admin, 'USER_BANNED', [
            'target_user_id' => $targetUser->id,
            'target_username' => $targetUser->username,
            'target_email' => $targetUser->email,
            'ban_reason' => $reason,
            'ban_duration' => $duration,
        ]);
    }

    public static function logUserUnban(User $admin, User $targetUser)
    {
        self::logAction($admin, 'USER_UNBANNED', [
            'target_user_id' => $targetUser->id,
            'target_username' => $targetUser->username,
            'target_email' => $targetUser->email,
        ]);
    }

    public static function logRoleChange(User $admin, User $targetUser, string $oldRole, string $newRole)
    {
        self::logAction($admin, 'ROLE_CHANGED', [
            'target_user_id' => $targetUser->id,
            'target_username' => $targetUser->username,
            'target_email' => $targetUser->email,
            'old_role' => $oldRole,
            'new_role' => $newRole,
        ]);
    }

    public static function logTopicDelete(User $admin, $topicId, string $topicTitle, $authorId)
    {
        self::logAction($admin, 'TOPIC_DELETED', [
            'topic_id' => $topicId,
            'topic_title' => $topicTitle,
            'topic_author_id' => $authorId,
        ]);
    }

    public static function logPostDelete(User $admin, $postId, $topicId, $authorId)
    {
        self::logAction($admin, 'POST_DELETED', [
            'post_id' => $postId,
            'topic_id' => $topicId,
            'post_author_id' => $authorId,
        ]);
    }

    public static function logLogin(User $user, bool $successful = true, string $reason = '')
    {
        $action = $successful ? 'LOGIN_SUCCESS' : 'LOGIN_FAILED';
        
        $logData = [
            'user_id' => $user->id ?? null,
            'username' => $user->username ?? 'unknown',
            'email' => $user ? hash('sha256', $user->email) : 'unknown',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toDateTimeString(),
            'reason' => $reason,
        ];

        if ($successful) {
            Log::channel('admin')->info($action, $logData);
        } else {
            Log::channel('security')->warning($action, $logData);
        }
    }

    public static function logSuspiciousActivity(string $activity, array $details = [])
    {
        $logData = [
            'activity' => $activity,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toDateTimeString(),
            'details' => $details
        ];

        Log::channel('security')->warning('Suspicious Activity', $logData);
    }
}