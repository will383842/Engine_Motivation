<?php

declare(strict_types=1);

namespace App\Filament\Resources\ChatterResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class EventsRelationManager extends RelationManager
{
    protected static string $relationship = 'events';
    protected static ?string $title = 'Events';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('event_type')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('firebase_event_id')->label('Firebase ID')->limit(20),
                Tables\Columns\TextColumn::make('occurred_at')->dateTime()->sortable(),
            ])
            ->defaultSort('occurred_at', 'desc')
            ->paginated([10, 25]);
    }
}
