<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * PROSES LOGIN AMAN (Di sini letak Rate Limiting & Cek Aktif)
     */
    public function authenticate(): void
    {
        // 1. Cek apakah user terlalu sering salah (Brute Force Protection)
        $this->ensureIsNotRateLimited();

        // 2. Coba Login dengan Guard 'admin'
        if (! Auth::guard('admin')->attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            
            // Jika Gagal: Catat kegagalan (Hit Rate Limiter)
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'), // Pesan error generik biar aman
            ]);
        }

        // 3. Cek apakah akun Aktif/Suspend?
        // (Ini kode dari controller lama Anda, dipindah ke sini biar rapi)
        $user = Auth::guard('admin')->user();
        if (!$user->is_active) {
            
            Auth::guard('admin')->logout(); // Tendang keluar
            
            throw ValidationException::withMessages([
                'email' => 'Akun dinonaktifkan. Hubungi Super Admin.',
            ]);
        }

        // 4. Jika sukses & aman: Bersihkan catatan Rate Limiter
        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Logika Rate Limiter (Maksimal 5x salah per menit)
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->input('email')).'|'.$this->ip());
    }
}