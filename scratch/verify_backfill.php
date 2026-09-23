<?php
$mysqli = new mysqli('localhost', 'root', '', 'db_dashboardgm');
echo "=== tb_engage_archieve (storage 32a) ===\n";
$res = $mysqli->query("SELECT DATE(transaction_date) as t_date, storage_nr, count(*), sum(case when qty > 0 then qty else 0 end) as in_qty, sum(case when qty < 0 then abs(qty) else 0 end) as out_qty FROM tb_engage_archieve WHERE storage_nr = '32a' GROUP BY t_date ORDER BY t_date DESC");
while ($r = $res->fetch_assoc()) {
    printf("Date: %s | Rows: %4d | In: %6d | Out: %6d\n", $r['t_date'], $r['count(*)'], $r['in_qty'], $r['out_qty']);
}
echo "\n=== tb_engage_transactions (storage 32a) ===\n";
$res2 = $mysqli->query("SELECT DATE(transaction_date) as t_date, storage_nr, count(*), sum(case when qty > 0 then qty else 0 end) as in_qty, sum(case when qty < 0 then abs(qty) else 0 end) as out_qty FROM tb_engage_transactions WHERE storage_nr = '32a' GROUP BY t_date ORDER BY t_date DESC");
while ($r = $res2->fetch_assoc()) {
    printf("Date: %s | Rows: %4d | In: %6d | Out: %6d\n", $r['t_date'], $r['count(*)'], $r['in_qty'], $r['out_qty']);
}
