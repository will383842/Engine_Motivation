<?php
declare(strict_types=1);
namespace App\Filament\Resources\TelegramBotResource\Pages;
use App\Filament\Resources\TelegramBotResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditTelegramBot extends EditRecord {
    protected static string $resource = TelegramBotResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
