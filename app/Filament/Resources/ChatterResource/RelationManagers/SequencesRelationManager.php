<?php

declare(strict_types=1);

namespace App\Filament\Resources\ChatterResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SequencesRelationManager extends RelationManager
{
    protected static string $relationship = 'chatterSequences';
    protected static ?string $title = 'Sequences';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sequence.name')->label('Sequence')->sortable()->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'info' => 'active',
                        'success' => 'completed',
                        'warning' => 'paused',
                        'danger' => 'exited',
                    ]),
                Tables\Columns\TextColumn::make('current_step_order')->label('Step'),
                Tables\Columns\TextColumn::make('sequence_version')->label('Version'),
                Tables\Columns\TextColumn::make('next_step_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('completed_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25]);
    }
}
