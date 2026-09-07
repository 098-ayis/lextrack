<?php

namespace App\Filament\Resources\ActionTypes;

use App\Filament\Clusters\SettingsCluster;
use App\Filament\Resources\ActionTypes\Pages\CreateActionType;
use App\Filament\Resources\ActionTypes\Pages\EditActionType;
use App\Filament\Resources\ActionTypes\Pages\ListActionTypes;
use App\Models\ActionType;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ActionTypeResource extends Resource
{
    protected static ?string $model = ActionType::class;

    protected static ?string $cluster = SettingsCluster::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static ?string $navigationLabel = 'Action Types';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('action_name')
                ->label('Action name')
                ->required()
                ->maxLength(255),
            ColorPicker::make('color')
                ->label('Color')
                ->required()
                ->default('#059669'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('action_name')
                    ->label('Action')
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
            ->defaultSort('action_name');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActionTypes::route('/'),
            'create' => CreateActionType::route('/create'),
            'edit' => EditActionType::route('/{record}/edit'),
        ];
    }
}
