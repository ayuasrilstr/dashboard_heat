<?php
$tempDir = sys_get_temp_dir();
echo "Temp dir: $tempDir\n";
$files = glob($tempDir . '/*.json');
echo "Found " . count($files) . " json files in temp.\n";
foreach ($files as $f) {
    $content = file_get_contents($f);
    if (strpos($content, 'KX3655') !== false || strpos($content, '0903150517') !== false) {
        echo "FOUND matching payload: $f (" . filesize($f) . " bytes)\n";
    }
}

