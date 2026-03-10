<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\AbTestResource\Pages;
use App\Models\AbTest;
use Filament\Forms;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AbTestResource extends Resource
{
    protected static ?string $model = AbTest::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Analytics';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->required(),
            Select::make('status')
                ->options(['draft' => 'Draft', 'running' => 'Running', 'completed' => 'Completed', 'cancelled' => 'Cancelled']),
            TextInput::make('metric')->required(),
            TextInput::make('confidence_level')->numeric()->default(95),
            KeyValue::make('traffic_split'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('status')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('metric')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('confidence_level')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('started_at')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('ended_at')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('created_at')->sortable()->searchable(),
            ])
            ->filters([])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAbTests::route('/'),
            'create' => Pages\CreateAbTest::route('/create'),
            'edit' => Pages\EditAbTest::route('/{record}/edit'),
        ];
    }
}
