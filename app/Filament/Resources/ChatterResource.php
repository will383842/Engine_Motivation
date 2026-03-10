<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ChatterResource\Pages;
use App\Models\Chatter;
use App\Services\LevelService;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Components\Grid as InfoGrid;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ChatterResource extends Resource
{
    protected static ?string $model = Chatter::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Chatters';
    protected static ?string $recordTitleAttribute = 'display_name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Identity')
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('firebase_uid')->disabled()->columnSpan(1),
                        TextInput::make('display_name')->disabled()->columnSpan(1),
                        TextInput::make('email')->disabled()->columnSpan(1),
                    ]),
                    Grid::make(3)->schema([
                        Select::make('language')
                            ->options(['fr' => 'FR', 'en' => 'EN', 'es' => 'ES', 'de' => 'DE', 'pt' => 'PT', 'ru' => 'RU', 'zh' => 'ZH', 'hi' => 'HI', 'ar' => 'AR'])
                            ->disabled()
                            ->columnSpan(1),
                        TextInput::make('country')->disabled()->columnSpan(1),
                        TextInput::make('timezone')->disabled()->columnSpan(1),
                    ]),
                ]),

            Section::make('Status & Lifecycle')
                ->schema([
                    Grid::make(3)->schema([
                        Select::make('lifecycle_state')
                            ->options([
                                'registered' => 'Registered',
                                'onboarding' => 'Onboarding',
                                'active' => 'Active',
                                'declining' => 'Declining',
                                'dormant' => 'Dormant',
                                'churned' => 'Churned',
                                'sunset' => 'Sunset',
                            ])
                            ->columnSpan(1),
                        Toggle::make('is_active')->columnSpan(1),
                        TextInput::make('preferred_channel')->disabled()->columnSpan(1),
                    ]),
                ]),

            Section::make('Gamification')
                ->schema([
                    Grid::make(4)->schema([
                        TextInput::make('total_xp')->numeric()->disabled()->columnSpan(1),
                        TextInput::make('level')->numeric()->disabled()
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('levelInfo')
                                    ->icon('heroicon-o-information-circle')
                                    ->tooltip(fn ($record) => $record
                                        ? (LevelService::getTierInfo($record->level ?? 1)['name'] ?? 'Novice')
                                        : 'Novice'
                                    )
                            )
                            ->columnSpan(1),
                        TextInput::make('current_streak')->numeric()->disabled()->columnSpan(1),
                        TextInput::make('league_tier')->disabled()->columnSpan(1),
                    ]),
                ]),

            Section::make('Finances')
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('balance_cents')
                            ->numeric()
                            ->disabled()
                            ->formatStateUsing(fn ($state) => $state ? '$' . number_format($state / 100, 2) : '$0.00')
                            ->columnSpan(1),
                        TextInput::make('lifetime_earnings_cents')
                            ->numeric()
                            ->disabled()
                            ->formatStateUsing(fn ($state) => $state ? '$' . number_format($state / 100, 2) : '$0.00')
                            ->columnSpan(1),
                        TextInput::make('badges_count')->numeric()->disabled()->columnSpan(1),
                    ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('display_name')
                    ->sortable()
                    ->searchable()
                    ->description(fn (Chatter $record) => $record->firebase_uid),
                Tables\Columns\TextColumn::make('level')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => "Lv.{$state} " . (LevelService::getTierInfo($state ?? 1)['name'] ?? '')),
                Tables\Columns\TextColumn::make('total_xp')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_streak')
                    ->label('Streak')
                    ->sortable()
                    ->suffix(' days'),
                Tables\Columns\TextColumn::make('balance_cents')
                    ->label('Balance')
                    ->money('usd', divideBy: 100)
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('lifecycle_state')
                    ->colors([
                        'gray' => 'registered',
                        'info' => 'onboarding',
                        'success' => 'active',
                        'warning' => 'declining',
                        'danger' => fn ($state) => in_array($state, ['dormant', 'churned', 'sunset']),
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('preferred_channel')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_active_at')
                    ->dateTime()
                    ->sortable()
                    ->since(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('lifecycle_state')
                    ->options([
                        'registered' => 'Registered',
                        'onboarding' => 'Onboarding',
                        'active' => 'Active',
                        'declining' => 'Declining',
                        'dormant' => 'Dormant',
                        'churned' => 'Churned',
                        'sunset' => 'Sunset',
                    ]),
                SelectFilter::make('preferred_channel')
                    ->options([
                        'telegram' => 'Telegram',
                        'whatsapp' => 'WhatsApp',
                        'dashboard' => 'Dashboard',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getRelations(): array
    {
        return [
            ChatterResource\RelationManagers\EventsRelationManager::class,
            ChatterResource\RelationManagers\MissionsRelationManager::class,
            ChatterResource\RelationManagers\BadgesRelationManager::class,
            ChatterResource\RelationManagers\SequencesRelationManager::class,
            ChatterResource\RelationManagers\MessageLogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChatters::route('/'),
            'create' => Pages\CreateChatter::route('/create'),
            'view' => Pages\ViewChatter::route('/{record}'),
            'edit' => Pages\EditChatter::route('/{record}/edit'),
        ];
    }
}
