<?php

declare(strict_types=1);

namespace App\Filament\Resources\ChatterResource\Pages;

use App\Filament\Resources\ChatterResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewChatter extends ViewRecord
{
    protected static string $resource = ChatterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
