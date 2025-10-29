<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BarUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $pairId,
        public string $resolution,
        public int $time,
        public float $open,
        public float $high,
        public float $low,
        public float $close,
        public float $volume,
        public bool $closed = false,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('pair.' . $this->pairId . '.' . $this->resolution);
    }

    public function broadcastAs(): string
    {
        return 'bar';
    }

    public function broadcastWith(): array
    {
        return [
            'time' => $this->time,
            'open' => $this->open,
            'high' => $this->high,
            'low' => $this->low,
            'close' => $this->close,
            'volume' => $this->volume,
            'closed' => $this->closed,
        ];
    }
}


