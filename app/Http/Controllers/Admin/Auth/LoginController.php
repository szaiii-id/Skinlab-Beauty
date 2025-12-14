<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AdminLoginRequest; // <--- PENTING: Import Request Baru
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }
        return Inertia::render('Admin/Auth/Login');
    }

    /**
     * PROSES LOGIN UTAMA
     * Menggunakan AdminLoginRequest untuk keamanan.
     */
    public function login(AdminLoginRequest $request)
    {
        // 1. Eksekusi Login & Keamanan (Rate Limiting, Cek Password, Cek Aktif)
        // Jika gagal/kena limit, otomatis stop di sini dan lempar error ke frontend.
        $request->authenticate();

        // 2. SECURITY: Regenerasi Session ID (Mencegah Session Fixation)
        // Ini wajib ada di "Best Practice" keamanan.
        $request->session()->regenerate();

        // 3. AUDIT: Update Last Login Time & IP
        // Agar kita tahu kapan terakhir admin ini masuk.
        $admin = Auth::guard('admin')->user();
        $admin->update([
            'last_login_at' => now(),
            // 'last_login_ip' => $request->ip() // (Opsional: aktifkan jika ada kolom ip di database)
        ]);

        // 4. Redirect ke Dashboard (atau halaman yang ingin dibuka sebelumnya)
        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();

        // SECURITY: Hapus session server & token
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}