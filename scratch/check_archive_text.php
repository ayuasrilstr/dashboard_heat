<?php
$mysqli = new mysqli('localhost', 'root', '', 'db_dashboardgm');
$res = $mysqli->query("SELECT text, count(*) FROM tb_engage_archieve GROUP BY text ORDER BY count(*) DESC LIMIT 10");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
