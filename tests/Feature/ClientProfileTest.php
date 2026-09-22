<?php

namespace Tests\Feature;

use App\Filament\Client\Pages\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClientProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_open_their_profile_page(): void
    {
        $client = User::factory()->create([
            'status' => User::DEFAULT_STATUS,
        ]);
        $client->assignRole(Role::create([
            'name' => 'Client',
            'guard_name' => 'web',
        ]));

        $this->actingAs($client)
            ->get('/client/profile')
            ->assertOk()
            ->assertSee('Personal Information')
            ->assertSee($client->name)
            ->assertSee($client->email)
            ->assertSee('Office / Unit');
    }

    public function test_client_can_save_office_information(): void
    {
        $client = User::factory()->create([
            'status' => User::DEFAULT_STATUS,
        ]);
        $client->assignRole(Role::create([
            'name' => 'Client',
            'guard_name' => 'web',
        ]));

        $this->actingAs($client);

        Livewire::test(Profile::class)
            ->set('office', 'College of Science')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('clients', [
            'user_id' => $client->id,
            'office' => 'College of Science',
        ]);
    }

    public function test_user_without_client_access_cannot_open_the_client_profile_page(): void
    {
        $user = User::factory()->create([
            'status' => User::DEFAULT_STATUS,
        ]);

        $this->actingAs($user)
            ->get('/client/profile')
            ->assertForbidden();
    }
}
