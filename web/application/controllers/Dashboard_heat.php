<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/Dashboard_base.php';

class Dashboard_heat extends Dashboard_base
{
    public function __construct()
    {
        parent::__construct();
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $this->load->model('Dashboard_model', 'dashboard');
    }

    public function index()
    {
        $calendar_authenticated = $this->calendar_authenticated();
        $date_range = $this->resolve_date_range();
        $selected_date_from = $date_range['from'];
        $selected_date_to = $date_range['to'];
        $dashboard_data = $this->dashboard->get_heat_dashboard_data($selected_date_from, $selected_date_to);
        $style_smv_catalog = $this->dashboard->get_heat_style_smv_catalog($selected_date_from, !empty($dashboard_data['list_orders']) ? $dashboard_data['list_orders'] : array(), !empty($dashboard_data['top_priority_orders']) ? $dashboard_data['top_priority_orders'] : array());
        $analytics_settings = $this->dashboard->get_heat_analytics_settings();
        $this->load->view('dashboards/heat/index', array(
            'title' => 'Dashboard Heat Transfer',
            'status_url' => site_url('dashboard_heat/api/status'),
            'qty_history_url' => site_url('dashboard_heat/api/qty-history'),
            'save_workdays_url' => site_url('dashboard_heat/api/save-workdays'),
            'run_url' => site_url('dashboard_heat/api/run-download'),
            'download_url' => site_url('dashboard_heat/download'),
            'material_to_load_download_url' => site_url('dashboard_heat/download_material_to_load'),
            'selected_date' => $selected_date_from,
            'selected_date_from' => $selected_date_from,
            'selected_date_to' => $selected_date_to,
            'style_smv_catalog' => $style_smv_catalog,
            'initial_dashboard_payload' => array(
                'calendar_authenticated' => $calendar_authenticated,
                'dashboard_data' => $dashboard_data,
                'style_smv_catalog' => $style_smv_catalog,
                'analytics_settings' => $analytics_settings,
                'server_time' => date('c'),
                'selected_date' => $selected_date_from,
                'selected_date_from' => $selected_date_from,
                'selected_date_to' => $selected_date_to,
            ),
        ));
    }

    public function admin()
    {
        if (!$this->portal_authenticated()) {
            redirect(site_url('dashboard'));
            return;
        }

        $date_range = $this->resolve_date_range();
        $selected_date_from = $date_range['from'];
        $selected_date_to = $date_range['to'];
        $user = isset($_SESSION['dashboard_portal_user']) && is_array($_SESSION['dashboard_portal_user'])
            ? $_SESSION['dashboard_portal_user']
            : array();
        $dashboard_data = $this->dashboard->get_heat_dashboard_data($selected_date_from, $selected_date_to);
        $style_smv_catalog = $this->dashboard->get_heat_style_smv_catalog($selected_date_from, !empty($dashboard_data['list_orders']) ? $dashboard_data['list_orders'] : array(), !empty($dashboard_data['top_priority_orders']) ? $dashboard_data['top_priority_orders'] : array());
        $analytics_settings = $this->dashboard->get_heat_analytics_settings();

        $this->load->view('dashboard_admin', array(
            'title' => 'Dashboard GM Admin',
            'dashboard_url' => site_url('dashboard_heat'),
            'portal_logout_url' => site_url('dashboard_heat/api/portal-logout'),
            'status_url' => site_url('dashboard_heat/api/status'),
            'qty_history_url' => site_url('dashboard_heat/api/qty-history'),
            'save_workdays_url' => site_url('dashboard_heat/api/save-workdays'),
            'save_style_smv_url' => site_url('dashboard_heat/api/save-style-smv'),
            'calendar_login_url' => site_url('dashboard_heat/api/calendar-login'),
            'run_url' => site_url('dashboard_heat/api/run-download'),
            'download_url' => site_url('dashboard_heat/download'),
            'material_to_load_download_url' => site_url('dashboard_heat/download_material_to_load'),
            'save_analytics_settings_url' => site_url('dashboard_heat/api/save-analytics-settings'),
            'users_url' => site_url('dashboard_heat/api/users'),
            'save_user_url' => site_url('dashboard_heat/api/save-user'),
            'delete_user_url' => site_url('dashboard_heat/api/delete-user'),
            'toggle_user_status_url' => site_url('dashboard_heat/api/toggle-user-status'),
            'selected_date' => $selected_date_from,
            'selected_date_from' => $selected_date_from,
            'selected_date_to' => $selected_date_to,
            'user' => $user,
            'style_smv_catalog' => $style_smv_catalog,
            'analytics_settings' => $analytics_settings,
            'initial_dashboard_payload' => array(
                'calendar_authenticated' => $this->calendar_authenticated(),
                'dashboard_data' => $dashboard_data,
                'style_smv_catalog' => $style_smv_catalog,
                'analytics_settings' => $analytics_settings,
                'server_time' => date('c'),
                'selected_date' => $selected_date_from,
                'selected_date_from' => $selected_date_from,
                'selected_date_to' => $selected_date_to,
            ),
        ));
    }

    public function api($action = NULL)
    {
        if ($action === 'status') {
            return $this->api_status();
        }

        if ($action === 'qty-history') {
            return $this->api_qty_history();
        }

        if ($action === 'run-download') {
            return $this->run_download();
        }

        if ($action === 'save-workdays') {
            return $this->save_workdays();
        }

        if ($action === 'save-style-smv') {
            return $this->save_style_smv();
        }

        if ($action === 'save-analytics-settings') {
            return $this->save_analytics_settings();
        }

        if ($action === 'calendar-login') {
            return $this->calendar_login();
        }

        if ($action === 'calendar-logout') {
            return $this->calendar_logout();
        }

        if ($action === 'portal-logout') {
            return $this->portal_logout();
        }

        if ($action === 'portal-login') {
            return $this->portal_login();
        }

        if ($action === 'users') {
            return $this->api_users();
        }

        if ($action === 'save-user') {
            return $this->api_save_user();
        }

        if ($action === 'delete-user') {
            return $this->api_delete_user();
        }

        if ($action === 'toggle-user-status') {
            return $this->api_toggle_user_status();
        }

        show_404();
    }

    public function api_qty_history()
    {
        $delivery_count = (int) $this->input->get('delivery_count', TRUE);
        return $this->json($this->dashboard->get_heat_qty_history($delivery_count));
    }

    public function api_status()
    {
        $date_range = $this->resolve_date_range();
        $selected_date_from = $date_range['from'];
        $selected_date_to = $date_range['to'];
        $dashboard_data = $this->dashboard->get_heat_dashboard_data($selected_date_from, $selected_date_to);
        $style_smv_catalog = $this->dashboard->get_heat_style_smv_catalog($selected_date_from, !empty($dashboard_data['list_orders']) ? $dashboard_data['list_orders'] : array(), !empty($dashboard_data['top_priority_orders']) ? $dashboard_data['top_priority_orders'] : array());
        $analytics_settings = $this->dashboard->get_heat_analytics_settings();
        return $this->json(array(
            'dashboard_data' => $dashboard_data,
            'style_smv_catalog' => $style_smv_catalog,
            'analytics_settings' => $analytics_settings,
            'server_time' => date('c'),
            'calendar_authenticated' => $this->calendar_authenticated(),
            'selected_date' => $selected_date_from,
            'selected_date_from' => $selected_date_from,
            'selected_date_to' => $selected_date_to,
        ));
    }

    public function run_download()
    {
        if (strtoupper($this->input->method(TRUE)) !== 'POST') {
            return $this->json(array('ok' => FALSE, 'message' => 'Method tidak valid.'), 405);
        }

        $result = $this->dashboard->run_download_once();
        return $this->json($result, $result['ok'] ? 202 : 500);
    }

    public function save_workdays()
    {
        if (strtoupper($this->input->method(TRUE)) !== 'POST') {
            return $this->json(array('ok' => FALSE, 'message' => 'Method tidak valid.'), 405);
        }

        if (!$this->calendar_authenticated()) {
            return $this->json(array('ok' => FALSE, 'message' => 'Login diperlukan untuk mengubah kalender.'), 401);
        }

        $payload = json_decode($this->input->raw_input_stream, TRUE);
        if (!is_array($payload)) {
            return $this->json(array('ok' => FALSE, 'message' => 'Payload tidak valid.'), 400);
        }

        $result = $this->dashboard->save_heat_holiday_settings(array(
            'holidays' => isset($payload['holidays']) ? $payload['holidays'] : array(),
            'half_days' => isset($payload['half_days']) ? $payload['half_days'] : array(),
            'quarter_days' => isset($payload['quarter_days']) ? $payload['quarter_days'] : array(),
            'work_days' => isset($payload['work_days']) ? $payload['work_days'] : array(),
        ));
        return $this->json($result, !empty($result['ok']) ? 200 : 500);
    }

    public function save_style_smv()
    {
        if (strtoupper($this->input->method(TRUE)) !== 'POST') {
            return $this->json(array('ok' => FALSE, 'message' => 'Method tidak valid.'), 405);
        }

        if (!$this->portal_authenticated()) {
            return $this->json(array('ok' => FALSE, 'message' => 'Login diperlukan untuk mengubah SMV.'), 401);
        }

        $payload = json_decode($this->input->raw_input_stream, TRUE);
        if (!is_array($payload) || !isset($payload['items']) || !is_array($payload['items'])) {
            return $this->json(array('ok' => FALSE, 'message' => 'Payload tidak valid.'), 400);
        }

        $result = $this->dashboard->save_heat_style_smv_settings($payload['items']);
        if (!empty($result['ok']) && (array_key_exists('direct_actual', $payload) || array_key_exists('double_machine_active', $payload) || array_key_exists('default_capacity_mode', $payload))) {
            $current_settings = $this->dashboard->get_heat_analytics_settings();
            $analytics_result = $this->dashboard->save_heat_analytics_settings(array(
                'visible_cards' => isset($current_settings['visible_cards']) && is_array($current_settings['visible_cards']) ? $current_settings['visible_cards'] : array(),
                'language' => isset($current_settings['language']) ? $current_settings['language'] : 'id',
                'direct_actual' => array_key_exists('direct_actual', $payload) ? $payload['direct_actual'] : (isset($current_settings['direct_actual']) ? $current_settings['direct_actual'] : NULL),
                'double_machine_active' => array_key_exists('double_machine_active', $payload) ? $payload['double_machine_active'] : (isset($current_settings['double_machine_active']) ? $current_settings['double_machine_active'] : 2),
                'default_capacity_mode' => array_key_exists('default_capacity_mode', $payload) ? $payload['default_capacity_mode'] : (isset($current_settings['default_capacity_mode']) ? $current_settings['default_capacity_mode'] : 'mesin'),
            ));

            if (empty($analytics_result['ok'])) {
                return $this->json(array('ok' => FALSE, 'message' => isset($analytics_result['message']) ? $analytics_result['message'] : 'Gagal menyimpan pengaturan.'), 500);
            }

            $result['analytics_settings'] = isset($analytics_result['analytics_settings']) ? $analytics_result['analytics_settings'] : $current_settings;
        }

        return $this->json($result, !empty($result['ok']) ? 200 : 500);
    }

    public function save_analytics_settings()
    {
        if (strtoupper($this->input->method(TRUE)) !== 'POST') {
            return $this->json(array('ok' => FALSE, 'message' => 'Method tidak valid.'), 405);
        }

        if (!$this->portal_authenticated()) {
            return $this->json(array('ok' => FALSE, 'message' => 'Login diperlukan untuk mengubah analytics.'), 401);
        }

        $payload = json_decode($this->input->raw_input_stream, TRUE);
        if (!is_array($payload)) {
            return $this->json(array('ok' => FALSE, 'message' => 'Payload tidak valid.'), 400);
        }

        $current_settings = $this->dashboard->get_heat_analytics_settings();
        $result = $this->dashboard->save_heat_analytics_settings(array(
            'visible_cards' => isset($payload['visible_cards']) ? $payload['visible_cards'] : array(),
            'language' => isset($payload['language']) ? $payload['language'] : 'id',
            'direct_actual' => array_key_exists('direct_actual', $payload)
                ? $payload['direct_actual']
                : (isset($current_settings['direct_actual']) ? $current_settings['direct_actual'] : NULL),
            'double_machine_active' => array_key_exists('double_machine_active', $payload)
                ? $payload['double_machine_active']
                : (isset($current_settings['double_machine_active']) ? $current_settings['double_machine_active'] : 2),
            'default_capacity_mode' => array_key_exists('default_capacity_mode', $payload)
                ? $payload['default_capacity_mode']
                : (isset($current_settings['default_capacity_mode']) ? $current_settings['default_capacity_mode'] : 'mesin'),
        ));
        return $this->json($result, !empty($result['ok']) ? 200 : 500);
    }

    public function calendar_login()
    {
        if (strtoupper($this->input->method(TRUE)) !== 'POST') {
            return $this->json(array('ok' => FALSE, 'message' => 'Method tidak valid.'), 405);
        }

        $payload = json_decode($this->input->raw_input_stream, TRUE);
        if (!is_array($payload)) {
            return $this->json(array('ok' => FALSE, 'message' => 'Payload tidak valid.'), 400);
        }

        $config = $this->dashboard_config();
        $expected_user = isset($config['dashboard_heat_calendar_user']) ? (string) $config['dashboard_heat_calendar_user'] : 'admin';
        $expected_password = isset($config['dashboard_heat_calendar_password']) ? (string) $config['dashboard_heat_calendar_password'] : 'admin';
        $username = isset($payload['username']) ? trim((string) $payload['username']) : '';
        $password = isset($payload['password']) ? (string) $payload['password'] : '';

        if (!hash_equals($expected_user, $username) || !hash_equals($expected_password, $password)) {
            return $this->json(array('ok' => FALSE, 'message' => 'Username atau password salah.'), 401);
        }

        $_SESSION['dashboard_heat_calendar_auth'] = TRUE;
        return $this->json(array('ok' => TRUE, 'message' => 'Login berhasil.'));
    }

    public function calendar_logout()
    {
        unset($_SESSION['dashboard_heat_calendar_auth']);
        unset($_SESSION['dashboard_portal_user']);
        return $this->json(array('ok' => TRUE, 'message' => 'Logout berhasil.'));
    }

    public function portal_logout()
    {
        unset($_SESSION['dashboard_heat_calendar_auth']);
        unset($_SESSION['dashboard_portal_user']);
        return $this->json(array('ok' => TRUE, 'message' => 'Logout berhasil.'));
    }

    public function portal_login()
    {
        if (strtoupper($this->input->method(TRUE)) !== 'POST') {
            return $this->json(array('ok' => FALSE, 'message' => 'Method tidak valid.'), 405);
        }

        $payload = json_decode($this->input->raw_input_stream, TRUE);
        if (!is_array($payload)) {
            return $this->json(array('ok' => FALSE, 'message' => 'Payload tidak valid.'), 400);
        }

        $username = isset($payload['username']) ? trim((string) $payload['username']) : '';
        $password = isset($payload['password']) ? (string) $payload['password'] : '';
        if ($username === '' || $password === '') {
            return $this->json(array('ok' => FALSE, 'message' => 'Username dan password wajib diisi.'), 400);
        }

        $db = $this->load->database('dashboard_heat_history', TRUE);
        if (!$db || empty($db->conn_id)) {
            return $this->json(array('ok' => FALSE, 'message' => 'Koneksi database tidak tersedia.'), 500);
        }

        $table = $this->portal_user_table();
        $this->ensure_portal_user_table($db, $table);
        $user_count = (int) $db->count_all_results($table);
        if ($user_count <= 0) {
            return $this->json(array('ok' => FALSE, 'message' => 'Belum ada akun portal di database.'), 401);
        }

        $query = $db->select('id, username, password, full_name, role, is_active')
            ->from($table)
            ->where('username', $username)
            ->order_by('id', 'ASC')
            ->limit(1)
            ->get();

        if (!$query || !$query->num_rows()) {
            return $this->json(array('ok' => FALSE, 'message' => 'Akun tidak ditemukan.'), 401);
        }

        $user = $query->row_array();
        if (empty($user['is_active'])) {
            return $this->json(array('ok' => FALSE, 'message' => 'Akun sedang nonaktif.'), 401);
        }

        $stored_password = isset($user['password']) ? (string) $user['password'] : '';
        if ($stored_password === '' || !hash_equals($stored_password, $password)) {
            return $this->json(array('ok' => FALSE, 'message' => 'Username atau password salah.'), 401);
        }

        $_SESSION['dashboard_heat_calendar_auth'] = TRUE;
        $_SESSION['dashboard_portal_user'] = array(
            'id' => isset($user['id']) ? (int) $user['id'] : 0,
            'username' => $user['username'],
            'full_name' => isset($user['full_name']) ? $user['full_name'] : '',
            'role' => isset($user['role']) ? $user['role'] : '',
        );

        return $this->json(array(
            'ok' => TRUE,
            'message' => 'Login berhasil.',
            'user' => $_SESSION['dashboard_portal_user'],
        ));
    }

    private function calendar_authenticated()
    {
        return !empty($_SESSION['dashboard_heat_calendar_auth']);
    }

    private function portal_authenticated()
    {
        return !empty($_SESSION['dashboard_portal_user']) && is_array($_SESSION['dashboard_portal_user']);
    }

    private function portal_user_table()
    {
        $config = $this->dashboard_config();
        $table = !empty($config['dashboard_portal_user_table']) ? (string) $config['dashboard_portal_user_table'] : 'dashboard_portal_users';
        return preg_match('/^[A-Za-z0-9_]+$/', $table) ? $table : 'dashboard_portal_users';
    }

    private function ensure_portal_user_table($db, $table)
    {
        static $created = array();
        if (isset($created[$table])) {
            return;
        }

        if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
            return;
        }

        $created[$table] = TRUE;
        $sql = 'CREATE TABLE IF NOT EXISTS `' . $table . '` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `username` varchar(100) NOT NULL,
            `password_hash` varchar(255) NOT NULL,
            `full_name` varchar(150) DEFAULT NULL,
            `role` varchar(50) DEFAULT NULL,
            `is_active` tinyint(1) NOT NULL DEFAULT 1,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_username` (`username`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8';
        $db->query($sql);
    }

    private function dashboard_config()
    {
        $config = array();
        $path = APPPATH . 'config' . DIRECTORY_SEPARATOR . 'dashboard.php';

        if (is_file($path)) {
            include $path;
        }

        return is_array($config) ? $config : array();
    }

    public function download()
    {
        $filename = (string) $this->input->get('file', TRUE);
        $path = $this->dashboard->get_download_path($filename);

        if (!$path) {
            show_404();
            return;
        }

        $this->load->helper('download');
        force_download(basename($path), file_get_contents($path));
    }
    public function download_material_to_load()
    {
        $date_range = $this->resolve_date_range();
        $selected_date_from = $date_range['from'];
        $selected_date_to = $date_range['to'];
        $dashboard = $this->dashboard->get_heat_dashboard_data($selected_date_from, $selected_date_to);

        if (empty($dashboard['available'])) {
            show_404();
            return;
        }

        $rows = isset($dashboard['material_to_load']) && is_array($dashboard['material_to_load'])
            ? $dashboard['material_to_load']
            : array();

        if (!$rows) {
            show_404();
            return;
        }

        $filename = 'material_to_load_' . date('Ymd_His') . '.xls';
        $period_label = !empty($dashboard['selected_period']) ? $dashboard['selected_period'] : (!empty($rows[0]['period']) ? (string) $rows[0]['period'] : 'Heat');
        $html = $this->build_material_to_load_export($rows, $period_label);

        $this->output
            ->set_content_type('application/vnd.ms-excel', 'UTF-8')
            ->set_header('Content-Disposition: attachment; filename="' . $filename . '"')
            ->set_header('Cache-Control: max-age=0, no-cache, no-store, must-revalidate')
            ->set_header('Pragma: no-cache')
            ->set_header('Expires: 0')
            ->set_output($html);
    }

    private function build_material_to_load_export(array $rows, $period_label = '')
    {
        $title = 'Material To Load - ' . (!empty($period_label) ? $period_label : 'Periode Tanggal');
        $generated_at = date('Y-m-d H:i:s');

        $html = array();
        $html[] = '<!doctype html>';
        $html[] = '<html>';
        $html[] = '<head>';
        $html[] = '<meta charset="utf-8">';
        $html[] = '<style>';
        $html[] = 'body{font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#102033;}';
        $html[] = 'table{border-collapse:collapse;width:100%;}';
        $html[] = 'th,td{border:1px solid #dbe4ee;padding:6px 8px;}';
        $html[] = 'th{background:#176b87;color:#fff;text-align:left;}';
        $html[] = '.num{text-align:right;}';
        $html[] = '</style>';
        $html[] = '</head>';
        $html[] = '<body>';
        $html[] = '<h2>' . html_escape($title) . '</h2>';
        if ($period_label !== '') {
            $html[] = '<div>Current Period: ' . html_escape($period_label) . '</div>';
        }
        $html[] = '<div>Generated at: ' . html_escape($generated_at) . '</div>';
        $html[] = '<div>Source: dashboard Heat Transfer</div>';
        $html[] = '<br>';
        $html[] = '<table>';
        $html[] = '<thead><tr><th>No.</th><th>Order</th><th>Style</th><th>Process</th><th>Item Nr</th><th>Tanggal</th><th class="num">Qty Ready</th><th>Source</th><th class="num">Target</th><th class="num">Aktual APS</th><th class="num">Aktual Engage</th></tr></thead>';
        $html[] = '<tbody>';

        for ($i = 0; $i < count($rows); $i++) {
            $row = $rows[$i];
            $process = isset($row['process']) && $row['process'] !== '' ? $row['process'] : (isset($row['route']) ? $row['route'] : '-');
            $html[] = '<tr>';
            $html[] = '<td class="num">' . ($i + 1) . '.</td>';
            $html[] = '<td>' . html_escape(isset($row['order']) ? $row['order'] : '') . '</td>';
            $html[] = '<td>' . html_escape(isset($row['style']) ? $row['style'] : '') . '</td>';
            $html[] = '<td>' . html_escape($process) . '</td>';
            $html[] = '<td>' . html_escape(isset($row['item']) ? $row['item'] : '') . '</td>';
            $html[] = '<td>' . html_escape(isset($row['delivery']) ? $row['delivery'] : '') . '</td>';
            $html[] = '<td class="num">' . number_format((float) (isset($row['qty_ready']) ? $row['qty_ready'] : 0), 0, ',', '.') . '</td>';
            $html[] = '<td>' . html_escape(isset($row['source']) ? $row['source'] : '-') . '</td>';
            $html[] = '<td class="num">' . number_format((float) (isset($row['qty_pdk']) ? $row['qty_pdk'] : 0), 0, ',', '.') . '</td>';
            $html[] = '<td class="num">' . number_format((float) (isset($row['qty_out_aps']) ? $row['qty_out_aps'] : 0), 0, ',', '.') . '</td>';
            $html[] = '<td class="num">' . number_format((float) (isset($row['qty_out_engage']) ? $row['qty_out_engage'] : 0), 0, ',', '.') . '</td>';
            $html[] = '</tr>';
        }

        $html[] = '</tbody>';
        $html[] = '</table>';
        $html[] = '</body>';
        $html[] = '</html>';

        return implode("
", $html);
    }

    private function resolve_date_range()
    {
        $get_from = $this->input->get('from', TRUE);
        if ($get_from === NULL) {
            $get_from = $this->input->get('date_from', TRUE);
        }
        $get_to = $this->input->get('to', TRUE);
        if ($get_to === NULL) {
            $get_to = $this->input->get('date_to', TRUE);
        }

        // Backward compatibility: ?date=YYYY-MM-DD
        $get_single = $this->input->get('date', TRUE);
        if (empty($get_from) && empty($get_to) && !empty($get_single)) {
            $get_from = $get_single;
            $get_to = $get_single;
        }

        $cookie_from = isset($_COOKIE['heatDateFrom']) ? $_COOKIE['heatDateFrom'] : (isset($_COOKIE['heatSelectedDate']) ? $_COOKIE['heatSelectedDate'] : NULL);
        $cookie_to = isset($_COOKIE['heatDateTo']) ? $_COOKIE['heatDateTo'] : (isset($_COOKIE['heatSelectedDate']) ? $_COOKIE['heatSelectedDate'] : NULL);

        $from = !empty($get_from) ? $get_from : (!empty($cookie_from) ? $cookie_from : NULL);
        $to = !empty($get_to) ? $get_to : (!empty($cookie_to) ? $cookie_to : NULL);

        $from_str = (is_string($from) && preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($from))) ? trim($from) : '';
        $to_str = (is_string($to) && preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($to))) ? trim($to) : '';

        if ($from_str === '' && $to_str === '') {
            $day_num = (int) date('j');
            if ($day_num <= 15) {
                $from_str = date('Y-m-01');
                $to_str = date('Y-m-15');
            } else {
                $from_str = date('Y-m-16');
                $to_str = date('Y-m-t');
            }
        } elseif ($from_str === '') {
            $from_str = $to_str;
        } elseif ($to_str === '') {
            $to_str = $from_str;
        }

        if ($from_str > $to_str) {
            $tmp = $from_str;
            $from_str = $to_str;
            $to_str = $tmp;
        }

        return array(
            'from' => $from_str,
            'to' => $to_str,
        );
    }

    public function api_users()
    {
        if (!$this->portal_authenticated()) {
            return $this->json(array('ok' => FALSE, 'message' => 'Login diperlukan.'), 401);
        }

        $db = $this->load->database('dashboard_heat_history', TRUE);
        if (!$db || empty($db->conn_id)) {
            return $this->json(array('ok' => FALSE, 'message' => 'Database tidak tersedia.'), 500);
        }

        $table = $this->portal_user_table();
        $this->ensure_portal_user_table($db, $table);

        $query = $db->select('id, username, full_name, role, is_active, last_login_at, created_at, updated_at')
            ->from($table)
            ->order_by('id', 'ASC')
            ->get();

        $users = $query ? $query->result_array() : array();
        return $this->json(array('ok' => TRUE, 'users' => $users));
    }

    public function api_save_user()
    {
        if (strtoupper($this->input->method(TRUE)) !== 'POST') {
            return $this->json(array('ok' => FALSE, 'message' => 'Method tidak valid.'), 405);
        }

        if (!$this->portal_authenticated()) {
            return $this->json(array('ok' => FALSE, 'message' => 'Login diperlukan.'), 401);
        }

        $payload = json_decode($this->input->raw_input_stream, TRUE);
        if (!is_array($payload)) {
            return $this->json(array('ok' => FALSE, 'message' => 'Payload tidak valid.'), 400);
        }

        $db = $this->load->database('dashboard_heat_history', TRUE);
        if (!$db || empty($db->conn_id)) {
            return $this->json(array('ok' => FALSE, 'message' => 'Database tidak tersedia.'), 500);
        }

        $table = $this->portal_user_table();
        $this->ensure_portal_user_table($db, $table);

        $id = isset($payload['id']) && is_numeric($payload['id']) ? (int) $payload['id'] : 0;
        $username = isset($payload['username']) ? trim((string) $payload['username']) : '';
        $full_name = isset($payload['full_name']) ? trim((string) $payload['full_name']) : '';
        $role_input = isset($payload['role']) ? strtolower(trim((string) $payload['role'])) : 'viewer';
        $allowed_roles = array('admin', 'planning', 'production', 'viewer');
        $role = in_array($role_input, $allowed_roles, TRUE) ? $role_input : 'viewer';
        $is_active = isset($payload['is_active']) ? (int) $payload['is_active'] : 1;
        $password = isset($payload['password']) ? (string) $payload['password'] : '';

        if ($username === '') {
            return $this->json(array('ok' => FALSE, 'message' => 'Username tidak boleh kosong.'), 400);
        }

        if (!preg_match('/^[A-Za-z0-9_.-]+$/', $username)) {
            return $this->json(array('ok' => FALSE, 'message' => 'Username hanya boleh huruf, angka, titik, minus, atau garis bawah.'), 400);
        }

        if ($id <= 0) {
            // CREATE
            if ($password === '') {
                return $this->json(array('ok' => FALSE, 'message' => 'Password wajib diisi untuk akun baru.'), 400);
            }
            if (strlen($password) < 4) {
                return $this->json(array('ok' => FALSE, 'message' => 'Password minimal 4 karakter.'), 400);
            }

            // Check duplicate username
            $exists = $db->from($table)->where('username', $username)->count_all_results();
            if ($exists > 0) {
                return $this->json(array('ok' => FALSE, 'message' => 'Username sudah digunakan.'), 400);
            }

            $insert_data = array(
                'username' => $username,
                'password' => $password,
                'full_name' => $full_name !== '' ? $full_name : $username,
                'role' => $role !== '' ? $role : 'admin',
                'is_active' => $is_active ? 1 : 0,
            );

            $ok = $db->insert($table, $insert_data);
            if (!$ok) {
                return $this->json(array('ok' => FALSE, 'message' => 'Gagal membuat akun baru.'), 500);
            }

            return $this->json(array('ok' => TRUE, 'message' => 'Akun berhasil dibuat.', 'id' => $db->insert_id()));
        } else {
            // UPDATE
            $exists = $db->from($table)->where('username', $username)->where('id !=', $id)->count_all_results();
            if ($exists > 0) {
                return $this->json(array('ok' => FALSE, 'message' => 'Username sudah digunakan akun lain.'), 400);
            }

            $update_data = array(
                'username' => $username,
                'full_name' => $full_name,
                'role' => $role !== '' ? $role : 'admin',
                'is_active' => $is_active ? 1 : 0,
            );

            if ($password !== '') {
                if (strlen($password) < 4) {
                    return $this->json(array('ok' => FALSE, 'message' => 'Password baru minimal 4 karakter.'), 400);
                }
                $update_data['password'] = $password;
            }

            $db->where('id', $id)->update($table, $update_data);
            return $this->json(array('ok' => TRUE, 'message' => 'Akun berhasil diperbarui.'));
        }
    }

    public function api_delete_user()
    {
        if (strtoupper($this->input->method(TRUE)) !== 'POST') {
            return $this->json(array('ok' => FALSE, 'message' => 'Method tidak valid.'), 405);
        }

        if (!$this->portal_authenticated()) {
            return $this->json(array('ok' => FALSE, 'message' => 'Login diperlukan.'), 401);
        }

        $payload = json_decode($this->input->raw_input_stream, TRUE);
        $id = isset($payload['id']) ? (int) $payload['id'] : 0;
        if ($id <= 0) {
            return $this->json(array('ok' => FALSE, 'message' => 'ID akun tidak valid.'), 400);
        }

        $current_user_id = isset($_SESSION['dashboard_portal_user']['id']) ? (int) $_SESSION['dashboard_portal_user']['id'] : 0;
        if ($id === $current_user_id) {
            return $this->json(array('ok' => FALSE, 'message' => 'Tidak dapat menghapus akun yang sedang Anda gunakan.'), 400);
        }

        $db = $this->load->database('dashboard_heat_history', TRUE);
        $table = $this->portal_user_table();
        $this->ensure_portal_user_table($db, $table);

        $db->where('id', $id)->delete($table);
        return $this->json(array('ok' => TRUE, 'message' => 'Akun berhasil dihapus.'));
    }

    public function api_toggle_user_status()
    {
        if (strtoupper($this->input->method(TRUE)) !== 'POST') {
            return $this->json(array('ok' => FALSE, 'message' => 'Method tidak valid.'), 405);
        }

        if (!$this->portal_authenticated()) {
            return $this->json(array('ok' => FALSE, 'message' => 'Login diperlukan.'), 401);
        }

        $payload = json_decode($this->input->raw_input_stream, TRUE);
        $id = isset($payload['id']) ? (int) $payload['id'] : 0;
        $is_active = !empty($payload['is_active']) ? 1 : 0;

        if ($id <= 0) {
            return $this->json(array('ok' => FALSE, 'message' => 'ID akun tidak valid.'), 400);
        }

        $current_user_id = isset($_SESSION['dashboard_portal_user']['id']) ? (int) $_SESSION['dashboard_portal_user']['id'] : 0;
        if ($id === $current_user_id && !$is_active) {
            return $this->json(array('ok' => FALSE, 'message' => 'Tidak dapat menonaktifkan akun yang sedang digunakan.'), 400);
        }

        $db = $this->load->database('dashboard_heat_history', TRUE);
        $table = $this->portal_user_table();
        $this->ensure_portal_user_table($db, $table);

        $db->where('id', $id)->update($table, array('is_active' => $is_active));
        return $this->json(array('ok' => TRUE, 'message' => 'Status akun berhasil diperbarui.'));
    }

}
