<?php
define('BASEPATH', '1');
define('APPPATH', 'web/application/');
require_once 'web/application/models/Dashboard_model.php';
$model = new Dashboard_model();

$ref = new ReflectionClass('Dashboard_model');
$method = $ref->getMethod('heat_rpa_sources');
$method->setAccessible(true);
$sources = $method->invoke($model);

echo "=== SOURCES ===\n";
print_r($sources);

foreach (['aps', 'accessories'] as $k) {
    if (!empty($sources[$k]) && file_exists($sources[$k])) {
        echo "=== $k file: {$sources[$k]} ===\n";
        $c = file_get_contents($sources[$k]);
        preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $c, $m);
        echo "Total tr: " . count($m[0]) . "\n";
        for ($i = 0; $i < min(3, count($m[0])); $i++) {
            preg_match_all('/<t[dh][^>]*>(.*?)<\/t[dh]>/is', $m[0][$i], $cells);
            foreach ($cells[1] as $cell) {
                echo trim(strip_tags($cell)) . " | ";
            }
            echo "\n";
        }
    }
}
