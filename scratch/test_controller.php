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

// Test for delivery counts
foreach ([1, 2, 4, 6] as $dc) {
    echo "\n=== CONTROLLER TEST DC $dc ===\n";
    $dashboard_data = $CI->dashboard->get_heat_dashboard_data($dc);
    echo "available: " . ($dashboard_data['available'] ? 'true' : 'false') . "\n";
    if (!empty($dashboard_data['output_vs_capacity'])) {
        foreach ($dashboard_data['output_vs_capacity'] as $item) {
            echo "  {$item['label']} => in: {$item['input']}, out: {$item['output']}, cap: {$item['capacity']}, dem: {$item['demand']}\n";
        }
    }
}
