<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DataProviderResource\Pages;
use App\Models\DataProvider;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DataProviderResource extends Resource
{
    protected static ?string $model = DataProvider::class;

    protected static ?string $navigationIcon = 'heroicon-o-cloud';
    protected static ?string $navigationGroup = 'Рыночные данные';
    protected static ?string $navigationLabel = 'Поставщики данных';
    protected static ?string $modelLabel = 'Поставщик данных';
    protected static ?string $pluralModelLabel = 'Поставщики данных';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->label('Код')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(100),
                Forms\Components\TextInput::make('name')
                    ->label('Название')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TagsInput::make('asset_classes')
                    ->label('Классы активов')
                    ->placeholder('crypto, stock, forex, metal, fiat')
                    ->suggestions(['crypto', 'stock', 'forex', 'metal', 'fiat'])
                    ->separator(','),
                Forms\Components\TextInput::make('base_url')
                    ->label('Базовый URL')
                    ->url()
                    ->maxLength(255),
                Forms\Components\Toggle::make('enabled')
                    ->label('Включён')
                    ->default(true),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('Код')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name')->label('Название')->searchable(),
                Tables\Columns\TagsColumn::make('asset_classes')->label('Классы активов'),
                Tables\Columns\TextColumn::make('base_url')->label('Базовый URL')->toggleable(),
                Tables\Columns\IconColumn::make('enabled')->boolean()->label('Включён'),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->since()->label('Обновлён'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDataProviders::route('/'),
            'create' => Pages\CreateDataProvider::route('/create'),
            'edit'   => Pages\EditDataProvider::route('/{record}/edit'),
        ];
    }
}