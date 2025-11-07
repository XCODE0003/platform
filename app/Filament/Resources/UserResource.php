<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use App\Models\Currency;
use Filament\Forms;
use Filament\Forms\Form;
use App\Http\Service\User\CalculateTotalBalance;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('email')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Toggle::make('google_2fa_enabled')
                    ->required(),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->required()
                    ->maxLength(255),



            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_balance')
                    ->label('Total Balance (USD)')
                    ->getStateUsing(function ($record) {
                        return (new CalculateTotalBalance())->calculate($record);
                    })
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('wallets_count')
                    ->label('Wallets Count')
                    ->counts('wallets')
                    ->sortable(),
                Tables\Columns\IconColumn::make('google_2fa_enabled')
                    ->label('2FA Enabled')
                    ->boolean(),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->label('Email Verified')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registered')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('has_balance')
                    ->label('Users with Balance')
                    ->query(
                        fn(Builder $query): Builder =>
                        $query->whereHas('wallets', fn($q) => $q->where('balance', '>', '0'))
                    ),
                Tables\Filters\Filter::make('verified_email')
                    ->label('Email Verified')
                    ->query(fn(Builder $query): Builder => $query->whereNotNull('email_verified_at')),
                Tables\Filters\Filter::make('2fa_enabled')
                    ->label('2FA Enabled')
                    ->query(fn(Builder $query): Builder => $query->where('google_2fa_enabled', true)),
                Tables\Filters\SelectFilter::make('currency')
                    ->label('Has Currency')
                    ->options(Currency::all()->pluck('name', 'id'))
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            fn(Builder $query, $value): Builder => $query->whereHas(
                                'wallets',
                                fn($q) => $q->where('currency_id', $value)
                            )
                        );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('view_wallets')
                    ->label('View Wallets')
                    ->icon('heroicon-o-wallet')
                    ->url(fn(User $record): string => UserResource::getUrl('edit', ['record' => $record->id]) . '#wallets'),
                Tables\Actions\Action::make('reset_balances')
                    ->label('Reset All Balances')
                    ->icon('heroicon-o-arrow-path')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        $record->wallets()->update([
                            'balance' => '0.00000000',
                            'pending_balance' => '0.00000000',
                        ]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('reset_all_balances')
                        ->label('Reset All Balances')
                        ->icon('heroicon-o-arrow-path')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $user) {
                                $user->wallets()->update([
                                    'balance' => '0.00000000',
                                    'pending_balance' => '0.00000000',
                                ]);
                            }
                        }),
                    Tables\Actions\BulkAction::make('add_test_balance')
                        ->label('Add Test Balance')
                        ->icon('heroicon-o-plus')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $user) {
                                // Добавляем тестовые балансы для основных валют
                                $currencies = Currency::whereIn('code', ['BTC', 'ETH', 'USDT'])->get();
                                foreach ($currencies as $currency) {
                                    $wallet = $user->wallets()->firstOrCreate([
                                        'currency_id' => $currency->id,
                                    ], [
                                        'balance' => '1000.00000000',
                                        'pending_balance' => '0.00000000',
                                    ]);

                                    if ($wallet->wasRecentlyCreated === false) {
                                        $wallet->increment('balance', '1000.00000000');
                                    }
                                }
                            }
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\WalletsRelationManager::class,
            RelationManagers\DepositWalletsRelationManager::class,
            RelationManagers\BillsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
