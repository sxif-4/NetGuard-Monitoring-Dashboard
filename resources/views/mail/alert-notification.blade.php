<x-mail::message>
# {{ $alert->title }}

**Severity:** {{ $alert->severity->label() }}
**Category:** {{ $alert->category->label() }}
**Device:** {{ $alert->device?->name ?? 'Network-wide' }}

{{ $alert->description }}

<x-mail::panel>
Occurrences: {{ $alert->occurrences }}
First detected: {{ $alert->first_detected_at->toDayDateTimeString() }}
Last detected: {{ $alert->last_detected_at->toDayDateTimeString() }}
</x-mail::panel>

<x-mail::button :url="route('alerts.index')">
View in NetGuard
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
