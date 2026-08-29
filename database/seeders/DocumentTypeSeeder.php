<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'type_name' => 'MOA',
                'type_desc' => 'Memorandum of Agreement',
            ],
            [
                'type_name' => 'Correspondence',
                'type_desc' => 'Official correspondence documents',
            ],
            [
                'type_name' => 'Contract',
                'type_desc' => 'Contract-related documents',
            ],
            [
                'type_name' => 'Proposal',
                'type_desc' => 'Proposal documents',
            ],
            [
                'type_name' => 'PROCUREMENT',
                'type_desc' => 'Procurement-related documents',
            ],
            [
                'type_name' => 'REFERENCE SLIP',
                'type_desc' => 'Reference slip documents',
            ],
            [
                'type_name' => 'Clearance',
                'type_desc' => 'Clearance documents',
            ],
            [
                'type_name' => 'MOU',
                'type_desc' => 'Memorandum of Understanding',
            ],
            [
                'type_name' => 'NDA',
                'type_desc' => 'Non-Disclosure Agreement',
            ],
            [
                'type_name' => 'DOD',
                'type_desc' => 'DOD documents',
            ],
            [
                'type_name' => 'GBA',
                'type_desc' => 'GBA documents',
            ],
            [
                'type_name' => 'Others',
                'type_desc' => 'Other document types',
            ],
        ];

        foreach ($types as $type) {
            DocumentType::firstOrCreate(
                [
                    'type_name' => $type['type_name'],
                ],
                [
                    'type_desc' => $type['type_desc'],
                ]
            );
        }
    }
}