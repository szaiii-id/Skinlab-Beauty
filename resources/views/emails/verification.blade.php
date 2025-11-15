<x-mail::message>
# Verify Your Email

Your verification code is:

<x-mail::panel>
{{ $code }}
</x-mail::panel>

This code will expire in {{ $expires }} minutes.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>