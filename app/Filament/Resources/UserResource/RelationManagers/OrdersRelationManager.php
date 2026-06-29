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

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';
    protected static ?string $title = 'Ордера';
    protected static ?string $modelLabel = 'Ордер';
    protected static ?string $pluralModelLabel = 'Ордера';

    private const STATUSES = [
        'open'      => 'Открыт',
        'queued'    => 'В очереди',
        'filled'    => 'Исполнен',
        'partial'   => 'Частично',
        'cancelled' => 'Отменён',
        'rejected'  => 'Отклонён',
    ];

    /** "BTC/USD" label for a pair. */
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
                    ->options(['buy' => 'Buy', 'sell' => 'Sell'])
                    ->required(),
                Forms\Components\Select::make('type')
                    ->label('Тип')
                    ->options(['market' => 'Market', 'limit' => 'Limit', 'stop' => 'Stop'])
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('Статус')
                    ->options(self::STATUSES)
                    ->required(),
                Forms\Components\TextInput::make('price')
                    ->label('Цена')
                    ->numeric()
                    ->step('0.0000000001'),
                Forms\Components\TextInput::make('stop_price')
                    ->label('Стоп-цена')
                    ->numeric()
                    ->step('0.0000000001'),
                Forms\Components\TextInput::make('amount')
                    ->label('Количество (base)')
                    ->numeric()
                    ->step('0.0000000001')
                    ->required(),
                Forms\Components\TextInput::make('total')
                    ->label('Сумма (quote)')
                    ->numeric()
                    ->step('0.0000000001'),
                Forms\Components\Select::make('tif')
                    ->label('TIF')
                    ->options(['GTC' => 'GTC', 'IOC' => 'IOC', 'FOK' => 'FOK']),
                Forms\Components\Toggle::make('post_only')
                    ->label('Post only'),
                Forms\Components\Select::make('stops_mode')
                    ->label('Режим стопов')
                    ->options(['none' => 'Нет', 'pips' => 'Пипсы', 'price' => 'Цена'])
                    ->default('none'),
                Forms\Components\TextInput::make('take_profit')
                    ->label('Take Profit')
                    ->numeric()
                    ->step('0.0000000001'),
                Forms\Components\TextInput::make('stop_loss')
                    ->label('Stop Loss')
                    ->numeric()
                    ->step('0.0000000001'),
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
                Tables\Columns\TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'filled'             => 'success',
                        'open', 'queued'     => 'warning',
                        'partial'            => 'info',
                        'cancelled', 'rejected' => 'danger',
                        default              => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => self::STATUSES[$state] ?? $state),
                Tables\Columns\TextColumn::make('price')
                    ->label('Цена')
                    ->numeric(decimalPlaces: 8)
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('stop_price')
                    ->label('Стоп')
                    ->numeric(decimalPlaces: 8)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Кол-во')
                    ->numeric(decimalPlaces: 8),
                Tables\Columns\TextColumn::make('total')
                    ->label('Сумма')
                    ->numeric(decimalPlaces: 8)
                    ->placeholder('—'),
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
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options(self::STATUSES),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Тип')
                    ->options(['market' => 'Market', 'limit' => 'Limit', 'stop' => 'Stop']),
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
