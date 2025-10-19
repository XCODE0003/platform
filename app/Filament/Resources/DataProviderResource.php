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
    protected static ?string $navigationGroup = 'Market Data';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->label('Code')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(100),
                Forms\Components\TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TagsInput::make('asset_classes')
                    ->label('Asset Classes')
                    ->placeholder('crypto, stock, forex, metal, fiat')
                    ->suggestions(['crypto', 'stock', 'forex', 'metal', 'fiat'])
                    ->separator(','),
                Forms\Components\TextInput::make('base_url')
                    ->label('Base URL')
                    ->url()
                    ->maxLength(255),
                Forms\Components\Toggle::make('enabled')
                    ->label('Enabled')
                    ->default(true),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('Code')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name')->label('Name')->searchable(),
                Tables\Columns\TagsColumn::make('asset_classes')->label('Asset Classes'),
                Tables\Columns\TextColumn::make('base_url')->label('Base URL')->toggleable(),
                Tables\Columns\IconColumn::make('enabled')->boolean()->label('Enabled'),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->since()->label('Updated'),
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