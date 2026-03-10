<?php
declare(strict_types=1);
namespace App\Filament\Resources\ChatterResource\Pages;
use App\Filament\Resources\ChatterResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditChatter extends EditRecord {
    protected static string $resource = ChatterResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
