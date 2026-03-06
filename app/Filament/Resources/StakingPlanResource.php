<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StakingPlanResource\Pages;
use App\Models\Currency;
use App\Models\StakingPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StakingPlanResource extends Resource
{
    protected static ?string $model = StakingPlan::class;
    protected static ?string $navigationIcon  = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Staking Plans';
    protected static ?string $navigationGroup = 'Finance';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('currency_id')
                ->label('Currency')
                ->options(Currency::where('status', 'active')->pluck('name', 'id'))
                ->searchable()
                ->required(),

            Forms\Components\TextInput::make('name')
                ->label('Plan name')
                ->placeholder('e.g. BTC 30-Day Flex')
                ->required(),

            Forms\Components\TextInput::make('apy_percent')
                ->label('APY (%)')
                ->numeric()
                ->step(0.01)
                ->suffix('%')
                ->required(),

            Forms\Components\TextInput::make('duration_days')
                ->label('Duration (days)')
                ->numeric()
                ->integer()
                ->required(),

            Forms\Components\TextInput::make('min_amount')
                ->label('Min amount')
                ->numeric()
                ->default(0)
                ->required(),

            Forms\Components\TextInput::make('max_amount')
                ->label('Max amount (leave empty for no limit)')
                ->numeric()
                ->nullable(),

            Forms\Components\Toggle::make('is_active')
                ->label('Active')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('currency.symbol')->label('Currency')->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('apy_percent')->label('APY')->suffix('%')->sortable(),
                Tables\Columns\TextColumn::make('duration_days')->label('Days')->sortable(),
                Tables\Columns\TextColumn::make('min_amount')->label('Min'),
                Tables\Columns\TextColumn::make('max_amount')->label('Max')->placeholder('No limit'),
                Tables\Columns\IconColumn::make('is_active')->label('Active')->boolean(),
                Tables\Columns\TextColumn::make('stakings_count')
                    ->label('Stakings')
                    ->counts('stakings'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListStakingPlans::route('/'),
            'create' => Pages\CreateStakingPlan::route('/create'),
            'edit'   => Pages\EditStakingPlan::route('/{record}/edit'),
        ];
    }
}
