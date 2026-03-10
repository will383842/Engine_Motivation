<?php
declare(strict_types=1);
namespace App\Filament\Resources\SequenceResource\Pages;
use App\Filament\Resources\SequenceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListSequences extends ListRecords {
    protected static string $resource = SequenceResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
