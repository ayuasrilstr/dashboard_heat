<?php
$mysqli = new mysqli('localhost', 'root', '', 'db_dashboardgm');

echo "=== engage_daily_history in MySQL ===\n";
$res = $mysqli->query("SELECT * FROM engage_daily_history ORDER BY date DESC LIMIT 10");
while ($r = $res->fetch_assoc()) {
    printf("Date: %s | In: %6d | Out: %6d | Ready: %6d\n", $r['date'], $r['input_qty'], $r['output_qty'], $r['ready_qty']);
}
