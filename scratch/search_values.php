<?php
$mysqli = new mysqli('localhost', 'root', '', 'db_dashboardgm');
$res = $mysqli->query("SELECT * FROM dashboard_heat_history");
while ($r = $res->fetch_assoc()) {
    $text = json_encode($r);
    if (strpos($text, '13155') !== false || strpos($text, '16854') !== false || strpos($text, '13560') !== false) {
        echo "Found in dashboard_heat_history: type={$r['history_type']}, date={$r['history_date']}, del={$r['delivery_count']}\n";
    }
}

// Also check all files in rpa/
echo "Done checking DB.\n";
