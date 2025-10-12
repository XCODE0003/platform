<?php

namespace App\Filament\Resources\PairResource\Pages;

use App\Filament\Resources\PairResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPair extends EditRecord
{
    protected static string $resource = PairResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
