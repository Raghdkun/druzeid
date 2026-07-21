<x-mail::message>
# {{ __('contact.title') }}

**{{ __('contact.name') }}:** {{ $data['name'] }}

**{{ __('contact.email') }}:** {{ $data['email'] }}

@if (! empty($data['subject']))
**{{ __('contact.subject') }}:** {{ $data['subject'] }}
@endif

**{{ __('lang.label') }}:** {{ $data['locale'] }}

---

{{ $data['message'] }}
</x-mail::message>
