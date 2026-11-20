<?php

namespace SamTest;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class IsolatedDatabaseConnectionTest extends TestCase
{
    public function test_laravel_connects_to_the_dedicated_database(): void
    {
        DB::connection()->getPdo();

        $this->assertSame(
            'lextrack_sam_test',
            DB::connection()->getDatabaseName(),
        );
    }
}
