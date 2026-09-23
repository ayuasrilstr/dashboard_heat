<?php
define('BASEPATH', '1');
define('APPPATH', 'web/application/');

// Let's create a test harness or inspect DB
$mysqli = new mysqli('localhost', 'root', '', 'db_dashboardgm');
if ($mysqli->connect_error) {
    echo "MySQL connect error: " . $mysqli->connect_error . PHP_EOL;
} else {
    echo "MySQL connected." . PHP_EOL;
    $res = $mysqli->query("SELECT DATE(transaction_date) as d, COUNT(*) as cnt, MIN(TIME(transaction_date)) as min_t, MAX(TIME(transaction_date)) as max_t FROM tb_engage_archieve GROUP BY DATE(transaction_date) ORDER BY d DESC LIMIT 10");
    if ($res) {
        print_r($res->fetch_all(MYSQLI_ASSOC));
    }
    $res2 = $mysqli->query("SELECT DATE(transaction_date) as t_date, HOUR(transaction_date) as t_hr, SUM(CASE WHEN qty > 0 THEN qty ELSE 0 END) as in_qty, SUM(CASE WHEN qty < 0 THEN ABS(qty) ELSE 0 END) as out_qty FROM tb_engage_transactions GROUP BY t_date, t_hr ORDER BY t_date DESC, t_hr ASC LIMIT 30");
    if ($res2) {
        while ($row = $res2->fetch_assoc()) {
            echo "{$row['t_date']} {$row['t_hr']}:00 -> IN: {$row['in_qty']}, OUT: {$row['out_qty']}\n";
        }
    }
}
