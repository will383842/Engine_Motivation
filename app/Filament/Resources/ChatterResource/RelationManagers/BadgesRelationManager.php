<?php

declare(strict_types=1);

namespace App\Filament\Resources\ChatterResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class BadgesRelationManager extends RelationManager
{
    protected static string $relationship = 'badges';
    protected static ?string $title = 'Badges';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('slug')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('category')->badge()->sortable(),
                Tables\Columns\TextColumn::make('xp_reward')->label('XP')->sortable(),
                Tables\Columns\TextColumn::make('pivot.awarded_at')->label('Awarded')->dateTime()->sortable(),
            ])
            ->defaultSort('pivot.awarded_at', 'desc')
            ->paginated([10, 25]);
    }
}
