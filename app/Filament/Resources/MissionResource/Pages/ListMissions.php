<?php
declare(strict_types=1);
namespace App\Filament\Resources\MissionResource\Pages;
use App\Filament\Resources\MissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListMissions extends ListRecords {
    protected static string $resource = MissionResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
