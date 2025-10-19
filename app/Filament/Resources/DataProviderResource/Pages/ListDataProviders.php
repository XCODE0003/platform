<?php

namespace App\Filament\Resources\DataProviderResource\Pages;

use App\Filament\Resources\DataProviderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDataProviders extends ListRecords
{
    protected static string $resource = DataProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}