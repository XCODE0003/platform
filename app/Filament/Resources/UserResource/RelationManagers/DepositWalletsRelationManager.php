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
    protected static ?string $title = 'Адреса для депозита';
    protected static ?string $modelLabel = 'Адрес депозита';
    protected static ?string $pluralModelLabel = 'Адреса депозитов';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('currency_id')
                    ->label('Валюта')
                    ->options(Currency::all()->pluck('name', 'id'))
                    ->required()
                    ->searchable(),
                Forms\Components\TextInput::make('address')
                    ->label('Адрес для депозита')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('private_key')
                    ->label('Приватный ключ')
                    ->password()
                    ->maxLength(255),
                Forms\Components\Toggle::make('is_active')
                    ->label('Активен')
                    ->default(true),
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
                Tables\Columns\TextColumn::make('address')
                    ->label('Адрес для депозита')
                    ->copyable()
                    ->copyMessage('Адрес скопирован!')
                    ->limit(20)
                    ->tooltip(fn($record) => $record->address),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('currency_id')
                    ->label('Валюта')
                    ->options(Currency::all()->pluck('name', 'id'))
                    ->searchable(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активен'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Добавить адрес'),
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
