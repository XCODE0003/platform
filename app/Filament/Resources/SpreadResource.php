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
    protected static ?string $navigationLabel = 'Спреды';
    protected static ?string $modelLabel = 'Спред';
    protected static ?string $pluralModelLabel = 'Спреды';
    protected static ?string $navigationGroup = 'Финансы';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Пользователь')
                    ->native(false)
                    ->options(User::all()->pluck('email', 'id')),
                Forms\Components\Select::make('currency_id_in')
                    ->label('Валюта (базовая)')
                    ->native(false)
                    ->options(Currency::all()->pluck('name', 'id'))
                    ->required()
                    ->searchable(),
                Forms\Components\TextInput::make('spread_value')
                    ->label('Размер спреда')
                    ->numeric()
                    ->suffix('%')
                    ->helperText('Процентная наценка к цене (например 0.5 = +0.5%)'),
                Forms\Components\DatePicker::make('start_date')
                    ->label('Дата начала')
                    ->native(false)
                    ->nullable()
                    ->helperText('Оставьте пустым, чтобы применять спред с любой даты'),
                Forms\Components\DatePicker::make('end_date')
                    ->label('Дата окончания')
                    ->native(false)
                    ->nullable()
                    ->helperText('Оставьте пустым, чтобы применять спред без срока окончания'),
                Forms\Components\Toggle::make('is_active')
                    ->label('Активен')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Пользователь')
                    ->placeholder('Все пользователи'),
                Tables\Columns\TextColumn::make('currency_in.name')
                    ->label('Валюта'),
                Tables\Columns\TextColumn::make('spread_value')
                    ->label('Спред')
                    ->suffix('%'),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('С')
                    ->date('d.m.Y')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('По')
                    ->date('d.m.Y')
                    ->placeholder('—'),
                Tables\Columns\ToggleColumn::make('is_active')->label('Активен'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Пользователь')
                    ->native(false)
                    ->options(User::all()->pluck('email', 'id')),
                Tables\Filters\SelectFilter::make('currency_id_in')
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
            'index' => Pages\ListSpreads::route('/'),
            'create' => Pages\CreateSpread::route('/create'),
            'edit' => Pages\EditSpread::route('/{record}/edit'),
        ];
    }
}
