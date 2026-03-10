<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\Channel;
use App\Models\MessageLog;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MessageHistoryPage extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    protected static string $view = 'filament.pages.message-history';
    protected static ?string $title = 'Message History';
    protected static ?string $navigationGroup = 'Messaging';

    public function table(Table $table): Table
    {
        return $table
            ->query(MessageLog::query()->with('chatter')->latest('sent_at'))
            ->columns([
                TextColumn::make('chatter.display_name')
                    ->label('Chatter')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('channel')
                    ->label('Channel')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'telegram' => 'info',
                        'whatsapp' => 'success',
                        'email' => 'warning',
                        'sms' => 'gray',
                        default => 'secondary',
                    }),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'delivered' => 'success',
                        'sent' => 'info',
                        'read' => 'success',
                        'failed' => 'danger',
                        'pending' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('body')
                    ->label('Body')
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->body)
                    ->wrap(),
                TextColumn::make('sent_at')
                    ->label('Sent At')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('cost_cents')
                    ->label('Cost')
                    ->formatStateUsing(fn ($state) => $state ? '$' . number_format($state / 100, 2) : '-')
                    ->alignEnd(),
            ])
            ->filters([
                SelectFilter::make('channel')
                    ->options(
                        collect(Channel::cases())
                            ->mapWithKeys(fn (Channel $c) => [$c->value => $c->label()])
                            ->toArray()
                    ),
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'sent' => 'Sent',
                        'delivered' => 'Delivered',
                        'read' => 'Read',
                        'failed' => 'Failed',
                    ]),
                Filter::make('date_range')
                    ->form([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('until')->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn (Builder $q, $date) => $q->where('sent_at', '>=', $date))
                            ->when($data['until'], fn (Builder $q, $date) => $q->where('sent_at', '<=', $date));
                    }),
            ])
            ->defaultSort('sent_at', 'desc')
            ->defaultPaginationPageOption(25);
    }
}
