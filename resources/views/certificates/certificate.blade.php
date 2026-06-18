{{-- 
  CERTIFICATE TEMPLATE v4.0 
  Background dekoratif & logo PalComTech sebagai file PNG terpisah, 
  direferensikan lewat public_path() (bukan asset()/URL) supaya dompdf 
  baca langsung dari disk tanpa butuh HTTP request / config enable_remote.
  WAJIB: taruh certificate-bg-art.png dan logo-palcomtech.png di public/image/

  CHANGELOG v4.0 (disesuaikan dengan foto referensi):
  - Title dipecah jadi 2 baris: "CERTIFICATE" (besar, navy) + "OF COMPLETION" (gold, lebih kecil)
  - Divider diganti jadi garis ganda + ornamen diamond di tengah (pakai table, bukan border-top biasa)
  - Nama peserta diperbesar jadi 56pt uppercase (paling dominan di halaman, sesuai foto)
  - Seluruh blok teks utama (logo, title, subtitle, nama, course) digeser ke kanan
    (center ±175mm, bukan center halaman 148.5mm) supaya seimbang dengan panel navy diagonal
  - Blok tanda tangan digeser & dilebarkan (center ±167mm) menyesuaikan posisi di foto
  - subtitle-top & subtitle-mid dikembalikan ke teks semula + font sans-serif (di foto bukan serif)
  - divider-bottom (sebelum tanda tangan) dihapus, karena di foto tidak ada garis di situ
  - Updated: {{ now() }}

  CATATAN UKURAN NAMA: font-size 56pt untuk nama itu pas untuk nama pendek
  seperti "Romi". Kalau ada nama yang jauh lebih panjang, baris nama bisa
  melebar/overflow karena dompdf tidak bisa auto-shrink teks. Kalau itu
  jadi masalah nyata, pertimbangkan logika di controller untuk mengecilkan
  font-size sesuai panjang $user->name sebelum dikirim ke view.
--}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certificate of Completion - {{ $certificate->certificate_number }}</title>
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
            background: #ffffff !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .certificate {
            position: relative;
            width: 297mm;
            height: 210mm;
            background: #ffffff;
            page-break-after: avoid;
            page-break-inside: avoid;
            overflow: hidden;
        }

        /* Background dekoratif (panel navy diagonal + garis gold + tekstur dots), PNG raster */
        .bg-art {
            position: absolute;
            top: 0; left: 0;
            width: 297mm;
            height: 210mm;
        }

        /* Logo PalComTech. Digeser ikut blok konten utama (center ±175mm). */
        .logo-wrap {
            position: absolute;
            top: 8mm;
            left: 53mm; right: 0;
            text-align: center;
        }
        .logo-wrap img {
            width: 24mm;
            height: auto;
        }

        /* ===== TITLE: 2 baris, "CERTIFICATE" besar navy + "OF COMPLETION" gold ===== */
        .title-main {
            position: absolute;
            top: 43mm;
            left: 53mm; right: 0;
            text-align: center;
            font-size: 48pt;
            font-weight: bold;
            color: #0D145A;
            letter-spacing: 5px;
            text-transform: uppercase;
        }
        .title-sub {
            position: absolute;
            top: 60mm;
            left: 53mm; right: 0;
            text-align: center;
            font-size: 24pt;
            font-weight: bold;
            color: #D4AF37;
            letter-spacing: 5px;
            text-transform: uppercase;
        }

        /* ===== Ornamen divider: garis ganda + diamond di tengah (dipakai utk divider-top & user-name-underline) ===== */
        .divider-top,
        .user-name-underline {
            position: absolute;
            left: 118mm;
            right: 65mm;
        }
        .divider-top { top: 70mm; }
        .user-name-underline { top: 111mm; }

        .ornate-table {
            width: 100%;
            border-collapse: collapse;
        }
        .ornate-table .line-cell {
            vertical-align: middle;
        }
        .ornate-table .line-cell .line-inner {
            border-top: 1.2px solid #D4AF37;
            font-size: 0;
            line-height: 0;
        }
        .ornate-table .diamond-cell {
            width: 16px;
            text-align: center;
            vertical-align: middle;
            color: #D4AF37;
            font-size: 9pt;
            padding: 0 8px;
        }

        /* Subtitle atas - sans-serif tipis, bukan serif (sesuai foto) */
        .subtitle-top {
            position: absolute;
            top: 80mm;
            left: 53mm; right: 0;
            text-align: center;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13pt;
            color: #404040;
        }

        /* Nama peserta - paling dominan di halaman */
        .user-name {
            position: absolute;
            top: 91mm;
            left: 53mm; right: 0;
            text-align: center;
            font-size: 56pt;
            font-weight: bold;
            color: #0D145A;
            font-style: normal;
            text-transform: uppercase;
            line-height: 1.1;
        }

        /* Subtitle tengah - sama gaya dengan subtitle-top */
        .subtitle-mid {
            position: absolute;
            top: 122mm;
            left: 53mm; right: 0;
            text-align: center;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13pt;
            color: #404040;
        }

        /* Nama kursus */
        .course-name {
            position: absolute;
            top: 130mm;
            left: 53mm; right: 0;
            text-align: center;
            font-size: 30pt;
            font-weight: bold;
            color: #0D145A;
        }

        /* Instructor course */
        .course-instructor {
            position: absolute;
            top: 145mm;
            left: 53mm; right: 0;
            text-align: center;
            font-size: 9pt;
            color: #666666;
            font-style: italic;
        }

        /* Tanggal */
        .date {
            position: absolute;
            top: 152mm;
            left: 53mm; right: 0;
            text-align: center;
            font-size: 9pt;
            color: #888888;
            font-style: italic;
        }

        /* SIGNATURE: digeser & dilebarkan supaya 2 kolomnya center di ±121mm & ±213mm (sesuai foto) */
        .signature-wrap {
            position: absolute;
            top: 170mm;
            left: 84mm;
            right: 31mm;
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
            border-top: 1.5px solid #c9a227;
            width: 70%;
            margin: 0 auto 2mm auto;
            padding-top: 1mm;
        }

        .sig-name {
            font-size: 10pt;
            font-weight: bold;
            color: #1a1a4e;
            text-align: center;
        }

        .sig-title {
            font-size: 8pt;
            color: #888888;
            font-style: italic;
            text-align: center;
            margin-top: 1mm;
        }

        /* Info admin yang approve - sejajar dgn blok tanda tangan (±167mm) */
        .admin-info {
            position: absolute;
            top: 194mm;
            left: 84mm; right: 31mm;
            text-align: center;
            font-size: 8pt;
            color: #999999;
            font-style: italic;
        }

        /* Nomor sertifikat pojok kanan bawah */
        .certificate-number {
            position: absolute;
            bottom: 6mm;
            right: 16mm;
            font-size: 7pt;
            color: #aaaaaa;
            font-family: monospace;
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

    {{-- Background dekoratif --}}
    <img class="bg-art" src="{{ public_path('image/certificate-bg-art.png') }}" alt="">

    {{-- Logo PalComTech --}}
    <div class="logo-wrap">
        <img src="{{ public_path('image/logo-palcomtech.png') }}" alt="PalComTech">
    </div>

    {{-- Title: 2 baris --}}
    <div class="title-main">Certificate</div>
    <div class="title-sub">of completion</div>

    {{-- Divider atas (garis + diamond) --}}
    <div class="divider-top">
        <table class="ornate-table"><tr>
            <td class="line-cell"><div class="line-inner"></div></td>
            <td class="diamond-cell">&#9670;</td>
            <td class="line-cell"><div class="line-inner"></div></td>
        </tr></table>
    </div>

    {{-- Subtitle atas --}}
    <div class="subtitle-top">This is to certify that</div>

    {{-- Nama user --}}
    <div class="user-name">{{ $user->name }}</div>

    {{-- Underline nama (garis + diamond) --}}
    <div class="user-name-underline">
        <table class="ornate-table"><tr>
            <td class="line-cell"><div class="line-inner"></div></td>
            <td class="diamond-cell">&#9670;</td>
            <td class="line-cell"><div class="line-inner"></div></td>
        </tr></table>
    </div>

    {{-- Subtitle tengah --}}
    <div class="subtitle-mid">has successfully completed the course</div>

    {{-- Nama kursus --}}
    <div class="course-name">{{ $course->title }}</div>

    {{-- Instructor kursus --}}
    <div class="course-instructor">Instructor: {{ $course->instructor }}</div>

    {{-- Tanggal approved --}}
    <div class="date">
        Approved on: {{ $certificate->approved_at->format('F j, Y') }}
    </div>

    {{-- Signature --}}
    <div class="signature-wrap">
        <table class="signature-table">
            <tr>
                <td>
                    <div class="sig-line"></div>
                    <div class="sig-name">{{ $course->instructor }}</div>
                    <div class="sig-title">Course Instructor</div>
                </td>
                <td>
                    <div class="sig-line"></div>
                    <div class="sig-name">{{ $admin->name ?? 'Admin PalComTech' }}</div>
                    <div class="sig-title">Administrator, PalComTech</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Info admin yang approve --}}
    @if($certificate->approvedBy)
    <div class="admin-info">
        Issued by: {{ $certificate->approvedBy->name }} (Admin)
    </div>
    @endif

    {{-- Nomor sertifikat --}}
    <div class="certificate-number">No: {{ $certificate->certificate_number }}</div>

</div>
</body>
</html>