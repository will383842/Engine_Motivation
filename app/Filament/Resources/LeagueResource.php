<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\LeagueResource\Pages;
use App\Models\League;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LeagueResource extends Resource
{
    protected static ?string $model = League::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Engagement';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('tier')
                ->options(['bronze' => 'Bronze', 'silver' => 'Silver', 'gold' => 'Gold', 'platinum' => 'Platinum', 'diamond' => 'Diamond', 'master' => 'Master', 'legend' => 'Legend'])
                ->required(),
            TextInput::make('week_key')->required(),
            TextInput::make('max_participants')->numeric()->default(30),
            TextInput::make('promotion_count')->numeric()->default(5),
            TextInput::make('relegation_count')->numeric()->default(5),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tier')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('week_key')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('max_participants')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('promotion_count')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('relegation_count')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('created_at')->sortable()->searchable(),
            ])
            ->filters([])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeagues::route('/'),
            'create' => Pages\CreateLeague::route('/create'),
            'edit' => Pages\EditLeague::route('/{record}/edit'),
        ];
    }
}
