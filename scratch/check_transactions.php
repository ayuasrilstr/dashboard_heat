<?php
$mysqli = new mysqli('localhost', 'root', '', 'db_dashboardgm');
$res = $mysqli->query("SELECT * FROM tb_engage_transactions LIMIT 2");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
