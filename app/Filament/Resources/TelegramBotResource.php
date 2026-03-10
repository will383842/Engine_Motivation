<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\TelegramBotResource\Pages;
use App\Models\TelegramBot;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TelegramBotResource extends Resource
{
    protected static ?string $model = TelegramBot::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Channels';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('bot_username')->required()->unique(ignoreRecord: true),
            TextInput::make('bot_token_encrypted')->required()->password(),
            TextInput::make('bot_label'),
            Select::make('role')
                ->options(['primary' => 'Primary', 'secondary' => 'Secondary', 'standby' => 'Standby']),
            Toggle::make('is_active')->default(true),
            Toggle::make('is_restricted')->default(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('bot_username')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('role')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('is_active')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('assigned_chatters_count')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('health_score')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('total_sent')->sortable()->searchable(),
            ])
            ->filters([])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTelegramBots::route('/'),
            'create' => Pages\CreateTelegramBot::route('/create'),
            'edit' => Pages\EditTelegramBot::route('/{record}/edit'),
        ];
    }
}
