<?php
declare(strict_types=1);
namespace App\Filament\Resources\MissionResource\Pages;
use App\Filament\Resources\MissionResource;
use Filament\Resources\Pages\CreateRecord;
class CreateMission extends CreateRecord {
    protected static string $resource = MissionResource::class;
}
