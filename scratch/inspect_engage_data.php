<?php
$html = file_get_contents('rpa/engage-rpa/downloads/32a_engage.xlsx');
preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $html, $matches);
preg_match_all('/<t[dh][^>]*>(.*?)<\/t[dh]>/is', $matches[0][1], $h_cells);
$headers = [];
foreach ($h_cells[1] as $idx => $hc) {
    $headers[trim(strip_tags($hc))] = $idx;
}

$units = [];
$texts = [];
$cost_centers = [];
$udefs = [];

for ($i = 2; $i < count($matches[0]); $i++) {
    preg_match_all('/<t[dh][^>]*>(.*?)<\/t[dh]>/is', $matches[0][$i], $cells);
    $unit = trim(strip_tags($cells[1][$headers['Unit']] ?? ''));
    $text = trim(strip_tags($cells[1][$headers['Text']] ?? ''));
    $cost = trim(strip_tags($cells[1][$headers['Cost Center']] ?? ''));
    
    $units[$unit] = ($units[$unit] ?? 0) + 1;
    $texts[$text] = ($texts[$text] ?? 0) + 1;
    $cost_centers[$cost] = ($cost_centers[$cost] ?? 0) + 1;
}

echo "=== Distinct Units ===\n";
print_r($units);

echo "=== Distinct Cost Centers ===\n";
print_r($cost_centers);

echo "=== Sample Texts (top 20) ===\n";
arsort($texts);
print_r(array_slice($texts, 0, 20));

