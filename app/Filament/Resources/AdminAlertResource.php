<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\AdminAlertResource\Pages;
use App\Models\AdminAlert;
use Filament\Forms;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AdminAlertResource extends Resource
{
    protected static ?string $model = AdminAlert::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Security';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('severity')->disabled(),
            TextInput::make('category')->disabled(),
            Textarea::make('message')->disabled(),
            Toggle::make('acknowledged')->label('Acknowledged'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('severity')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('category')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('message')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('acknowledged_at')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('created_at')->sortable()->searchable(),
            ])
            ->filters([])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdminAlerts::route('/'),
            'create' => Pages\CreateAdminAlert::route('/create'),
            'edit' => Pages\EditAdminAlert::route('/{record}/edit'),
        ];
    }
}
