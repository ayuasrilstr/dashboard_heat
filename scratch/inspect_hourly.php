<?php
$db = new mysqli('localhost', 'root', '', 'db_dashboardgm');
$today = date('Y-m-d');

echo "=== Distinct transaction_date for today ===\n";
$res = $db->query("SELECT DISTINCT transaction_date FROM tb_engage_transactions WHERE DATE(transaction_date) = '$today'");
while($r = $res->fetch_row()) echo $r[0] . "\n";

echo "\n=== Distinct created_at for today ===\n";
$res = $db->query("SELECT DISTINCT created_at FROM tb_engage_transactions WHERE DATE(created_at) = '$today' OR DATE(transaction_date) = '$today'");
while($r = $res->fetch_row()) echo $r[0] . "\n";

echo "\n=== COUNT per hour of created_at today ===\n";
$res = $db->query("SELECT HOUR(created_at) as hr, COUNT(*) as cnt, SUM(qty) as total_qty FROM tb_engage_transactions WHERE DATE(transaction_date) = '$today' GROUP BY HOUR(created_at)");
while($r = $res->fetch_assoc()) {
    echo "Hour: " . $r['hr'] . " -> Count: " . $r['cnt'] . ", Total Qty: " . $r['total_qty'] . "\n";
}

echo "\n=== Check tb_engage_archieve ===\n";
$res = $db->query("SELECT COUNT(*) FROM tb_engage_archieve");
$r = $res->fetch_row();
echo "Archive rows: " . $r[0] . "\n";

echo "\n=== Check engage_daily_history ===\n";
$res = $db->query("SELECT * FROM engage_daily_history ORDER BY id DESC LIMIT 5");
if ($res) {
    while($r = $res->fetch_assoc()) {
        print_r($r);
    }
}

