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
        return Socialite::driver('google')
        ->scopes([
            'openid',
            'profile',
            'email',
        ])
        ->redirect();
    }

    private function isValidEmailDomain($email){
        $email = strtolower(trim($email));

        return filter_var($email, FILTER_VALIDATE_EMAIL) 
            && str_ends_with($email, '@bicol-u.edu.ph');
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            $email = strtolower(trim($googleUser->getEmail() ?? ''));

            if (empty($email)) {
                abort(403, 'No email was returned from Google.');
            }

            if (! $this->isValidEmailDomain($email)) {
                abort(403, 'Unauthorized email domain. Only Bicol University accounts are allowed.');
            }

            $avatar = $googleUser->getAvatar()
                ?? ($googleUser->user['picture'] ?? null);

            $user = User::where('google_id', $googleUser->getId())
                ->orWhere('email', $email)
                ->first();

            if (! $user) {
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $email,
                    'google_id' => $googleUser->getId(),
                    'provider' => 'google',
                    'profile_photo_url' => $avatar,
                    'password' => bcrypt(Str::random(24)),
                    'status' => 'Active',
                    'join_date' => now(),
                ]);

                $user->assignRole('Client');
            } else {
                $user->update([
                    'name' => $googleUser->getName(),
                    'google_id' => $googleUser->getId(),
                    'provider' => 'google',
                    'profile_photo_url' => $avatar,
                ]);

                if (! $user->hasRole('Client') && ! $user->getRoleNames()->count()) {
                    $user->assignRole('Client');
                }
            }

            if ($user->status !== 'Active') {
                abort(403, 'Your account is not authorized to access LexTrack.');
            }

            $user->update([
                'last_login' => now(),
            ]);

            Auth::login($user);

            if ($user->isAdmin()) {
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
