<?php
$mysqli = new mysqli('localhost', 'root', '', 'db_dashboardgm');
$r = $mysqli->query("SELECT storage_nr, DATE(transaction_date) as d, count(*), sum(qty) FROM tb_engage_archieve GROUP BY storage_nr, d ORDER BY d DESC");
echo "ARCHIEVE:\n";
while ($row = $r->fetch_assoc()) {
    print_r($row);
}
$r = $mysqli->query("SELECT storage_nr, DATE(transaction_date) as d, count(*), sum(qty) FROM tb_engage_transactions GROUP BY storage_nr, d ORDER BY d DESC");
echo "TRANSACTIONS:\n";
while ($row = $r->fetch_assoc()) {
    print_r($row);
}
