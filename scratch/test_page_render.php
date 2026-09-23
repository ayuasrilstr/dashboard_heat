<?php
$ch = curl_init('http://localhost/dashboard_heat/index.php/dashboard_heat');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
$info = curl_getinfo($ch);
echo "HTTP " . $info['http_code'] . "\n";
echo "Page size: " . strlen($res) . " bytes\n";
$checks = [
    'listOrderFilterProcess',
    'listOrderFilterPcs',
    'listOrderFilterReset',
    'listOrderCountBadge',
    'Process',
];
foreach ($checks as $c) {
    echo "Check '$c': " . (strpos($res, $c) !== false ? 'FOUND' : 'NOT FOUND') . "\n";
}

