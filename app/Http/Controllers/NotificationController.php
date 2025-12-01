<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

   public function index()
{
    $notifications = auth()->user()->notifications()
        ->with('fromUser')
        ->latest()
        ->paginate(20);

    // Передаём информацию о бане
    $user = auth()->user();
    $isBanned = $user->isBanned();
    $banInfo = $isBanned ? $user->getBanInfo() : null;

    return view('notifications.index', compact('notifications', 'isBanned', 'banInfo'));
}


    public function show(Notification $notification)
    {
        // Проверяем, что уведомление принадлежит текущему пользователю
        if ($notification->user_id !== auth()->id()) {
            abort(403);
        }

        // Отмечаем как прочитанное
        if (!$notification->is_read) {
            $notification->markAsRead();
        }

        // Перенаправляем на прямую ссылку
        return redirect($notification->direct_link);
    }

    public function markAsRead(Notification $notification)
    {
        // Проверяем, что уведомление принадлежит текущему пользователю
        if ($notification->user_id !== auth()->id()) {
            abort(403);
        }

        $notification->markAsRead();

        return response()->json(['success' => true]);
    }
    public function markDropdownAsViewed()
    {
        auth()->user()->notifications()
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now()
            ]);

        return response()->json(['success' => true]);
    }

    public function markAllAsRead()
    {
        auth()->user()->notifications()->unread()->update([
            'is_read' => true,
            'read_at' => now()
        ]);

        return back()->with('success', 'Все уведомления отмечены как прочитанные');
    }

    public function deleteAll()
    {
        auth()->user()->notifications()->delete();
        
        return back()->with('success', 'Все уведомления удалены');
    }

    public function destroy(Notification $notification)
    {
        // Проверяем, что уведомление принадлежит текущему пользователю
        if ($notification->user_id !== auth()->id()) {
            abort(403);
        }

        $notification->delete();

        return back()->with('success', 'Уведомление удалено');
    }

    public function getUnreadCount()
    {
        $count = auth()->user()->notifications()->unread()->count();
        
        return response()->json(['count' => $count]);
    }
}