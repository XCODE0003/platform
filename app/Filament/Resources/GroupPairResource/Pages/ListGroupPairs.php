<?php

namespace App\Filament\Resources\GroupPairResource\Pages;

use App\Filament\Resources\GroupPairResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGroupPairs extends ListRecords
{
    protected static string $resource = GroupPairResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
