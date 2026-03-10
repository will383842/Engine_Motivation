<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ScheduledReportResource\Pages;
use App\Models\ScheduledReport;
use Filament\Forms;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ScheduledReportResource extends Resource
{
    protected static ?string $model = ScheduledReport::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Analytics';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->required(),
            TextInput::make('report_type')->required(),
            TextInput::make('schedule_cron')->required(),
            KeyValue::make('recipients'),
            KeyValue::make('filters'),
            Select::make('format')
                ->options(['pdf' => 'PDF', 'csv' => 'CSV', 'html' => 'HTML']),
            Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('report_type')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('schedule_cron')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('format')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('is_active')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('last_sent_at')->sortable()->searchable(),
            ])
            ->filters([])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListScheduledReports::route('/'),
            'create' => Pages\CreateScheduledReport::route('/create'),
            'edit' => Pages\EditScheduledReport::route('/{record}/edit'),
        ];
    }
}
