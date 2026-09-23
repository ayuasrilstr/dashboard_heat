<?php
foreach ([1, 2, 4, 6] as $dc) {
    $res = @file_get_contents("http://localhost/dashboard_gm/index.php/dashboard_heat/api/status?delivery_count=$dc");
    if (!$res) {
        echo "DC $dc: failed to fetch\n";
        continue;
    }
    $data = json_decode($res, true);
    echo "=== DELIVERY COUNT $dc ===\n";
    if (isset($data['dashboard_data']['output_vs_capacity'])) {
        foreach ($data['dashboard_data']['output_vs_capacity'] as $item) {
            echo "  " . $item['label'] . ": out=" . ($item['output'] ?? 'null') . ", in=" . ($item['input'] ?? 'null') . ", cap=" . ($item['capacity'] ?? 'null') . ", demand=" . ($item['demand'] ?? 'null') . "\n";
        }
    } else {
        echo "  no output_vs_capacity\n";
    }
}
