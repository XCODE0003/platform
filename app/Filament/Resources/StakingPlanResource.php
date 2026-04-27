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
    protected static ?string $navigationLabel = 'Тарифы стейкинга';
    protected static ?string $modelLabel = 'Тариф стейкинга';
    protected static ?string $pluralModelLabel = 'Тарифы стейкинга';
    protected static ?string $navigationGroup = 'Финансы';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('currency_id')
                ->label('Валюта')
                ->options(Currency::where('status', 'active')->pluck('name', 'id'))
                ->searchable()
                ->required(),

            Forms\Components\TextInput::make('name')
                ->label('Название тарифа')
                ->placeholder('например: BTC 30 дней Flex')
                ->required(),

            Forms\Components\TextInput::make('apy_percent')
                ->label('Годовая ставка (APY, %)')
                ->numeric()
                ->step(0.01)
                ->suffix('%')
                ->required(),

            Forms\Components\TextInput::make('duration_days')
                ->label('Срок (дней)')
                ->numeric()
                ->integer()
                ->required(),

            Forms\Components\TextInput::make('min_amount')
                ->label('Минимальная сумма')
                ->numeric()
                ->default(0)
                ->required(),

            Forms\Components\TextInput::make('max_amount')
                ->label('Максимальная сумма (пусто = без лимита)')
                ->numeric()
                ->nullable(),

            Forms\Components\Toggle::make('is_active')
                ->label('Активен')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('currency.symbol')->label('Валюта')->sortable(),
                Tables\Columns\TextColumn::make('name')->label('Название')->searchable(),
                Tables\Columns\TextColumn::make('apy_percent')->label('APY')->suffix('%')->sortable(),
                Tables\Columns\TextColumn::make('duration_days')->label('Дней')->sortable(),
                Tables\Columns\TextColumn::make('min_amount')->label('Мин.'),
                Tables\Columns\TextColumn::make('max_amount')->label('Макс.')->placeholder('Без лимита'),
                Tables\Columns\IconColumn::make('is_active')->label('Активен')->boolean(),
                Tables\Columns\TextColumn::make('stakings_count')
                    ->label('Стейкингов')
                    ->counts('stakings'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Активен'),
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
