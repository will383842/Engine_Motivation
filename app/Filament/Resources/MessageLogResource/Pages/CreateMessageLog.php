<?php
declare(strict_types=1);
namespace App\Filament\Resources\MessageLogResource\Pages;
use App\Filament\Resources\MessageLogResource;
use Filament\Resources\Pages\CreateRecord;
class CreateMessageLog extends CreateRecord {
    protected static string $resource = MessageLogResource::class;
}
