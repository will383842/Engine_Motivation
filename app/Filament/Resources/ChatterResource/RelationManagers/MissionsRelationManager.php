<?php

declare(strict_types=1);

namespace App\Filament\Resources\ChatterResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class MissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'chatterMissions';
    protected static ?string $title = 'Missions';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('mission.slug')->label('Mission')->sortable()->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'assigned',
                        'info' => 'in_progress',
                        'success' => 'completed',
                        'danger' => 'expired',
                    ]),
                Tables\Columns\TextColumn::make('progress_count')->label('Progress'),
                Tables\Columns\TextColumn::make('target_count')->label('Target'),
                Tables\Columns\TextColumn::make('completed_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('expires_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25]);
    }
}
