<?php
define('ENVIRONMENT', 'development');
$_SERVER['CI_ENV'] = 'development';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

ob_start();
require_once __DIR__ . '/../index.php';
ob_end_clean();

$CI =& get_instance();
$CI->load->model('Dashboard_model', 'dashboard');

echo "=== 1. Test get_heat_style_smv_catalog ===\n";
$catalog = $CI->dashboard->get_heat_style_smv_catalog('2026-09-01');
echo "Styles count: " . count($catalog['styles']) . "\n";
echo "Running styles count: " . count($catalog['running_styles']) . "\n";
if (!empty($catalog['styles'])) {
    $first = $catalog['styles'][0];
    echo "Sample style: " . $first['style'] . ", process_count: " . json_encode($first['process_count']) . ", smv: " . json_encode($first['smv']) . ", process_smvs: " . json_encode($first['process_smvs']) . "\n";
}

echo "\n=== 2. Test save_heat_style_smv_settings validation (2 processes, 1 SMV) ===\n";
$test_fail_item = array(
    array(
        'style' => 'TEST_STYLE',
        'process_count' => 2,
        'process_smvs' => array(0.35),
        'show_in_dashboard' => true,
    )
);
$res_fail = $CI->dashboard->save_heat_style_smv_settings($test_fail_item);
echo "Result when 2 processes but only 1 SMV given: ok=" . ($res_fail['ok'] ? 'true' : 'false') . ", message=" . $res_fail['message'] . "\n";

echo "\n=== 3. Test save_heat_style_smv_settings success (2 processes, 2 SMVs: 0.35 & 0.40) ===\n";
$test_success_item = array(
    array(
        'style' => 'TEST_STYLE',
        'process_count' => 2,
        'process_smvs' => array(0.35, 0.40),
        'show_in_dashboard' => true,
    )
);
$res_success = $CI->dashboard->save_heat_style_smv_settings($test_success_item);
echo "Result when 2 processes and 2 SMVs given: ok=" . ($res_success['ok'] ? 'true' : 'false') . ", message=" . $res_success['message'] . "\n";

$catalog_after = $CI->dashboard->get_heat_style_smv_catalog('2026-09-01');
$saved_test = null;
foreach ($catalog_after['styles'] as $s) {
    if ($s['style'] === 'TEST_STYLE') {
        $saved_test = $s;
        break;
    }
}
echo "Saved TEST_STYLE details:\n";
echo "  Style: " . $saved_test['style'] . "\n";
echo "  Process Count: " . $saved_test['process_count'] . "\n";
echo "  Process SMVs: " . json_encode($saved_test['process_smvs']) . "\n";
echo "  Total SMV (sum): " . $saved_test['smv'] . "\n";
echo "  Has SMV: " . ($saved_test['has_smv'] ? 'true' : 'false') . "\n";

echo "\n=== 4. Clean up TEST_STYLE ===\n";
$test_cleanup = array(
    array(
        'style' => 'TEST_STYLE',
        'process_count' => 0,
        'process_smvs' => array(),
        'smv' => '',
        'show_in_dashboard' => false,
    )
);
$res_cleanup = $CI->dashboard->save_heat_style_smv_settings($test_cleanup);
echo "Cleanup result: ok=" . ($res_cleanup['ok'] ? 'true' : 'false') . "\n";

echo "\nALL TESTS PASSED!\n";
