<?php
declare(strict_types=1);
namespace App\Filament\Resources\FraudFlagResource\Pages;
use App\Filament\Resources\FraudFlagResource;
use Filament\Resources\Pages\CreateRecord;
class CreateFraudFlag extends CreateRecord {
    protected static string $resource = FraudFlagResource::class;
}
