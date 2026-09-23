<?php
$_SERVER['CI_ENV'] = 'development';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['argv'] = ['index.php', 'dashboard_heat', 'api_data_dump'];

// In CodeIgniter CLI, call via controller method or create a test method in Dashboard_heat
require_once 'index.php';
