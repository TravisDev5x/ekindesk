@component('mail::message')
# Nueva respuesta

Hay una respuesta nueva en el ticket **#{{ $ticket->folio }}** —
"{{ $ticket->subject }}".

@component('mail::button', ['url' => $ticketUrl])
Ver la respuesta
@endcomponent

{{ config('mail.from.name') }}
@endcomponent
