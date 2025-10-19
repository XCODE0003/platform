<?php

namespace App\Filament\Resources\PairSourceResource\Pages;

use App\Filament\Resources\PairSourceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPairSource extends EditRecord
{
    protected static string $resource = PairSourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
