<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function current(Request $request): JsonResponse
    {
        $user   = $request->user();
        $ticket = Ticket::where('user_id', $user->id)
            ->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_IN_PROGRESS])
            ->with(['messages' => fn ($q) => $q->orderBy('created_at')])
            ->latest()
            ->first();

        return response()->json([
            'ticket'   => $ticket,
            'messages' => $ticket?->messages ?? [],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'category' => ['required', 'string', 'in:technical,account,trading,other'],
            'message'  => ['required', 'string', 'max:5000'],
        ]);

        $user = $request->user();

        $existing = Ticket::where('user_id', $user->id)
            ->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_IN_PROGRESS])
            ->with(['messages' => fn ($q) => $q->orderBy('created_at')])
            ->latest()
            ->first();

        if ($existing) {
            return response()->json([
                'ticket'   => $existing,
                'messages' => $existing->messages,
            ]);
        }

        $ticket = Ticket::create([
            'user_id'     => $user->id,
            'title'       => Ticket::CATEGORIES[$request->input('category')] ?? 'Support Request',
            'description' => $request->input('message'),
            'category'    => $request->input('category'),
            'status'      => Ticket::STATUS_OPEN,
        ]);

        $msg = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id'   => $user->id,
            'is_admin'  => false,
            'content'   => $request->input('message'),
        ]);

        return response()->json([
            'ticket'   => $ticket,
            'messages' => [$msg],
        ]);
    }

    public function addMessage(Request $request): JsonResponse
    {
        $request->validate([
            'ticket_id' => ['required', 'integer'],
            'message'   => ['required', 'string', 'max:5000'],
        ]);

        $user   = $request->user();
        $ticket = Ticket::where('id', $request->input('ticket_id'))
            ->where('user_id', $user->id)
            ->firstOrFail();

        if (!$ticket->isOpen()) {
            return response()->json(['error' => 'Ticket is closed.'], 422);
        }

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id'   => $user->id,
            'is_admin'  => false,
            'content'   => $request->input('message'),
        ]);

        $messages = $ticket->messages()->orderBy('created_at')->get();

        return response()->json(['messages' => $messages]);
    }
}
