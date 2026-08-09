<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            if (empty($googleUser->email)) {
                return redirect()->route('login')
                    ->withErrors('No email returned from Google account.');
            }

            $user = User::where('google_id', $googleUser->id)
                ->orWhere('email', $googleUser->email)
                ->first();

            if (!$user) {
                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'provider' => 'google',
                    'avatar' => $googleUser->avatar,
                    'password' => bcrypt(Str::random(24)),
                    'role_name' => 'Client',
                    'status' => 'Active',
                    'join_date' => now(),
                ]);
            } else {
                if (!$user->google_id) {
                    $user->update([
                        'google_id' => $googleUser->id,
                        'provider'  => 'google',
                    ]);
                }

                if (!$user->role_name) {
                    $user->update([
                        'role_name' => 'Client',
                    ]);
                }
            }

            $user->update([
                'last_login' => now(),
            ]);

            Auth::login($user);

            if($user->role_name === 'Admin') {
                return redirect('/admin');
            }

            return redirect('/client');


        } catch (\Exception $e) {
            \Log::error('Google OAuth Error', [
                'message' => $e->getMessage(),
            ]);

            return redirect('/login')
                ->withErrors('Authentication failed. Please try again.');
        }
    }
}