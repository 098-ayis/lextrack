<?php

namespace App\Filament\Resources\OfficeUnits;

use App\Filament\Clusters\SettingsCluster;
use App\Filament\Resources\OfficeUnits\Pages\CreateOfficeUnit;
use App\Filament\Resources\OfficeUnits\Pages\EditOfficeUnit;
use App\Filament\Resources\OfficeUnits\Pages\ListOfficeUnits;
use App\Models\OfficeUnit;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OfficeUnitResource extends Resource
{
    protected static ?string $model = OfficeUnit::class;

    protected static ?string $cluster = SettingsCluster::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    protected static ?string $navigationLabel = 'Office Units';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Office / Unit')
                ->required()
                ->maxLength(255),
            ColorPicker::make('color')
                ->label('Color')
                ->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Office / Unit')
                    ->searchable()
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
            ->defaultSort('name');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOfficeUnits::route('/'),
            'create' => CreateOfficeUnit::route('/create'),
            'edit' => EditOfficeUnit::route('/{record}/edit'),
        ];
    }
}
