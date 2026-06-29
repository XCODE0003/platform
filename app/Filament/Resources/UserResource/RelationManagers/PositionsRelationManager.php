<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\Pair;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PositionsRelationManager extends RelationManager
{
    protected static string $relationship = 'positions';
    protected static ?string $title = 'Сделки (позиции)';
    protected static ?string $modelLabel = 'Сделка';
    protected static ?string $pluralModelLabel = 'Сделки';

    /** "BTC/USD" label for a pair id. */
    private static function pairLabel(?Pair $pair): string
    {
        if (! $pair) {
            return '—';
        }

        return ($pair->currencyIn?->symbol ?? '?') . '/' . ($pair->currencyOut?->symbol ?? '?');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('pair_id')
                    ->label('Пара')
                    ->searchable()
                    ->getSearchResultsUsing(fn(string $search): array => Pair::query()
                        ->with(['currencyIn', 'currencyOut'])
                        ->whereHas('currencyIn', fn(Builder $q) => $q
                            ->where('symbol', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%"))
                        ->limit(40)
                        ->get()
                        ->mapWithKeys(fn(Pair $p): array => [$p->id => self::pairLabel($p)])
                        ->all())
                    ->getOptionLabelUsing(fn($value): string => self::pairLabel(Pair::with(['currencyIn', 'currencyOut'])->find($value)))
                    ->required(),
                Forms\Components\Select::make('side')
                    ->label('Сторона')
                    ->options(['buy' => 'Buy (long)', 'sell' => 'Sell (short)'])
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('Статус')
                    ->options(['open' => 'Открыта', 'closed' => 'Закрыта'])
                    ->required()
                    ->live(),
                Forms\Components\TextInput::make('entry_price')
                    ->label('Цена входа')
                    ->numeric()
                    ->step('0.0000000001')
                    ->required(),
                Forms\Components\TextInput::make('quantity')
                    ->label('Количество')
                    ->numeric()
                    ->step('0.0000000001')
                    ->required(),
                Forms\Components\TextInput::make('entry_total')
                    ->label('Сумма входа')
                    ->numeric()
                    ->step('0.0000000001')
                    ->required(),
                Forms\Components\TextInput::make('take_profit')
                    ->label('Take Profit')
                    ->numeric()
                    ->step('0.0000000001'),
                Forms\Components\TextInput::make('stop_loss')
                    ->label('Stop Loss')
                    ->numeric()
                    ->step('0.0000000001'),
                Forms\Components\TextInput::make('close_price')
                    ->label('Цена закрытия')
                    ->numeric()
                    ->step('0.0000000001')
                    ->visible(fn(Forms\Get $get): bool => $get('status') === 'closed'),
                Forms\Components\TextInput::make('close_total')
                    ->label('Сумма закрытия')
                    ->numeric()
                    ->step('0.0000000001')
                    ->visible(fn(Forms\Get $get): bool => $get('status') === 'closed'),
                Forms\Components\TextInput::make('realized_pnl')
                    ->label('Реализованный PnL')
                    ->numeric()
                    ->step('0.0000000001')
                    ->helperText('Прибыль/убыток по закрытой сделке.'),
                Forms\Components\TextInput::make('swap')
                    ->label('Swap')
                    ->numeric()
                    ->step('0.0000000001')
                    ->default('0'),
                Forms\Components\Select::make('close_reason')
                    ->label('Причина закрытия')
                    ->options([
                        'manual'      => 'Вручную',
                        'take_profit' => 'Take Profit',
                        'stop_loss'   => 'Stop Loss',
                    ])
                    ->visible(fn(Forms\Get $get): bool => $get('status') === 'closed'),
                Forms\Components\DateTimePicker::make('closed_at')
                    ->label('Время закрытия')
                    ->seconds(false)
                    ->visible(fn(Forms\Get $get): bool => $get('status') === 'closed'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->modifyQueryUsing(fn(Builder $query): Builder => $query->with(['pair.currencyIn', 'pair.currencyOut']))
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('pair')
                    ->label('Пара')
                    ->getStateUsing(fn($record): string => self::pairLabel($record->pair)),
                Tables\Columns\TextColumn::make('side')
                    ->label('Сторона')
                    ->badge()
                    ->color(fn(string $state): string => $state === 'buy' ? 'success' : 'danger')
                    ->formatStateUsing(fn(string $state): string => $state === 'buy' ? 'Buy' : 'Sell'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn(string $state): string => $state === 'open' ? 'warning' : 'gray')
                    ->formatStateUsing(fn(string $state): string => $state === 'open' ? 'Открыта' : 'Закрыта'),
                Tables\Columns\TextColumn::make('entry_price')
                    ->label('Вход')
                    ->numeric(decimalPlaces: 8)
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Кол-во')
                    ->numeric(decimalPlaces: 8),
                Tables\Columns\TextColumn::make('entry_total')
                    ->label('Сумма входа')
                    ->numeric(decimalPlaces: 8),
                Tables\Columns\TextColumn::make('close_price')
                    ->label('Закрытие')
                    ->numeric(decimalPlaces: 8)
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('realized_pnl')
                    ->label('PnL')
                    ->numeric(decimalPlaces: 8)
                    ->placeholder('—')
                    ->color(fn($state): string => $state === null ? 'gray' : ((float) $state >= 0 ? 'success' : 'danger')),
                Tables\Columns\TextColumn::make('take_profit')
                    ->label('TP')
                    ->numeric(decimalPlaces: 8)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('stop_loss')
                    ->label('SL')
                    ->numeric(decimalPlaces: 8)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('swap')
                    ->label('Swap')
                    ->numeric(decimalPlaces: 8)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('closed_at')
                    ->label('Закрыта')
                    ->dateTime()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Открыта')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options(['open' => 'Открыта', 'closed' => 'Закрыта']),
                Tables\Filters\SelectFilter::make('side')
                    ->label('Сторона')
                    ->options(['buy' => 'Buy', 'sell' => 'Sell']),
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
