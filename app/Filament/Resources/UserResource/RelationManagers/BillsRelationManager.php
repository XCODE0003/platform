<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\Currency;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BillsRelationManager extends RelationManager
{
    protected static string $relationship = 'bills';
    protected static ?string $title = 'Счета';
    protected static ?string $modelLabel = 'Счёт';
    protected static ?string $pluralModelLabel = 'Счета';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('currency_id')
                    ->label('Валюта')
                    ->options(Currency::all()->pluck('name', 'id'))
                    ->required()
                    ->searchable(),
                Forms\Components\TextInput::make('balance')
                    ->label('Баланс')
                    ->numeric()
                    ->step('0.00000001')
                    ->default('0.00000000')
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->label('Название')
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('currency.name')
            ->columns([
                Tables\Columns\TextColumn::make('currency.name')
                    ->label('Валюта')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('currency.symbol')
                    ->label('Символ')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Баланс')
                    ->numeric(decimalPlaces: 8)
                    ->sortable(),
                Tables\Columns\TextColumn::make('usd_value')
                    ->label('Стоимость в USD')
                    ->getStateUsing(function ($record) {
                        $balance = (string) $record->balance;

                        // Приводим курс к строке с корректным форматом для bc* функций
                        $rate = $record->currency->exchange_rate ?? '1';
                        // Убираем возможные нечисловые символы и приводим к строке с точкой
                        $rate = preg_replace('/[^0-9\.\-]/', '', (string) $rate);
                        if ($rate === '' || !is_numeric($rate)) {
                            $rate = '1';
                        }

                        return bcmul($balance, $rate, 2);
                    })
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Обновлён')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('currency_id')
                    ->label('Валюта')
                    ->options(Currency::all()->pluck('name', 'id'))
                    ->searchable(),
                Tables\Filters\Filter::make('has_balance')
                    ->label('С балансом')
                    ->query(fn(Builder $query): Builder => $query->where('balance', '>', '0')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Добавить счёт'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
