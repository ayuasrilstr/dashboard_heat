<?php
$db = new mysqli('localhost', 'root', '', 'db_dashboardgm');
$res = $db->query("SELECT MIN(id) as min_id, MAX(id) as max_id, COUNT(*) as cnt FROM tb_engage_transactions WHERE DATE(transaction_date) = '2026-09-15'");
$info = $res->fetch_assoc();
print_r($info);

// Let's test timestamp generation:
// 07:00:00 is 25200 sec, 11:15:00 is 40500 sec (diff = 15300 sec)
$min_id = (int) $info['min_id'];
$max_id = (int) $info['max_id'];
$range = max(1, $max_id - $min_id);

$start_sec = 7 * 3600; // 07:00:00
$end_sec = 11 * 3600 + 15 * 60; // 11:15:00
$time_span = $end_sec - $start_sec;

// Let's simulate
$res = $db->query("SELECT id, qty, (id - $min_id) / $range as frac FROM tb_engage_transactions WHERE DATE(transaction_date) = '2026-09-15'");
$hourly_sim = [];
while ($r = $res->fetch_assoc()) {
    $sec = (int) ($start_sec + $r['frac'] * $time_span);
    $hr = sprintf('%02d:00', (int) ($sec / 3600));
    if (!isset($hourly_sim[$hr])) $hourly_sim[$hr] = ['in' => 0, 'out' => 0];
    if ($r['qty'] > 0) $hourly_sim[$hr]['in'] += $r['qty'];
    else $hourly_sim[$hr]['out'] += abs($r['qty']);
}
ksort($hourly_sim);
echo "=== Simulated Hourly for 2026-09-15 ===\n";
foreach ($hourly_sim as $hr => $vals) {
    echo "$hr -> IN: {$vals['in']}, OUT: {$vals['out']}\n";
}

