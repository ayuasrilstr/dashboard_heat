<?php
$mysqli = new mysqli('localhost', 'root', '', 'db_dashboardgm');
$r = $mysqli->query("SHOW CREATE TABLE dashboard_heat_history")->fetch_assoc();
echo $r['Create Table'] . "\n\n";

$r2 = $mysqli->query("SHOW CREATE TABLE dashboard_heat_analytics_settings")->fetch_assoc();
echo $r2['Create Table'] . "\n\n";
