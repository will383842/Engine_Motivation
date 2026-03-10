<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\FraudFlagResource\Pages;
use App\Models\FraudFlag;
use Filament\Forms;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FraudFlagResource extends Resource
{
    protected static ?string $model = FraudFlag::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Security';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('chatter_id')
                ->relationship('chatter', 'display_name')
                ->disabled(),
            TextInput::make('flag_type')->disabled(),
            Select::make('severity')
                ->options(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High']),
            KeyValue::make('evidence')->disabled(),
            Toggle::make('resolved'),
            TextInput::make('resolved_by'),
            Textarea::make('resolution_notes'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('chatter_id')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('flag_type')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('severity')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('resolved')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('created_at')->sortable()->searchable(),
            ])
            ->filters([])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFraudFlags::route('/'),
            'create' => Pages\CreateFraudFlag::route('/create'),
            'edit' => Pages\EditFraudFlag::route('/{record}/edit'),
        ];
    }
}
