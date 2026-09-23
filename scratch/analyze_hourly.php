<?php
require_once 'scratch/inspect_engage.php';
$mysqli = new mysqli('localhost', 'root', '', 'db_dashboardgm');

// Check dates in tb_engage_transactions and raw excel
$f_32a = 'rpa/engage-rpa/downloads/32a_engage.xlsx';
$html = file_get_contents($f_32a);
preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $html, $matches);

$headers = [];
$in_hourly = [];
$out_hourly = [];

// Let's see what each row contains
preg_match_all('/<t[dh][^>]*>(.*?)<\/t[dh]>/is', $matches[0][1], $h_cells);
foreach ($h_cells[1] as $idx => $hc) {
    $headers[trim(strip_tags($hc))] = $idx;
}

echo "=== Headers ===\n";
print_r($headers);

$date_col = $headers['Date'] ?? 1;
$qty_col = $headers['Qty'] ?? 12;
$storage_col = $headers['Storage Nr'] ?? 2;
$storage2_col = $headers['Storage 2'] ?? 10;
$text_col = $headers['Text'] ?? 14;
$item_col = $headers['Item Nr'] ?? 4;

$sample_today = [];
$dates_found = [];

for ($i = 2; $i < count($matches[0]); $i++) {
    preg_match_all('/<t[dh][^>]*>(.*?)<\/t[dh]>/is', $matches[0][$i], $cells);
    $d = trim(strip_tags($cells[1][$date_col] ?? ''));
    $q = (float) trim(strip_tags($cells[1][$qty_col] ?? 0));
    $s = trim(strip_tags($cells[1][$storage_col] ?? ''));
    $s2 = trim(strip_tags($cells[1][$storage2_col] ?? ''));
    $txt = trim(strip_tags($cells[1][$text_col] ?? ''));
    $item = trim(strip_tags($cells[1][$item_col] ?? ''));
    
    $day = substr($d, 0, 10);
    $hr = substr($d, 11, 2);
    $dates_found[$day] = ($dates_found[$day] ?? 0) + 1;
    
    // Inflow vs Outflow rules
    // 32a inflow: transfer into 32a
    // 32a outflow: transfer out of 32a
    $sample_today[$hr][] = [
        'd' => $d,
        'q' => $q,
        's' => $s,
        's2' => $s2,
        'txt' => $txt,
        'item' => $item
    ];
}

echo "=== Dates in file ===\n";
print_r($dates_found);

echo "=== Hours in file ===\n";
foreach ($sample_today as $hr => $rows) {
    $totQty = array_sum(array_column($rows, 'q'));
    echo "Hour $hr: " . count($rows) . " rows, Total Qty: $totQty\n";
}

