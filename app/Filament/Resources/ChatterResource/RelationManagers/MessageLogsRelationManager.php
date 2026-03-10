<?php

declare(strict_types=1);

namespace App\Filament\Resources\ChatterResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class MessageLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'messageLogs';
    protected static ?string $title = 'Messages';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('channel')->badge()->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'pending',
                        'success' => 'sent',
                        'danger' => 'failed',
                    ]),
                Tables\Columns\TextColumn::make('body')->limit(50)->searchable(),
                Tables\Columns\TextColumn::make('source_type')->sortable(),
                Tables\Columns\TextColumn::make('sent_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('cost_cents')
                    ->label('Cost')
                    ->money('usd', divideBy: 100),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25]);
    }
}
