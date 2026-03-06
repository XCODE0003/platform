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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required(),
                Forms\Components\TextInput::make('symbol')
                    ->required(),
                Forms\Components\TextInput::make('code')
                    ->required(),
                Forms\Components\TextInput::make('icon')
                    ->required(),
                Forms\Components\TextInput::make('network')
                    ->required(),
                Forms\Components\TextInput::make('exchange_rate')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ])
                    ->default('active')
                    ->required(),
                Forms\Components\Toggle::make('is_deposit')
                    ->default(false),
                Forms\Components\TextInput::make('min_deposit_amount')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Forms\Components\Section::make('Portfolio Transfer Fee')
                    ->description('Commission applied when transferring from Portfolio → Trading Account')
                    ->schema([
                        Forms\Components\TextInput::make('portfolio_fee_percent')
                            ->label('Fee, %')
                            ->numeric()
                            ->default(0)
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100),
                        Forms\Components\TextInput::make('portfolio_fee_fixed')
                            ->label('Fixed fee')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                    ])->columns(2),
                Forms\Components\TextInput::make('address_regex')
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('symbol'),
                Tables\Columns\TextColumn::make('code'),
                Tables\Columns\TextColumn::make('icon'),
                Tables\Columns\TextColumn::make('network'),
                Tables\Columns\TextColumn::make('exchange_rate'),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('is_deposit'),
                Tables\Columns\TextColumn::make('min_deposit_amount'),
                Tables\Columns\TextColumn::make('address_regex'),
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
