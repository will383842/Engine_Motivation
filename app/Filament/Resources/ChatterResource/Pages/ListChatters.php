<?php
declare(strict_types=1);
namespace App\Filament\Resources\ChatterResource\Pages;
use App\Filament\Resources\ChatterResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListChatters extends ListRecords {
    protected static string $resource = ChatterResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
