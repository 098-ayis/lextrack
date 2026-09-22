<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class UserEmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_link_verifies_the_users_current_email_address(): void
    {
        $user = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(30),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ],
        );

        $this->get($url)
            ->assertRedirect(route('home'))
            ->assertSessionHas('status', 'Email address verified.');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_a_verification_link_for_a_previous_email_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(30),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ],
        );

        $user->forceFill(['email' => 'changed@example.test'])->save();

        $this->get($url)->assertForbidden();
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }
}
