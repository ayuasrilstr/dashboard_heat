<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['dashboard_heat_excel_file'] = '';
$config['dashboard_heat_unc_excel_file'] = '';

$config['dashboard_heat_data_dir'] = '';
$config['dashboard_heat_unc_dir'] = '';

// Direktori root sumber data Excel RPA
$config['dashboard_heat_rpa_dir'] = 'E:\\xampp\\htdocs\\dashboard_gm\\rpa';

// Tanggal merah bawaan. Kalender dashboard tetap bisa menambah libur, 1/2 hari,
// dan Minggu kerja dari modal Kalender Libur.
$config['dashboard_heat_holidays'] = array();

// Login untuk mengubah Kalender Libur.
$config['dashboard_heat_calendar_user'] = 'admin';
$config['dashboard_heat_calendar_password'] = 'admin';

// Login portal berbasis database.
$config['dashboard_portal_user_table'] = 'tbl_login';
