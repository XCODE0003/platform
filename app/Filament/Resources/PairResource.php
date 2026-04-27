<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PairResource\Pages;
use App\Filament\Resources\PairResource\RelationManagers;
use App\Models\Pair;
use App\Models\DataProvider;
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
    protected static ?string $navigationGroup = 'Рыночные данные';
    protected static ?string $navigationLabel = 'Пары';
    protected static ?string $modelLabel = 'Пара';
    protected static ?string $pluralModelLabel = 'Пары';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('currency_id_in')
                    ->label('Базовая валюта')
                    ->relationship('currencyIn', 'name')
                    ->required()
                    ->searchable()
                    ->native(false),
                Forms\Components\Select::make('currency_id_out')
                    ->label('Котируемая валюта')
                    ->relationship('currencyOut', 'name')
                    ->searchable()
                    ->required()
                    ->native(false),
                Forms\Components\Select::make('group_id')
                    ->label('Группа')
                    ->relationship('group', 'name')
                    ->required()
                    ->native(false),
                Forms\Components\Select::make('asset_class')
                    ->label('Класс актива')
                    ->options([
                        'crypto' => 'Крипто',
                        'metal' => 'Металл',
                        'stock' => 'Акции',
                        'forex' => 'Форекс',
                        'fiat' => 'Фиат',
                    ])
                    ->required(),
                Forms\Components\Select::make('default_source')
                    ->label('Источник по умолчанию')
                    ->options(fn () => DataProvider::query()->where('enabled', true)->pluck('name', 'code'))
                    ->searchable()
                    ->native(false)
                    ->helperText('Поставщик данных, используемый по умолчанию'),
                Forms\Components\Toggle::make('is_active')
                    ->label('Активна')
                    ->required()->default(true),

                Forms\Components\Section::make('Источники')
                    ->schema([
                        Forms\Components\Repeater::make('sources')
                            ->relationship('sources')
                            ->defaultItems(0)
                            ->schema([
                                Forms\Components\Select::make('provider')
                                    ->label('Поставщик')
                                    ->options(fn () => DataProvider::query()->where('enabled', true)->pluck('name', 'code'))
                                    ->required()
                                    ->native(false),
                                Forms\Components\TextInput::make('provider_symbol')
                                    ->label('Символ у поставщика')
                                    ->required()
                                    ->placeholder('например: BTCUSDT, AAPL, XAUUSD'),
                                Forms\Components\TextInput::make('priority')
                                    ->label('Приоритет')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1)
                                    ->required(),
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'pending' => 'ожидание',
                                        'valid' => 'валиден',
                                        'invalid' => 'невалиден',
                                    ])
                                    ->disabled()
                                    ->dehydrated()
                                    ->label('Статус'),
                            ])
                            ->columns(4)
                            ->collapsible(),
                    ])->collapsed()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('currencyIn.symbol')
                    ->label('Базовая'),
                Tables\Columns\TextColumn::make('currencyOut.symbol')
                    ->label('Котируемая'),
                Tables\Columns\TextColumn::make('group.name')
                    ->label('Группа'),
                Tables\Columns\BadgeColumn::make('asset_class')
                    ->label('Класс')
                    ->colors([
                        'primary',
                    ]),
                Tables\Columns\TextColumn::make('default_source')
                    ->label('Источник')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('sources_count')
                    ->counts('sources')
                    ->label('Источники'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Активна'),
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
