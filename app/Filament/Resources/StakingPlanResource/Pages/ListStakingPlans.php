<?php
namespace App\Filament\Resources\StakingPlanResource\Pages;
use App\Filament\Resources\StakingPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListStakingPlans extends ListRecords {
    protected static string $resource = StakingPlanResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
