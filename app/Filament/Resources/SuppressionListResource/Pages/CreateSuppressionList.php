<?php
declare(strict_types=1);
namespace App\Filament\Resources\SuppressionListResource\Pages;
use App\Filament\Resources\SuppressionListResource;
use Filament\Resources\Pages\CreateRecord;
class CreateSuppressionList extends CreateRecord {
    protected static string $resource = SuppressionListResource::class;
}
