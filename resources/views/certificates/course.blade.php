<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certificate of Completion</title>
    <style>
        body {
            font-family: 'Times New Roman', serif;
            margin: 0;
            padding: 40px;
            background: #fff;
            color: #333;
        }
        .certificate {
            border: 20px solid #5b2d8e;
            padding: 60px;
            text-align: center;
            position: relative;
        }
        .certificate::before {
            content: '';
            position: absolute;
            top: 10px; left: 10px; right: 10px; bottom: 10px;
            border: 2px solid #a67cda;
        }
        .logo {
            font-size: 32px;
            font-weight: bold;
            color: #5b2d8e;
            margin-bottom: 20px;
        }
        .logo span { color: #0A4D9B; }
        .title {
            font-size: 48px;
            font-weight: bold;
            margin: 30px 0;
            color: #5b2d8e;
        }
        .subtitle {
            font-size: 24px;
            margin: 20px 0;
            color: #666;
        }
        .user-name {
            font-size: 36px;
            font-weight: bold;
            color: #0A4D9B;
            margin: 30px 0;
            border-bottom: 2px solid #5b2d8e;
            display: inline-block;
            padding-bottom: 10px;
        }
        .course-name {
            font-size: 28px;
            font-weight: bold;
            margin: 20px 0;
            color: #333;
        }
        .date {
            font-size: 18px;
            margin: 30px 0;
            color: #666;
        }
        .signature {
            margin-top: 60px;
            display: flex;
            justify-content: space-around;
        }
        .signature-line {
            border-top: 2px solid #333;
            width: 200px;
            padding-top: 10px;
            font-size: 16px;
        }
        .qr-section {
            margin-top: 40px;
            text-align: center;
        }
        .qr-section img {
            width: 100px;
            height: 100px;
            border: 1px solid #eee;
        }
        .verification {
            font-size: 12px;
            color: #999;
            margin-top: 10px;
        }
        .certificate-number {
            position: absolute;
            bottom: 20px;
            right: 40px;
            font-size: 12px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="certificate">
        <div class="logo">SPEAK<span>OUT</span></div>
        
        <div class="title">Certificate of Completion</div>
        
        <div class="subtitle">This is to certify that</div>
        
        <div class="user-name">{{ $userName }}</div>
        
        <div class="subtitle">has successfully completed the course</div>
        
        <div class="course-name">{{ $courseTitle }}</div>
        
        <div class="date">
            Completed on: {{ $completedDate->format('F j, Y') }}
        </div>
        
        <div class="signature">
            <div>
                <div class="signature-line">Instructor Signature</div>
            </div>
            <div>
                <div class="signature-line">SpeakOut Director</div>
            </div>
        </div>
        
        <!-- ✅ QR Code Section: Handle both URL and base64 -->
        <div class="qr-section">
            @if(isset($qrCodeType) && $qrCodeType === 'url')
                <!-- External API QR Code -->
                <img src="{{ $qrCode }}" alt="Verification QR">
            @else
                <!-- Local base64 QR Code (fallback) -->
                <img src="data:image/png;base64,{{ $qrCode }}" alt="Verification QR">
            @endif
            
            <div class="verification">
                Verify at: {{ config('app.url') }}/verify/{{ $verificationCode }}
            </div>
        </div>
        
        <div class="certificate-number">
            No: {{ $certificateNumber }}
        </div>
    </div>
</body>
</html>