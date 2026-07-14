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

    /**
     * Fase 5.3: reemplaza la resolución legacy por area_current_id/area_id
     * (un agente/supervisor vinculado al ticket SOLO por site_user, sin
     * area_id coincidente, nunca recibía notificación de un ticket que sí
     * podía ver y atender según TicketPolicy -- misma clase de bug que los
     * Obstáculos A/B de la Sub-fase 5.1, en este código path). Reusa
     * TicketPolicy::notifiableStaff(), que aplica el mismo criterio de
     * alcance que view()/update()/assign() (supervisor: todo su site;
     * agente: sin asignar o suyo) -- no se reinventa el criterio aquí.
     */
    private function recipients(Ticket $ticket): Collection
    {
        $siteStaff = app(\App\Policies\TicketPolicy::class)->notifiableStaff($ticket);

        $globalUsers = User::permission('tickets.manage_all')->get()
            ->filter(fn (User $user) => $this->operatorScope->userInTicketOperatorScope($user, $ticket));

        return $siteStaff->merge($globalUsers)->unique('id');
    }
}
