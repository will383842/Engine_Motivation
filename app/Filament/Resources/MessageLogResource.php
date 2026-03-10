<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\MessageLogResource\Pages;
use App\Models\MessageLog;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MessageLogResource extends Resource
{
    protected static ?string $model = MessageLog::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Messaging';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('chatter_id')->disabled(),
            TextInput::make('channel')->disabled(),
            TextInput::make('status')->disabled(),
            TextInput::make('source_type')->disabled(),
            TextInput::make('sent_at')->disabled(),
            TextInput::make('delivered_at')->disabled(),
            TextInput::make('cost_cents')->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('chatter_id')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('channel')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('status')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('source_type')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('sent_at')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('delivered_at')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('cost_cents')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('created_at')->sortable()->searchable(),
            ])
            ->filters([])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMessageLogs::route('/'),
            'create' => Pages\CreateMessageLog::route('/create'),
            'edit' => Pages\EditMessageLog::route('/{record}/edit'),
        ];
    }
}
