<?php

namespace Tests\Feature;

use App\Filament\Pages\Document;
use Filament\Tables\Table;
use Tests\TestCase;

class DocumentTableConfigurationTest extends TestCase
{
    public function test_document_table_builds_with_supported_action_alignment(): void
    {
        $page = new Document;
        $table = $page->table(Table::make($page));
        $this->assertSame('fi-align-center', $table->getRecordActionsAlignment());
        $this->assertSame('ACTION', $table->getRecordActionsColumnLabel());
    }
}
