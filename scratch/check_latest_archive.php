<?php
$mysqli = new mysqli('localhost', 'root', '', 'db_dashboardgm');
$res = $mysqli->query("SELECT id, transaction_date, storage_nr, storage_2, qty, text, user_creator, created_at FROM tb_engage_archieve WHERE id >= 275000 LIMIT 10");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
