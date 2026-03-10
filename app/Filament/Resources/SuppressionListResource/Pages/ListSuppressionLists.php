<?php
declare(strict_types=1);
namespace App\Filament\Resources\SuppressionListResource\Pages;
use App\Filament\Resources\SuppressionListResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListSuppressionLists extends ListRecords {
    protected static string $resource = SuppressionListResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
