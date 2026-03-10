<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\MessageTemplateResource\Pages;
use App\Models\MessageTemplate;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MessageTemplateResource extends Resource
{
    protected static ?string $model = MessageTemplate::class;
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';
    protected static ?string $navigationGroup = 'Engagement';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Template Info')
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText('Unique identifier (e.g. welcome, first_sale_celebration)')
                            ->columnSpan(1),
                        TextInput::make('name')
                            ->required()
                            ->columnSpan(1),
                        Select::make('category')
                            ->options([
                                'onboarding' => 'Onboarding',
                                'celebration' => 'Celebration',
                                'gamification' => 'Gamification',
                                'engagement' => 'Engagement',
                                'reactivation' => 'Reactivation',
                                'transactional' => 'Transactional',
                                'alert' => 'Alert',
                            ])
                            ->required()
                            ->columnSpan(1),
                    ]),
                    Toggle::make('is_active')->default(true),
                ]),

            Section::make('Variants')
                ->description('Add message content per channel and language. Use {{variables}} for dynamic content.')
                ->schema([
                    Repeater::make('variants')
                        ->relationship()
                        ->schema([
                            Grid::make(3)->schema([
                                Select::make('channel')
                                    ->options([
                                        'telegram' => 'Telegram',
                                        'whatsapp' => 'WhatsApp',
                                        'email' => 'Email',
                                        'push' => 'Push',
                                        'dashboard' => 'Dashboard',
                                    ])
                                    ->required()
                                    ->columnSpan(1),
                                Select::make('language')
                                    ->options([
                                        'fr' => 'Français',
                                        'en' => 'English',
                                        'es' => 'Español',
                                        'de' => 'Deutsch',
                                        'pt' => 'Português',
                                        'ru' => 'Русский',
                                        'zh' => '中文',
                                        'hi' => 'हिन्दी',
                                        'ar' => 'العربية',
                                    ])
                                    ->required()
                                    ->columnSpan(1),
                                TextInput::make('subject')
                                    ->label('Subject / Title')
                                    ->columnSpan(1),
                            ]),
                            Textarea::make('body')
                                ->required()
                                ->rows(4)
                                ->helperText('Variables: {{displayName}}, {{streakCount}}, {{level}}, {{balance}}, {{xp}}')
                                ->columnSpanFull(),
                        ])
                        ->itemLabel(fn (array $state): ?string =>
                            ($state['channel'] ?? '?') . ' / ' . ($state['language'] ?? '?')
                        )
                        ->collapsible()
                        ->cloneable()
                        ->defaultItems(1)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('slug')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\BadgeColumn::make('category')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\TextColumn::make('variants_count')
                    ->counts('variants')
                    ->label('Variants')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('category')
                    ->options([
                        'onboarding' => 'Onboarding',
                        'celebration' => 'Celebration',
                        'gamification' => 'Gamification',
                        'engagement' => 'Engagement',
                        'reactivation' => 'Reactivation',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMessageTemplates::route('/'),
            'create' => Pages\CreateMessageTemplate::route('/create'),
            'edit' => Pages\EditMessageTemplate::route('/{record}/edit'),
        ];
    }
}
