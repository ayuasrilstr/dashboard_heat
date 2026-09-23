<?php
$res = file_get_contents('http://localhost/dashboard_gm/index.php/dashboard_heat/api/qty-history?delivery_count=1');
echo "QTY HISTORY (delivery=1):\n" . $res . "\n\n";

$status = file_get_contents('http://localhost/dashboard_gm/index.php/dashboard_heat/api/status?delivery_count=1');
$statusData = json_decode($status, true);
echo "STATUS DATA DATES:\n";
if (isset($statusData['dashboard_data']['output_vs_capacity'])) {
    echo "output_vs_capacity labels: " . json_encode(array_column($statusData['dashboard_data']['output_vs_capacity'], 'label')) . "\n";
    foreach ($statusData['dashboard_data']['output_vs_capacity'] as $item) {
        echo " - " . $item['label'] . ": out=" . ($item['output'] ?? 0) . ", in=" . ($item['input'] ?? 0) . ", cap=" . ($item['capacity'] ?? 0) . "\n";
    }
}
if (isset($statusData['dashboard_data']['qty_pdk_vs_output'])) {
    echo "qty_pdk_vs_output labels: " . json_encode(array_column($statusData['dashboard_data']['qty_pdk_vs_output'], 'label')) . "\n";
}
