<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\MissionResource\Pages;
use App\Models\Mission;
use Filament\Forms;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MissionResource extends Resource
{
    protected static ?string $model = Mission::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Engagement';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('slug')->required()->unique(ignoreRecord: true),
            KeyValue::make('names')->required(),
            KeyValue::make('descriptions'),
            Select::make('type')
                ->options(['one_time' => 'One Time', 'daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'recurring' => 'Recurring', 'streak_based' => 'Streak Based', 'event_triggered' => 'Event Triggered'])
                ->required(),
            Select::make('status')
                ->options(['active' => 'Active', 'paused' => 'Paused', 'archived' => 'Archived']),
            TextInput::make('target_count')->numeric()->required(),
            TextInput::make('xp_reward')->numeric()->required(),
            TextInput::make('bonus_cents')->numeric()->default(0),
            Toggle::make('is_active')->default(true),
            Toggle::make('is_secret')->default(false),
            TextInput::make('sort_order')->numeric(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('slug')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('type')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('status')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('target_count')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('xp_reward')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('bonus_cents')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('created_at')->sortable()->searchable(),
            ])
            ->filters([])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMissions::route('/'),
            'create' => Pages\CreateMission::route('/create'),
            'edit' => Pages\EditMission::route('/{record}/edit'),
        ];
    }
}
