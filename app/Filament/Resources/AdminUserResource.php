<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\AdminUserResource\Pages;
use App\Models\AdminUser;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AdminUserResource extends Resource
{
    protected static ?string $model = AdminUser::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Settings';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->required(),
            TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
            TextInput::make('password')->password()->required()->visibleOn('create'),
            Select::make('role')
                ->options(['super_admin' => 'Super Admin', 'admin' => 'Admin', 'manager' => 'Manager', 'analyst' => 'Analyst', 'viewer' => 'Viewer'])
                ->required(),
            Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('email')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('role')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('is_active')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('last_login_at')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('created_at')->sortable()->searchable(),
            ])
            ->filters([])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdminUsers::route('/'),
            'create' => Pages\CreateAdminUser::route('/create'),
            'edit' => Pages\EditAdminUser::route('/{record}/edit'),
        ];
    }
}
