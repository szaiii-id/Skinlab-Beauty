<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Ambil semua notifikasi user
     */
    public function index()
    {
        $user = Auth::user();
        
        return response()->json([
            // Ambil 10 notifikasi terakhir
            'notifications' => $user->notifications()->latest()->take(10)->get(),
            // Hitung berapa yang belum dibaca
            'unread_count' => $user->unreadNotifications->count()
        ]);
    }

    /**
     * Tandai satu notifikasi sebagai 'Read'
     */
    public function markAsRead($id)
    {
        $notification = Auth::user()->notifications()->where('id', $id)->first();
        
        if ($notification) {
            $notification->markAsRead();
        }
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Tandai SEMUA sebagai 'Read'
     */
    public function markAllRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        return back();
    }

    /**
     * Hapus Semua Notifikasi (Clear History)
     */
    public function destroy()
    {
        Auth::user()->notifications()->delete();
        return back()->with('success', 'Notifications cleared.');
    }
}