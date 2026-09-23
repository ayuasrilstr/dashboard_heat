<?php
$ch = curl_init('http://localhost/dashboard_heat/index.php/dashboard_heat/download_material_to_load');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
$info = curl_getinfo($ch);
echo "HTTP " . $info['http_code'] . "\n";
echo "Export size: " . strlen($res) . " bytes\n";
echo "Has '<th>Process</th>': " . (strpos($res, '<th>Process</th>') !== false ? 'YES' : 'NO') . "\n";
if (preg_match('/<tr>.*?<\/tr>/s', $res, $m)) {
    echo "Sample row:\n" . substr($m[0], 0, 300) . "\n";
}

