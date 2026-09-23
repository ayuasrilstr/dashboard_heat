<?php
$html = file_get_contents('http://localhost/dashboard_gm/index.php/dashboard_heat');
if (preg_match('/const initialDashboardPayload\s*=\s*(\{.*?\});\s*const initialDeliveryCountValue/s', $html, $matches)) {
    $payload = json_decode($matches[1], true);
    echo "=== PARSED initialDashboardPayload ===\n";
    if (isset($payload['dashboard_data']['output_vs_capacity'])) {
        foreach ($payload['dashboard_data']['output_vs_capacity'] as $row) {
            echo "  {$row['label']}: in={$row['input']}, out={$row['output']}, cap={$row['capacity']}, dem={$row['demand']}\n";
        }
    } else {
        echo "No output_vs_capacity in payload!\n";
    }
} else {
    echo "Could not find initialDashboardPayload in HTML!\n";
}
