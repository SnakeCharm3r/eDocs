<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>CCBRT Policies</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 40px;
            font-size: 12px;
        }
        .policy-section {
            page-break-after: always;
            margin-bottom: 30px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .policy-section:last-child {
            page-break-after: auto;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 15px;
        }
        .header img {
            height: 60px;
            margin-bottom: 10px;
        }
        .header h2 {
            font-size: 18px;
            color: #333;
            margin-top: 10px;
        }
        .content {
            margin: 20px 0;
            line-height: 1.8;
            flex: 1;
            text-align: justify;
        }
        .signature-section {
            margin-top: 50px;
            padding-top: 30px;
            border-top: 1px solid #ddd;
            width: 100%;
        }
        .signature-top-row {
            width: 100%;
            margin-bottom: 40px;
            display: block;
        }
        .signature-top-row::after {
            content: "";
            display: table;
            clear: both;
        }
        .signature-top-item {
            float: left;
            width: 33.33%;
            padding: 0 15px;
            vertical-align: top;
        }
        .signature-top-item strong {
            display: block;
            margin-bottom: 8px;
            font-size: 12px;
            font-weight: bold;
        }
        .signature-top-item span {
            display: block;
            font-size: 12px;
            margin-top: 5px;
        }
        .signature-bottom {
            clear: both;
            margin-top: 30px;
            width: 100%;
            display: block;
        }
        .signature-bottom strong {
            display: block;
            margin-bottom: 10px;
            font-size: 12px;
            font-weight: bold;
        }
        .signature-image {
            max-width: 200px;
            max-height: 80px;
            height: auto;
            display: block;
            margin-top: 10px;
            border: 1px solid #ddd;
            padding: 5px;
        }
        .signature-placeholder {
            border-bottom: 2px solid #000;
            width: 250px;
            height: 60px;
            margin-top: 10px;
            display: block;
        }
        @media print {
            .policy-section {
                page-break-after: always;
            }
            .policy-section:last-child {
                page-break-after: auto;
            }
        }
    </style>
</head>
<body>
    @foreach ($policies as $index => $policy)
        <div class="policy-section">
            <div class="header">
                @if(file_exists(public_path('assets/img/ccbrt.jpg')))
                    <img src="{{ public_path('assets/img/ccbrt.jpg') }}" alt="CCBRT Logo">
                @endif
                <h2>{{ $policy->title }}</h2>
            </div>

            <div class="content">
                {!! $policy->content !!}
            </div>

            <div class="signature-section">
                <div class="signature-top-row">
                    <div class="signature-top-item">
                        <strong>Names:</strong>
                        <span>{{ $user->fname }} {{ $user->mname }} {{ $user->lname }}</span>
                    </div>
                    <div class="signature-top-item">
                        <strong>CCBRT Code:</strong>
                        <span>{{ $user->ccbrt_code ?? 'N/A' }}</span>
                    </div>
                    <div class="signature-top-item">
                        <strong>Date:</strong>
                        <span>{{ \Carbon\Carbon::now()->format('d F Y') }}</span>
                    </div>
                </div>
                <div class="signature-bottom">
                    <strong>Signature:</strong>
                    @if (isset($signaturePath) && $signaturePath && file_exists($signaturePath))
                        <img src="file://{{ str_replace('\\', '/', $signaturePath) }}" 
                             alt="User Signature" 
                             class="signature-image"
                             style="max-width: 200px; max-height: 80px; height: auto; display: block; margin-top: 10px; border: 1px solid #ddd; padding: 5px; background: white;">
                    @elseif ($user->signature)
                        @php
                            // Signature is stored as base64 string (without data URI prefix)
                            $signatureData = trim($user->signature);
                            
                            // Ensure we have just the base64 string
                            if (strpos($signatureData, 'data:image') !== false) {
                                // Extract base64 part if it's a data URI
                                $signatureData = preg_replace('/^data:image\/\w+;base64,/', '', $signatureData);
                            }
                            
                            // Clean the base64 string
                            $signatureData = trim($signatureData);
                            
                            // For DomPDF, we need to use data URI format
                            $signatureSrc = 'data:image/png;base64,' . $signatureData;
                        @endphp
                        <img src="{{ $signatureSrc }}" 
                             alt="User Signature" 
                             class="signature-image"
                             style="max-width: 200px; max-height: 80px; height: auto; display: block; margin-top: 10px; border: 1px solid #ddd; padding: 5px; background: white;">
                    @else
                        <div class="signature-placeholder"></div>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</body>
</html>
