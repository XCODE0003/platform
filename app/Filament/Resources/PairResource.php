<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PairResource\Pages;
use App\Filament\Resources\PairResource\RelationManagers;
use App\Models\Pair;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PairResource extends Resource
{
    protected static ?string $model = Pair::class;

    protected static ?string $navigationIcon = 'heroicon-o-swatch';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('currency_id_in')
                    ->relationship('currencyIn', 'name')
                    ->required()

                    ->native(false),
                Forms\Components\Select::make('currency_id_out')
                    ->relationship('currencyOut', 'name')
                    ->required()

                    ->native(false),
                Forms\Components\Select::make('group_id')
                    ->relationship('group', 'name')
                    ->required()
                    ->native(false),
                Forms\Components\Toggle::make('is_active')
                    ->required()->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('currencyIn.symbol')
                    ->label('Currency In'),
                Tables\Columns\TextColumn::make('currencyOut.symbol')
                    ->label('Currency Out'),
                Tables\Columns\TextColumn::make('group.name')
                    ->label('Group'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Is Active'),
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
            'index' => Pages\ListPairs::route('/'),
            'create' => Pages\CreatePair::route('/create'),
            'edit' => Pages\EditPair::route('/{record}/edit'),
        ];
    }
}
