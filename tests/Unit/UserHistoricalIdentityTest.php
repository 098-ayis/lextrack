<?php

namespace Tests\Unit;

use App\Models\User;
use Filament\Panel;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UserHistoricalIdentityTest extends TestCase
{
    public function test_active_users_keep_their_normal_display_name(): void
    {
        $user = new User(['name' => 'Alex Santos']);

        $this->assertSame('Alex Santos', $user->historical_name);
        $this->assertNull($user->historical_status_label);
        $this->assertSame('Alex Santos', $user->historical_display_name);
    }

    public function test_soft_deleted_users_keep_their_name_and_receive_former_user_label(): void
    {
        $user = new User(['name' => 'Alex Santos']);
        $user->setAttribute('deleted_at', Carbon::now());

        $this->assertTrue($user->trashed());
        $this->assertSame('Alex Santos', $user->historical_name);
        $this->assertSame(User::FORMER_USER_LABEL, $user->historical_status_label);
        $this->assertSame('Alex Santos (Former User)', $user->historical_display_name);
    }

    public function test_soft_deleted_users_cannot_access_a_filament_panel(): void
    {
        $user = new User([
            'name' => 'Alex Santos',
            'status' => User::DEFAULT_STATUS,
        ]);
        $user->setAttribute('deleted_at', Carbon::now());

        $this->assertFalse($user->canAccessPanel(Panel::make()->id('admin')));
        $this->assertFalse($user->canAccessPanel(Panel::make()->id('client')));
    }
}
