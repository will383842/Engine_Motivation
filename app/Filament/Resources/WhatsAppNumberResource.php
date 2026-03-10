<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\WhatsAppNumberResource\Pages;
use App\Models\WhatsAppNumber;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WhatsAppNumberResource extends Resource
{
    protected static ?string $model = WhatsAppNumber::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Channels';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('phone_number')->required()->tel()->unique(ignoreRecord: true),
            TextInput::make('twilio_sid')->required(),
            TextInput::make('display_name'),
            TextInput::make('country_code')->maxLength(3),
            DatePicker::make('warmup_start_date'),
            TextInput::make('warmup_week')->numeric()->default(1),
            TextInput::make('current_daily_limit')->numeric()->default(30),
            Toggle::make('is_active')->default(true),
            TextInput::make('daily_budget_cap_cents')->numeric(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('phone_number')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('display_name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('is_active')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('quality_rating')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('circuit_breaker_state')->sortable()->searchable(),
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
            'index' => Pages\ListWhatsAppNumbers::route('/'),
            'create' => Pages\CreateWhatsAppNumber::route('/create'),
            'edit' => Pages\EditWhatsAppNumber::route('/{record}/edit'),
        ];
    }
}
