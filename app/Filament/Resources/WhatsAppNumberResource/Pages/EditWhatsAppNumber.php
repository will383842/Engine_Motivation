<?php
declare(strict_types=1);
namespace App\Filament\Resources\WhatsAppNumberResource\Pages;
use App\Filament\Resources\WhatsAppNumberResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditWhatsAppNumber extends EditRecord {
    protected static string $resource = WhatsAppNumberResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
