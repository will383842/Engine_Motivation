<?php
declare(strict_types=1);
namespace App\Filament\Resources\LeagueResource\Pages;
use App\Filament\Resources\LeagueResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditLeague extends EditRecord {
    protected static string $resource = LeagueResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
