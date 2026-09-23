<?php
$mysqli = new mysqli('localhost', 'root', '', 'db_dashboardgm');
$res = $mysqli->query("SELECT storage_nr, storage_2, count(*), sum(qty) FROM tb_engage_archieve GROUP BY storage_nr, storage_2");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
