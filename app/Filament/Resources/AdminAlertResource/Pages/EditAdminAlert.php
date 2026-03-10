<?php
declare(strict_types=1);
namespace App\Filament\Resources\AdminAlertResource\Pages;
use App\Filament\Resources\AdminAlertResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditAdminAlert extends EditRecord {
    protected static string $resource = AdminAlertResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
