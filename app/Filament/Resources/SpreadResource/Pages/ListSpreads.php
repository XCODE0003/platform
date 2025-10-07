<?php

namespace App\Filament\Resources\SpreadResource\Pages;

use App\Filament\Resources\SpreadResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSpreads extends ListRecords
{
    protected static string $resource = SpreadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
