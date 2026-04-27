<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KycResource\Pages;
use App\Filament\Resources\KycResource\RelationManagers;
use App\Models\KycUser;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class KycResource extends Resource
{
    protected static ?string $model = KycUser::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';
    protected static ?string $navigationLabel = 'KYC';
    protected static ?string $modelLabel = 'Заявка KYC';
    protected static ?string $pluralModelLabel = 'Заявки KYC';
    protected static ?string $navigationGroup = 'Пользователи';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('user_id')
                    ->label('ID пользователя')
                    ->required()
                    ->numeric(),
                Forms\Components\Select::make('status')
                    ->label('Статус')
                    ->required()
                    ->native(false)
                    ->options(KycUser::STATUS_OPTIONS),
                Forms\Components\TextInput::make('error_message')
                    ->label('Сообщение об ошибке'),
                Forms\Components\Select::make('sex')
                    ->label('Пол')
                    ->required()
                    ->options(KycUser::SEX_OPTIONS),
                Forms\Components\TextInput::make('first_name')
                    ->label('Имя')
                    ->required(),
                Forms\Components\TextInput::make('last_name')
                    ->label('Фамилия')
                    ->required(),
                Forms\Components\TextInput::make('phone')
                    ->label('Телефон')
                    ->required(),
                Forms\Components\TextInput::make('date_of_birth')
                    ->label('Дата рождения')
                    ->required(),
                Forms\Components\TextInput::make('country')
                    ->label('Страна')
                    ->required(),
                Forms\Components\TextInput::make('city')
                    ->label('Город')
                    ->required(),
                Forms\Components\TextInput::make('address')
                    ->label('Адрес')
                    ->required(),
                Forms\Components\TextInput::make('zip_code')
                    ->label('Индекс')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.email')->label('Пользователь'),
                Tables\Columns\TextColumn::make('status')->label('Статус'),
                Tables\Columns\TextColumn::make('error_message')->label('Сообщение об ошибке'),
                Tables\Columns\TextColumn::make('sex')->label('Пол'),
                Tables\Columns\TextColumn::make('first_name')->label('Имя'),
                Tables\Columns\TextColumn::make('last_name')->label('Фамилия'),
                Tables\Columns\TextColumn::make('phone')->label('Телефон'),
                Tables\Columns\TextColumn::make('date_of_birth')->label('Дата рождения'),
                Tables\Columns\TextColumn::make('country')->label('Страна'),
                Tables\Columns\TextColumn::make('city')->label('Город'),
                Tables\Columns\TextColumn::make('address')->label('Адрес'),
                Tables\Columns\TextColumn::make('zip_code')->label('Индекс'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options(KycUser::STATUS_OPTIONS)
                    ->multiple(),
                Tables\Filters\SelectFilter::make('sex')
                    ->label('Пол')
                    ->options(KycUser::SEX_OPTIONS),
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
            'index' => Pages\ListKycs::route('/'),
            'create' => Pages\CreateKyc::route('/create'),
            'edit' => Pages\EditKyc::route('/{record}/edit'),
        ];
    }
}
