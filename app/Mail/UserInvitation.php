<?php

namespace App\Mail;

use App\Models\UserInvitation as UserInvitationModel;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public UserInvitationModel $invitation
    ) {
        $this->invitation->loadMissing(['invitedBy', 'role', 'client']);
    }

    public function build()
    {
        $acceptUrl = url('/register/accept?token=' . $this->invitation->token);

        return $this->subject('Invitación a HelpDesk')
            ->view('emails.user-invitation')
            ->with([
                'inviterName' => $this->invitation->invitedBy?->name ?? 'Un administrador',
                'roleName' => $this->invitation->role?->name ?? 'Usuario',
                'clientName' => $this->invitation->client?->name,
                'acceptUrl' => $acceptUrl,
                'expiresAt' => $this->invitation->expires_at,
            ]);
    }
}
