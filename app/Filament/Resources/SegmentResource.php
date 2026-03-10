<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\SegmentResource\Pages;
use App\Models\Segment;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SegmentResource extends Resource
{
    protected static ?string $model = Segment::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Analytics';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->required(),
            Textarea::make('description'),
            Select::make('operator')
                ->options(['and' => 'AND', 'or' => 'OR'])
                ->default('and'),
            Toggle::make('is_dynamic')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('operator')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('is_dynamic')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('cached_count')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('cached_at')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('created_at')->sortable()->searchable(),
            ])
            ->filters([])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSegments::route('/'),
            'create' => Pages\CreateSegment::route('/create'),
            'edit' => Pages\EditSegment::route('/{record}/edit'),
        ];
    }
}
