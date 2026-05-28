<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certificate of Completion - {{ $certificateNumber }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 0;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            width: 297mm;
            height: 210mm;
        }

        body {
            font-family: 'Times New Roman', serif;
            width: 297mm;
            height: 210mm;
            margin: 0;
            padding: 0;
            background: #ffffff;
        }

        /*
         * DOMPDF NOTES:
         * - flexbox tidak didukung, gunakan position:absolute / table
         * - overflow:hidden diabaikan, jadi kita kontrol height ketat
         * - @page size harus match dengan body width/height
         * - page-break harus dicegah dengan page-break-inside:avoid
         */

        .certificate {
            position: relative;
            width: 297mm;
            height: 210mm;
            background: #ffffff;
            page-break-after: avoid;
            page-break-inside: avoid;
        }

        /* Border luar ungu */
        .border-outer {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            border: 8px solid #5b2d8e;
        }

        /* Border dalam tipis */
        .border-inner {
            position: absolute;
            top: 6px; left: 6px; right: 6px; bottom: 6px;
            border: 1px solid #a67cda;
        }

        /* ── KONTEN: semua pakai position absolute dengan koordinat mm ── */

        /* Logo SPEAKOUT */
        .logo {
            position: absolute;
            top: 14mm;
            left: 0; right: 0;
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            color: #5b2d8e;
            letter-spacing: 3px;
        }
        .logo .out { color: #0A4D9B; }

        /* Title */
        .title {
            position: absolute;
            top: 24mm;
            left: 0; right: 0;
            text-align: center;
            font-size: 22pt;
            font-weight: bold;
            color: #5b2d8e;
            letter-spacing: 1px;
        }

        /* Subtitle atas */
        .subtitle-top {
            position: absolute;
            top: 40mm;
            left: 0; right: 0;
            text-align: center;
            font-size: 10pt;
            color: #888888;
            font-style: italic;
        }

        /* Divider atas — pakai table trick karena dompdf tidak support hr gradient */
        .divider-top {
            position: absolute;
            top: 46mm;
            left: 20mm; right: 20mm;
            height: 2px;
            border-top: 1px solid #a67cda;
        }

        /* Nama pengguna */
        .user-name {
            position: absolute;
            top: 52mm;
            left: 0; right: 0;
            text-align: center;
            font-size: 22pt;
            font-weight: bold;
            color: #0A4D9B;
        }

        /* Underline nama — pakai border-bottom di span wrapper */
        .user-name-underline {
            position: absolute;
            top: 66mm;
            left: 60mm; right: 60mm;
            height: 1px;
            border-top: 1.5px solid #5b2d8e;
        }

        /* Subtitle tengah */
        .subtitle-mid {
            position: absolute;
            top: 70mm;
            left: 0; right: 0;
            text-align: center;
            font-size: 10pt;
            color: #888888;
            font-style: italic;
        }

        /* Nama kursus */
        .course-name {
            position: absolute;
            top: 80mm;
            left: 0; right: 0;
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            color: #333333;
        }

        /* Tanggal */
        .date {
            position: absolute;
            top: 91mm;
            left: 0; right: 0;
            text-align: center;
            font-size: 9pt;
            color: #999999;
            font-style: italic;
        }

        /* Divider bawah */
        .divider-bottom {
            position: absolute;
            top: 101mm;
            left: 20mm; right: 20mm;
            height: 2px;
            border-top: 1px solid #a67cda;
        }

        /* ── SIGNATURE: pakai table ── */
        .signature-wrap {
            position: absolute;
            top: 130mm;
            left: 30mm;
            right: 30mm;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }

        .signature-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0 10mm;
        }

        .sig-line {
            border-top: 1.5px solid #555555;
            width: 70%;
            margin: 0 auto 2mm auto;
            padding-top: 1mm;
        }

        .sig-name {
            font-size: 10pt;
            font-weight: bold;
            color: #333333;
            text-align: center;
        }

        .sig-title {
            font-size: 8pt;
            color: #888888;
            font-style: italic;
            text-align: center;
            margin-top: 1mm;
        }

        /* QR code */
        .qr-wrap {
            position: absolute;
            top: 148mm;
            left: 0; right: 0;
            text-align: center;
        }

        .qr-code {
            width: 35px;
            height: 35px;
        }

        /* Teks verifikasi */
        .verification {
            position: absolute;
            top: 163mm;
            left: 0; right: 0;
            text-align: center;
            font-size: 7pt;
            color: #bbbbbb;
            line-height: 1.3;
        }

        /* Nomor sertifikat pojok kanan bawah */
        .certificate-number {
            position: absolute;
            bottom: 6mm;
            right: 16mm;
            font-size: 7pt;
            color: #cccccc;
            font-family: monospace;
        }

        /* Ornamen sudut — 4 buah, pakai SVG inline */
        .corner-tl {
            position: absolute;
            top: 8px; left: 8px;
            width: 32px; height: 32px;
        }
        .corner-tr {
            position: absolute;
            top: 8px; right: 8px;
            width: 32px; height: 32px;
        }
        .corner-bl {
            position: absolute;
            bottom: 8px; left: 8px;
            width: 32px; height: 32px;
        }
        .corner-br {
            position: absolute;
            bottom: 8px; right: 8px;
            width: 32px; height: 32px;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
<div class="certificate">

    {{-- Border luar & dalam --}}
    <div class="border-outer"></div>
    <div class="border-inner"></div>

    {{-- Ornamen sudut (SVG inline, dompdf support SVG di HTML) --}}
    <div class="corner-tl">
        <svg width="32" height="32" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
            <line x1="2" y1="2" x2="30" y2="2" stroke="#5b2d8e" stroke-width="2.5"/>
            <line x1="2" y1="2" x2="2" y2="30" stroke="#5b2d8e" stroke-width="2.5"/>
            <line x1="8" y1="8" x2="22" y2="8" stroke="#a67cda" stroke-width="1"/>
            <line x1="8" y1="8" x2="8" y2="22" stroke="#a67cda" stroke-width="1"/>
            <rect x="13" y="13" width="6" height="6" fill="#5b2d8e" transform="rotate(45 16 16)"/>
        </svg>
    </div>
    <div class="corner-tr">
        <svg width="32" height="32" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
            <line x1="30" y1="2" x2="2" y2="2" stroke="#5b2d8e" stroke-width="2.5"/>
            <line x1="30" y1="2" x2="30" y2="30" stroke="#5b2d8e" stroke-width="2.5"/>
            <line x1="24" y1="8" x2="10" y2="8" stroke="#a67cda" stroke-width="1"/>
            <line x1="24" y1="8" x2="24" y2="22" stroke="#a67cda" stroke-width="1"/>
            <rect x="13" y="13" width="6" height="6" fill="#5b2d8e" transform="rotate(45 16 16)"/>
        </svg>
    </div>
    <div class="corner-bl">
        <svg width="32" height="32" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
            <line x1="2" y1="30" x2="30" y2="30" stroke="#5b2d8e" stroke-width="2.5"/>
            <line x1="2" y1="30" x2="2" y2="2" stroke="#5b2d8e" stroke-width="2.5"/>
            <line x1="8" y1="24" x2="22" y2="24" stroke="#a67cda" stroke-width="1"/>
            <line x1="8" y1="24" x2="8" y2="10" stroke="#a67cda" stroke-width="1"/>
            <rect x="13" y="13" width="6" height="6" fill="#5b2d8e" transform="rotate(45 16 16)"/>
        </svg>
    </div>
    <div class="corner-br">
        <svg width="32" height="32" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
            <line x1="30" y1="30" x2="2" y2="30" stroke="#5b2d8e" stroke-width="2.5"/>
            <line x1="30" y1="30" x2="30" y2="2" stroke="#5b2d8e" stroke-width="2.5"/>
            <line x1="24" y1="24" x2="10" y2="24" stroke="#a67cda" stroke-width="1"/>
            <line x1="24" y1="24" x2="24" y2="10" stroke="#a67cda" stroke-width="1"/>
            <rect x="13" y="13" width="6" height="6" fill="#5b2d8e" transform="rotate(45 16 16)"/>
        </svg>
    </div>

    {{-- Logo --}}
    <div class="logo">SPEAK<span class="out">OUT</span></div>

    {{-- Title --}}
    <div class="title">Certificate of Completion</div>

    {{-- Subtitle atas --}}
    <div class="subtitle-top">This is to certify that</div>

    {{-- Divider atas --}}
    <div class="divider-top"></div>

    {{-- Nama user --}}
    <div class="user-name">{{ $userName }}</div>
    <div class="user-name-underline"></div>

    {{-- Subtitle tengah --}}
    <div class="subtitle-mid">has successfully completed the course</div>

    {{-- Nama kursus --}}
    <div class="course-name">{{ $courseTitle }}</div>

    {{-- Tanggal --}}
    <div class="date">Completed on: {{ $completedDate->format('F j, Y') }}</div>

    {{-- Divider bawah --}}
    <div class="divider-bottom"></div>

    {{-- Signature --}}
    <div class="signature-wrap">
        <table class="signature-table">
            <tr>
                <td>
                    <div class="sig-line"></div>
                    <div class="sig-name">{{ $instructorName ?? 'Krisna Islami' }}</div>
                    <div class="sig-title">Course Instructor</div>
                </td>
                <td>
                    <div class="sig-line"></div>
                    <div class="sig-name">{{ $directorName ?? 'Dr. SpeakOut' }}</div>
                    <div class="sig-title">Director, SpeakOut Institute</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- QR Code --}}
    <div class="qr-wrap">
        <img class="qr-code" src="{{ $qrCode }}" alt="Verification QR">
    </div>

    {{-- Verifikasi --}}
    <div class="verification">
        Verify certificate at:<br>
        {{ config('app.url') }}/verify/{{ $verificationCode }}
    </div>

    {{-- Nomor sertifikat --}}
    <div class="certificate-number">No: {{ $certificateNumber }}</div>

</div>
</body>
</html>