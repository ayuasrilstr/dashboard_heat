<?php
$mysqli = new mysqli('localhost', 'root', '', 'db_dashboardgm');
$r1 = $mysqli->query("SHOW CREATE TABLE tb_engage_transactions")->fetch_assoc();
$r2 = $mysqli->query("SHOW CREATE TABLE tb_engage_archieve")->fetch_assoc();
echo "=== tb_engage_transactions ===\n" . $r1['Create Table'] . "\n\n";
echo "=== tb_engage_archieve ===\n" . $r2['Create Table'] . "\n\n";
