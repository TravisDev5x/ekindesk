@component('mail::message')
# Ticket reasignado a ti

**{{ $actor->name }}** te reasignó el ticket **#{{ $ticket->folio }}** — "{{ $ticket->subject }}".

@component('mail::button', ['url' => $ticketUrl])
Ver el ticket
@endcomponent

{{ config('mail.from.name') }}
@endcomponent
