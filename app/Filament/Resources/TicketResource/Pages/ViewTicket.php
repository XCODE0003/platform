<?php

declare(strict_types=1);

namespace App\Filament\Resources\TicketResource\Pages;

use App\Filament\Resources\TicketResource;
use App\Models\Ticket;
use App\Models\TicketMessage;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketResource::class;

    protected static string $view = 'filament.resources.ticket-resource.pages.view-ticket';

    public ?string $replyMessage = null;

    public ?string $replyStatus = null;

    public function mount(int | string $record): void
    {
        parent::mount($record);
        $this->replyStatus = $this->record->status;
    }

    public function getTitle(): string | Htmlable
    {
        return 'Ticket #' . $this->record->id . ' — ' . $this->record->title;
    }

    public function getHeading(): string | Htmlable
    {
        return $this->getTitle();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('change_status')
                ->label('Change Status')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->form([
                    \Filament\Forms\Components\Select::make('status')
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
                    } else {
                        $this->record->closed_at = null;
                    }
                    $this->record->save();
                    $this->replyStatus = $this->record->status;
                })
                ->modalHeading('Change Ticket Status')
                ->modalSubmitActionLabel('Update'),
        ];
    }

    public function sendReply(): void
    {
        $this->validate([
            'replyMessage' => ['required', 'string', 'max:10000'],
            'replyStatus'  => ['required', 'in:open,in_progress,closed'],
        ]);

        TicketMessage::create([
            'ticket_id' => $this->record->id,
            'user_id'   => null,
            'is_admin'  => true,
            'content'   => $this->replyMessage,
        ]);

        $this->record->status = $this->replyStatus;
        if ($this->replyStatus === Ticket::STATUS_CLOSED) {
            $this->record->closed_at = now();
        } else {
            $this->record->closed_at = null;
        }
        $this->record->save();

        $this->replyMessage = null;
        $this->replyStatus = $this->record->status;

        $this->dispatch('reply-sent');
    }

    public function reopenTicket(): void
    {
        $this->record->update([
            'status'    => Ticket::STATUS_OPEN,
            'closed_at' => null,
        ]);
        $this->replyStatus = Ticket::STATUS_OPEN;
    }

    protected function hasInfolist(): bool
    {
        return false;
    }
}
