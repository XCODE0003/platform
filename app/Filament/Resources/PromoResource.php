<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PromoResource\Pages;
use App\Filament\Resources\PromoResource\RelationManagers;
use App\Models\Promocode;
use Filament\Forms;
use Filament\Forms\Form;
use App\Models\Currency;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PromoResource extends Resource
{
    protected static ?string $model = Promocode::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationLabel = 'Промокоды';
    protected static ?string $modelLabel = 'Промокод';
    protected static ?string $pluralModelLabel = 'Промокоды';
    protected static ?string $navigationGroup = 'Финансы';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->label('Код')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('amount')
                    ->label('Сумма')
                    ->required()
                    ->numeric(),
                Forms\Components\Select::make('currency_id')
                    ->label('Валюта')
                    ->required()
                    ->native(false)
                    ->options(Currency::all()->pluck('name', 'id')),
                Forms\Components\DatePicker::make('expiration_date')
                    ->label('Действует до')
                    ->native(false)
                    ->required(),
                Forms\Components\Toggle::make('is_active')
                    ->label('Активен')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('Код'),
                Tables\Columns\TextColumn::make('amount')->label('Сумма'),
                Tables\Columns\TextColumn::make('currency.name')->label('Валюта'),
                Tables\Columns\TextColumn::make('expiration_date')->label('Действует до'),
                Tables\Columns\ToggleColumn::make('is_active')->label('Активен'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('currency_id')
                    ->label('Валюта')
                    ->native(false)
                    ->options(Currency::all()->pluck('name', 'id')),

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
            'index' => Pages\ListPromos::route('/'),
            'create' => Pages\CreatePromo::route('/create'),
            'edit' => Pages\EditPromo::route('/{record}/edit'),
        ];
    }
}
