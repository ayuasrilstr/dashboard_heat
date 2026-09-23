<?php
define('BASEPATH', '1');
define('APPPATH', 'web/application/');

// Let's inspect the exact in/out per hour using Dashboard_model methods or direct parsing
$f_32a = 'rpa/engage-rpa/downloads/32a_engage.xlsx';
$html = file_get_contents($f_32a);
preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $html, $matches);

preg_match_all('/<t[dh][^>]*>(.*?)<\/t[dh]>/is', $matches[0][1], $h_cells);
$headers = [];
foreach ($h_cells[1] as $idx => $hc) {
    $headers[trim(strip_tags($hc))] = $idx;
}

$date_col = $headers['Date'] ?? 1;
$qty_col = $headers['Qty'] ?? 12;
$storage_col = $headers['Storage Nr'] ?? 2;
$storage2_col = $headers['Storage 2'] ?? 10;
$text_col = $headers['Text'] ?? 14;
$item_col = $headers['Item Nr'] ?? 4;
$cost_col = $headers['Cost Center'] ?? 15;
$udef8_col = $headers['Udef 8'] ?? 24;
$udef4_col = $headers['Udef 4'] ?? 20;
$udef5_col = $headers['Udef 5'] ?? 21;
$udef6_col = $headers['Udef 6'] ?? 22;
$udef10_col = $headers['Udef 10'] ?? 26;

$hourly = [];
$seen = [];

for ($i = 2; $i < count($matches[0]); $i++) {
    preg_match_all('/<t[dh][^>]*>(.*?)<\/t[dh]>/is', $matches[0][$i], $cells);
    $d = trim(strip_tags($cells[1][$date_col] ?? ''));
    $q = (float) trim(strip_tags($cells[1][$qty_col] ?? 0));
    $s = trim(strip_tags($cells[1][$storage_col] ?? ''));
    $txt = strtolower(trim(strip_tags($cells[1][$text_col] ?? '')));
    $item = trim(strip_tags($cells[1][$item_col] ?? ''));
    $u6 = strtolower(trim(strip_tags($cells[1][$udef6_col] ?? '')));
    $u10 = trim(strip_tags($cells[1][$udef10_col] ?? ''));
    
    // Filter rule for 32a:
    // HT in item, csdb, transfer, bundle_receive, no sk in udef6
    if (stripos($item, 'HT') === false) continue;
    if (stripos($txt, 'csdb') === false || stripos($txt, 'transfer') === false || stripos($txt, 'bundle_receive') === false) continue;
    if (stripos($u6, 'sk') !== false) continue;
    
    $qty = abs($q);
    if ($qty <= 0) continue;
    
    $hr = substr($d, 11, 2);
    if ($hr === '') $hr = '00';
    $hr_label = $hr . ':00';
    
    // Deduplication key
    $key = $d . "\n" . $item . "\n" . $qty . "\n" . $u10;
    if (isset($seen[$key])) continue;
    $seen[$key] = true;
    
    if (!isset($hourly[$hr_label])) {
        $hourly[$hr_label] = ['in' => 0, 'out' => 0];
    }
    
    // Check direction: if q > 0 is in, q < 0 is out (or storage flow)
    if ($q > 0) {
        $hourly[$hr_label]['in'] += $qty;
    } else {
        $hourly[$hr_label]['out'] += $qty;
    }
}

ksort($hourly);
echo "=== Hourly 32a IN / OUT ===\n";
foreach ($hourly as $hr => $vals) {
    echo "$hr -> IN: {$vals['in']}, OUT: {$vals['out']}\n";
}

$dates = [];
for ($i = 2; $i < min(100, count($matches[0])); $i++) {
    preg_match_all('/<t[dh][^>]*>(.*?)<\/t[dh]>/is', $matches[0][$i], $cells);
    $d = trim(strip_tags($cells[1][$date_col] ?? ''));
    $dates[substr($d, 0, 10)] = ($dates[substr($d, 0, 10)] ?? 0) + 1;
}
print_r($dates);

