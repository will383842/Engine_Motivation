<?php
declare(strict_types=1);
namespace App\Filament\Resources\AdminAlertResource\Pages;
use App\Filament\Resources\AdminAlertResource;
use Filament\Resources\Pages\CreateRecord;
class CreateAdminAlert extends CreateRecord {
    protected static string $resource = AdminAlertResource::class;
}
