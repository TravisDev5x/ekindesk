<?php

namespace App\Listeners;

use App\Events\TicketCreated;
use App\Events\TicketUpdated;
use App\Mail\TicketCreatedConfirmationMail;
use App\Mail\TicketReplyNotificationMail;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketActivityNotification;
use App\Services\OperatorScopeService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendTicketNotification
{
    public function __construct(
        protected OperatorScopeService $operatorScope
    ) {}

    public function handle($event): void
    {
        $ticket = $event->ticket;
        $isCreated = $event instanceof TicketCreated;
        $action = $isCreated ? 'created' : 'updated';

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

        if ($isCreated) {
            $this->sendCreatedConfirmation($ticket);
        } else {
            $this->sendReplyNotification($ticket, $recipients);
        }
    }

    /**
     * Confirmación al solicitante — folio + link a su ticket.
     */
    private function sendCreatedConfirmation(Ticket $ticket): void
    {
        $ticket->loadMissing('requester');
        $requesterEmail = $ticket->requester?->email;

        if (! $requesterEmail) {
            return;
        }

        try {
            Mail::to($requesterEmail)->queue(new TicketCreatedConfirmationMail($ticket));
        } catch (\Throwable $e) {
            Log::warning('ticket created confirmation mail failed', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Al destinatario correcto según quién respondió: si fue el solicitante,
     * a los agentes (mismos $recipients del in-app); si fue un agente, al
     * solicitante. Determina el actor por el TicketHistory más reciente.
     */
    private function sendReplyNotification(Ticket $ticket, Collection $agentRecipients): void
    {
        $ticket->loadMissing('requester');

        $lastHistory = $ticket->histories()->latest('created_at')->first();
        $requesterReplied = $lastHistory
            && $ticket->requester_id !== null
            && (int) $lastHistory->actor_id === (int) $ticket->requester_id;

        try {
            if ($requesterReplied) {
                foreach ($agentRecipients as $user) {
                    if ($user->email) {
                        Mail::to($user->email)->queue(new TicketReplyNotificationMail($ticket));
                    }
                }
            } elseif ($ticket->requester?->email) {
                Mail::to($ticket->requester->email)->queue(new TicketReplyNotificationMail($ticket));
            }
        } catch (\Throwable $e) {
            Log::warning('ticket reply notification mail failed', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
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
