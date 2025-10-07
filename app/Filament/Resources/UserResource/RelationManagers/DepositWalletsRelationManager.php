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

class DepositWalletsRelationManager extends RelationManager
{
    protected static string $relationship = 'DepositWallets';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('currency_id')
                    ->label('Currency')
                    ->options(Currency::all()->pluck('name', 'id'))
                    ->required()
                    ->searchable(),
                Forms\Components\TextInput::make('address')
                    ->label('Deposit Address')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('private_key')
                    ->label('Private Key')
                    ->password()
                    ->maxLength(255),
                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
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
                Tables\Columns\TextColumn::make('address')
                    ->label('Deposit Address')
                    ->copyable()
                    ->copyMessage('Address copied!')
                    ->limit(20)
                    ->tooltip(fn($record) => $record->address),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('currency_id')
                    ->label('Currency')
                    ->options(Currency::all()->pluck('name', 'id'))
                    ->searchable(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Deposit Address'),
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
