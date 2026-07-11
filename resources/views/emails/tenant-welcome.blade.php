@component('mail::message')
# ¡Bienvenido a Tikara!

Hola,

La cuenta de **{{ $client->name }}** ya está activa en Tikara. Ya pueden empezar a
recibir y dar seguimiento a sus tickets de soporte.

@if($portalUrl)
@component('mail::button', ['url' => $portalUrl])
Ir a mi portal
@endcomponent
@endif

Cualquier duda, respondan este correo.

{{ config('mail.from.name') }}
@endcomponent
