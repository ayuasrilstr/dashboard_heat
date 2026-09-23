<?php
define('ENVIRONMENT', 'development');
$_SERVER['CI_ENV'] = 'development';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

// Set up CodeIgniter environment
ob_start();
require_once __DIR__ . '/../index.php';
ob_end_clean();

$CI =& get_instance();
$CI->load->model('Dashboard_model', 'dashboard');

foreach ([1, 2, 4, 6] as $dc) {
    echo "=== DELIVERY COUNT $dc ===\n";
    $data = $CI->dashboard->get_heat_dashboard_data($dc);
    if (!empty($data['output_vs_capacity'])) {
        foreach ($data['output_vs_capacity'] as $row) {
            echo "  {$row['label']}: in={$row['input']}, out={$row['output']}, cap={$row['capacity']}, dem={$row['demand']}\n";
        }
    } else {
        echo "  no output_vs_capacity or empty\n";
    }
}
