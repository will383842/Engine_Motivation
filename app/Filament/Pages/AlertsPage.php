<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\AdminAlert;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class AlertsPage extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';
    protected static string $view = 'filament.pages.alerts';
    protected static ?string $title = 'Alerts';
    protected static ?string $navigationGroup = 'Monitoring';

    public function table(Table $table): Table
    {
        return $table
            ->query(AdminAlert::query()->with('acknowledgedBy')->latest('created_at'))
            ->columns([
                TextColumn::make('severity')
                    ->label('Severity')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'critical' => 'danger',
                        'high' => 'danger',
                        'warning' => 'warning',
                        'info' => 'info',
                        'low' => 'gray',
                        default => 'secondary',
                    })
                    ->sortable(),
                TextColumn::make('category')
                    ->label('Type')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                TextColumn::make('message')
                    ->label('Message')
                    ->limit(80)
                    ->tooltip(fn ($record) => $record->message)
                    ->wrap()
                    ->searchable(),
                IconColumn::make('acknowledged')
                    ->label('Ack')
                    ->state(fn ($record) => $record->acknowledged_at !== null)
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                TextColumn::make('acknowledgedBy.name')
                    ->label('Ack By')
                    ->placeholder('-'),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('severity')
                    ->options([
                        'critical' => 'Critical',
                        'high' => 'High',
                        'warning' => 'Warning',
                        'info' => 'Info',
                        'low' => 'Low',
                    ]),
                TernaryFilter::make('acknowledged')
                    ->label('Acknowledged')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('acknowledged_at'),
                        false: fn ($query) => $query->whereNull('acknowledged_at'),
                    ),
            ])
            ->actions([
                Action::make('acknowledge')
                    ->label('Acknowledge')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn ($record) => $record->acknowledged_at === null)
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'acknowledged_by' => auth()->id(),
                            'acknowledged_at' => now(),
                        ]);
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25);
    }
}
