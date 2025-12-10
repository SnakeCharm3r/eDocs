<style>
    body {
        font-family: Arial, sans-serif;
    }

    .header {
        text-align: center;
        margin-bottom: 20px;
    }

    .content {
        margin: 20px 0;
    }

    .signature {
        margin-top: 30px;
    }
</style>

<div class="header">
    {{-- <img src="{{ asset('assets/img/ccbrt.JPG') }}" alt="CCBRT Logo" style="height: 50px;"> --}}
    <h2>{{ $policy->title }}</h2>
</div>

<div class="content">
    {!! $policy->content !!}
</div>

<div class="signature">
    <p><strong>Names:</strong> {{ $user->fname }} {{ $user->lname }}</p>
    <p><strong>Signature:<img src="data:image/png;base64,{{ $user->signature }}"
        alt="User Signature"
        style="max-width:20%; height: auto; margin-right: 5px;"></strong></p>

    <p><strong>Date:
        </strong> {{ \Carbon\Carbon::parse($user->created_at)->format('d F Y') }}
    </p>
</div>

</div>

<script>
    function downloadPolicy() {
        const policy = policies[currentPolicyIndex];
        const url = `{{ route('user.policy.download', ['id' => $user->id]) }}?policy_id=${policy.id}`;
        window.location.href = url;
    }
</script>

{{-- </div>
    </div> --}}
{{-- @endsection --}}
