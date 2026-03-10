<?php
declare(strict_types=1);
namespace App\Filament\Resources\ChatterResource\Pages;
use App\Filament\Resources\ChatterResource;
use Filament\Resources\Pages\CreateRecord;
class CreateChatter extends CreateRecord {
    protected static string $resource = ChatterResource::class;
}
