<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class GoogleAuthController extends Controller
{
    /**
     * Redirect user to Google login.
     */
    public function redirect()
    {
        return Socialite::driver('google')
            ->scopes([
                'openid',
                'profile',
                'email',
            ])
            ->with([
                // Always show "Choose an account"
                'prompt' => 'select_account',
            ])
            ->redirect();
    }

    /**
     * Check if email belongs to Bicol University.
     */
    private function isValidEmailDomain(string $email): bool
    {
        $email = strtolower(trim($email));

        return filter_var($email, FILTER_VALIDATE_EMAIL)
            && str_ends_with($email, '@bicol-u.edu.ph');
    }

    /**
     * Handle Google callback.
     */
    public function callback()
    {
        try {
            /*
            |--------------------------------------------------------------------------
            | Get Google User
            |--------------------------------------------------------------------------
            */
            $googleUser = Socialite::driver('google')->user();

            $email = strtolower(
                trim($googleUser->getEmail() ?? '')
            );

            /*
            |--------------------------------------------------------------------------
            | Validate Email
            |--------------------------------------------------------------------------
            */
            if (empty($email)) {
                abort(
                    403,
                    'No email was returned from Google.'
                );
            }

            if (! $this->isValidEmailDomain($email)) {
                abort(
                    403,
                    'Unauthorized email domain. Only Bicol University accounts are allowed.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Google Profile Picture
            |--------------------------------------------------------------------------
            */
            $avatar = $googleUser->getAvatar()
                ?? ($googleUser->user['picture'] ?? null);

            /*
            |--------------------------------------------------------------------------
            | Find Existing User
            |--------------------------------------------------------------------------
            */
            $user = User::where(
                    'google_id',
                    $googleUser->getId()
                )
                ->orWhere('email', $email)
                ->first();

            /*
            |--------------------------------------------------------------------------
            | Create New User
            |--------------------------------------------------------------------------
            */
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

                // Default role for new Google users
                $user->assignRole('Client');
            }

            /*
            |--------------------------------------------------------------------------
            | Update Existing User
            |--------------------------------------------------------------------------
            */
            else {
                $user->update([
                    'name' => $googleUser->getName(),
                    'google_id' => $googleUser->getId(),
                    'provider' => 'google',
                    'profile_photo_url' => $avatar,
                ]);

                /*
                 * If user somehow has no role,
                 * assign the default Client role.
                 */
                if ($user->getRoleNames()->isEmpty()) {
                    $user->assignRole('Client');
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Check Account Status
            |--------------------------------------------------------------------------
            */
            if ($user->status !== 'Active') {
                abort(
                    403,
                    'Your account is not authorized to access LexTrack.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Update Last Login
            |--------------------------------------------------------------------------
            */
            $user->update([
                'last_login' => now(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Login User
            |--------------------------------------------------------------------------
            */
            Auth::login($user);

            // Regenerate session ID after authentication
            request()->session()->regenerate();

            /*
            |--------------------------------------------------------------------------
            | Redirect Based on Role
            |--------------------------------------------------------------------------
            */
            if ($user->hasAnyRole([
                'Super Admin',
                'Admin',
            ])) {
                return redirect('/admin');
            }

            return redirect('/client');

        } catch (HttpExceptionInterface $e) {

            /*
             * Keep our intentional 403 errors.
             *
             * Without this, abort(403) would also be caught by the
             * general Exception handler below and the user would only
             * see "Authentication failed".
             */
            throw $e;

        } catch (\Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | Log Unexpected Google OAuth Errors
            |--------------------------------------------------------------------------
            */
            Log::error('Google OAuth Error', [
                'message' => $e->getMessage(),
            ]);

            return redirect('/login')
                ->withErrors([
                    'google' => 'Authentication failed. Please try again.',
                ]);
        }
    }
}