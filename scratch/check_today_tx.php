<?php
$db = new mysqli('localhost', 'root', '', 'db_dashboardgm');
$res = $db->query("SHOW COLUMNS FROM tb_engage_transactions");
$cols = [];
while ($r = $res->fetch_assoc()) $cols[] = $r['Field'];
echo "Columns: " . implode(', ', $cols) . "\n";

$res = $db->query("SELECT * FROM tb_engage_transactions LIMIT 1");
print_r($res->fetch_assoc());

$res = $db->query("SELECT DISTINCT user_creator, DATE(created_at), TIME(created_at) FROM tb_engage_transactions");
while ($r = $res->fetch_assoc()) print_r($r);

$res = $db->query("SELECT * FROM tb_engage_transactions ORDER BY id DESC LIMIT 1");
print_r($res->fetch_assoc());

$res = $db->query("SELECT COUNT(DISTINCT cost_center), MIN(id), MAX(id) FROM tb_engage_transactions");
print_r($res->fetch_assoc());

