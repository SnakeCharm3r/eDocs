@component('mail::message')
    @if (($noticeType ?? '') === '30day')
        # SOP Expiring in About One Month
        **This SOP will expire in {{ $daysUntilExpiry }} days. As the owner (Line Manager), please plan for renewal or revision.**
    @elseif(($noticeType ?? '') === '7day')
        # ⚠️ URGENT: SOP Expiring in One Week or Less
        **This SOP is expiring in {{ $daysUntilExpiry }} {{ $daysUntilExpiry == 1 ? 'day' : 'days' }} and requires urgent action.**
    @elseif($daysUntilExpiry <= 0)
        # ⚠️ URGENT: SOP Has Expired
        **This SOP has expired and requires immediate attention.**
    @elseif($daysUntilExpiry <= 7)
        # ⚠️ URGENT: SOP Expiring Soon
        **This SOP is expiring in {{ $daysUntilExpiry }} {{ $daysUntilExpiry == 1 ? 'day' : 'days' }} and requires urgent review.**
    @else
        # SOP Expiry Reminder
        **This SOP will expire in {{ $daysUntilExpiry }} days and needs to be reviewed.**
    @endif

    ## SOP Details

    **Title:** {{ $sop->title }}

    @if ($sop->document_code)
        **Document Code:** {{ $sop->document_code }}
    @endif

    @if ($sop->version)
        **Version:** {{ $sop->version }}
    @endif

    @if ($sop->division)
        **Entity/Division:** {{ $sop->division->name }}
    @endif

    @if ($sop->departments && $sop->departments->isNotEmpty())
        **Departments:**
        @foreach ($sop->departments as $dept)
            - {{ $dept->dept_name }}
        @endforeach
    @elseif($sop->global)
        **Departments:** All Departments
    @endif

    @if ($sop->effective_date)
        **Effective Date:** {{ \Carbon\Carbon::parse($sop->effective_date)->format('d M Y') }}
    @endif

    @if ($sop->expiry_date)
        **Expiry Date:** {{ \Carbon\Carbon::parse($sop->expiry_date)->format('d M Y') }}
    @endif

    @if ($sop->description)
        **Description:** {{ $sop->description }}
    @endif

    ---

    **Action Required:**

    @if (($noticeType ?? '') === '7day')
        **Quality Assurance:** Please coordinate with the Line Manager (owner) to renew, revise, or archive this SOP before expiry.
    @endif

    As the Line Manager for your department, please:

    1. **Review this SOP** to determine if it needs to be:
    - **Renewed** - If the SOP is still valid and current
    - **Revised** - If updates are needed
    - **Archived** - If the SOP is no longer applicable

    2. **Contact Quality Assurance (QA)** to request the appropriate action:
    - For renewal: Request QA to update the expiry date
    - For revision: Request QA to create a new version
    - For archiving: Request QA to archive the SOP

    3. **Ensure staff compliance** with the SOP until it is renewed or replaced.

    @component('mail::button', ['url' => route('sops.index')])
        View SOP Details
    @endcomponent

    @if ($daysUntilExpiry <= 7)
        **⚠️ Please take action as soon as possible to avoid operational disruption.**
    @endif

    Thanks,<br>
    {{ config('app.name') }} System
@endcomponent
