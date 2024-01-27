<?php


if (!function_exists('bytesToHuman')) {
    function bytesToHuman($bytes, $precision = 2): string
    {
        $units = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        if ($bytes <= 0) {
            return '0 Bytes';
        }
        $exponent = floor(log($bytes, 1024));
        $value = round($bytes / pow(1024, $exponent), $precision);
        return $value . ' ' . $units[$exponent];
    }
}
