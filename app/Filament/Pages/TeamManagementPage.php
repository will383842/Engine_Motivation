<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\AdminUser;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class TeamManagementPage extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static string $view = 'filament.pages.team-management';
    protected static ?string $title = 'Team Management';
    protected static ?string $navigationGroup = 'Settings';

    public function table(Table $table): Table
    {
        return $table
            ->query(AdminUser::query())
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'super_admin' => 'danger',
                        'admin' => 'warning',
                        'editor' => 'info',
                        'viewer' => 'gray',
                        default => 'secondary',
                    })
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                TextColumn::make('last_login_at')
                    ->label('Last Login')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->placeholder('Never'),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options([
                        'super_admin' => 'Super Admin',
                        'admin' => 'Admin',
                        'editor' => 'Editor',
                        'viewer' => 'Viewer',
                    ]),
                TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                EditAction::make()
                    ->form([
                        TextInput::make('name')->required(),
                        TextInput::make('email')->email()->required(),
                        Select::make('role')
                            ->options([
                                'super_admin' => 'Super Admin',
                                'admin' => 'Admin',
                                'editor' => 'Editor',
                                'viewer' => 'Viewer',
                            ])
                            ->required(),
                        Toggle::make('is_active')->label('Active'),
                    ]),
                Action::make('toggleActive')
                    ->label(fn ($record) => $record->is_active ? 'Deactivate' : 'Activate')
                    ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->update(['is_active' => !$record->is_active])),
            ])
            ->defaultSort('name')
            ->defaultPaginationPageOption(25);
    }
}
