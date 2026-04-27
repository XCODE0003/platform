<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CurrencyResource\Pages;
use App\Filament\Resources\CurrencyResource\RelationManagers;
use App\Models\Currency;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CurrencyResource extends Resource
{
    protected static ?string $model = Currency::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'Валюты';
    protected static ?string $modelLabel = 'Валюта';
    protected static ?string $pluralModelLabel = 'Валюты';
    protected static ?string $navigationGroup = 'Финансы';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Название')
                    ->required(),
                Forms\Components\TextInput::make('symbol')
                    ->label('Символ')
                    ->required(),
                Forms\Components\TextInput::make('code')
                    ->label('Код')
                    ->required(),
                Forms\Components\TextInput::make('icon')
                    ->label('Иконка')
                    ->required(),
                Forms\Components\TextInput::make('network')
                    ->label('Сеть')
                    ->required(),
                Forms\Components\TextInput::make('exchange_rate')
                    ->label('Курс обмена')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('Статус')
                    ->options([
                        'active' => 'Активна',
                        'inactive' => 'Неактивна',
                    ])
                    ->default('active')
                    ->required(),
                Forms\Components\Toggle::make('is_deposit')
                    ->label('Доступна для депозита')
                    ->default(false),
                Forms\Components\TextInput::make('min_deposit_amount')
                    ->label('Минимальная сумма депозита')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Forms\Components\Section::make('Комиссия перевода из портфеля')
                    ->description('Комиссия при переводе Портфель → Торговый счёт')
                    ->schema([
                        Forms\Components\TextInput::make('portfolio_fee_percent')
                            ->label('Комиссия, %')
                            ->numeric()
                            ->default(0)
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100),
                        Forms\Components\TextInput::make('portfolio_fee_fixed')
                            ->label('Фиксированная комиссия')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                    ])->columns(2),
                Forms\Components\TextInput::make('address_regex')
                    ->label('Регулярное выражение адреса')
                    ->nullable(),
                Forms\Components\Section::make('Адрес для депозита')
                    ->description('Адрес, который видит пользователь в модальном окне депозита для этой монеты')
                    ->icon('heroicon-o-wallet')
                    ->schema([
                        Forms\Components\TextInput::make('deposit_address')
                            ->label('Адрес кошелька')
                            ->maxLength(255)
                            ->nullable(),
                        Forms\Components\TextInput::make('deposit_memo')
                            ->label('Мемо / Тег (опционально)')
                            ->maxLength(255)
                            ->nullable(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Название'),
                Tables\Columns\TextColumn::make('symbol')->label('Символ'),
                Tables\Columns\TextColumn::make('code')->label('Код'),
                Tables\Columns\TextColumn::make('icon')->label('Иконка'),
                Tables\Columns\TextColumn::make('network')->label('Сеть'),
                Tables\Columns\TextColumn::make('exchange_rate')->label('Курс'),
                Tables\Columns\TextColumn::make('status')->label('Статус'),
                Tables\Columns\TextColumn::make('is_deposit')->label('Депозит'),
                Tables\Columns\TextColumn::make('min_deposit_amount')->label('Мин. сумма депозита'),
                Tables\Columns\TextColumn::make('address_regex')->label('Регулярка адреса'),
                Tables\Columns\TextColumn::make('deposit_address')
                    ->label('Адрес для депозита')
                    ->limit(20)
                    ->copyable()
                    ->copyMessage('Адрес скопирован!')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('deposit_memo')
                    ->label('Мемо')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCurrencies::route('/'),
            'create' => Pages\CreateCurrency::route('/create'),
            'edit' => Pages\EditCurrency::route('/{record}/edit'),
        ];
    }
}
