<?php
setlocale(LC_ALL, 'ja_JP.SJIS');
$file = './storage/order_20231229193312.csv';
if (($handle = fopen($file, 'r')) !== false) {
    $str = "";
    while (($row = fgetcsv($handle, 1000, ',')) !== false) {
        foreach ($row as $item) {
            $item = mb_convert_encoding($item, 'utf-8', 'JIS, eucjp-win, sjis-win,shift-jis,utf-8');
            $str .= "-" . $item;
        }
        $str .= PHP_EOL;
    }
    echo $str;
    fclose($handle);
}