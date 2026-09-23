<?php
define('ENVIRONMENT', 'development');
ob_start();
require_once dirname(__DIR__) . '/index.php';
ob_end_clean();

$CI =& get_instance();
$CI->load->model('Dashboard_model');
$catalog = $CI->Dashboard_model->get_heat_style_smv_catalog();

echo "RUNNING STYLES COUNT: " . count($catalog['running_styles']) . PHP_EOL;

$total_proc = 0;
$total_smv = 0;
$filled_cnt = 0;

foreach ($catalog['running_styles'] as $s) {
    $smv = (float)($s['smv'] ?? 0);
    $p_smvs = $s['process_smvs'] ?? [];
    $p_cnt = (int)($s['process_count'] ?? (count($p_smvs) ?: 1));
    echo sprintf(" - Style: %-25s | Proses: %2d | SMV: %6.2f | Status: %s\n", 
        $s['style'], 
        $p_cnt, 
        $smv, 
        $smv > 0 ? 'TERISI' : 'KOSONG'
    );
    if ($smv > 0) {
        $total_smv += $smv;
        $total_proc += $p_cnt;
        $filled_cnt++;
    }
}

echo "--------------------------------------------------------\n";
echo "TOTAL PROSES (TERISI): " . $total_proc . PHP_EOL;
echo "TOTAL SMV: " . number_format($total_smv, 2) . PHP_EOL;
echo "RATA-RATA PROSES: " . ($filled_cnt > 0 ? number_format($total_proc / $filled_cnt, 1) : 0) . PHP_EOL;
echo "RATA-RATA SMV: " . ($filled_cnt > 0 ? number_format($total_smv / $filled_cnt, 2) : 0) . PHP_EOL;
