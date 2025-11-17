<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VerificationCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class VerificationController extends Controller
{
    public function notice(Request $request)
    {
        Log::info('Verification notice accessed', [
            'has_session_email' => session('verification_email') ? true : false,
            'auth_check' => Auth::check(),
            'auth_email' => Auth::check() ? Auth::user()->email : null,
            'verified' => Auth::check() ? !is_null(Auth::user()->email_verified_at) : false
        ]);

        if (Auth::check() && !is_null(Auth::user()->email_verified_at)) {
            Log::info('User already verified, redirecting to home');
            return redirect()->intended('/');
        }

        $email = session('verification_email');
        
        if (!$email && Auth::check() && is_null(Auth::user()->email_verified_at)) {
            $email = Auth::user()->email;
            session(['verification_email' => $email]);
            
            Log::info('Session set from auth user (unverified)', ['email' => $email]);
            
            if (!$this->hasActiveCode(Auth::user())) {
                $this->sendVerificationCode(Auth::user());
            }
        }

        // ✅ JIKA MASIH TIDAK ADA EMAIL, REDIRECT KE REGISTER
        if (!$email) {
            Log::warning('No email found for verification, redirecting to register');
            return redirect()->route('register');
        }

        return Inertia::render('auth/VerifyCode', [
            'email' => $email,
            'status' => session('status'),
        ]);
    }

    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
            'email' => 'required|email'
        ]);

        Log::info('Verification attempt', ['email' => $request->email, 'code' => $request->code]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            Log::warning('User not found for verification', ['email' => $request->email]);
            return back()->withErrors(['code' => 'User not found. Please register again.']);
        }

        if (!is_null($user->email_verified_at)) {
            Log::info('User already verified, logging in', ['email' => $user->email]);
            Auth::login($user);
            return redirect()->intended('/dashboard')
                ->with('status', 'Email already verified!');
        }

        $verificationCode = VerificationCode::where('user_id', $user->id)
            ->where('code', $request->code)
            ->first();

        if (!$verificationCode) {
            Log::warning('Invalid verification code', ['email' => $user->email, 'code' => $request->code]);
            return $this->handleInvalidCode($user);
        }

        if ($verificationCode->isExpired()) {
            Log::warning('Expired verification code', ['email' => $user->email]);
            $verificationCode->delete();
            return back()->withErrors(['code' => 'Kode verifikasi telah kadaluarsa']);
        }

        if ($verificationCode->attempts >= 3) {
            Log::warning('Max attempts reached', ['email' => $user->email]);
            $verificationCode->delete();
            return back()->withErrors(['code' => 'Terlalu banyak percobaan. Silakan minta kode baru']);
        }

        $user->email_verified_at = now();
        $saved = $user->save(); 
        if (!$saved) {
            Log::error('Failed to update email_verified_at', ['email' => $user->email]);
            return back()->withErrors(['code' => 'Gagal memverifikasi email. Silakan coba lagi.']);
        }

        Log::info('Email verification updated in database', [
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
            'saved' => $saved
        ]);

        $verificationCode->delete();
        
        Auth::login($user);
        
        VerificationCode::cleanupExpired();


        Log::info('Email verified successfully and user logged in', [
            'email' => $user->email,
            'user_id' => $user->id,
            'auth_check' => Auth::check(),
            'email_verified_at' => $user->email_verified_at
        ]);

        return redirect()->intended('/')
            ->with('status', 'Email berhasil diverifikasi! Selamat datang!');
    }

    public function send(Request $request)
    {
        $email = session('verification_email');
        
        if (!$email && Auth::check()) {
            $email = Auth::user()->email;
            session(['verification_email' => $email]);
        }
        
        if (!$email) {
            return redirect()->route('register');
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            return redirect()->route('register');
        }

        // ✅ CEK VERIFIED MANUAL
        if (!is_null($user->email_verified_at)) {
            Auth::login($user);
            return redirect()->intended('/dashboard');
        }

        // Delete existing codes
        VerificationCode::where('user_id', $user->id)->delete();
        
        // Send new code
        $this->sendVerificationCode($user);

        return back()->with('status', 'Kode verifikasi baru telah dikirim!');
    }

    private function handleInvalidCode(User $user)
    {
        $activeCode = VerificationCode::where('user_id', $user->id)->first();
        
        if ($activeCode) {
            $activeCode->incrementAttempts();
            
            $remainingAttempts = 3 - $activeCode->attempts;
            
            if ($remainingAttempts > 0) {
                return back()->withErrors(['code' => "Kode salah. Sisa percobaan: {$remainingAttempts}"]);
            }
            
            $activeCode->delete();
        }

        return back()->withErrors(['code' => 'Kode verifikasi tidak valid']);
    }

    private function sendVerificationCode(User $user): bool
    {
        $code = sprintf("%06d", random_int(1, 999999));
        
        VerificationCode::create([
            'user_id' => $user->id,
            'code' => $code,
            'expires_at' => now()->addMinutes(10),
        ]);

        try {
             Mail::send('emails.verification', [
                'name' => $user->name,
                'code' => $code,
            ], function ($message) use ($user) {
                $message->to($user->email)
                       ->subject('Kode Verifikasi - ' . config('app.name'));
            });
            
            Log::info('Verification code sent', ['email' => $user->email, 'code' => $code]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send verification email: ' . $e->getMessage());
            return false;
        }
    }

    private function hasActiveCode(User $user): bool
    {
        return VerificationCode::where('user_id', $user->id)
            ->where('expires_at', '>', now())
            ->exists();
    }
}