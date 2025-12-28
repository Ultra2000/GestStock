@component('mail::message')
# Vous êtes invité à rejoindre {{ $companyName }}

Bonjour,

**{{ $inviterName }}** vous invite à rejoindre l'équipe de **{{ $companyName }}** sur GestStock.

Vous aurez le rôle : **{{ $roleName }}**

@component('mail::button', ['url' => $acceptUrl])
Accepter l'invitation
@endcomponent

Cette invitation expire le **{{ $expiresAt }}**.

Si vous n'avez pas demandé cette invitation, vous pouvez ignorer cet email.

Cordialement,<br>
L'équipe {{ config('app.name') }}

---
<small>Si le bouton ne fonctionne pas, copiez ce lien dans votre navigateur :<br>{{ $acceptUrl }}</small>
@endcomponent
