<?php
$f = 'rpa/engage-rpa/downloads/32a_engage.xlsx';
$html = file_get_contents($f);
preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $html, $matches);
echo "Total tr: " . count($matches[0]) . "\n";
for ($i = 0; $i < min(5, count($matches[0])); $i++) {
    echo "--- TR $i ---\n";
    preg_match_all('/<t[dh][^>]*>(.*?)<\/t[dh]>/is', $matches[0][$i], $cells);
    foreach ($cells[1] as $c) {
        echo trim(strip_tags($c)) . " | ";
    }
    echo "\n";
}

