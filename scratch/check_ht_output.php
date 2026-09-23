<?php
$mysqli = new mysqli('localhost', 'root', '', 'db_dashboardgm');
$res = $mysqli->query("SELECT storage_nr, storage_2, qty, text, count(*) FROM tb_engage_archieve WHERE text NOT LIKE '%[CSDB]%' GROUP BY storage_nr, storage_2, qty > 0, text LIMIT 20");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
