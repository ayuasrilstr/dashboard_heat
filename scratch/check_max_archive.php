<?php
$mysqli = new mysqli('localhost', 'root', '', 'db_dashboardgm');
$res = $mysqli->query("SELECT min(id), max(id), count(*) FROM tb_engage_archieve")->fetch_assoc();
print_r($res);
$res2 = $mysqli->query("SELECT id, transaction_date, storage_nr, storage_2, qty, text, user_creator, created_at FROM tb_engage_archieve ORDER BY id DESC LIMIT 5");
while ($r = $res2->fetch_assoc()) {
    print_r($r);
}
