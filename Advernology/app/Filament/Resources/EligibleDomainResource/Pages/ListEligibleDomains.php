<?php

namespace App\Filament\Resources\EligibleDomainResource\Pages;

use App\Filament\Resources\EligibleDomainResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEligibleDomains extends ListRecords
{
    protected static string $resource = EligibleDomainResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
