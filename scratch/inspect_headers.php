<?php
foreach (['rpa/aps-rpa/downloads/JO.xlsx', 'rpa/accessories-rpa/downloads/CONTROLIST.xlsx'] as $f) {
    echo "=== $f ===\n";
    $c = file_get_contents($f);
    preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $c, $m);
    echo "Total tr: " . count($m[0]) . "\n";
    for ($i = 0; $i < min(2, count($m[0])); $i++) {
        preg_match_all('/<t[dh][^>]*>(.*?)<\/t[dh]>/is', $m[0][$i], $cells);
        foreach ($cells[1] as $cell) {
            echo trim(strip_tags($cell)) . " | ";
        }
        echo "\n";
    }
}

