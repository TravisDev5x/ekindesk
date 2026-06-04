<?php

namespace App\Listeners;

use App\Events\TicketCreated;
use App\Events\TicketUpdated;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketActivityNotification;
use App\Services\OperatorScopeService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SendTicketNotification
{
    public function __construct(
        protected OperatorScopeService $operatorScope
    ) {}

    public function handle($event): void
    {
        $ticket = $event->ticket;
        $action = $event instanceof TicketCreated ? 'created' : 'updated';

        $recipients = $this->recipients($ticket);

        foreach ($recipients as $user) {
            try {
                $user->notify(new TicketActivityNotification($ticket, $action));
            } catch (\Throwable $e) {
                Log::warning('ticket notification failed', [
                    'user_id' => $user->id,
                    'ticket_id' => $ticket->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function recipients(Ticket $ticket): Collection
    {
        $areaId = $ticket->area_current_id;

        $areaUsers = $areaId
            ? User::where('area_id', $areaId)->get()
            : collect();

        $globalUsers = User::permission('tickets.manage_all')->get()
            ->filter(fn (User $user) => $this->operatorScope->userInTicketOperatorScope($user, $ticket));

        return $areaUsers->merge($globalUsers)->unique('id');
    }
}
