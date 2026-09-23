<?php
$mysqli = new mysqli('localhost', 'root', '', 'db_dashboardgm');
$res = $mysqli->query("SHOW COLUMNS FROM tb_engage_archieve");
while ($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
echo "--- Sample row ---\n";
$res2 = $mysqli->query("SELECT * FROM tb_engage_archieve LIMIT 2");
while ($row = $res2->fetch_assoc()) {
    print_r($row);
}
