<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\WithdrawResource\Pages;
use App\Models\Withdraw;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WithdrawResource extends Resource
{
    protected static ?string $model = Withdraw::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Finance';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Withdrawal Details')
                ->schema([
                    Forms\Components\Placeholder::make('user_email')
                        ->label('User')
                        ->content(fn (?Withdraw $record): string => $record?->user?->email ?? '-'),
                    Forms\Components\Placeholder::make('currency_symbol')
                        ->label('Currency')
                        ->content(fn (?Withdraw $record): string => $record?->currency?->symbol ?? '-'),
                    Forms\Components\Placeholder::make('amount_display')
                        ->label('Amount')
                        ->content(fn (?Withdraw $record): string => $record ? number_format((float) $record->amount, 8) : '0.00000000'),
                    Forms\Components\Placeholder::make('fee_display')
                        ->label('Fee')
                        ->content(fn (?Withdraw $record): string => $record ? number_format((float) $record->fee, 8) : '0.00000000'),
                    Forms\Components\Placeholder::make('net_amount_display')
                        ->label('Net amount')
                        ->content(fn (?Withdraw $record): string => $record ? number_format((float) $record->net_amount, 8) : '0.00000000'),
                    Forms\Components\Placeholder::make('address_display')
                        ->label('Address')
                        ->content(fn (?Withdraw $record): string => $record->address ?? '-'),
                ])->columns(2),
            Forms\Components\Section::make('Management')
                ->schema([
                    Forms\Components\Select::make('status')
                        ->options([
                            Withdraw::STATUS_PENDING => 'Pending',
                            Withdraw::STATUS_PROCESSING => 'Processing',
                            Withdraw::STATUS_COMPLETED => 'Completed',
                            Withdraw::STATUS_REJECTED => 'Rejected',
                        ])
                        ->required(),
                    Forms\Components\TextInput::make('tx_hash')
                        ->label('Transaction Hash')
                        ->maxLength(255),
                    Forms\Components\DateTimePicker::make('processed_at')
                        ->native(false),
                    Forms\Components\KeyValue::make('meta')
                        ->label('Meta')
                        ->columnSpanFull()
                        ->nullable(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('User')
                    ->searchable(),
                Tables\Columns\TextColumn::make('currency.symbol')
                    ->label('Currency')
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->formatStateUsing(fn (?string $state, Withdraw $record): string => sprintf('%s %s', number_format((float) $state, 8), $record->currency?->symbol ?? ''))
                    ->sortable(),
                Tables\Columns\TextColumn::make('fee')
                    ->label('Fee')
                    ->formatStateUsing(fn (?string $state, Withdraw $record): string => sprintf('%s %s', number_format((float) $state, 8), $record->currency?->symbol ?? '')),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'primary' => [Withdraw::STATUS_PENDING],
                        'warning' => [Withdraw::STATUS_PROCESSING],
                        'success' => [Withdraw::STATUS_COMPLETED],
                        'danger' => [Withdraw::STATUS_REJECTED],
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('processed_at')
                    ->dateTime()
                    ->label('Processed'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        Withdraw::STATUS_PENDING => 'Pending',
                        Withdraw::STATUS_PROCESSING => 'Processing',
                        Withdraw::STATUS_COMPLETED => 'Completed',
                        Withdraw::STATUS_REJECTED => 'Rejected',
                    ]),
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWithdraws::route('/'),
            'edit' => Pages\EditWithdraw::route('/{record}/edit'),
        ];
    }
}

