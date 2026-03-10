<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\BadgeResource\Pages;
use App\Models\Badge;
use Filament\Forms;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BadgeResource extends Resource
{
    protected static ?string $model = Badge::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Engagement';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('slug')->required()->unique(ignoreRecord: true),
            KeyValue::make('names')->required(),
            KeyValue::make('descriptions'),
            TextInput::make('category')->required(),
            TextInput::make('xp_reward')->numeric()->required(),
            KeyValue::make('criteria')->required(),
            TextInput::make('icon_url'),
            Toggle::make('is_active')->default(true),
            TextInput::make('sort_order')->numeric(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('slug')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('category')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('xp_reward')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('is_active')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('sort_order')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('created_at')->sortable()->searchable(),
            ])
            ->filters([])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBadges::route('/'),
            'create' => Pages\CreateBadge::route('/create'),
            'edit' => Pages\EditBadge::route('/{record}/edit'),
        ];
    }
}
