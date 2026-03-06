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
    protected static ?string $navigationLabel = 'Support Tickets';
    protected static ?string $navigationGroup = 'Support';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Ticket Info')
                ->schema([
                    Infolists\Components\TextEntry::make('id')->label('ID'),
                    Infolists\Components\TextEntry::make('user.email')->label('User'),
                    Infolists\Components\TextEntry::make('category')
                        ->formatStateUsing(fn (string $state): string => Ticket::CATEGORIES[$state] ?? $state),
                    Infolists\Components\TextEntry::make('status')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            Ticket::STATUS_OPEN        => 'warning',
                            Ticket::STATUS_IN_PROGRESS => 'info',
                            Ticket::STATUS_CLOSED      => 'gray',
                            default                    => 'gray',
                        }),
                    Infolists\Components\TextEntry::make('title')->columnSpanFull(),
                    Infolists\Components\TextEntry::make('description')->columnSpanFull(),
                    Infolists\Components\TextEntry::make('created_at')->dateTime()->label('Created'),
                ])->columns(2),

            Infolists\Components\Section::make('Messages')
                ->schema([
                    Infolists\Components\RepeatableEntry::make('messages')
                        ->label('')
                        ->schema([
                            Infolists\Components\TextEntry::make('is_admin')
                                ->label('From')
                                ->formatStateUsing(fn (bool $state): string => $state ? 'Support' : 'User')
                                ->badge()
                                ->color(fn (bool $state): string => $state ? 'success' : 'primary'),
                            Infolists\Components\TextEntry::make('content')
                                ->label('Message')
                                ->columnSpan(2),
                            Infolists\Components\TextEntry::make('created_at')
                                ->dateTime()
                                ->label('Time'),
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
                    ->label('User')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category')
                    ->formatStateUsing(fn (string $state): string => Ticket::CATEGORIES[$state] ?? $state),
                Tables\Columns\TextColumn::make('title')
                    ->limit(40)
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Ticket::STATUS_OPEN        => 'warning',
                        Ticket::STATUS_IN_PROGRESS => 'info',
                        Ticket::STATUS_CLOSED      => 'gray',
                        default                    => 'gray',
                    }),
                Tables\Columns\TextColumn::make('messages_count')
                    ->label('Messages')
                    ->counts('messages'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        Ticket::STATUS_OPEN        => 'Open',
                        Ticket::STATUS_IN_PROGRESS => 'In Progress',
                        Ticket::STATUS_CLOSED      => 'Closed',
                    ]),
                Tables\Filters\SelectFilter::make('category')
                    ->options(Ticket::CATEGORIES),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('reply')
                    ->label('Reply')
                    ->icon('heroicon-o-chat-bubble-left')
                    ->color('success')
                    ->form([
                        Forms\Components\Textarea::make('message')
                            ->label('Reply message')
                            ->required()
                            ->rows(4),
                        Forms\Components\Select::make('status')
                            ->label('Update status')
                            ->options([
                                Ticket::STATUS_OPEN        => 'Open',
                                Ticket::STATUS_IN_PROGRESS => 'In Progress',
                                Ticket::STATUS_CLOSED      => 'Closed',
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
                    ->modalHeading('Reply to Ticket')
                    ->modalSubmitActionLabel('Send Reply'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('close')
                        ->label('Close selected')
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
