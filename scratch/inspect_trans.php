<?php
$mysqli = new mysqli('localhost', 'root', '', 'db_dashboardgm');
$res = $mysqli->query("SELECT * FROM tb_engage_transactions");
echo "=== tb_engage_transactions ===\n";
while ($r = $res->fetch_assoc()) {
    echo "Date: {$r['transaction_date']} | storage: {$r['storage_nr']} | qty: {$r['qty']} | order: {$r['cost_center']} | item: {$r['item_nr']} | text: {$r['text']} | udef4: {$r['udef_4']} | udef5: {$r['udef_5']} | udef6: {$r['udef_6']}\n";
}
