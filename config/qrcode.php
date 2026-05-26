<?php
// config/qrcode.php

return [
    // ✅ PENTING: Driver harus 'gd' (bukan 'imagick')
    'driver' => 'gd',
    
    // Settings lainnya (bisa default)
    'size' => 300,
    'margin' => 4,
    'error_correction' => 'L',
    'foreground' => ['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0],
    'background' => ['r' => 255, 'g' => 255, 'b' => 255, 'a' => 0],
    'encoding' => 'PNG',
    'png_quality' => 90,
];