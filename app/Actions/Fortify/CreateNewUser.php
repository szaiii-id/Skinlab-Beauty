<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Models\VerificationCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => $this->passwordRules(),
            'terms' => ['accepted'],
        ])->validate();

        // Create user
        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
        ]);

        Log::info('User registered', ['user_id' => $user->id, 'email' => $user->email]);

        session(['verification_email' => $user->email]);

        $this->sendVerificationCode($user);

        Log::info('Session set and code sent', ['session_email' => session('verification_email')]);

        return $user;
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
            'code' => $code,
            'expires' => 10, // 10 minutes
            ], function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('✨ Verify Your Email - ' . config('app.name'));
            });
            
            Log::info('Verification code sent', ['email' => $user->email, 'code' => $code]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send verification email: ' . $e->getMessage());
            return false;
        }
    }
}