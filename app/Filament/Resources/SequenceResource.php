<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\SequenceResource\Pages;
use App\Models\Sequence;
use Filament\Forms;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SequenceResource extends Resource
{
    protected static ?string $model = Sequence::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Engagement';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->required()->maxLength(255),
            Select::make('status')
                ->options(['draft' => 'Draft', 'active' => 'Active', 'paused' => 'Paused', 'archived' => 'Archived'])
                ->required(),
            TextInput::make('trigger_event')->maxLength(255),
            Select::make('segment_id')
                ->relationship('segment', 'name')
                ->searchable()
                ->preload(),
            TextInput::make('priority')->numeric()->default(50)->minValue(1)->maxValue(100),
            TextInput::make('max_concurrent')->numeric()->default(3),
            Toggle::make('is_repeatable')->default(false),
            KeyValue::make('exit_conditions'),
            TextInput::make('version')->numeric()->default(1)->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('trigger_event')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('status')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('priority')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('is_repeatable')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('created_at')->sortable()->searchable(),
            ])
            ->filters([])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSequences::route('/'),
            'create' => Pages\CreateSequence::route('/create'),
            'edit' => Pages\EditSequence::route('/{record}/edit'),
        ];
    }
}
