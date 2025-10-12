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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                Forms\Components\Select::make('status')
                    ->required()
                    ->native(false)
                    ->options(KycUser::STATUS_OPTIONS),
                Forms\Components\TextInput::make('error_message'),
                Forms\Components\Select::make('sex')
                    ->required()
                    ->options(KycUser::SEX_OPTIONS),
                Forms\Components\TextInput::make('first_name')
                    ->required(),
                Forms\Components\TextInput::make('last_name')
                    ->required(),
                Forms\Components\TextInput::make('phone')
                    ->required(),
                Forms\Components\TextInput::make('date_of_birth')
                    ->required(),
                Forms\Components\TextInput::make('country')
                    ->required(),
                Forms\Components\TextInput::make('city')
                    ->required(),
                Forms\Components\TextInput::make('address')
                    ->required(),
                Forms\Components\TextInput::make('zip_code')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.email'),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('error_message'),
                Tables\Columns\TextColumn::make('sex'),
                Tables\Columns\TextColumn::make('first_name'),
                Tables\Columns\TextColumn::make('last_name'),
                Tables\Columns\TextColumn::make('phone'),
                Tables\Columns\TextColumn::make('date_of_birth'),
                Tables\Columns\TextColumn::make('country'),
                Tables\Columns\TextColumn::make('city'),
                Tables\Columns\TextColumn::make('address'),
                Tables\Columns\TextColumn::make('zip_code'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(KycUser::STATUS_OPTIONS)
                    ->multiple(),
                Tables\Filters\SelectFilter::make('sex')
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
