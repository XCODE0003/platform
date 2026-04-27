<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PairSourceResource\Pages;
use App\Models\PairSource;
use App\Models\DataProvider;
use App\Models\Pair;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PairSourceResource extends Resource
{
    protected static ?string $model = PairSource::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';
    protected static ?string $navigationGroup = 'Рыночные данные';
    protected static ?string $navigationLabel = 'Источники пар';
    protected static ?string $modelLabel = 'Источник пары';
    protected static ?string $pluralModelLabel = 'Источники пар';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('pair_id')
                ->label('Пара')
                ->options(fn () => \App\Models\Pair::with(['currencyIn', 'currencyOut'])
                    ->get()
                    ->mapWithKeys(fn ($p) => [
                        $p->id => sprintf(
                            '%s - %s',
                            $p->currencyIn->name ?? ('#'.$p->id),
                            $p->currencyOut->name ?? ''
                        ),
                    ]))
                ->searchable()
                ->required()
                ->native(false),

                Forms\Components\Select::make('provider')
                    ->label('Поставщик')
                    ->options(DataProvider::query()->pluck('name', 'code'))
                    ->required()
                    ->native(false),

                Forms\Components\TextInput::make('provider_symbol')
                    ->label('Символ у поставщика')
                    ->placeholder('например: BTCUSDT, EURUSD, AAPL, XAUUSD')
                    ->required()
                    ->maxLength(100),

                Forms\Components\TextInput::make('priority')
                    ->label('Приоритет')
                    ->numeric()
                    ->minValue(1)
                    ->default(1)
                    ->required(),

                Forms\Components\Select::make('status')
                    ->label('Статус')
                    ->options([
                        'pending' => 'ожидание',
                        'valid'   => 'валиден',
                        'invalid' => 'невалиден',
                    ])
                    ->required(),

                Forms\Components\DateTimePicker::make('validated_at')
                    ->label('Дата валидации')
                    ->seconds(false),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('pair_id')->label('Пара')->sortable(),
                Tables\Columns\TextColumn::make('provider')->label('Поставщик')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('provider_symbol')->label('Символ')->searchable(),
                Tables\Columns\TextColumn::make('priority')->label('Приоритет')->sortable(),
                Tables\Columns\BadgeColumn::make('status')->label('Статус')->colors([
                    'warning' => 'pending',
                    'success' => 'valid',
                    'danger'  => 'invalid',
                ]),
                Tables\Columns\TextColumn::make('validated_at')->dateTime()->label('Валидирован'),
                Tables\Columns\TextColumn::make('updated_at')->since()->label('Обновлён'),
            ])
            ->filters([])
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
            'index'  => Pages\ListPairSources::route('/'),
            'create' => Pages\CreatePairSource::route('/create'),
            'edit'   => Pages\EditPairSource::route('/{record}/edit'),
        ];
    }
}