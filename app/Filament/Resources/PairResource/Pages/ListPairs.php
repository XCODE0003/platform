<?php

namespace App\Filament\Resources\PairResource\Pages;

use App\Filament\Resources\PairResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPairs extends ListRecords
{
    protected static string $resource = PairResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
