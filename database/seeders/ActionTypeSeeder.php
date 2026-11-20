<?php

namespace Database\Seeders;

use App\Models\ActionType;
use Illuminate\Database\Seeder;

class ActionTypeSeeder extends Seeder
{
    public function run(): void
    {
        $actions = [
            [
                'action_name' => 'FOR REVIEW',
                'color' => '#059669',
            ],
            [
                'action_name' => 'FOR ENDORSEMENT',
                'color' => '#4F46E5',
            ],
            [
                'action_name' => 'FOR ACTION',
                'color' => '#D97706',
            ],
            [
                'action_name' => 'FOR SIGNATURE',
                'color' => '#7C3AED',
            ],
            [
                'action_name' => 'FOR FILING',
                'color' => '#475569',
            ],
            [
                'action_name' => 'FOR LEGAL OPINION',
                'color' => '#0891B2',
            ],
            [
                'action_name' => 'FOR UCMC',
                'color' => '#0F766E',
            ],
            [
                'action_name' => 'FOR INFORMATION',
                'color' => '#2563EB',
            ],
            [
                'action_name' => 'FOR COMMENTS',
                'color' => '#E11D48',
            ],
        ];

        foreach ($actions as $action) {
            ActionType::firstOrCreate([
                'action_name' => $action['action_name'],
            ], [
                'color' => $action['color'],
            ]);
        }
    }
}
