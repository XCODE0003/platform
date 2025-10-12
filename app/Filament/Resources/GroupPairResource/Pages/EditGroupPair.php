<?php

namespace App\Filament\Resources\GroupPairResource\Pages;

use App\Filament\Resources\GroupPairResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGroupPair extends EditRecord
{
    protected static string $resource = GroupPairResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
