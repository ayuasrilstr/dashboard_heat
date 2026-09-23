<?php
$m = new mysqli('localhost', 'root', '', 'db_dashboardgm');
if ($m->connect_error) die($m->connect_error);
$res = $m->query('SHOW TABLES');
while ($row = $res->fetch_row()) {
    echo $row[0] . "\n";
}

