<?php

namespace Tests\Feature;

use App\Http\Controllers\UserExportController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserLegacyColumnsTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_use_the_primary_key_and_profile_photo_without_legacy_columns(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'profile_photo_url' => 'profile-photos/user.png',
        ]);

        $this->assertFalse(Schema::hasColumn('users', 'user_id'));
        $this->assertFalse(Schema::hasColumn('users', 'avatar'));
        $this->assertNotNull($user->getKey());
        $this->assertSame(
            Storage::disk('public')->url('profile-photos/user.png'),
            $user->getProfilePhotoUrl(),
        );
    }

    public function test_user_export_no_longer_reads_the_legacy_user_id(): void
    {
        User::factory()->create([
            'name' => 'Exported User',
            'email' => 'exported@example.test',
            'status' => User::DEFAULT_STATUS,
        ]);

        $response = app(UserExportController::class)();

        ob_start();
        $response->sendContent();
        $csv = (string) ob_get_clean();

        $this->assertStringStartsWith(
            "\"Full Name\",Email,Status,Role,\"Joined Date\",\"Last Active\"",
            trim($csv),
        );
        $this->assertStringNotContainsString('Username', $csv);
        $this->assertStringContainsString('exported@example.test', $csv);
    }
}
