<?php
define('BASEPATH', '1');
define('APPPATH', __DIR__ . '/../web/application/');
define('VIEWPATH', __DIR__ . '/../web/application/views/');
define('ENVIRONMENT', 'development');

require_once __DIR__ . '/../web/system/core/Model.php';
// We can test CI or run via curl/http!
