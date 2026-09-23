<?php
$mysqli = new mysqli('localhost', 'root', '', 'db_dashboardgm');
$res = $mysqli->query("SELECT storage_nr, storage_2, DATE(transaction_date) as d, count(*), sum(qty) FROM tb_engage_archieve GROUP BY storage_nr, storage_2, d ORDER BY d DESC");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
