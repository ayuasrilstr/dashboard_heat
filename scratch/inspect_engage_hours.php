<?php
$f = 'rpa/engage-rpa/downloads/32a_engage.xlsx';
$html = file_get_contents($f);
preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $html, $matches);

$dates = [];
$hours = [];

for ($i = 2; $i < count($matches[0]); $i++) {
    preg_match_all('/<t[dh][^>]*>(.*?)<\/t[dh]>/is', $matches[0][$i], $cells);
    $d = trim(strip_tags($cells[1][1] ?? ''));
    if ($d) {
        $dt = substr($d, 0, 10);
        $dates[$dt] = ($dates[$dt] ?? 0) + 1;
        $hr = substr($d, 11, 2);
        if ($hr !== '') {
            $hours[$dt][$hr] = ($hours[$dt][$hr] ?? 0) + 1;
        }
    }
}

echo "=== Dates in 32a_engage.xlsx ===\n";
print_r($dates);

echo "=== Hours breakdown ===\n";
print_r($hours);

