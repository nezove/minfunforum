<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BannedController extends Controller
{
    /**
     * Показать страницу для заблокированных пользователей
     */
    public function index()
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        if (!$user->isBanned()) {
            return redirect()->route('forum.index');
        }

        return view('banned.index', compact('user'));
    }
}