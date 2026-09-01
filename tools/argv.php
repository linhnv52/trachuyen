<?php
/**
 * Đọc tham số dòng lệnh dạng --key value.
 * Trả về mảng key => value (giá trị string).
 */

function parseArgv(array $argv): array
{
    $out = [];
    $n = count($argv);
    for ($i = 1; $i < $n; $i++) {
        $a = $argv[$i];
        if (substr($a, 0, 2) === '--') {
            $key = substr($a, 2);
            $val = ($i + 1 < $n && substr($argv[$i + 1], 0, 1) !== '-') ? $argv[++$i] : '';
            $out[$key] = $val;
        }
    }
    return $out;
}

$argv = parseArgv($argv ?? []);