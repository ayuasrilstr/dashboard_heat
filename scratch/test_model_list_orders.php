<?php
define('BASEPATH', '1');
define('APPPATH', 'web/application/');

require_once 'web/application/models/Dashboard_model.php';

// Mock CI_Model
class TestDashboardModel extends Dashboard_model {
    public function __construct() {
        // mock
    }
}

