<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SpreadResource\Pages;
use App\Filament\Resources\SpreadResource\RelationManagers;
use App\Models\Spread;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\User;
use App\Models\Currency;

class SpreadResource extends Resource
{
    protected static ?string $model = Spread::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->native(false)
                    ->options(User::all()->pluck('email', 'id')),
                Forms\Components\Select::make('currency_id_in')
                    ->label('Currency (base)')
                    ->native(false)
                    ->options(Currency::all()->pluck('name', 'id'))
                    ->required()
                    ->searchable(),
                Forms\Components\TextInput::make('spread_value')
                    ->numeric()
                    ->suffix('%')
                    ->helperText('Percentage markup added to prices (e.g. 0.5 = +0.5%)'),
                Forms\Components\DatePicker::make('start_date')
                    ->native(false)
                    ->nullable()
                    ->helperText('Leave empty to apply spread from any date'),
                Forms\Components\DatePicker::make('end_date')
                    ->native(false)
                    ->nullable()
                    ->helperText('Leave empty to apply spread with no end date'),
                Forms\Components\Toggle::make('is_active')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.email')
                    ->label('User')
                    ->placeholder('All users'),
                Tables\Columns\TextColumn::make('currency_in.name')
                    ->label('Currency'),
                Tables\Columns\TextColumn::make('spread_value')
                    ->suffix('%'),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('From')
                    ->date('d.m.Y')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('To')
                    ->date('d.m.Y')
                    ->placeholder('—'),
                Tables\Columns\ToggleColumn::make('is_active'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->native(false)
                    ->options(User::all()->pluck('email', 'id')),
                Tables\Filters\SelectFilter::make('currency_id_in')
                    ->label('Currency')
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
            'index' => Pages\ListSpreads::route('/'),
            'create' => Pages\CreateSpread::route('/create'),
            'edit' => Pages\EditSpread::route('/{record}/edit'),
        ];
    }
}
