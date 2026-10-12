<?php

namespace Tests\Feature;

use App\Filament\Pages\Document;
use App\Models\Document as DocumentModel;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
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

    public function test_incoming_table_edit_action_keeps_the_selected_record_context(): void
    {
        $page = new Document;
        $page->activeSection = 'incoming';
        $table = $page->table(Table::make($page));

        $this->assertInstanceOf(Action::class, $table->getAction('editDocument'));

        $record = new DocumentModel;
        $record->setRawAttributes([
            'document_id' => 123,
            'status' => 'in_progress',
        ]);
        $record->exists = true;

        $group = collect($table->getRecordActions())
            ->first(fn (Action|ActionGroup $action): bool => $action instanceof ActionGroup);

        $this->assertInstanceOf(ActionGroup::class, $group);

        $group->record($record);

        $editAction = collect($group->getActions())
            ->first(fn (Action|ActionGroup $action): bool => $action instanceof Action && $action->getName() === 'editDocument');

        $this->assertInstanceOf(Action::class, $editAction);
        $this->assertSame('123', $editAction->getContext()['recordKey']);

        $messageAction = collect($group->getActions())
            ->first(fn (Action|ActionGroup $action): bool => $action instanceof Action && $action->getName() === 'messageDocumentTable');

        $this->assertInstanceOf(Action::class, $messageAction);
        $this->assertStringContainsString(
            'document=123',
            (string) $messageAction->getUrl(),
        );
    }
}
