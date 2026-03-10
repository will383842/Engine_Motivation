<?php
declare(strict_types=1);
namespace App\Filament\Resources\ConsentRecordResource\Pages;
use App\Filament\Resources\ConsentRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListConsentRecords extends ListRecords {
    protected static string $resource = ConsentRecordResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
