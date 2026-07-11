@component('mail::message')
# No pudimos crear tu ticket

Hola,

Recibimos tu correo, pero esta dirección no está registrada como usuario del
portal de soporte de **{{ $tenant->name }}**. Por seguridad, solo usuarios ya
registrados o invitados pueden generar tickets por correo.

Si crees que esto es un error, contacta al administrador de soporte de
**{{ $tenant->name }}** para que verifique tu registro.

Tikara
@endcomponent
