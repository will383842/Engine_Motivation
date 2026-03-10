<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\CampaignResource\Pages;
use App\Models\Campaign;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;
    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $navigationGroup = 'Engagement';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Wizard::make([
                Wizard\Step::make('Basics')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(100)
                                ->columnSpan(1),
                            Select::make('status')
                                ->options([
                                    'draft' => 'Draft',
                                    'scheduled' => 'Scheduled',
                                    'sending' => 'Sending',
                                    'sent' => 'Sent',
                                    'paused' => 'Paused',
                                    'cancelled' => 'Cancelled',
                                    'failed' => 'Failed',
                                ])
                                ->default('draft')
                                ->columnSpan(1),
                        ]),
                    ]),
                Wizard\Step::make('Targeting')
                    ->icon('heroicon-o-user-group')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('channel')
                                ->options([
                                    'telegram' => 'Telegram',
                                    'whatsapp' => 'WhatsApp',
                                ])
                                ->required()
                                ->columnSpan(1),
                            Select::make('segment_id')
                                ->relationship('segment', 'name')
                                ->searchable()
                                ->preload()
                                ->columnSpan(1),
                        ]),
                    ]),
                Wizard\Step::make('Content')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Select::make('template_id')
                            ->relationship('template', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('ab_test_id')
                            ->relationship('abTest', 'name')
                            ->searchable()
                            ->preload()
                            ->label('A/B Test (optional)'),
                    ]),
                Wizard\Step::make('Schedule')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        Grid::make(2)->schema([
                            DateTimePicker::make('scheduled_at')
                                ->label('Send at')
                                ->columnSpan(1),
                            TextInput::make('send_rate_per_second')
                                ->numeric()
                                ->default(10)
                                ->helperText('Messages per second (throttle)')
                                ->columnSpan(1),
                        ]),
                        Toggle::make('timezone_aware')
                            ->default(false)
                            ->helperText('Send at local time for each chatter'),
                    ]),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'scheduled',
                        'primary' => 'sending',
                        'success' => 'sent',
                        'gray' => 'paused',
                        'danger' => fn ($state) => in_array($state, ['cancelled', 'failed']),
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('channel')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_recipients')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sent_count')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('delivered_count')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'scheduled' => 'Scheduled',
                        'sending' => 'Sending',
                        'sent' => 'Sent',
                        'paused' => 'Paused',
                    ]),
                SelectFilter::make('channel')
                    ->options(['telegram' => 'Telegram', 'whatsapp' => 'WhatsApp']),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCampaigns::route('/'),
            'create' => Pages\CreateCampaign::route('/create'),
            'edit' => Pages\EditCampaign::route('/{record}/edit'),
        ];
    }
}
