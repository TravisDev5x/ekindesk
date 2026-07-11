@component('mail::message')
# Recibimos tu ticket

Hola {{ $ticket->requester?->first_name ?? '' }},

Tu ticket **#{{ $ticket->folio }}** — "{{ $ticket->subject }}" — quedó registrado
correctamente. Nuestro equipo lo revisará lo antes posible.

@component('mail::button', ['url' => $ticketUrl])
Ver mi ticket
@endcomponent

Gracias por tu paciencia,<br>
{{ config('mail.from.name') }}
@endcomponent
