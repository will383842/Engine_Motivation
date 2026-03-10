<?php
declare(strict_types=1);
namespace App\Filament\Resources\FraudFlagResource\Pages;
use App\Filament\Resources\FraudFlagResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditFraudFlag extends EditRecord {
    protected static string $resource = FraudFlagResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
