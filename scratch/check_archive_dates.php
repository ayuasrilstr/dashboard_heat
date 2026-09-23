<?php
$mysqli = new mysqli('localhost', 'root', '', 'db_dashboardgm');
$res = $mysqli->query("SELECT DATE(transaction_date) as t_date, DATE(created_at) as c_date, storage_nr, count(*) FROM tb_engage_archieve GROUP BY t_date, c_date, storage_nr ORDER BY t_date DESC");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
