<?php
$mysqli = new mysqli('localhost', 'root', '', 'db_dashboardgm');

echo "=== dashboard_heat_analytics_settings ===\n";
$res = $mysqli->query("SELECT * FROM dashboard_heat_analytics_settings");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        echo "key: {$r['setting_key']} | json: {$r['payload_json']}\n";
    }
}

echo "\n=== dashboard_heat_history capacity rows ===\n";
$res = $mysqli->query("SELECT history_type, history_date, delivery_count, qty_pdk, qty_output, balance_qty, capacity, input_qty, output_qty FROM dashboard_heat_history WHERE history_type = 'capacity' ORDER BY history_date DESC, delivery_count ASC LIMIT 25");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        echo "date: {$r['history_date']} | del: {$r['delivery_count']} | cap: {$r['capacity']} | in: {$r['input_qty']} | out: {$r['output_qty']} | pdk: {$r['qty_pdk']} | bal: {$r['balance_qty']}\n";
    }
}

echo "\n=== tb_engage_transactions count per date ===\n";
$res = $mysqli->query("SELECT DATE(transaction_date) as d, COUNT(*) as cnt, SUM(CASE WHEN storage_nr='32A' THEN 1 ELSE 0 END) as 32a_cnt FROM tb_engage_transactions GROUP BY d ORDER BY d DESC LIMIT 10");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        echo "Date: {$r['d']} | Total: {$r['cnt']} | 32a: {$r['32a_cnt']}\n";
    }
}

echo "\n=== tb_engage_archieve count per date ===\n";
$res = $mysqli->query("SELECT DATE(transaction_date) as d, COUNT(*) as cnt, SUM(CASE WHEN storage_nr='32A' THEN 1 ELSE 0 END) as 32a_cnt FROM tb_engage_archieve GROUP BY d ORDER BY d DESC LIMIT 10");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        echo "Date: {$r['d']} | Total: {$r['cnt']} | 32a: {$r['32a_cnt']}\n";
    }
}
