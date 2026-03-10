<?php
declare(strict_types=1);
namespace App\Filament\Resources\WhatsAppNumberResource\Pages;
use App\Filament\Resources\WhatsAppNumberResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListWhatsAppNumbers extends ListRecords {
    protected static string $resource = WhatsAppNumberResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
