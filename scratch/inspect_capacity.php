<?php
$mysqli = new mysqli('localhost', 'root', '', 'db_dashboardgm');
$res = $mysqli->query("SELECT * FROM dashboard_heat_history WHERE history_type = 'capacity' ORDER BY id DESC LIMIT 15");
echo "=== CAPACITY HISTORY ===\n";
while ($row = $res->fetch_assoc()) {
    echo "del: {$row['delivery_count']} | date: {$row['history_date']} | cap: {$row['capacity']} | dem: {$row['demand']} | sisa: {$row['sisa_hari_kerja']} | bal: {$row['balance_qty']}\n";
}

$res2 = $mysqli->query("SELECT id, history_date, delivery_count, payload FROM dashboard_heat_history WHERE history_type = 'summary' ORDER BY id DESC LIMIT 4");
echo "=== SUMMARY PAYLOADS ===\n";
while ($row = $res2->fetch_assoc()) {
    $payload = json_decode($row['payload'], true);
    echo "ID: {$row['id']}, date: {$row['history_date']}, del: {$row['delivery_count']}\n";
    if (isset($payload['output_vs_capacity'])) {
        foreach ($payload['output_vs_capacity'] as $item) {
            echo "   " . $item['label'] . ": out=" . ($item['output'] ?? 'null') . ", in=" . ($item['input'] ?? 'null') . ", cap=" . ($item['capacity'] ?? 'null') . "\n";
        }
    }
}
