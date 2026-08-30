<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ActionTypeSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('action_types')->upsert([
            [
                'action_id' => 1,
                'action_name' => 'FOR REVIEW',
                'color' => '#059669',
            ],
            [
                'action_id' => 2,
                'action_name' => 'FOR ENDORSEMENT',
                'color' => '#4F46E5',
            ],
            [
                'action_id' => 3,
                'action_name' => 'FOR ACTION',
                'color' => '#D97706',
            ],
            [
                'action_id' => 4,
                'action_name' => 'FOR SIGNATURE',
                'color' => '#7C3AED',
            ],
            [
                'action_id' => 5,
                'action_name' => 'FOR FILING',
                'color' => '#475569',
            ],
            [
                'action_id' => 6,
                'action_name' => 'FOR LEGAL OPINION',
                'color' => '#0891B2',
            ],
            [
                'action_id' => 7,
                'action_name' => 'FOR UCMC',
                'color' => '#0F766E',
            ],
            [
                'action_id' => 8,
                'action_name' => 'FOR INFORMATION',
                'color' => '#2563EB',
            ],
            [
                'action_id' => 9,
                'action_name' => 'FOR COMMENTS',
                'color' => '#E11D48',
            ],
        ], ['action_id'], ['action_name', 'color']);
    }
}