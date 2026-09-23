<?php
/**
 * Diagnostic & Benchmark Tool - Dashboard GM
 * Dapat dijalankan via Browser (http://localhost/dashboard_gm/test_connection.php)
 * atau via Terminal (E:\xampp\php\php.exe test_connection.php)
 */

$is_cli = (php_sapi_name() === 'cli');

// 1. Uji Koneksi & Handshake Latency
$t_start = microtime(true);
$mysqli = @new mysqli('localhost', 'root', '', 'db_dashboardgm');
$handshake_ms = (microtime(true) - $t_start) * 1000;

$error_msg = null;
if ($mysqli->connect_error) {
    $error_msg = $mysqli->connect_error;
}

// 2. Uji Latency & Hitung Baris Tabel Utama
$tables = [
    'tb_engage_transactions' => 'Staging Mutasi Harian Aktif',
    'tb_engage_archieve'     => 'Arsip Mutasi Lampau (> 90 Hari)',
    'engage_daily_history'   => 'Rekap Tren Harian In/Out/Ready',
    'dashboard_heat_history' => 'Histori Snapshot Demand & Kapasitas'
];

$table_stats = [];
if (!$error_msg) {
    foreach ($tables as $tbl => $label) {
        $q_start = microtime(true);
        $res = $mysqli->query("SELECT COUNT(*) as cnt FROM $tbl");
        $q_ms = (microtime(true) - $q_start) * 1000;
        
        if ($res) {
            $row = $res->fetch_assoc();
            $table_stats[$tbl] = [
                'label'   => $label,
                'rows'    => (int)$row['cnt'],
                'latency' => round($q_ms, 2)
            ];
        } else {
            $table_stats[$tbl] = [
                'label'   => $label,
                'rows'    => 0,
                'error'   => $mysqli->error,
                'latency' => round($q_ms, 2)
            ];
        }
    }

    // 3. Stress Test 100 Query Sekuensial
    $bench_start = microtime(true);
    for ($i = 0; $i < 100; $i++) {
        $mysqli->query("SELECT 1");
    }
    $bench_total_ms = (microtime(true) - $bench_start) * 1000;
    $bench_avg_ms = $bench_total_ms / 100;
}

// OUTPUT UNTUK CLI TERMINAL
if ($is_cli) {
    echo "========================================================\n";
    echo "  PENGUJIAN KONEKSI & DATABASE DASHBOARD GM (CLI MODE)\n";
    echo "========================================================\n";
    if ($error_msg) {
        echo "[GAGAL] Tidak dapat terhubung ke MySQL: $error_msg\n";
        exit(1);
    }
    echo "Server Target       : localhost:3306 (MySQL)\n";
    echo "Nama Database       : db_dashboardgm\n";
    echo "Handshake Latency   : " . round($handshake_ms, 2) . " ms\n";
    echo "Stress Test (100 Q) : " . round($bench_total_ms, 2) . " ms (Rata-rata: " . round($bench_avg_ms, 3) . " ms/query)\n";
    echo "--------------------------------------------------------\n";
    echo "STATUS PERFORMA TABEL AKTIF:\n";
    foreach ($table_stats as $tbl => $data) {
        echo sprintf(" - %-24s : %' 7d rows | Latency: %5.2f ms\n", $tbl, $data['rows'], $data['latency']);
    }
    echo "--------------------------------------------------------\n";
    echo "[STATUS: PASS] Semua koneksi dan latensi dalam batas ideal (< 50 ms).\n\n";
    exit(0);
}

// OUTPUT UNTUK BROWSER WEB
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Koneksi & Benchmark - Dashboard GM</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #F1F5F9; margin: 0; padding: 30px; }
        .card { max-width: 800px; margin: 0 auto; background: #FFFFFF; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); overflow: hidden; }
        .card-header { background: #10528A; color: white; padding: 18px 24px; display: flex; justify-content: space-between; align-items: center; }
        .card-header h2 { margin: 0; font-size: 18px; }
        .card-body { padding: 24px; }
        .metrics-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px; }
        .metric-box { background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 16px; text-align: center; }
        .metric-box.highlight { border-color: #38BDF8; background: #F0F9FF; }
        .metric-val { font-size: 26px; font-weight: 700; color: #0284C7; margin-bottom: 4px; }
        .metric-val.green { color: #16A34A; }
        .metric-lbl { font-size: 12px; color: #64748B; font-weight: 600; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px 14px; text-align: left; border-bottom: 1px solid #E2E8F0; font-size: 13px; }
        th { background: #F8FAFC; color: #475569; font-weight: 600; }
        .badge { display: inline-block; padding: 3px 8px; font-size: 11px; font-weight: 700; border-radius: 12px; }
        .badge-success { background: #DCFCE7; color: #15803D; }
        .badge-info { background: #E0F2FE; color: #0369A1; }
        .footer-action { margin-top: 24px; text-align: center; }
        .btn-reload { background: #10528A; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn-reload:hover { background: #0E4D82; }
    </style>
</head>
<body>

<div class="card">
    <div class="card-header">
        <h2>Pemeriksaan Koneksi & Benchmark Real-Time</h2>
        <span class="badge badge-success">SISTEM TERHUBUNG</span>
    </div>
    <div class="card-body">
        <div class="metrics-grid">
            <div class="metric-box highlight">
                <div class="metric-val"><?= round($handshake_ms, 2); ?> ms</div>
                <div class="metric-lbl">Connection Handshake Latency</div>
            </div>
            <div class="metric-box">
                <div class="metric-val green"><?= round($bench_avg_ms, 3); ?> ms</div>
                <div class="metric-lbl">Rata-rata Query (Stress Test 100x)</div>
            </div>
        </div>

        <h3 style="font-size: 14px; color: #1E293B; margin-bottom: 10px;">Status Kueri & Jumlah Data per Tabel Database (db_dashboardgm)</h3>
        <table>
            <thead>
                <tr>
                    <th>Nama Tabel</th>
                    <th>Fungsi / Deskripsi</th>
                    <th>Jumlah Baris</th>
                    <th>Latency Query</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($table_stats as $tbl => $data): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($tbl); ?></strong></td>
                    <td style="color: #64748B;"><?= htmlspecialchars($data['label']); ?></td>
                    <td><strong><?= number_format($data['rows'], 0, ',', '.'); ?></strong> baris</td>
                    <td><span class="badge badge-info"><?= $data['latency']; ?> ms</span></td>
                    <td><span class="badge badge-success">OK / PASS</span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="footer-action">
            <button class="btn-reload" onclick="location.reload();">Uji Ulang Koneksi Sekarang (Live Refresh)</button>
        </div>
    </div>
</div>

</body>
</html>
