<?php
$ch = curl_init('http://localhost/dashboard_heat/index.php/dashboard_heat/api/status');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
$info = curl_getinfo($ch);
echo "HTTP " . $info['http_code'] . PHP_EOL;
$json = json_decode($res, true);
if ($json && isset($json['dashboard_data']['list_orders'])) {
    $orders = $json['dashboard_data']['list_orders'];
    echo "Found " . count($orders) . " list orders.\n";
    if (count($orders) > 0) {
        echo "First row keys: " . implode(', ', array_keys($orders[0])) . "\n";
        print_r(array_slice($orders, 0, 3));
    }
} else {
    echo "No list_orders or JSON error. Response:\n" . substr($res, 0, 500) . "\n";
}
