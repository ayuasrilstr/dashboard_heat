<?php
$files = array(
    'rpa/engage-rpa/archive/2026-09/2026-09-01_32a_inflow.xlsx',
    'rpa/engage-rpa/archive/2026-09/2026-09-01_32a_outflow.xlsx',
    'rpa/engage-rpa/archive/2026-09/2026-09-01_32_inflow.xlsx',
    'rpa/engage-rpa/archive/2026-09/2026-09-01_32_outflow.xlsx',
);

foreach ($files as $f) {
    if (!file_exists($f)) {
        echo "$f not found\n";
        continue;
    }
    $c = file_get_contents($f);
    preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $c, $m);
    echo "=== $f (total tr: " . count($m[0]) . ") ===\n";
    for ($i = 0; $i < min(4, count($m[0])); $i++) {
        preg_match_all('/<(?:td|th)[^>]*>(.*?)<\/(?:td|th)>/is', $m[0][$i], $tds);
        $cols = array_map(function($x) { return trim(strip_tags($x)); }, $tds[1]);
        echo "Row $i: " . implode(' | ', array_slice($cols, 0, 14)) . "\n";
    }
}
