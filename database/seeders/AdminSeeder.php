<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;


class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminEmails = array_filter([
            env('ADMIN_EMAIL_1'),
            env('ADMIN_EMAIL_2'),
            env('ADMIN_EMAIL_3'),
        ]);

        User::whereIn('email', $adminEmails)
            ->update([
                'role_name' => 'Admin',
                'status' => 'Active'
            ]);
    }
}
