<?php

namespace App\Http\View\Composers;

use Illuminate\View\View;

class NotificationComposer
{
    public function compose(View $view)
    {
        if (auth()->check()) {
            $recentNotifications = auth()->user()->notifications()
                ->with('fromUser')
                ->unread()
                ->latest()
                ->limit(5)
                ->get();
            
            $view->with('recentNotifications', $recentNotifications);
        } else {
            $view->with('recentNotifications', collect());
        }
    }
}