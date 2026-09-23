<?php
$mysqli = new mysqli('localhost', 'root', '', 'db_dashboardgm');
$res = $mysqli->query("SELECT * FROM dashboard_heat_history WHERE history_type = 'capacity' AND delivery_count = 4 ORDER BY history_date ASC");
while ($r = $res->fetch_assoc()) {
    echo "date: {$r['history_date']} | cap: {$r['capacity']} | sisa: {$r['total_capacity']} | bal: {$r['balance_qty']} | json: {$r['snapshot_json']}\n";
}
