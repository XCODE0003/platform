<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\Bill;
use App\Models\Currency;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

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
                Tables\Actions\Action::make('topup')
                    ->label('Пополнить')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->modalHeading('Пополнение счёта')
                    ->modalSubmitActionLabel('Пополнить')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Сумма пополнения')
                            ->numeric()
                            ->step('0.00000001')
                            ->minValue(0.00000001)
                            ->required(),
                        Forms\Components\Textarea::make('comment')
                            ->label('Комментарий для клиента')
                            ->helperText('Будет виден клиенту в истории операций в личном кабинете.')
                            ->placeholder('Напр.: Пополнение счёта')
                            ->rows(2)
                            ->maxLength(1000),
                    ])
                    ->action(function (Bill $record, array $data): void {
                        DB::transaction(function () use ($record, $data): void {
                            $amount = number_format((float) $data['amount'], 8, '.', '');
                            $record->balance = number_format(((float) $record->balance) + (float) $amount, 8, '.', '');
                            $record->save();

                            Transaction::create([
                                'user_id'     => $record->user_id,
                                'currency_id' => $record->currency_id,
                                'bill_id'     => $record->id,
                                'amount'      => $amount,
                                'type'        => 'deposit',
                                'status'      => 'completed',
                                'comment'     => $data['comment'] ?? null,
                            ]);
                        });

                        Notification::make()
                            ->title('Счёт пополнен')
                            ->success()
                            ->send();
                    }),
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
