<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\SuppressionListResource\Pages;
use App\Models\SuppressionList;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SuppressionListResource extends Resource
{
    protected static ?string $model = SuppressionList::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Messaging';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('chatter_id')
                ->relationship('chatter', 'display_name')
                ->searchable()
                ->required(),
            Select::make('channel')
                ->options(['telegram' => 'Telegram', 'whatsapp' => 'WhatsApp', 'email' => 'Email']),
            Select::make('reason')
                ->options(['opt_out' => 'Opt Out', 'blocked' => 'Blocked', 'bounced' => 'Bounced', 'spam_reported' => 'Spam', 'gdpr_erasure' => 'GDPR', 'admin_manual' => 'Admin Manual', 'sunset_policy' => 'Sunset', 'invalid_number' => 'Invalid Number', 'duplicate' => 'Duplicate'])
                ->required(),
            TextInput::make('source'),
            DateTimePicker::make('expires_at'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('chatter_id')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('channel')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('reason')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('source')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('suppressed_at')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('expires_at')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('lifted_at')->sortable()->searchable(),
            ])
            ->filters([])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuppressionLists::route('/'),
            'create' => Pages\CreateSuppressionList::route('/create'),
            'edit' => Pages\EditSuppressionList::route('/{record}/edit'),
        ];
    }
}
