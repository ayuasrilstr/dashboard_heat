<?php
define('BASEPATH', '1');
define('APPPATH', 'web/application/');

$db = new mysqli('localhost', 'root', '', 'db_dashboardgm');
$res = $db->query("
    SELECT transaction_date, qty, text, cost_center, item_nr, udef_10
    FROM tb_engage_transactions 
    WHERE DATE(transaction_date) = '2026-09-15'
");

$in_hourly = [];
$out_hourly = [];
$seen = [];

while ($r = $res->fetch_assoc()) {
    $date = $r['transaction_date'];
    $ts = strtotime($date);
    $hr = date('H:00', $ts);
    $qty = abs((float) $r['qty']);
    $order = $r['cost_center'];
    $identity = $date . "\n" . $order . "\n" . $qty . "\n" . $r['udef_10'];
    if (isset($seen[$identity])) continue;
    $seen[$identity] = true;

    if ((float) $r['qty'] > 0) {
        $in_hourly[$hr] = ($in_hourly[$hr] ?? 0) + $qty;
    } else {
        $out_hourly[$hr] = ($out_hourly[$hr] ?? 0) + $qty;
    }
}

ksort($in_hourly);
ksort($out_hourly);

echo "=== Hourly Aggregation for 2026-09-15 ===\n";
$all_hours = array('07:00', '08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00');
foreach ($all_hours as $hr) {
    $inp = $in_hourly[$hr] ?? 0;
    $outp = $out_hourly[$hr] ?? 0;
    echo "$hr -> IN: $inp | OUT: $outp | KAP: 1341\n";
}

