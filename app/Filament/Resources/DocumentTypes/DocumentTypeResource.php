<?php

namespace App\Filament\Resources\DocumentTypes;

use App\Filament\Clusters\SettingsCluster;
use App\Filament\Resources\DocumentTypes\Pages\CreateDocumentType;
use App\Filament\Resources\DocumentTypes\Pages\EditDocumentType;
use App\Filament\Resources\DocumentTypes\Pages\ListDocumentTypes;
use App\Models\DocumentType;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DocumentTypeResource extends Resource
{
    protected static ?string $model = DocumentType::class;

    protected static ?string $cluster = SettingsCluster::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Document Types';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('type_name')
                ->label('Document type')
                ->required()
                ->maxLength(255),
            Textarea::make('type_desc')
                ->label('Description')
                ->required()
                ->rows(3),
            TextInput::make('days_to_process')
                ->label('Days to process')
                ->numeric()
                ->minValue(0)
                ->maxValue(65535)
                ->helperText('Used to calculate the document deadline.')
                ->nullable(),
            ColorPicker::make('color')
                ->label('Color')
                ->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type_name')
                    ->label('Document type')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type_desc')
                    ->label('Description')
                    ->limit(60)
                    ->searchable(),
                TextColumn::make('days_to_process')
                    ->label('Days to process')
                    ->suffix(' days')
                    ->placeholder('Not set')
                    ->sortable(),
                ColorColumn::make('color')
                    ->label('Color')
                    ->copyable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                \Filament\Actions\CreateAction::make(),
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('type_name');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocumentTypes::route('/'),
            'create' => CreateDocumentType::route('/create'),
            'edit' => EditDocumentType::route('/{record}/edit'),
        ];
    }
}
