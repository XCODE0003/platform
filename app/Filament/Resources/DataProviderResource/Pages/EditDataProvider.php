<?php

namespace App\Filament\Resources\DataProviderResource\Pages;

use App\Filament\Resources\DataProviderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDataProvider extends EditRecord
{
    protected static string $resource = DataProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}