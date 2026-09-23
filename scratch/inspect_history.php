<?php
$db = new mysqli('localhost', 'root', '', 'db_dashboardgm');
echo "=== COLUMNS OF dashboard_heat_history ===\n";
$res = $db->query("SHOW COLUMNS FROM dashboard_heat_history");
if ($res) {
    while($r = $res->fetch_assoc()) echo "{$r['Field']} ({$r['Type']})\n";
}

echo "\n=== SAMPLE dashboard_heat_history ===\n";
$res = $db->query("SELECT * FROM dashboard_heat_history ORDER BY id DESC LIMIT 5");
if ($res) {
    while($r = $res->fetch_assoc()) print_r($r);
}

echo "\n=== COLUMNS OF dashboard_engage_history ===\n";
$res = $db->query("SHOW COLUMNS FROM dashboard_engage_history");
if ($res) {
    while($r = $res->fetch_assoc()) echo "{$r['Field']} ({$r['Type']})\n";
}

echo "\n=== SAMPLE dashboard_engage_history ===\n";
$res = $db->query("SELECT * FROM dashboard_engage_history ORDER BY id DESC LIMIT 5");
if ($res) {
    while($r = $res->fetch_assoc()) print_r($r);
}

