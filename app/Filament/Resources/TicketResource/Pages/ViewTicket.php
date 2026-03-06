<?php

declare(strict_types=1);

namespace App\Filament\Resources\TicketResource\Pages;

use App\Filament\Resources\TicketResource;
use App\Models\Ticket;
use App\Models\TicketMessage;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ViewRecord;

class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('reply')
                ->label('Send Reply')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->form([
                    Forms\Components\Textarea::make('message')
                        ->label('Reply message')
                        ->required()
                        ->rows(5),
                    Forms\Components\Select::make('status')
                        ->label('Update status')
                        ->options([
                            Ticket::STATUS_OPEN        => 'Open',
                            Ticket::STATUS_IN_PROGRESS => 'In Progress',
                            Ticket::STATUS_CLOSED      => 'Closed',
                        ])
                        ->default(fn () => $this->record->status),
                ])
                ->action(function (array $data): void {
                    TicketMessage::create([
                        'ticket_id' => $this->record->id,
                        'user_id'   => null,
                        'is_admin'  => true,
                        'content'   => $data['message'],
                    ]);

                    $this->record->status = $data['status'];
                    if ($data['status'] === Ticket::STATUS_CLOSED) {
                        $this->record->closed_at = now();
                    }
                    $this->record->save();

                    $this->refreshFormData(['status']);
                })
                ->modalHeading('Reply to Ticket')
                ->modalSubmitActionLabel('Send'),

            Actions\Action::make('change_status')
                ->label('Change Status')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->form([
                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options([
                            Ticket::STATUS_OPEN        => 'Open',
                            Ticket::STATUS_IN_PROGRESS => 'In Progress',
                            Ticket::STATUS_CLOSED      => 'Closed',
                        ])
                        ->default(fn () => $this->record->status)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->record->status = $data['status'];
                    if ($data['status'] === Ticket::STATUS_CLOSED) {
                        $this->record->closed_at = now();
                    }
                    $this->record->save();
                })
                ->modalHeading('Change Ticket Status')
                ->modalSubmitActionLabel('Update'),
        ];
    }
}
