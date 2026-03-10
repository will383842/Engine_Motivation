<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ConsentRecordResource\Pages;
use App\Models\ConsentRecord;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ConsentRecordResource extends Resource
{
    protected static ?string $model = ConsentRecord::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Security';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('chatter_id')->disabled(),
            TextInput::make('consent_type')->disabled(),
            Toggle::make('granted')->disabled(),
            TextInput::make('version')->disabled(),
            TextInput::make('granted_at')->disabled(),
            TextInput::make('revoked_at')->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('chatter_id')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('consent_type')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('granted')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('version')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('granted_at')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('revoked_at')->sortable()->searchable(),
            ])
            ->filters([])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConsentRecords::route('/'),
            'create' => Pages\CreateConsentRecord::route('/create'),
            'edit' => Pages\EditConsentRecord::route('/{record}/edit'),
        ];
    }
}
