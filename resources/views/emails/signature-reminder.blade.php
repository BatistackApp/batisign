@component('mail::message')
    # Bonjour {{ $document->client_name }},

    Nous vous informons que votre devis est toujours disponible et en attente de votre signature.

    Afin de valider notre collaboration et de planifier les prochaines étapes, nous vous invitons à le consulter et à le signer électroniquement en cliquant sur le bouton ci-dessous.

@component('mail::button', ['url' => route('public.document.sign', ['document' => $document, 'uuid' => $document->uuid])])
    Consulter et Signer le Devis
@endcomponent

Si vous avez la moindre question, n'hésitez pas à nous contacter.

Cordialement,<br>
L'équipe {{ config('app.name') }}
@endcomponent
