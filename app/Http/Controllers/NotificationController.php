<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * 通知一覧画面を表示する。
     *
     * @return View 通知一覧画面
     */
    public function index(): View
    {
        $notifications = auth()->user()
            ->notifications()
            ->latest()
            ->get();

        return view('notifications.index', compact('notifications'));
    }

    /**
     * 通知一を既読にする。
     *
     * @param  string  $id  通知ID
     * @return RedirectResponse 直前の画面へリダイレクト
     */
    public function read(string $id): RedirectResponse
    {
        $notification = auth()->user()
            ->unreadNotifications()
            ->findOrFail($id);

        $notification->markAsRead();

        return back();
    }
}
