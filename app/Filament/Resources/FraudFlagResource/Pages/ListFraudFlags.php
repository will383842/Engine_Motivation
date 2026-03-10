<?php
declare(strict_types=1);
namespace App\Filament\Resources\FraudFlagResource\Pages;
use App\Filament\Resources\FraudFlagResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListFraudFlags extends ListRecords {
    protected static string $resource = FraudFlagResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
