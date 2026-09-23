<?php
$db = new mysqli('localhost', 'root', '', 'db_dashboardgm');
require_once 'web/application/models/Dashboard_model.php';

// Instantiate model or test query
$model = new class {
    use \Dashboard_model_trait; // wait, Dashboard_model is a class extending CI_Model
};
