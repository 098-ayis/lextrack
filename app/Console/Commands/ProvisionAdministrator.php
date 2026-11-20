<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\SystemSettingService;
use App\Support\RoleSecurity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ProvisionAdministrator extends Command
{
    protected $signature = 'admin:provision
        {email : The Google account email to provision}
        {--name= : The display name when creating a new account}';

    protected $description = 'Provision a first administrator for Google OAuth without creating a password';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        $domain = app(SystemSettingService::class)->allowedEmailDomain();

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)
            || ! str_ends_with($email, '@'.$domain)) {
            $this->error("The email must be a valid @{$domain} address.");

            return self::FAILURE;
        }

        $user = DB::transaction(function () use ($email): User {
            $user = User::withTrashed()->where('email', $email)->first();

            if ($user?->trashed()) {
                throw new \RuntimeException('A deleted account already uses this email. Restore it before provisioning.');
            }

            if (! $user) {
                $user = User::create([
                    'name' => trim((string) ($this->option('name') ?: strstr($email, '@', true))),
                    'email' => $email,
                    'password' => null,
                    'status' => User::DEFAULT_STATUS,
                    'join_date' => now(),
                ]);
            } else {
                // Do not overwrite profile, OAuth, or custom user data.
                $user->update(['status' => User::DEFAULT_STATUS]);
            }

            $role = Role::firstOrCreate([
                'name' => RoleSecurity::SUPER_ADMIN,
                'guard_name' => 'web',
            ]);

            $role->givePermissionTo(Permission::query()->where('guard_name', 'web')->get());
            $user->assignRole($role);

            return $user->fresh();
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->info("{$user->email} is now a Super Admin. No password was created or changed.");

        return self::SUCCESS;
    }
}
