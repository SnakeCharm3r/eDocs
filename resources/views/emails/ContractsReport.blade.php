@component('mail::message')
# Contract Renewal Reminder

{{ $customMessage }}

@foreach($contracts as $contract)
## Contract Details

**Title:** {{ $contract->title }}

@if($contract->vendor)
**Vendor:** {{ $contract->vendor->name }}
@endif

@if($contract->department)
**Department:** {{ $contract->department->dept_name }}
@endif

@if($contract->start_date)
**Start Date:** {{ \Carbon\Carbon::parse($contract->start_date)->format('Y-m-d') }}
@endif

@if($contract->end_date)
**End Date:** {{ \Carbon\Carbon::parse($contract->end_date)->format('Y-m-d') }}
@endif

@if($contract->value)
**Value:** {{ number_format($contract->value, 2) }}
@endif

@if($contract->description)
**Description:** {{ $contract->description }}
@endif

Please review this contract and take appropriate action (Renew, Terminate, or Hold).

---
@endforeach

@component('mail::button', ['url' => route('procurements.contracts.index')])
View Contracts
@endcomponent

Thanks,<br>
{{ config('app.name') }}

@component('mail::subcopy')
This is an automated notification from {{ config('app.name') }}. Please do not reply to this email.
@endcomponent
@endcomponent
