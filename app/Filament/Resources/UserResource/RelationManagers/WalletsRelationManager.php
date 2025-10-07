<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\Currency;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WalletsRelationManager extends RelationManager
{
    protected static string $relationship = 'wallets';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('currency_id')
                    ->label('Currency')
                    ->options(Currency::all()->pluck('name', 'id'))
                    ->required()
                    ->searchable(),
                Forms\Components\TextInput::make('balance')
                    ->label('Balance')
                    ->numeric()
                    ->step('0.00000001')
                    ->default('0.00000000')
                    ->required(),
                Forms\Components\TextInput::make('pending_balance')
                    ->label('Pending Balance')
                    ->numeric()
                    ->step('0.00000001')
                    ->default('0.00000000')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('currency.name')
            ->columns([
                Tables\Columns\TextColumn::make('currency.name')
                    ->label('Currency')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('currency.symbol')
                    ->label('Symbol')
                    ->sortable(),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Balance')
                    ->numeric(decimalPlaces: 8)
                    ->sortable(),
                Tables\Columns\TextColumn::make('pending_balance')
                    ->label('Pending Balance')
                    ->numeric(decimalPlaces: 8)
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_balance')
                    ->label('Total Balance')
                    ->getStateUsing(fn($record) => bcadd($record->balance, $record->pending_balance, 8))
                    ->numeric(decimalPlaces: 8),
                Tables\Columns\TextColumn::make('usd_value')
                    ->label('USD Value')
                    ->getStateUsing(function ($record) {
                        $total = bcadd($record->balance, $record->pending_balance, 8);
                        $rate = $record->currency->exchange_rate ?? 1;
                        return bcmul($total, $rate, 2);
                    })
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('currency_id')
                    ->label('Currency')
                    ->options(Currency::all()->pluck('name', 'id'))
                    ->searchable(),
                Tables\Filters\Filter::make('has_balance')
                    ->label('Has Balance')
                    ->query(fn(Builder $query): Builder => $query->where('balance', '>', '0')),
                Tables\Filters\Filter::make('has_pending')
                    ->label('Has Pending')
                    ->query(fn(Builder $query): Builder => $query->where('pending_balance', '>', '0')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Wallet'),
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
