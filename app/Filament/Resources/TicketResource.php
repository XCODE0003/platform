<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\TicketResource\Pages;
use App\Models\Ticket;
use App\Models\TicketMessage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static ?string $navigationIcon  = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationLabel = 'Тикеты поддержки';
    protected static ?string $modelLabel = 'Тикет';
    protected static ?string $pluralModelLabel = 'Тикеты';
    protected static ?string $navigationGroup = 'Поддержка';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Информация о тикете')
                ->schema([
                    Infolists\Components\TextEntry::make('id')->label('ID'),
                    Infolists\Components\TextEntry::make('user.email')->label('Пользователь'),
                    Infolists\Components\TextEntry::make('category')
                        ->label('Категория')
                        ->formatStateUsing(fn (string $state): string => Ticket::CATEGORIES[$state] ?? $state),
                    Infolists\Components\TextEntry::make('status')
                        ->label('Статус')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            Ticket::STATUS_OPEN        => 'warning',
                            Ticket::STATUS_IN_PROGRESS => 'info',
                            Ticket::STATUS_CLOSED      => 'gray',
                            default                    => 'gray',
                        }),
                    Infolists\Components\TextEntry::make('title')->label('Тема')->columnSpanFull(),
                    Infolists\Components\TextEntry::make('description')->label('Описание')->columnSpanFull(),
                    Infolists\Components\TextEntry::make('created_at')->dateTime()->label('Создан'),
                ])->columns(2),

            Infolists\Components\Section::make('Сообщения')
                ->schema([
                    Infolists\Components\RepeatableEntry::make('messages')
                        ->label('')
                        ->schema([
                            Infolists\Components\TextEntry::make('is_admin')
                                ->label('От кого')
                                ->formatStateUsing(fn (bool $state): string => $state ? 'Поддержка' : 'Пользователь')
                                ->badge()
                                ->color(fn (bool $state): string => $state ? 'success' : 'primary'),
                            Infolists\Components\TextEntry::make('content')
                                ->label('Сообщение')
                                ->columnSpan(2),
                            Infolists\Components\TextEntry::make('created_at')
                                ->dateTime()
                                ->label('Время'),
                        ])
                        ->columns(4),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Пользователь')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category')
                    ->label('Категория')
                    ->formatStateUsing(fn (string $state): string => Ticket::CATEGORIES[$state] ?? $state),
                Tables\Columns\TextColumn::make('title')
                    ->label('Тема')
                    ->limit(40)
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Ticket::STATUS_OPEN        => 'warning',
                        Ticket::STATUS_IN_PROGRESS => 'info',
                        Ticket::STATUS_CLOSED      => 'gray',
                        default                    => 'gray',
                    }),
                Tables\Columns\TextColumn::make('messages_count')
                    ->label('Сообщений')
                    ->counts('messages'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        Ticket::STATUS_OPEN        => 'Открыт',
                        Ticket::STATUS_IN_PROGRESS => 'В работе',
                        Ticket::STATUS_CLOSED      => 'Закрыт',
                    ]),
                Tables\Filters\SelectFilter::make('category')
                    ->label('Категория')
                    ->options(Ticket::CATEGORIES),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('reply')
                    ->label('Ответить')
                    ->icon('heroicon-o-chat-bubble-left')
                    ->color('success')
                    ->form([
                        Forms\Components\Textarea::make('message')
                            ->label('Текст ответа')
                            ->required()
                            ->rows(4),
                        Forms\Components\Select::make('status')
                            ->label('Изменить статус')
                            ->options([
                                Ticket::STATUS_OPEN        => 'Открыт',
                                Ticket::STATUS_IN_PROGRESS => 'В работе',
                                Ticket::STATUS_CLOSED      => 'Закрыт',
                            ])
                            ->default(fn (Ticket $record): string => $record->status),
                    ])
                    ->action(function (array $data, Ticket $record): void {
                        TicketMessage::create([
                            'ticket_id' => $record->id,
                            'user_id'   => null,
                            'is_admin'  => true,
                            'content'   => $data['message'],
                        ]);

                        $record->status = $data['status'];
                        if ($data['status'] === Ticket::STATUS_CLOSED) {
                            $record->closed_at = now();
                        }
                        $record->save();
                    })
                    ->modalHeading('Ответ на тикет')
                    ->modalSubmitActionLabel('Отправить ответ'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('close')
                        ->label('Закрыть выбранные')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn ($records) => $records->each(function (Ticket $ticket) {
                            $ticket->update(['status' => Ticket::STATUS_CLOSED, 'closed_at' => now()]);
                        }))
                        ->requiresConfirmation(),
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
            'index' => Pages\ListTickets::route('/'),
            'view'  => Pages\ViewTicket::route('/{record}'),
        ];
    }
}
