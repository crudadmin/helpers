<x-mail::message>
# {{ _('Verifikačný kód') }}

{{ _('Pre pokračovanie zadajte nasledujúci verifikačný kód:') }} <strong>{{ $token }}</strong>

{{ _('S pozdravom') }},<br>
{{ config('app.name') }}
</x-mail::message>