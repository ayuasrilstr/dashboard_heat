<?php
$mysqli = new mysqli('localhost', 'root', '', 'db_dashboardgm');
$res = $mysqli->query("SELECT * FROM tb_engage_archieve WHERE transaction_date >= '2026-09-03' LIMIT 5");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
