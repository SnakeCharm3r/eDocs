<div class="container-fluid">
    @foreach ($policies as $index => $policy)
        <div class="card mb-4 border-primary">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-file-contract me-2"></i>{{ $policy->title }}
                </h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <img src="{{ asset('assets/img/ccbrt.jpg') }}" alt="CCBRT Logo" style="height: 60px;">
                </div>
                <div class="policy-content">
                    {!! $policy->content !!}
                </div>
                <div class="signature-section mt-4 pt-3 border-top">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong><i class="fas fa-user me-2"></i>Names:</strong>
                            <p class="mb-0 mt-1">{{ $user->fname }} {{ $user->mname }} {{ $user->lname }}</p>
                        </div>
                        <div class="col-md-4">
                            <strong><i class="fas fa-id-card me-2"></i>CCBRT Code:</strong>
                            <p class="mb-0 mt-1">{{ $user->ccbrt_code ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-4">
                            <strong><i class="fas fa-calendar me-2"></i>Date:</strong>
                            <p class="mb-0 mt-1">{{ \Carbon\Carbon::now()->format('d F Y') }}</p>
                        </div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <strong><i class="fas fa-signature me-2"></i>Signature:</strong>
                            <div class="mt-2">
                                @if ($user->signature)
                                    <img src="data:image/png;base64,{{ $user->signature }}" 
                                         alt="User Signature" 
                                         style="max-width: 250px; max-height: 80px; height: auto; border: 1px solid #ddd; padding: 5px; background: white; display: block;">
                                @else
                                    <div class="border-bottom border-dark d-inline-block" style="min-width: 250px; height: 60px; margin-top: 10px;"></div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

