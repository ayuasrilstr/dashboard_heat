<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard_model extends CI_Model
{
    private $reports = array(
        array('key' => '32a_inflow', 'label' => '32a Inflow', 'storage' => '32a', 'direction' => '1', 'filename' => '32a_inflow.xlsx'),
        array('key' => '32a_outflow', 'label' => '32a Outflow', 'storage' => '32a', 'direction' => '2', 'filename' => '32a_outflow.xlsx'),
    );

    private $months = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'June', 'July', 'Aug', 'Sept', 'Oct', 'Nov', 'Dec');
    private $heat_history_db = NULL;
    private $heat_history_db_ready = FALSE;

    private function root_path()
    {
        $root = realpath(APPPATH . '..' . DIRECTORY_SEPARATOR . '..');
        return $root ?: realpath(APPPATH . '..');
    }

    private function data_dir()
    {
        foreach ($this->data_dir_candidates() as $path) {
            if (is_dir($path)) {
                return $path;
            }
        }

        return $this->data_dir_candidates()[0];
    }

    private function data_file()
    {
        foreach ($this->data_file_candidates() as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return NULL;
    }

    private function data_file_diagnostics()
    {
        $items = array();

        foreach ($this->data_file_candidates() as $path) {
            $items[] = array(
                'path' => $path,
                'exists' => is_file($path),
                'readable' => is_readable($path),
            );
        }

        return $items;
    }

    private function data_file_candidates()
    {
        $dashboard_config = $this->dashboard_config();
        $paths = array();

        if (getenv('DASHBOARD_HEAT_EXCEL_FILE')) {
            $paths[] = getenv('DASHBOARD_HEAT_EXCEL_FILE');
        }

        if (!empty($dashboard_config['dashboard_heat_excel_file'])) {
            $paths[] = $dashboard_config['dashboard_heat_excel_file'];
        }
        if (!empty($dashboard_config['dashboard_heat_unc_excel_file'])) {
            $paths[] = $dashboard_config['dashboard_heat_unc_excel_file'];
        }

        return array_values(array_unique($paths));
    }

    private function data_dir_candidates()
    {
        $env_path = getenv('DASHBOARD_HEAT_DATA_DIR');
        $paths = array();

        if ($env_path) {
            $paths[] = rtrim($env_path, "\\/");
        }

        $dashboard_config = $this->dashboard_config();
        if (!empty($dashboard_config['dashboard_heat_data_dir'])) {
            $paths[] = rtrim($dashboard_config['dashboard_heat_data_dir'], "\\/");
        }
        if (!empty($dashboard_config['dashboard_heat_unc_dir'])) {
            $paths[] = rtrim($dashboard_config['dashboard_heat_unc_dir'], "\\/");
        }

        $paths = array_merge($paths, $this->rpa_data_dirs());

        if (getenv('DASHBOARD_HEAT_LOCAL_FALLBACK')) {
            $paths[] = $this->root_path() . DIRECTORY_SEPARATOR . 'rpa' . DIRECTORY_SEPARATOR . 'engage-rpa' . DIRECTORY_SEPARATOR . 'downloads';
        }

        return array_values(array_unique($paths));
    }
    private function rpa_root_path()
    {
        $dashboard_config = $this->dashboard_config();
        if (!empty($dashboard_config['dashboard_heat_rpa_dir']) && is_dir($dashboard_config['dashboard_heat_rpa_dir'])) {
            return rtrim($dashboard_config['dashboard_heat_rpa_dir'], "\\/");
        }

        $env_path = getenv('DASHBOARD_HEAT_RPA_DIR');
        if ($env_path && is_dir($env_path)) {
            return rtrim($env_path, "\\/");
        }

        $gm_rpa = 'E:' . DIRECTORY_SEPARATOR . 'xampp' . DIRECTORY_SEPARATOR . 'htdocs' . DIRECTORY_SEPARATOR . 'dashboard_gm' . DIRECTORY_SEPARATOR . 'rpa';
        if (is_dir($gm_rpa)) {
            return $gm_rpa;
        }

        return $this->root_path() . DIRECTORY_SEPARATOR . 'rpa';
    }

    private function rpa_root_candidates()
    {
        $roots = array();

        $dashboard_config = $this->dashboard_config();
        if (!empty($dashboard_config['dashboard_heat_rpa_dir'])) {
            $roots[] = rtrim($dashboard_config['dashboard_heat_rpa_dir'], "\\/");
        }

        $env_path = getenv('DASHBOARD_HEAT_RPA_DIR');
        if ($env_path) {
            $roots[] = rtrim($env_path, "\\/");
        }

        $roots[] = 'E:' . DIRECTORY_SEPARATOR . 'xampp' . DIRECTORY_SEPARATOR . 'htdocs' . DIRECTORY_SEPARATOR . 'dashboard_gm' . DIRECTORY_SEPARATOR . 'rpa';
        $roots[] = $this->root_path() . DIRECTORY_SEPARATOR . 'rpa';

        return array_values(array_filter(array_unique($roots), 'is_dir'));
    }

    private function rpa_data_dirs()
    {
        $dirs = array();
        foreach ($this->rpa_root_candidates() as $rpa_root) {
            foreach ($this->rpa_module_names() as $module) {
                $dirs[] = $rpa_root . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . 'downloads';
                $dirs[] = $rpa_root . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . 'archive';
            }
        }

        return array_values(array_filter(array_unique($dirs), 'is_dir'));
    }

    private function rpa_module_names()
    {
        return array('aps-rpa', 'accessories-rpa', 'engage-rpa');
    }

    private function rpa_module_dir($module, $subdir = NULL)
    {
        $path = $this->rpa_root_path() . DIRECTORY_SEPARATOR . $module;
        if ($subdir !== NULL && $subdir !== '') {
            $path .= DIRECTORY_SEPARATOR . $subdir;
        }

        return $path;
    }

    private function all_data_dirs()
    {
        return array_values(array_filter($this->data_dir_candidates(), 'is_dir'));
    }

    private function matching_files_in_trees(array $base_dirs, array $name_patterns)
    {
        $regexes = array();
        foreach ($name_patterns as $pattern) {
            $quoted = preg_quote($pattern, '#');
            $regexes[] = '#^' . str_replace('\\*', '.*', $quoted) . '$#i';
        }

        $files = array();
        foreach ($base_dirs as $base_dir) {
            if (!is_dir($base_dir)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($base_dir, FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $fileinfo) {
                if (!$fileinfo->isFile()) {
                    continue;
                }

                $filename = $fileinfo->getFilename();
                if (preg_match('/^~\\$/', $filename)) {
                    continue;
                }

                foreach ($regexes as $regex) {
                    if (preg_match($regex, $filename)) {
                        $files[] = $fileinfo->getPathname();
                        break;
                    }
                }
            }
        }

        $files = array_values(array_unique($files));
        if (!$files) {
            return array();
        }

        usort($files, function ($a, $b) {
            return filemtime($b) <=> filemtime($a);
        });

        return $files;
    }

    private function latest_matching_file_in_trees(array $base_dirs, array $name_patterns)
    {
        $files = $this->matching_files_in_trees($base_dirs, $name_patterns);
        return $files ? $files[0] : NULL;
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

    private function heat_history_table()
    {
        return 'dashboard_heat_history';
    }

    private function engage_daily_history_table()
    {
        return 'engage_daily_history';
    }

    private function heat_history_connection($force_reconnect = FALSE)
    {
        if (!$force_reconnect && isset($this->heat_history_db_ready) && $this->heat_history_db_ready && $this->heat_history_db && !empty($this->heat_history_db->conn_id)) {
            if (@mysqli_ping($this->heat_history_db->conn_id)) {
                return $this->heat_history_db;
            }
        }

        $this->heat_history_db_ready = FALSE;
        if ($this->heat_history_db) {
            @$this->heat_history_db->close();
            $this->heat_history_db = NULL;
        }

        $db = $this->load->database('dashboard_heat_history', TRUE);
        if (!$db || empty($db->conn_id)) {
            $this->heat_history_db = NULL;
            return NULL;
        }

        $this->heat_history_db = $db;
        $this->heat_history_db_ready = TRUE;
        $this->ensure_heat_history_table();
        return $this->heat_history_db;
    }

    private function ensure_heat_history_table()
    {
        $db = $this->heat_history_db;
        if (!$db || empty($db->conn_id)) {
            return FALSE;
        }

        $sql = "CREATE TABLE IF NOT EXISTS `{$this->heat_history_table()}` (
            `history_type` varchar(32) NOT NULL,
            `history_date` date NOT NULL,
            `delivery_count` tinyint unsigned NOT NULL DEFAULT 4,
            `qty_pdk` bigint NOT NULL DEFAULT 0,
            `qty_output` bigint NOT NULL DEFAULT 0,
            `balance_qty` bigint NOT NULL DEFAULT 0,
            `total_capacity` bigint NOT NULL DEFAULT 0,
            `capacity` bigint NOT NULL DEFAULT 0,
            `input_qty` bigint NOT NULL DEFAULT 0,
            `output_qty` bigint NOT NULL DEFAULT 0,
            `snapshot_json` longtext NOT NULL,
            `captured_at` datetime DEFAULT NULL,
            `updated_at` datetime NOT NULL,
            PRIMARY KEY (`history_type`, `history_date`, `delivery_count`),
            KEY `idx_history_date` (`history_date`),
            KEY `idx_delivery_count` (`delivery_count`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8";

        return (bool) $db->query($sql);
    }

    private function upsert_heat_history_row($history_type, $history_date, $delivery_count, array $row)
    {
        $db = $this->heat_history_connection();
        if (!$db || empty($db->conn_id)) {
            return FALSE;
        }

        $snapshot_json = '';
        if (isset($row['snapshot_json'])) {
            $snapshot_json = is_string($row['snapshot_json']) ? $row['snapshot_json'] : json_encode($row['snapshot_json'], JSON_UNESCAPED_UNICODE);
        } else {
            $snapshot_json = json_encode($row, JSON_UNESCAPED_UNICODE);
        }

        $payload = array(
            'history_type' => (string) $history_type,
            'history_date' => $history_date,
            'delivery_count' => (int) $delivery_count,
            'qty_pdk' => isset($row['qty_pdk']) ? (int) $row['qty_pdk'] : 0,
            'qty_output' => isset($row['qty_output']) ? (int) $row['qty_output'] : 0,
            'balance_qty' => isset($row['balance_qty']) ? (int) $row['balance_qty'] : 0,
            'total_capacity' => isset($row['total_capacity']) ? (int) $row['total_capacity'] : (isset($row['balance_qty']) ? (int) $row['balance_qty'] : 0),
            'capacity' => isset($row['capacity']) ? (int) $row['capacity'] : 0,
            'input_qty' => isset($row['input_qty']) ? (int) $row['input_qty'] : 0,
            'output_qty' => isset($row['output_qty']) ? (int) $row['output_qty'] : (isset($row['qty_output']) ? (int) $row['qty_output'] : 0),
            'snapshot_json' => $snapshot_json,
            'captured_at' => isset($row['captured_at']) ? $row['captured_at'] : NULL,
            'updated_at' => date('Y-m-d H:i:s'),
        );

        try {
            $res = $db->replace($this->heat_history_table(), $payload);
            if ($res) {
                return $res;
            }
        } catch (\Throwable $e) {
            log_message('error', 'upsert_heat_history_row error: ' . $e->getMessage());
        }

        // Retry once with a fresh connection if connection dropped or failed
        $db = $this->heat_history_connection(TRUE);
        if (!$db || empty($db->conn_id)) {
            return FALSE;
        }

        try {
            return $db->replace($this->heat_history_table(), $payload);
        } catch (\Throwable $e) {
            log_message('error', 'upsert_heat_history_row retry error: ' . $e->getMessage());
            return FALSE;
        }
    }

    private function read_heat_history_rows($history_type, $delivery_count = NULL)
    {
        $db = $this->heat_history_connection();
        if (!$db || empty($db->conn_id)) {
            return array();
        }

        $query = $db->from($this->heat_history_table())->where('history_type', (string) $history_type);
        if ($delivery_count !== NULL) {
            $query->where('delivery_count', (int) $delivery_count);
        }

        $rows = $query->order_by('history_date', 'ASC')->get()->result_array();
        $items = array();
        foreach ($rows as $row) {
            $snapshot = array();
            if (!empty($row['snapshot_json'])) {
                $decoded = json_decode($row['snapshot_json'], TRUE);
                if (is_array($decoded)) {
                    $snapshot = $decoded;
                }
            }

            $items[$row['history_date']] = array_merge($snapshot, array(
                'history_date' => $row['history_date'],
                'delivery_count' => (int) $row['delivery_count'],
                'qty_pdk' => (int) $row['qty_pdk'],
                'qty_output' => (int) $row['qty_output'],
                'balance_qty' => (int) $row['balance_qty'],
                'total_capacity' => (int) $row['total_capacity'],
                'capacity' => (int) $row['capacity'],
                'input_qty' => (int) $row['input_qty'],
                'output_qty' => (int) $row['output_qty'],
                'captured_at' => $row['captured_at'],
            ));
        }

        return $items;
    }

    private function read_engage_daily_history_rows()
    {
        $db = $this->heat_history_connection();
        if (!$db || empty($db->conn_id)) {
            return array();
        }

        $query = $db->from('engage_daily_history')->order_by('date', 'ASC')->get();
        if (!$query) {
            return array();
        }

        $items = array();
        foreach ($query->result_array() as $row) {
            if (empty($row['date'])) {
                continue;
            }

            $day = substr((string) $row['date'], 0, 10);
            $items[$day] = array(
                'date' => $day,
                'input_qty' => isset($row['input_qty']) ? (int) $row['input_qty'] : 0,
                'output_qty' => isset($row['output_qty']) ? (int) $row['output_qty'] : 0,
                'ready_qty' => isset($row['ready_qty']) ? (int) $row['ready_qty'] : 0,
            );
        }

        return $items;
    }

    private function has_engage_db_data()
    {
        $db = $this->heat_history_connection();
        if (!$db || empty($db->conn_id)) {
            return FALSE;
        }

        $query = $db->query("SELECT (SELECT COUNT(*) FROM `tb_engage_transactions`) + (SELECT COUNT(*) FROM `tb_engage_archieve`) AS total");
        if ($query) {
            $row = $query->row_array();
            return isset($row['total']) && (int) $row['total'] > 0;
        }

        return FALSE;
    }

    private function latest_engage_db_time_iso()
    {
        $db = $this->heat_history_connection();
        if (!$db || empty($db->conn_id)) {
            return NULL;
        }

        $query = $db->query("SELECT MAX(`created_at`) AS `max_time` FROM (SELECT `created_at` FROM `tb_engage_transactions` UNION ALL SELECT `created_at` FROM `tb_engage_archieve`) AS t");
        if ($query) {
            $row = $query->row_array();
            if (!empty($row['max_time'])) {
                return date('c', strtotime($row['max_time']));
            }
        }

        return date('c');
    }

    private function read_engage_report_from_db($storage, $direction = 'inflow')
    {
        $db = $this->heat_history_connection();
        if (!$db || empty($db->conn_id)) {
            return array('headers' => array(), 'rows' => array());
        }

        $headers = array(
            '#', 'Date', 'Storage Nr', 'Location Nr', 'Item Nr', 'Item Name', 'Item Name 2',
            'Serial Nr', 'Address Nr', 'Address Name', 'Storage 2', 'Location 2', 'Qty',
            'Unit', 'Text', 'Cost Center', 'Prod. Nr',
            'Udef 1', 'Udef 2', 'Udef 3', 'Udef 4', 'Udef 5',
            'Udef 6', 'Udef 7', 'Udef 8', 'Udef 9', 'Udef 10', 'User Creator'
        );

        $qty_condition = ($direction === 'inflow') ? '`qty` > 0' : '`qty` < 0';
        $storage_clean = $db->escape_str($storage);

        $sql = "
            SELECT 
                `transaction_date`, `storage_nr`, '' AS `location_nr`, `item_nr`, `item_name`, `item_name_2`,
                `serial_nr`, `address_nr`, `address_name`, `storage_2`, `location_2`, `qty`,
                `unit`, `text`, `cost_center`, `prod_nr`,
                `udef_1`, `udef_2`, `udef_3`, `udef_4`, `udef_5`,
                `udef_6`, `udef_7`, `udef_8`, `udef_9`, `udef_10`, `user_creator`
            FROM `tb_engage_archieve`
            WHERE `storage_nr` = '{$storage_clean}' AND {$qty_condition}
            UNION ALL
            SELECT 
                `transaction_date`, `storage_nr`, '' AS `location_nr`, `item_nr`, `item_name`, `item_name_2`,
                `serial_nr`, `address_nr`, `address_name`, `storage_2`, `location_2`, `qty`,
                `unit`, `text`, `cost_center`, `prod_nr`,
                `udef_1`, `udef_2`, `udef_3`, `udef_4`, `udef_5`,
                `udef_6`, `udef_7`, `udef_8`, `udef_9`, `udef_10`, `user_creator`
            FROM `tb_engage_transactions`
            WHERE `storage_nr` = '{$storage_clean}' AND {$qty_condition}
        ";

        $query = $db->query($sql);
        if (!$query) {
            return array('headers' => array(), 'rows' => array());
        }

        $rows = array();
        $num = 1;
        foreach ($query->result_array() as $r) {
            $rows[] = array(
                (string) $num++,
                isset($r['transaction_date']) ? (string) $r['transaction_date'] : '',
                isset($r['storage_nr']) ? (string) $r['storage_nr'] : '',
                isset($r['location_nr']) ? (string) $r['location_nr'] : '',
                isset($r['item_nr']) ? (string) $r['item_nr'] : '',
                isset($r['item_name']) ? (string) $r['item_name'] : '',
                isset($r['item_name_2']) ? (string) $r['item_name_2'] : '',
                isset($r['serial_nr']) ? (string) $r['serial_nr'] : '',
                isset($r['address_nr']) ? (string) $r['address_nr'] : '',
                isset($r['address_name']) ? (string) $r['address_name'] : '',
                isset($r['storage_2']) ? (string) $r['storage_2'] : '',
                isset($r['location_2']) ? (string) $r['location_2'] : '',
                isset($r['qty']) ? (string) $r['qty'] : '0',
                isset($r['unit']) ? (string) $r['unit'] : '',
                isset($r['text']) ? (string) $r['text'] : '',
                isset($r['cost_center']) ? (string) $r['cost_center'] : '',
                isset($r['prod_nr']) ? (string) $r['prod_nr'] : '',
                isset($r['udef_1']) ? (string) $r['udef_1'] : '',
                isset($r['udef_2']) ? (string) $r['udef_2'] : '',
                isset($r['udef_3']) ? (string) $r['udef_3'] : '',
                isset($r['udef_4']) ? (string) $r['udef_4'] : '',
                isset($r['udef_5']) ? (string) $r['udef_5'] : '',
                isset($r['udef_6']) ? (string) $r['udef_6'] : '',
                isset($r['udef_7']) ? (string) $r['udef_7'] : '',
                isset($r['udef_8']) ? (string) $r['udef_8'] : '',
                isset($r['udef_9']) ? (string) $r['udef_9'] : '',
                isset($r['udef_10']) ? (string) $r['udef_10'] : '',
                isset($r['user_creator']) ? (string) $r['user_creator'] : '',
            );
        }

        return array('headers' => $headers, 'rows' => $rows);
    }

    private function log_path()
    {
        foreach ($this->rpa_root_candidates() as $rpa_root) {
            $path = $rpa_root . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'scheduler.log';
            if (is_file($path)) {
                return $path;
            }
        }

        return $this->rpa_root_path() . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'scheduler.log';
    }

    private function heat_holidays_path()
    {
        return APPPATH . 'cache' . DIRECTORY_SEPARATOR . 'dashboard_heat_holidays.json';
    }

    public function get_heat_holiday_settings()
    {
        $path = $this->heat_holidays_path();
        if (!is_file($path) || !is_readable($path)) {
            return array('holidays' => array(), 'half_days' => array(), 'quarter_days' => array(), 'work_days' => array());
        }

        $payload = json_decode(file_get_contents($path), TRUE);
        if (!is_array($payload)) {
            return array('holidays' => array(), 'half_days' => array(), 'quarter_days' => array(), 'work_days' => array());
        }

        $holidays = isset($payload['holidays']) && is_array($payload['holidays']) ? $payload['holidays'] : array();
        $half_days = isset($payload['half_days']) && is_array($payload['half_days']) ? $payload['half_days'] : array();
        $quarter_days = isset($payload['quarter_days']) && is_array($payload['quarter_days']) ? $payload['quarter_days'] : array();
        $work_days = isset($payload['work_days']) && is_array($payload['work_days']) ? $payload['work_days'] : array();

        return array(
            'holidays' => $this->normalize_calendar_dates($holidays),
            'half_days' => $this->normalize_calendar_dates($half_days),
            'quarter_days' => $this->normalize_calendar_dates($quarter_days),
            'work_days' => $this->normalize_calendar_dates($work_days),
        );
    }

    private function normalize_calendar_dates($dates)
    {
        $items = array_values(array_unique(array_filter(array_map(function ($date) {
            $timestamp = strtotime($date);
            return $timestamp ? date('Y-m-d', $timestamp) : NULL;
        }, $dates))));
        sort($items);

        return $items;
    }

    public function save_heat_holiday_settings($calendar)
    {
        if (!is_array($calendar)) {
            return array('ok' => FALSE, 'message' => 'Data kalender tidak valid.');
        }

        $holidays = isset($calendar['holidays']) && is_array($calendar['holidays']) ? $calendar['holidays'] : $calendar;
        $half_days = isset($calendar['half_days']) && is_array($calendar['half_days']) ? $calendar['half_days'] : array();
        $quarter_days = isset($calendar['quarter_days']) && is_array($calendar['quarter_days']) ? $calendar['quarter_days'] : array();
        $work_days = isset($calendar['work_days']) && is_array($calendar['work_days']) ? $calendar['work_days'] : array();
        $clean_holidays = $this->normalize_calendar_dates($holidays);
        $clean_half_days = array_values(array_diff($this->normalize_calendar_dates($half_days), $clean_holidays));
        $clean_quarter_days = array_values(array_diff($this->normalize_calendar_dates($quarter_days), array_merge($clean_holidays, $clean_half_days)));
        $clean_work_days = array_values(array_diff($this->normalize_calendar_dates($work_days), array_merge($clean_holidays, $clean_half_days, $clean_quarter_days)));

        $path = $this->heat_holidays_path();
        $dir = dirname($path);
        if (!is_dir($dir) || !is_writable($dir)) {
            return array('ok' => FALSE, 'message' => 'Folder penyimpanan tidak bisa ditulis.');
        }

        $payload = array(
            'updated_at' => date('c'),
            'holidays' => $clean_holidays,
            'half_days' => $clean_half_days,
            'quarter_days' => $clean_quarter_days,
            'work_days' => $clean_work_days,
        );

        if (file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === FALSE) {
            return array('ok' => FALSE, 'message' => 'Gagal menyimpan kalender libur.');
        }

        return array('ok' => TRUE, 'message' => 'Kalender kerja tersimpan.', 'calendar' => $payload);
    }

    private function heat_style_smv_path()
    {
        return APPPATH . 'cache' . DIRECTORY_SEPARATOR . 'dashboard_heat_style_smv.json';
    }

    private function heat_style_smv_table()
    {
        return 'dashboard_heat_style_smv';
    }

    private function ensure_heat_style_smv_table()
    {
        $db = $this->heat_history_connection();
        if (!$db || empty($db->conn_id)) {
            return FALSE;
        }

        static $checked = FALSE;
        if ($checked) {
            return TRUE;
        }

        $table = $this->heat_style_smv_table();
        $sql = "CREATE TABLE IF NOT EXISTS `{$table}` (
            `style` varchar(255) NOT NULL,
            `smv` decimal(10,4) DEFAULT NULL,
            `process_count` int(11) NOT NULL DEFAULT 1,
            `process_smvs` text DEFAULT NULL,
            `show_in_dashboard` tinyint(1) NOT NULL DEFAULT 1,
            `updated_at` datetime NOT NULL,
            PRIMARY KEY (`style`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8";

        $db->query($sql);

        $fields = $db->list_fields($table);
        if (is_array($fields)) {
            if (!in_array('process_count', $fields, TRUE)) {
                $db->query("ALTER TABLE `{$table}` ADD COLUMN `process_count` int(11) NOT NULL DEFAULT 1 AFTER `smv`");
            }
            if (!in_array('process_smvs', $fields, TRUE)) {
                $db->query("ALTER TABLE `{$table}` ADD COLUMN `process_smvs` text DEFAULT NULL AFTER `process_count`");
            }
        }

        $checked = TRUE;
        return TRUE;
    }

    public function get_heat_saved_style_smv_settings()
    {
        $saved = array();
        $db = $this->heat_history_connection();

        if ($db && !empty($db->conn_id)) {
            $this->ensure_heat_style_smv_table();
            $query = $db->get($this->heat_style_smv_table());
            if ($query && $query->num_rows() > 0) {
                foreach ($query->result_array() as $row) {
                    $style = isset($row['style']) ? trim((string) $row['style']) : '';
                    if ($style === '') {
                        continue;
                    }

                    $smv_val = isset($row['smv']) && is_numeric($row['smv']) ? (float) $row['smv'] : NULL;
                    $process_cnt = isset($row['process_count']) && is_numeric($row['process_count']) && (int) $row['process_count'] > 0
                        ? (int) $row['process_count']
                        : 1;

                    $process_smvs = array();
                    if (!empty($row['process_smvs'])) {
                        $decoded = is_string($row['process_smvs']) ? json_decode($row['process_smvs'], TRUE) : $row['process_smvs'];
                        if (is_array($decoded)) {
                            foreach ($decoded as $psmv) {
                                if (is_numeric($psmv) && (float) $psmv > 0) {
                                    $process_smvs[] = (float) $psmv;
                                }
                            }
                        }
                    }

                    if (empty($process_smvs) && $smv_val !== NULL && $smv_val > 0) {
                        $process_smvs = array($smv_val);
                    }

                    $saved[$style] = array(
                        'style' => $style,
                        'smv' => $smv_val,
                        'process_count' => $process_cnt,
                        'process_smvs' => $process_smvs,
                        'show_in_dashboard' => !empty($row['show_in_dashboard']),
                        'updated_at' => isset($row['updated_at']) ? $row['updated_at'] : date('c'),
                    );
                }
            }
        }

        $path = $this->heat_style_smv_path();
        if (is_file($path) && is_readable($path)) {
            $json = json_decode(file_get_contents($path), TRUE);
            if (is_array($json) && !empty($json)) {
                if (empty($saved)) {
                    $saved = $json;
                    if ($db && !empty($db->conn_id)) {
                        foreach ($saved as $style_name => $item_val) {
                            $s_val = isset($item_val['smv']) && is_numeric($item_val['smv']) ? (float) $item_val['smv'] : NULL;
                            $p_c = isset($item_val['process_count']) ? (int) $item_val['process_count'] : 1;
                            $p_s = isset($item_val['process_smvs']) && is_array($item_val['process_smvs']) ? json_encode(array_values($item_val['process_smvs'])) : NULL;
                            $db->replace($this->heat_style_smv_table(), array(
                                'style' => $style_name,
                                'smv' => $s_val,
                                'process_count' => $p_c,
                                'process_smvs' => $p_s,
                                'show_in_dashboard' => 1,
                                'updated_at' => date('Y-m-d H:i:s'),
                            ));
                        }
                    }
                } else {
                    foreach ($json as $style_name => $item_val) {
                        if (!isset($saved[$style_name])) {
                            $saved[$style_name] = $item_val;
                            if ($db && !empty($db->conn_id)) {
                                $s_val = isset($item_val['smv']) && is_numeric($item_val['smv']) ? (float) $item_val['smv'] : NULL;
                                $p_c = isset($item_val['process_count']) ? (int) $item_val['process_count'] : 1;
                                $p_s = isset($item_val['process_smvs']) && is_array($item_val['process_smvs']) ? json_encode(array_values($item_val['process_smvs'])) : NULL;
                                $db->replace($this->heat_style_smv_table(), array(
                                    'style' => $style_name,
                                    'smv' => $s_val,
                                    'process_count' => $p_c,
                                    'process_smvs' => $p_s,
                                    'show_in_dashboard' => 1,
                                    'updated_at' => date('Y-m-d H:i:s'),
                                ));
                            }
                        }
                    }
                }
            }
        }

        return $saved;
    }

    private function heat_analytics_settings_path()
    {
        return APPPATH . 'cache' . DIRECTORY_SEPARATOR . 'dashboard_heat_analytics_settings.json';
    }

    public function get_heat_style_smv_catalog($delivery_count = 4, $list_orders = NULL, $top_priority_orders = NULL)
    {
        $saved = $this->get_heat_saved_style_smv_settings();

        if ($list_orders === NULL && $top_priority_orders === NULL) {
            $dashboard = $this->get_heat_dashboard_data($delivery_count);
            $list_orders = !empty($dashboard['list_orders']) ? $dashboard['list_orders'] : array();
            $top_priority_orders = !empty($dashboard['top_priority_orders']) ? $dashboard['top_priority_orders'] : array();
        }

        $target_month = date('Y-m');
        if (is_string($delivery_count) && preg_match('/^(\d{4}-\d{2})/', $delivery_count, $m_match)) {
            $target_month = $m_match[1];
        }

        $aps_running_styles = array();
        $aps_style_pdk = array();

        if (is_array($list_orders)) {
            foreach ($list_orders as $row) {
                $order_val = isset($row['order']) ? $row['order'] : '';
                $style_val = isset($row['style']) ? $row['style'] : '';
                if ($this->is_ofc_order($order_val) || $this->is_ofc_order($style_val) || !empty($row['is_ofc'])) {
                    continue;
                }
                if (!empty($style_val)) {
                    $del_raw = isset($row['delivery']) ? $row['delivery'] : '';
                    $del_ts = !empty($row['_sort_delivery']) ? (int) $row['_sort_delivery'] : $this->parse_date_timestamp($del_raw);
                    $del_month = ($del_ts > 0) ? date('Y-m', $del_ts) : '';

                    // Style berjalan otomatis disaring dari order dengan delivery date di bulan berjalan
                    if ($del_month === $target_month || ($del_month === '' && empty($target_month))) {
                        $aps_running_styles[$style_val] = TRUE;
                        $pdk_qty = isset($row['qty_pdk']) ? (int) $row['qty_pdk'] : (isset($row['pdk']) ? (int) $row['pdk'] : 0);
                        $aps_style_pdk[$style_val] = (isset($aps_style_pdk[$style_val]) ? $aps_style_pdk[$style_val] : 0) + $pdk_qty;
                    }
                }
            }
        }

        if (is_array($top_priority_orders)) {
            foreach ($top_priority_orders as $row) {
                $order_val = isset($row['order']) ? $row['order'] : '';
                $style_val = isset($row['style']) ? $row['style'] : '';
                if ($this->is_ofc_order($order_val) || $this->is_ofc_order($style_val) || !empty($row['is_ofc'])) {
                    continue;
                }
                if (!empty($style_val)) {
                    $del_raw = isset($row['delivery']) ? $row['delivery'] : '';
                    $del_ts = !empty($row['_sort_delivery']) ? (int) $row['_sort_delivery'] : $this->parse_date_timestamp($del_raw);
                    $del_month = ($del_ts > 0) ? date('Y-m', $del_ts) : '';

                    if ($del_month === $target_month || ($del_month === '' && empty($target_month))) {
                        $aps_running_styles[$style_val] = TRUE;
                        if (!isset($aps_style_pdk[$style_val])) {
                            $pdk_qty = isset($row['qty_pdk']) ? (int) $row['qty_pdk'] : (isset($row['pdk']) ? (int) $row['pdk'] : 0);
                            $aps_style_pdk[$style_val] = $pdk_qty;
                        }
                    }
                }
            }
        }

        $styles = array();
        foreach (array_keys($aps_running_styles) as $s) {
            if (!$this->is_ofc_order($s)) {
                $styles[$s] = TRUE;
            }
        }
        foreach (array_keys($saved) as $s) {
            if ($s !== '' && !$this->is_ofc_order($s)) {
                $styles[$s] = TRUE;
            }
        }

        $catalog = array();
        $running_styles = array();
        $process_map = array();
        $style_keys = array_keys($styles);
        sort($style_keys);

        foreach ($style_keys as $style) {
            if ($this->is_ofc_order($style)) {
                continue;
            }
            $smv_val = isset($saved[$style]['smv']) && is_numeric($saved[$style]['smv']) ? (float) $saved[$style]['smv'] : NULL;
            $process_val = isset($saved[$style]['process_count']) && is_numeric($saved[$style]['process_count'])
                ? (int) $saved[$style]['process_count']
                : (isset($saved[$style]['proses']) && is_numeric($saved[$style]['proses']) ? (int) $saved[$style]['proses'] : NULL);
            $process_smvs = array();
            if (isset($saved[$style]['process_smvs']) && is_array($saved[$style]['process_smvs'])) {
                foreach ($saved[$style]['process_smvs'] as $psmv) {
                    if (is_numeric($psmv) && (float) $psmv > 0) {
                        $process_smvs[] = (float) $psmv;
                    }
                }
            } elseif ($smv_val !== NULL && $smv_val > 0) {
                $process_smvs = array($smv_val);
            }
            if ($process_val === NULL && count($process_smvs) > 0) {
                $process_val = count($process_smvs);
            }
            $final_process_count = ($process_val !== NULL && $process_val > 0) ? (int) $process_val : 1;
            $style_key_upper = strtoupper(trim((string) $style));
            $process_map[$style_key_upper] = $final_process_count;

            // Style yang sedang berjalan dihitung otomatis dari data order APS yang memiliki delivery date di bulan berjalan
            $is_running = isset($aps_running_styles[$style]);
            $style_pdk = isset($aps_style_pdk[$style]) ? (int) $aps_style_pdk[$style] : 0;

            $item_data = array(
                'style' => $style,
                'smv' => $smv_val,
                'qty_pdk' => $style_pdk,
                'process_count' => $final_process_count,
                'process_smvs' => $process_smvs,
                'is_running' => $is_running,
                'show_in_dashboard' => $is_running,
                'has_smv' => $smv_val !== NULL && $smv_val > 0,
            );
            $catalog[] = $item_data;
            if ($is_running) {
                $running_styles[] = $item_data;
            }
        }

        foreach ($saved as $s_name => $s_data) {
            $s_upper = strtoupper(trim((string) $s_name));
            if (!isset($process_map[$s_upper])) {
                $p_cnt = isset($s_data['process_count']) && (int) $s_data['process_count'] > 0
                    ? (int) $s_data['process_count']
                    : (isset($s_data['process_smvs']) && count($s_data['process_smvs']) > 0 ? count($s_data['process_smvs']) : 1);
                $process_map[$s_upper] = $p_cnt;
            }
        }

        return array(
            'styles' => $catalog,
            'running_styles' => $running_styles,
            'process_map' => $process_map,
        );
    }

    public function save_heat_style_smv_settings($items)
    {
        if (!is_array($items)) {
            return array('ok' => FALSE, 'message' => 'Data SMV tidak valid.');
        }

        $db = $this->heat_history_connection();
        if (!$db || empty($db->conn_id)) {
            return array('ok' => FALSE, 'message' => 'Koneksi database gagal. Data SMV dan proses tidak dapat disimpan ke database.');
        }
        $this->ensure_heat_style_smv_table();

        $saved = $this->get_heat_saved_style_smv_settings();
        $saved_styles_map = array();

        foreach ($items as $item) {
            $style = isset($item['style']) ? trim((string) $item['style']) : '';
            if ($style === '' || $this->is_ofc_order($style)) {
                continue;
            }

            $raw_process = isset($item['process_count']) ? trim((string) $item['process_count']) : (isset($item['proses']) ? trim((string) $item['proses']) : '');
            $has_process = ($raw_process !== '' && is_numeric($raw_process));
            if ($has_process && (int) $raw_process < 1) {
                return array(
                    'ok' => FALSE,
                    'message' => "Style {$style}: Jumlah proses tidak boleh kurang dari 1 atau minus."
                );
            }
            $process_num = $has_process ? max(1, (int) $raw_process) : NULL;

            $process_smvs = array();
            if (isset($item['process_smvs']) && is_array($item['process_smvs'])) {
                foreach ($item['process_smvs'] as $psmv) {
                    $trimmed = trim((string) $psmv);
                    if ($trimmed !== '') {
                        if (!is_numeric($trimmed) || (float) $trimmed <= 0) {
                            return array(
                                'ok' => FALSE,
                                'message' => "Style {$style}: SMV per proses tidak boleh bernilai 0 atau minus."
                            );
                        }
                        $process_smvs[] = (float) $trimmed;
                    }
                }
            }

            $raw_smv = isset($item['smv']) ? trim((string) $item['smv']) : '';
            if (empty($process_smvs) && $raw_smv !== '') {
                if (!is_numeric($raw_smv) || (float) $raw_smv <= 0) {
                    return array(
                        'ok' => FALSE,
                        'message' => "Style {$style}: SMV per proses tidak boleh bernilai 0 atau minus."
                    );
                }
                $process_smvs[] = (float) $raw_smv;
            }

            // Validation: if process_count is set (> 0) and any SMV was provided
            if ($process_num !== NULL && $process_num > 0) {
                if (!empty($process_smvs)) {
                    if (count($process_smvs) < $process_num) {
                        return array(
                            'ok' => FALSE,
                            'message' => "Style {$style}: Jumlah proses {$process_num}, maka harus memasukkan {$process_num} SMV."
                        );
                    }
                }
            }

            $has_smv = !empty($process_smvs);
            $smv_num = $has_smv ? (float) array_sum($process_smvs) : NULL;
            $has_process_count = ($process_num !== NULL && $process_num > 0);

            if ($has_smv || $has_process_count) {
                $final_process_count = $process_num !== NULL ? $process_num : (count($process_smvs) ?: 1);
                $saved[$style] = array(
                    'style' => $style,
                    'smv' => $smv_num,
                    'process_count' => $final_process_count,
                    'process_smvs' => $process_smvs,
                    'updated_at' => date('c'),
                );
                $saved_styles_map[$style] = $smv_num !== NULL ? $smv_num : 0;

                $db->replace($this->heat_style_smv_table(), array(
                    'style' => $style,
                    'smv' => $smv_num,
                    'process_count' => $final_process_count,
                    'process_smvs' => !empty($process_smvs) ? json_encode(array_values($process_smvs)) : NULL,
                    'show_in_dashboard' => 1,
                    'updated_at' => date('Y-m-d H:i:s'),
                ));
            } elseif (isset($saved[$style])) {
                unset($saved[$style]);
                $db->where('style', $style)->delete($this->heat_style_smv_table());
            }
        }

        $path = $this->heat_style_smv_path();
        $dir = dirname($path);
        if (is_dir($dir) && is_writable($dir)) {
            @file_put_contents($path, json_encode($saved, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        return array(
            'ok' => TRUE,
            'message' => 'SMV dan proses tersimpan di database.',
            'saved' => count($saved_styles_map),
            'styles' => $saved_styles_map,
        );
    }

    public function get_heat_analytics_settings()
    {
        $defaults = array(
            'visible_cards' => array(),
            'language' => 'id',
            'direct_actual' => NULL,
            'double_machine_active' => 2,
            'default_capacity_mode' => 'mesin',
        );

        $path = $this->heat_analytics_settings_path();
        if (!is_file($path) || !is_readable($path)) {
            return $defaults;
        }

        $payload = json_decode(file_get_contents($path), TRUE);
        if (!is_array($payload)) {
            return $defaults;
        }

        return array(
            'visible_cards' => isset($payload['visible_cards']) && is_array($payload['visible_cards']) ? $payload['visible_cards'] : array(),
            'language' => isset($payload['language']) && in_array($payload['language'], array('id', 'en'), TRUE) ? $payload['language'] : 'id',
            'direct_actual' => isset($payload['direct_actual']) && is_numeric($payload['direct_actual']) ? (float) $payload['direct_actual'] : NULL,
            'double_machine_active' => isset($payload['double_machine_active']) && is_numeric($payload['double_machine_active']) ? max(0, min(2, (int) $payload['double_machine_active'])) : 2,
            'default_capacity_mode' => isset($payload['default_capacity_mode']) && in_array($payload['default_capacity_mode'], array('mesin', 'minutes'), TRUE) ? $payload['default_capacity_mode'] : 'mesin',
        );
    }

    public function save_heat_analytics_settings($settings)
    {
        if (!is_array($settings)) {
            return array('ok' => FALSE, 'message' => 'Data analytics settings tidak valid.');
        }

        $path = $this->heat_analytics_settings_path();
        $dir = dirname($path);
        if (!is_dir($dir) || !is_writable($dir)) {
            return array('ok' => FALSE, 'message' => 'Folder penyimpanan tidak bisa ditulis.');
        }

        $current = $this->get_heat_analytics_settings();
        $visible_cards = isset($settings['visible_cards']) && is_array($settings['visible_cards']) ? $settings['visible_cards'] : $current['visible_cards'];
        $language = isset($settings['language']) && in_array($settings['language'], array('id', 'en'), TRUE) ? $settings['language'] : $current['language'];
        $direct_actual = array_key_exists('direct_actual', $settings)
            ? (is_numeric($settings['direct_actual']) ? (float) $settings['direct_actual'] : NULL)
            : $current['direct_actual'];
        $double_machine_active = array_key_exists('double_machine_active', $settings)
            ? (is_numeric($settings['double_machine_active']) ? max(0, min(2, (int) $settings['double_machine_active'])) : 2)
            : (isset($current['double_machine_active']) ? (int) $current['double_machine_active'] : 2);
        $default_capacity_mode = array_key_exists('default_capacity_mode', $settings) && in_array($settings['default_capacity_mode'], array('mesin', 'minutes'), TRUE)
            ? $settings['default_capacity_mode']
            : (isset($current['default_capacity_mode']) && in_array($current['default_capacity_mode'], array('mesin', 'minutes'), TRUE) ? $current['default_capacity_mode'] : 'mesin');

        $payload = array(
            'updated_at' => date('c'),
            'visible_cards' => $visible_cards,
            'language' => $language,
            'direct_actual' => $direct_actual,
            'double_machine_active' => $double_machine_active,
            'default_capacity_mode' => $default_capacity_mode,
        );

        if (file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === FALSE) {
            return array('ok' => FALSE, 'message' => 'Gagal menyimpan analytics settings.');
        }

        return array(
            'ok' => TRUE,
            'message' => 'Analytics settings tersimpan.',
            'analytics_settings' => $payload,
        );
    }

    private function dashboard_calendar_signature()
    {
        return md5(json_encode($this->dashboard_calendar_days(), JSON_UNESCAPED_UNICODE));
    }

    private function read_heat_capacity_history($delivery_count = NULL)
    {
        $items = $this->read_heat_history_rows('capacity', $delivery_count);
        foreach ($items as $date => $item) {
            if (isset($item['breakdown']) && is_array($item['breakdown'])) {
                $items[$date]['breakdown'] = $item['breakdown'];
            } else {
                $items[$date]['breakdown'] = array();
            }
            $items[$date]['capacity'] = isset($item['capacity']) ? max(0, (float) $item['capacity']) : 0;
        }

        return $items;
    }

    private function write_heat_capacity_history($items)
    {
        ksort($items);
        foreach ($items as $date => $item) {
            $this->upsert_heat_history_row('capacity', $date, isset($item['delivery_count']) ? (int) $item['delivery_count'] : 4, $item);
        }
    }

    private function capacity_history_is_valid($entry, $delivery_count)
    {
        if (!is_array($entry)) {
            return FALSE;
        }

        $expected_export_prep = $this->export_prep_workdays($delivery_count);

        return isset($entry['calendar_signature'])
            && $entry['calendar_signature'] === $this->dashboard_calendar_signature()
            && isset($entry['delivery_count'])
            && (int) $entry['delivery_count'] === (int) $delivery_count
            && isset($entry['capacity_formula'])
            && $entry['capacity_formula'] === 'balance_qty_v2'
            && isset($entry['export_prep_days'])
            && (int) $entry['export_prep_days'] === $expected_export_prep
            && isset($entry['balance_qty'])
            && isset($entry['capacity']);
    }

    private function read_heat_qty_history($delivery_count = NULL)
    {
        return $this->read_heat_history_rows('qty', $delivery_count);
    }

    private function write_heat_qty_history($items)
    {
        ksort($items);
        foreach ($items as $date => $item) {
            $this->upsert_heat_history_row('qty', $date, isset($item['delivery_count']) ? (int) $item['delivery_count'] : 4, $item);
        }
    }

    private function save_heat_qty_daily_snapshot($total_pdk, $total_output, $balance_qty, $qty_pdk_vs_output, $selected_qty_pdk_vs_output, $delivery_count = 4)
    {
        $today = date('Y-m-d');
        $items = $this->read_heat_qty_history($delivery_count);

        // Build period breakdown for history
        $periods = array();
        foreach ($selected_qty_pdk_vs_output as $row) {
            $periods[] = array(
                'label' => $row['label'],
                'pdk' => isset($row['pdk']) ? $row['pdk'] : 0,
                'output' => isset($row['output']) ? $row['output'] : 0,
                'balance' => max(0, (isset($row['pdk']) ? $row['pdk'] : 0) - (isset($row['output']) ? $row['output'] : 0)),
            );
        }

        $existing = isset($items[$today]) ? $items[$today] : null;
        if ($existing !== null
            && isset($existing['total_pdk'])
            && (int) $existing['total_pdk'] === (int) $total_pdk
            && isset($existing['total_output'])
            && (int) $existing['total_output'] === (int) $total_output
            && isset($existing['balance_qty'])
            && (int) $existing['balance_qty'] === (int) $balance_qty
        ) {
            return;
        }

        $items[$today] = array(
            'delivery_count' => (int) $delivery_count,
            'qty_pdk' => (int) $total_pdk,
            'qty_output' => (int) $total_output,
            'total_pdk' => (int) $total_pdk,
            'total_output' => (int) $total_output,
            'balance_qty' => (int) $balance_qty,
            'total_capacity' => max(0, (int) $total_pdk - (int) $total_output),
            'periods' => $periods,
            'captured_at' => date('c'),
        );

        $this->upsert_heat_history_row('qty', $today, (int) $delivery_count, $items[$today]);
    }

    private function save_heat_summary_snapshot($delivery_count, array $data)
    {
        $analytics = isset($data['management_analytics']) && is_array($data['management_analytics'])
            ? $data['management_analytics']
            : array();
        $summary = isset($analytics['summary']) && is_array($analytics['summary'])
            ? $analytics['summary']
            : array();
        $details = isset($analytics['details']) && is_array($analytics['details'])
            ? $analytics['details']
            : array();
        $daily_requirement = isset($details['daily_requirement']) && is_array($details['daily_requirement'])
            ? $details['daily_requirement']
            : array();

        $total_pdk = isset($data['total_pdk']) ? (int) $data['total_pdk'] : 0;
        $total_output = isset($data['total_output']) ? (int) $data['total_output'] : 0;
        $balance_qty = isset($data['balance_qty']) ? (int) $data['balance_qty'] : 0;
        $total_ready = isset($summary['total_ready']) ? (int) round($summary['total_ready']) : 0;
        $avg_daily_output = isset($summary['avg_daily_output']) ? (float) $summary['avg_daily_output'] : 0;
        $avg_daily_capacity = isset($summary['avg_daily_capacity']) ? (float) $summary['avg_daily_capacity'] : 0;
        $capacity_gap = isset($summary['capacity_gap']) ? (float) $summary['capacity_gap'] : 0;
        $required_daily_output = isset($daily_requirement['required_daily_output']) ? (float) $daily_requirement['required_daily_output'] : 0;
        $remaining_workdays = isset($daily_requirement['period_days_left']) ? (float) $daily_requirement['period_days_left'] : 0;
        $prod_days_left = isset($data['prod_days_left']) ? (float) $data['prod_days_left'] : 0;
        if (!$remaining_workdays && isset($daily_requirement['prod_days_left'])) {
            $remaining_workdays = (float) $daily_requirement['prod_days_left'];
        }

        $summary_payload = array(
            'delivery_count' => (int) $delivery_count,
            'captured_at' => date('c'),
            'total_pdk' => $total_pdk,
            'total_output' => $total_output,
            'balance_qty' => $balance_qty,
            'total_ready' => $total_ready,
            'avg_daily_output' => $avg_daily_output,
            'avg_daily_capacity' => $avg_daily_capacity,
            'capacity_gap' => $capacity_gap,
            'required_daily_output' => $required_daily_output,
            'remaining_workdays' => $remaining_workdays,
            'prod_days_left' => $prod_days_left,
            'qty_pdk_vs_output' => isset($data['qty_pdk_vs_output']) && is_array($data['qty_pdk_vs_output']) ? $data['qty_pdk_vs_output'] : array(),
            'ready_to_load' => isset($data['ready_to_load']) && is_array($data['ready_to_load']) ? $data['ready_to_load'] : array(),
            'output_vs_capacity' => isset($data['output_vs_capacity']) && is_array($data['output_vs_capacity']) ? $data['output_vs_capacity'] : array(),
            'material_to_load' => isset($data['material_to_load']) && is_array($data['material_to_load']) ? array_slice($data['material_to_load'], 0, 100) : array(),
        );

        return $this->upsert_heat_history_row('summary', date('Y-m-d'), $delivery_count, array(
            'qty_pdk' => $total_pdk,
            'qty_output' => $total_output,
            'balance_qty' => $balance_qty,
            'total_capacity' => (int) round($avg_daily_capacity),
            'capacity' => (int) round($required_daily_output),
            'input_qty' => $total_ready,
            'output_qty' => $total_output,
            'snapshot_json' => json_encode($summary_payload, JSON_UNESCAPED_UNICODE),
            'captured_at' => date('Y-m-d H:i:s'),
        ));
    }

    public function get_heat_qty_history($delivery_count = NULL)
    {
        if ($delivery_count !== NULL) {
            $delivery_count = $this->normalize_delivery_count($delivery_count);
        }

        $items = $this->read_heat_qty_history($delivery_count);
        if (!$items) {
            return array(
                'ok' => FALSE,
                'message' => 'Riwayat QTY belum tersedia.',
                'data' => array(),
            );
        }

        // Return last 30 days
        $all_dates = array_keys($items);
        rsort($all_dates);
        $recent_dates = array_slice($all_dates, 0, 30);

        $recent_items = array();
        foreach ($recent_dates as $date) {
            $recent_items[$date] = $items[$date];
        }

        ksort($recent_items);

        $data = array();
        foreach ($recent_items as $date => $item) {
            $data[] = array(
                'tanggal' => $date,
                'date' => $date,
                'delivery_count' => isset($item['delivery_count']) ? (int) $item['delivery_count'] : (int) $delivery_count,
                'qty_pdk' => isset($item['qty_pdk']) ? (int) $item['qty_pdk'] : (int) (isset($item['total_pdk']) ? $item['total_pdk'] : 0),
                'qty_output' => isset($item['qty_output']) ? (int) $item['qty_output'] : (int) (isset($item['total_output']) ? $item['total_output'] : 0),
                'balance_qty' => isset($item['balance_qty']) ? (int) $item['balance_qty'] : 0,
                'total_capacity' => isset($item['total_capacity']) ? (int) $item['total_capacity'] : 0,
                'catatan' => isset($item['periods']) && is_array($item['periods']) ? count($item['periods']) . ' period' : '',
                'notes' => isset($item['periods']) && is_array($item['periods']) ? count($item['periods']) . ' period' : '',
            );
        }

        return array(
            'ok' => TRUE,
            'data' => $data,
            'total_days' => count($items),
            'delivery_count' => $delivery_count,
        );
    }

    private function capacity_snapshot_entry($capacity_detail, $balance_day, $delivery_count)
    {
        return array(
            'capacity' => $capacity_detail['capacity'],
            'balance_qty' => $capacity_detail['balance_qty'],
            'capacity_balance_day' => $balance_day,
            'remaining_days' => $capacity_detail['remaining_days'],
            'export_prep_days' => $capacity_detail['export_prep_days'],
            'sisa_hari_kerja' => $capacity_detail['sisa_hari_kerja'],
            'hari_kerja' => $capacity_detail['sisa_hari_kerja'],
            'capacity_formula' => 'balance_qty_v2',
            'delivery_count' => (int) $delivery_count,
            'captured_at' => date('c'),
            'calendar_signature' => $this->dashboard_calendar_signature(),
            'breakdown' => $this->capacity_breakdown_for_history($capacity_detail),
        );
    }

    public function get_report_status()
    {
        $rows = array();

        foreach ($this->reports as $report) {
            $path = $this->latest_report_path($report['filename']);
            $exists = is_file($path);
            $rows[] = $report + array(
                'exists' => $exists,
                'path' => $path,
                'size' => $exists ? filesize($path) : NULL,
                'size_label' => $exists ? $this->format_bytes(filesize($path)) : '-',
                'updated_at' => $exists ? date('c', filemtime($path)) : NULL,
                'rows' => $exists ? $this->count_report_rows($path) : NULL,
            );
        }

        return $rows;
    }

    public function get_dashboard_sheet()
    {
        $path = $this->data_file();
        if (!$path || !is_file($path)) {
            return array(
                'available' => FALSE,
                'message' => 'File Excel dashboard belum bisa diakses. Pastikan Apache/XAMPP punya akses ke drive X: atau UNC share.',
                'path' => $path,
                'diagnostics' => $this->data_file_diagnostics(),
                'rows' => array(),
            );
        }

        if (!$this->is_xlsx_zip($path)) {
            return array(
                'available' => FALSE,
                'message' => 'File dashboard bukan format Excel .xlsx yang valid.',
                'path' => $path,
                'rows' => array(),
            );
        }

        $sheet = $this->read_xlsx_sheet_grid($path, 'Dashboard');
        if (!$sheet['rows']) {
            return array(
                'available' => FALSE,
                'message' => 'Sheet Dashboard tidak ditemukan atau kosong.',
                'path' => $path,
                'sheet' => 'Dashboard',
                'rows' => array(),
            );
        }

        return array(
            'available' => TRUE,
            'path' => $path,
            'sheet' => 'Dashboard',
            'rows' => $sheet['rows'],
            'max_columns' => $sheet['max_columns'],
        );
    }

    public function get_heat_dashboard_data($date_from = NULL, $date_to = NULL)
    {
        $rpa_data = $this->get_heat_dashboard_data_from_rpa($date_from, $date_to);
        if ($rpa_data && !empty($rpa_data['available'])) {
            return $rpa_data;
        }

        return array(
            'available' => FALSE,
            'message' => $rpa_data && !empty($rpa_data['message'])
                ? $rpa_data['message']
                : 'Data RPA belum lengkap untuk dashboard Heat Transfer.',
        );
    }

    private function get_heat_dashboard_data_from_rpa($date_from = NULL, $date_to = NULL)
    {
        $sources = $this->heat_rpa_sources();
        $has_engage_db = $this->has_engage_db_data();

        $required = array('aps');
        if (!$has_engage_db) {
            $required = array_merge($required, array('engage_32a_inflow', 'engage_32a_outflow'));
        }

        foreach ($required as $key) {
            if (empty($sources[$key]) || !is_file($sources[$key])) {
                return array('available' => FALSE, 'message' => 'Data RPA belum lengkap untuk dashboard Heat Transfer.');
            }
        }

        $aps = $this->read_html_report($sources['aps']);
        $inflow_32a = $this->read_combined_engage_report(isset($sources['engage_32a_inflow']) ? $sources['engage_32a_inflow'] : NULL, '32a_inflow');
        $outflow_32a = $this->read_combined_engage_report(isset($sources['engage_32a_outflow']) ? $sources['engage_32a_outflow'] : NULL, '32a_outflow');
        $accessories = !empty($sources['accessories']) ? $this->read_html_report($sources['accessories']) : array('headers' => array(), 'rows' => array());

        if (!$aps['headers'] || !$inflow_32a['headers'] || !$outflow_32a['headers']) {
            return array('available' => FALSE, 'message' => 'Header data RPA APS atau Engage belum bisa dibaca.');
        }

        $source_data = $this->build_heat_data_from_rpa_sources($aps, $inflow_32a, $outflow_32a, $accessories, $date_from, $date_to);
        if (!$source_data['qty_pdk_vs_output'] && !$source_data['ready_to_load']) {
            return array('available' => FALSE, 'message' => 'Data RPA terbaca, tetapi belum ada order Heat Transfer yang bisa ditampilkan.');
        }

        $total_pdk = $source_data['total_pdk'];
        $total_output = $source_data['total_output'];
        $balance_qty = $source_data['balance_qty'];
        $prod_days_left = $source_data['prod_days_left'];
        $qty_pdk_vs_output = $source_data['qty_pdk_vs_output'];
        $selected_qty_pdk_vs_output = isset($source_data['selected_qty_pdk_vs_output']) ? $source_data['selected_qty_pdk_vs_output'] : $qty_pdk_vs_output;
        $ready_to_load = $source_data['ready_to_load'];
        $selected_ready_to_load = isset($source_data['selected_ready_to_load']) ? $source_data['selected_ready_to_load'] : $ready_to_load;
        $output_vs_capacity = $source_data['output_vs_capacity'];
        $daily_output_vs_capacity = isset($source_data['daily_output_vs_capacity']) ? $source_data['daily_output_vs_capacity'] : $output_vs_capacity;
        $list_orders = isset($source_data['list_orders']) && is_array($source_data['list_orders']) ? $source_data['list_orders'] : array();
        $material_to_load = isset($source_data['material_to_load']) ? $source_data['material_to_load'] : $source_data['top_priority_orders'];
        $top_priority_orders = $source_data['top_priority_orders'];
        $balance_qty_with_ofc = isset($source_data['balance_qty_with_ofc']) ? $source_data['balance_qty_with_ofc'] : $balance_qty;

        $management_analytics = $this->build_management_analytics(
            $total_pdk,
            $total_output,
            $balance_qty,
            $prod_days_left,
            $selected_qty_pdk_vs_output,
            $selected_ready_to_load,
            $daily_output_vs_capacity,
            $top_priority_orders
        );

        $this->save_heat_summary_snapshot(1, array(
            'total_pdk' => $total_pdk,
            'total_output' => $total_output,
            'balance_qty' => $balance_qty,
            'prod_days_left' => $prod_days_left,
            'qty_pdk_vs_output' => $qty_pdk_vs_output,
            'selected_qty_pdk_vs_output' => $selected_qty_pdk_vs_output,
            'ready_to_load' => $ready_to_load,
            'selected_ready_to_load' => $selected_ready_to_load,
            'output_vs_capacity' => $output_vs_capacity,
            'daily_output_vs_capacity' => $daily_output_vs_capacity,
            'list_orders' => $list_orders,
            'material_to_load' => $material_to_load,
            'top_priority_orders' => $top_priority_orders,
            'management_analytics' => $management_analytics,
        ));

        $actual_from = isset($source_data['selected_date_from']) ? $source_data['selected_date_from'] : (is_string($date_from) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from) ? $date_from : date('Y-m-d'));
        $actual_to = isset($source_data['selected_date_to']) ? $source_data['selected_date_to'] : (is_string($date_to) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to) ? $date_to : $actual_from);

        return array(
            'available' => TRUE,
            'source' => 'RPA: APS + Engage + Accessories',
            'selected_date' => $actual_from,
            'selected_date_from' => $actual_from,
            'selected_date_to' => $actual_to,
            'selected_period' => isset($source_data['selected_period']) ? $source_data['selected_period'] : '',
            'period_type' => isset($source_data['period_type']) ? $source_data['period_type'] : '',
            'period_range' => isset($source_data['period_range']) ? $source_data['period_range'] : null,
            'period_display_range' => isset($source_data['period_display_range']) ? $source_data['period_display_range'] : '',
            'all_periods' => isset($source_data['all_periods']) ? $source_data['all_periods'] : array(),
            'source_updated_at' => $this->latest_mtime_iso($sources),
            'sources' => $this->source_status_rows($sources),
            'kpis' => array(
                'total_output' => $total_output,
                'balance_qty' => $balance_qty,
                'balance_qty_with_ofc' => $balance_qty_with_ofc,
                'prod_days_left' => $prod_days_left,
            ),
            'holiday_settings' => $this->get_heat_holiday_settings(),
            'delivery_workdays' => $this->build_delivery_workdays($selected_qty_pdk_vs_output, $selected_ready_to_load),
            'qty_pdk_vs_output' => $selected_qty_pdk_vs_output,
            'ready_to_load' => $selected_ready_to_load,
            'output_vs_capacity' => $output_vs_capacity,
            'daily_output_vs_capacity' => $daily_output_vs_capacity,
            'list_orders' => $list_orders,
            'material_to_load' => $material_to_load,
            'top_priority_orders' => $top_priority_orders,
            'management_analytics' => $management_analytics,
            'analytics_settings' => $this->get_heat_analytics_settings(),
            'style_smv_catalog' => $this->get_heat_style_smv_catalog($actual_from, $list_orders, $top_priority_orders),
        );
    }

    public function summarize_32a_material()
    {
        $inflow = $this->read_html_report($this->latest_report_path('32a_inflow.xlsx'));
        $outflow = $this->read_html_report($this->latest_report_path('32a_outflow.xlsx'));

        if (!$inflow['headers']) {
            return array('available' => FALSE, 'message' => 'File 32a_inflow belum tersedia atau tidak bisa dibaca.');
        }

        $in_index = $this->header_index($inflow['headers']);
        $out_index = $this->header_index($outflow['headers']);
        $groups = array();

        foreach ($inflow['rows'] as $row) {
            $udef4 = $this->cell($row, $in_index, 'Udef 4');
            if ($udef4 === '') {
                continue;
            }
            if (!isset($groups[$udef4])) {
                $groups[$udef4] = $this->empty_group($udef4);
            }
            $groups[$udef4]['item'] = $groups[$udef4]['item'] ?: $this->cell($row, $in_index, 'Item Nr');
            $groups[$udef4]['prod'] = $groups[$udef4]['prod'] ?: $this->cell($row, $in_index, 'Prod. Nr');
            $groups[$udef4]['in_qty'] += $this->parse_number($this->cell($row, $in_index, 'Qty'));
            $groups[$udef4]['in_rows']++;
        }

        foreach ($outflow['rows'] as $row) {
            $udef4 = $this->cell($row, $out_index, 'Udef 4');
            if ($udef4 === '') {
                continue;
            }
            if (!isset($groups[$udef4])) {
                $groups[$udef4] = $this->empty_group($udef4);
            }
            $groups[$udef4]['item'] = $groups[$udef4]['item'] ?: $this->cell($row, $out_index, 'Item Nr');
            $groups[$udef4]['prod'] = $groups[$udef4]['prod'] ?: $this->cell($row, $out_index, 'Prod. Nr');
            $groups[$udef4]['out_qty'] += abs($this->parse_number($this->cell($row, $out_index, 'Qty')));
            $groups[$udef4]['out_rows']++;
        }

        $details = array();
        $capacity = array('panel_1' => 0, 'panel_2' => 0, 'panel_3' => 0, 'panel_more' => 0);
        $capacity_qty = array('panel_1' => 0, 'panel_2' => 0, 'panel_3' => 0, 'panel_more' => 0);

        foreach ($groups as $group) {
            $ready_qty = $group['in_qty'] - $group['out_qty'];
            if ($ready_qty <= 0) {
                continue;
            }

            $panel_count = max(0, $group['in_rows'] - $group['out_rows']);
            $group['ready_qty'] = $ready_qty;
            $group['panel_count'] = $panel_count;
            $details[] = $group;

            $bucket = $panel_count <= 1 ? 'panel_1' : ($panel_count == 2 ? 'panel_2' : ($panel_count == 3 ? 'panel_3' : 'panel_more'));
            $capacity[$bucket]++;
            $capacity_qty[$bucket] += $ready_qty;
        }

        usort($details, function ($a, $b) {
            return $b['ready_qty'] <=> $a['ready_qty'];
        });

        $ready_qty = array_sum(array_column($details, 'ready_qty'));
        $ready_pdk = count($details);
        $ready_panels = array_sum(array_column($details, 'panel_count'));

        return array(
            'available' => TRUE,
            'in_qty' => $this->sum_qty($inflow['rows'], $in_index),
            'out_qty' => abs($this->sum_qty($outflow['rows'], $out_index)),
            'ready_qty' => $ready_qty,
            'ready_pdk' => $ready_pdk,
            'ready_panels' => $ready_panels,
            'qty_per_pdk' => $ready_pdk ? $ready_qty / $ready_pdk : 0,
            'panels_per_pdk' => $ready_pdk ? $ready_panels / $ready_pdk : 0,
            'capacity_buckets' => $capacity,
            'capacity_qty_buckets' => $capacity_qty,
            'periods' => $this->summarize_monthly_periods(),
            'ready_to_load_periods' => $this->summarize_monthly_periods(),
            'top_ready' => array_slice($details, 0, 12),
        );
    }

    public function read_recent_logs($limit = 80)
    {
        $path = $this->log_path();
        if (!is_file($path)) {
            return array();
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES);
        return array_slice($lines ?: array(), -$limit);
    }

    public function run_download_once()
    {
        $rpa_root = $this->rpa_root_path();
        $command = 'cd /d ' . escapeshellarg($rpa_root) . ' && start /B python scheduler.py --once';
        pclose(popen('cmd /c ' . $command, 'r'));

        return array('ok' => TRUE, 'message' => 'Download dijalankan di background. Refresh status beberapa saat lagi.');
    }

    public function get_download_path($filename)
    {
        $allowed = array_column($this->reports, 'filename');
        if (!in_array($filename, $allowed, TRUE)) {
            return NULL;
        }

        $path = $this->latest_report_path($filename);
        return is_file($path) ? $path : NULL;
    }

    private function latest_report_path($filename)
    {
        $data_file = $this->data_file();
        if ($data_file) {
            return $data_file . '#' . $filename;
        }

        $path = $this->latest_matching_file_in_trees($this->all_data_dirs(), $this->report_filename_candidates($filename));
        if ($path) {
            return $path;
        }

        return $this->data_dir() . DIRECTORY_SEPARATOR . $filename;
    }

    private function report_filename_candidates($filename)
    {
        $info = pathinfo($filename);
        $dirname = isset($info['dirname']) && $info['dirname'] !== '.' ? $info['dirname'] . DIRECTORY_SEPARATOR : '';
        $name = isset($info['filename']) ? $info['filename'] : $filename;
        $extension = isset($info['extension']) ? strtolower($info['extension']) : '';
        $extensions = in_array($extension, array('xlsx', 'xls'), TRUE)
            ? array($extension === 'xlsx' ? 'xlsx' : 'xls', $extension === 'xlsx' ? 'xls' : 'xlsx')
            : array('xlsx', 'xls');

        $items = array();
        foreach ($extensions as $ext) {
            $items[] = $dirname . $name . '.' . $ext;
        }

        return array_values(array_unique($items));
    }

    private function read_html_report($path)
    {
        $sheet_hint = NULL;
        if (strpos($path, '#') !== FALSE) {
            list($path, $sheet_hint) = explode('#', $path, 2);
        }

        if (!is_file($path)) {
            return array('headers' => array(), 'rows' => array());
        }

        if ($this->is_xlsx_zip($path)) {
            return $this->read_xlsx_report($path, $sheet_hint);
        }

        $html = file_get_contents($path);
        $headers = array();
        if (preg_match('/<thead\b[^>]*>(.*?)<\/thead>/is', $html, $thead)) {
            preg_match_all('/<th\b[^>]*>(.*?)<\/th>/is', $thead[1], $matches);
            foreach ($matches[1] as $cell) {
                $headers[] = $this->normalize(html_entity_decode(strip_tags($cell), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            }
        }

        $rows = array();
        if (preg_match('/<tbody\b[^>]*>(.*?)<\/tbody>/is', $html, $tbody)) {
            $rows = $this->parse_table_rows($tbody[1]);
        } else {
            $rows = $this->parse_table_rows($html);
        }

        if (!$headers) {
            preg_match_all('/<th\b[^>]*>(.*?)<\/th>/is', $html, $matches);
            foreach ($matches[1] as $cell) {
                $headers[] = $this->normalize(html_entity_decode(strip_tags($cell), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            }
        }

        return array('headers' => $headers, 'rows' => $rows);
    }

    private function is_xlsx_zip($path)
    {
        $handle = fopen($path, 'rb');
        if (!$handle) {
            return FALSE;
        }

        $signature = fread($handle, 2);
        fclose($handle);

        return $signature === 'PK';
    }

    private function read_xlsx_report($path, $sheet_hint = NULL)
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== TRUE) {
            return array('headers' => array(), 'rows' => array());
        }

        $shared_strings = $this->read_xlsx_shared_strings($zip);
        $sheet_name = $this->xlsx_sheet_name_for_hint($zip, $sheet_hint);
        if (!$sheet_name) {
            $sheet_name = $this->first_xlsx_sheet_name($zip);
        }
        $sheet_xml = $sheet_name ? $zip->getFromName($sheet_name) : FALSE;
        $zip->close();

        if ($sheet_xml === FALSE) {
            return array('headers' => array(), 'rows' => array());
        }

        $xml = simplexml_load_string($sheet_xml);
        if (!$xml) {
            return array('headers' => array(), 'rows' => array());
        }

        $rows = array();
        foreach ($xml->sheetData->row as $row_node) {
            $cells = array();
            foreach ($row_node->c as $cell_node) {
                $ref = (string) $cell_node['r'];
                $index = $this->xlsx_column_index($ref);
                $cells[$index] = $this->xlsx_cell_value($cell_node, $shared_strings);
            }

            if (!$cells) {
                continue;
            }

            ksort($cells);
            $max = max(array_keys($cells));
            $row = array();
            for ($i = 0; $i <= $max; $i++) {
                $row[] = isset($cells[$i]) ? $this->normalize($cells[$i]) : '';
            }
            $rows[] = $row;
        }

        return $this->normalize_xlsx_report_rows($rows);
    }

    private function normalize_xlsx_report_rows($rows)
    {
        if (!$rows) {
            return array('headers' => array(), 'rows' => array());
        }

        $header_index = 0;
        $best_score = 0;
        foreach ($rows as $index => $row) {
            $score = $this->xlsx_header_score($row);
            if ($score > $best_score) {
                $best_score = $score;
                $header_index = $index;
            }
        }

        if ($best_score < 2) {
            return array(
                'headers' => array_shift($rows),
                'rows' => $rows,
            );
        }

        $header_row = $rows[$header_index];
        $next_row = isset($rows[$header_index + 1]) ? $rows[$header_index + 1] : array();
        $has_group_subheader = $this->xlsx_has_group_subheader($header_row, $next_row);
        $headers = $has_group_subheader
            ? $this->combine_xlsx_group_headers($header_row, $next_row)
            : $header_row;

        return array(
            'headers' => $headers,
            'rows' => array_slice($rows, $header_index + ($has_group_subheader ? 2 : 1)),
        );
    }

    private function xlsx_header_score($row)
    {
        $wanted = array(
            'customer name',
            'order no.',
            'jo',
            'delivery date',
            'plan qty',
            'process route',
            'process route name',
            'factory style',
            'date',
            'storage nr',
            'item nr',
            'qty',
            'cost center',
            'udef 1',
        );
        $values = array();
        foreach ($row as $value) {
            $values[] = strtolower($this->normalize($value));
        }

        $score = 0;
        foreach ($wanted as $name) {
            if (in_array($name, $values, TRUE)) {
                $score++;
            }
        }
        return $score;
    }

    private function xlsx_has_group_subheader($header_row, $next_row)
    {
        if (!$next_row) {
            return FALSE;
        }

        $groups = array('sewing', 'complete assembly', 'heat transfer');
        $has_group = FALSE;
        foreach ($header_row as $value) {
            if (in_array(strtolower($this->normalize($value)), $groups, TRUE)) {
                $has_group = TRUE;
                break;
            }
        }
        if (!$has_group) {
            return FALSE;
        }

        foreach ($next_row as $value) {
            $normalized = strtolower($this->normalize($value));
            if (in_array($normalized, array('plan qty', 'qty.', 'non-finished qty', 'schedule start date'), TRUE)) {
                return TRUE;
            }
        }

        return FALSE;
    }

    private function combine_xlsx_group_headers($header_row, $subheader_row)
    {
        $max = max(count($header_row), count($subheader_row));
        $headers = array();
        $current_group = '';
        $groups = array('sewing', 'complete assembly', 'heat transfer');

        for ($i = 0; $i < $max; $i++) {
            $header = isset($header_row[$i]) ? $this->normalize($header_row[$i]) : '';
            $subheader = isset($subheader_row[$i]) ? $this->normalize($subheader_row[$i]) : '';
            if ($header !== '') {
                $current_group = $header;
            }

            if ($subheader !== '' && in_array(strtolower($current_group), $groups, TRUE)) {
                $headers[] = $current_group . ' ' . $subheader;
                continue;
            }

            $headers[] = $header !== '' ? $header : $subheader;
        }

        return $headers;
    }

    private function read_xlsx_sheet_grid($path, $sheet_name)
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== TRUE) {
            return array('rows' => array(), 'max_columns' => 0);
        }

        $shared_strings = $this->read_xlsx_shared_strings($zip);
        $sheet_path = $this->xlsx_sheet_path_by_name($zip, $sheet_name);
        $sheet_xml = $sheet_path ? $zip->getFromName($sheet_path) : FALSE;
        $zip->close();

        if ($sheet_xml === FALSE) {
            return array('rows' => array(), 'max_columns' => 0);
        }

        $xml = simplexml_load_string($sheet_xml);
        if (!$xml) {
            return array('rows' => array(), 'max_columns' => 0);
        }

        $rows = array();
        $max_columns = 0;

        foreach ($xml->sheetData->row as $row_node) {
            $cells = array();
            foreach ($row_node->c as $cell_node) {
                $ref = (string) $cell_node['r'];
                $index = $this->xlsx_column_index($ref);
                $cells[$index] = $this->normalize($this->xlsx_cell_value($cell_node, $shared_strings));
            }

            if (!$cells) {
                $rows[] = array();
                continue;
            }

            ksort($cells);
            $row_max = max(array_keys($cells));
            $max_columns = max($max_columns, $row_max + 1);
            $row = array();
            for ($i = 0; $i <= $row_max; $i++) {
                $row[] = isset($cells[$i]) ? $cells[$i] : '';
            }
            $rows[] = $row;
        }

        $rows = $this->trim_empty_grid($rows, $max_columns);
        return array('rows' => $rows, 'max_columns' => $max_columns);
    }

    private function xlsx_sheet_path_by_name($zip, $sheet_name)
    {
        foreach ($this->xlsx_sheet_map($zip) as $title => $path) {
            if (strcasecmp(trim($title), trim($sheet_name)) === 0) {
                return $path;
            }
        }

        return NULL;
    }

    private function trim_empty_grid($rows, &$max_columns)
    {
        while ($rows && !$this->row_has_value($rows[0])) {
            array_shift($rows);
        }

        while ($rows && !$this->row_has_value($rows[count($rows) - 1])) {
            array_pop($rows);
        }

        $max_columns = 0;
        foreach ($rows as $row) {
            $max_columns = max($max_columns, count($row));
        }

        return array_map(function ($row) use ($max_columns) {
            for ($i = count($row); $i < $max_columns; $i++) {
                $row[] = '';
            }
            return $row;
        }, $rows);
    }

    private function row_has_value($row)
    {
        foreach ($row as $cell) {
            if ($this->normalize($cell) !== '') {
                return TRUE;
            }
        }

        return FALSE;
    }

    private function extract_qty_pdk_vs_output($rows)
    {
        $items = array();

        for ($row = 5; $row <= 8; $row++) {
            $label = $this->grid_cell($rows, $row, 'H') ?: $this->grid_cell($rows, $row, 'A');
            if ($label === '' || stripos($label, 'grand') !== FALSE) {
                continue;
            }

            $items[] = array(
                'label' => $label,
                'pdk' => $this->parse_number($this->grid_cell($rows, $row, 'I') ?: $this->grid_cell($rows, $row, 'B')),
                'output' => $this->parse_number($this->grid_cell($rows, $row, 'J') ?: $this->grid_cell($rows, $row, 'F')),
            );
        }

        return $items;
    }

    private function extract_ready_to_load($rows)
    {
        $items = array();

        for ($row = 6; $row <= 12; $row++) {
            $label = $this->grid_cell($rows, $row, 'Q');
            $ready = $this->parse_number($this->grid_cell($rows, $row, 'R'));
            if ($label === '' || $ready <= 0) {
                continue;
            }

            $items[] = array(
                'label' => $label,
                'ready' => $ready,
            );
        }

        return $items;
    }

    private function extract_output_vs_capacity($rows)
    {
        $items = array();

        for ($row = 3; $row <= 40; $row++) {
            $day = $this->grid_cell($rows, $row, 'AR');
            $output = $this->parse_number($this->grid_cell($rows, $row, 'AS'));
            $capacity = $this->parse_number($this->grid_cell($rows, $row, 'AT'));

            if ($day === '' || ($output <= 0 && $capacity <= 0)) {
                continue;
            }

            $items[] = array(
                'label' => $this->format_output_day_label($day, $rows),
                'output' => $output,
                'capacity' => $capacity,
            );
        }

        return $items;
    }

    private function format_output_day_label($day, $rows)
    {
        $month_number = $this->parse_number($this->grid_cell($rows, 3, 'B'));
        if ($month_number <= 0) {
            foreach (array('F', 'J', 'N', 'R', 'V') as $column) {
                $month_number = $this->parse_number($this->grid_cell($rows, 3, $column));
                if ($month_number > 0) {
                    break;
                }
            }
        }

        $month = $month_number > 0 && isset($this->months[(int) $month_number - 1])
            ? $this->months[(int) $month_number - 1]
            : '';

        return trim($day . ' ' . $month . ' ' . date('Y'));
    }

    private function extract_top_priority_orders($rows)
    {
        $items = array();

        for ($row = 7; $row <= 80 && count($items) < 10; $row++) {
            $order = $this->grid_cell($rows, $row, 'Y');
            if ($order === '') {
                continue;
            }

            $items[] = array(
                'order' => $order,
                'style' => $this->grid_cell($rows, $row, 'Z'),
                'delivery' => $this->format_excel_date($this->grid_cell($rows, $row, 'AA')),
                'qty_pdk' => $this->parse_number($this->grid_cell($rows, $row, 'AB')),
                'qty_ready' => $this->parse_number($this->grid_cell($rows, $row, 'AC')),
                'qty_out_aps' => $this->parse_number($this->grid_cell($rows, $row, 'AD')),
                'qty_out_engage' => $this->parse_number($this->grid_cell($rows, $row, 'AE')),
            );
        }

        return $items;
    }

    private function heat_rpa_sources()
    {
        $aps_file = NULL;
        $accessories_file = NULL;

        foreach ($this->rpa_root_candidates() as $rpa_root) {
            if ($aps_file === NULL) {
                $dirs = array_values(array_filter(array(
                    $rpa_root . DIRECTORY_SEPARATOR . 'aps-rpa' . DIRECTORY_SEPARATOR . 'downloads',
                    $rpa_root . DIRECTORY_SEPARATOR . 'aps-rpa' . DIRECTORY_SEPARATOR . 'archive',
                ), 'is_dir'));

                if (!empty($dirs)) {
                    $files = $this->matching_files_in_trees($dirs, array('JO.xlsx', 'JO.xls'));
                    if (empty($files)) {
                        $files = $this->matching_files_in_trees($dirs, array('JO_backup_*.xlsx', 'JO_backup_*.xls'));
                    }
                    if (empty($files)) {
                        $files = $this->matching_files_in_trees($dirs, array('JO_*.xlsx', 'JO_*.xls'));
                    }
                    if (!empty($files)) {
                        $aps_file = $files[0];
                    }
                }
            }

            if ($accessories_file === NULL) {
                $dirs = array_values(array_filter(array(
                    $rpa_root . DIRECTORY_SEPARATOR . 'accessories-rpa' . DIRECTORY_SEPARATOR . 'downloads',
                    $rpa_root . DIRECTORY_SEPARATOR . 'accessories-rpa' . DIRECTORY_SEPARATOR . 'archive',
                ), 'is_dir'));

                if (!empty($dirs)) {
                    $files = $this->matching_files_in_trees($dirs, array(
                        'CONTROLIST.xlsx',
                        'CONTROLIST.xls',
                        'CONTROLIST_*.xlsx',
                        'CONTROLIST_*.xls',
                    ));
                    if (!empty($files)) {
                        $accessories_file = $files[0];
                    }
                }
            }

            if ($aps_file !== NULL && $accessories_file !== NULL) {
                break;
            }
        }

        $inflow = NULL;
        $outflow = NULL;
        foreach ($this->rpa_root_candidates() as $rpa_root) {
            $e_dir = $rpa_root . DIRECTORY_SEPARATOR . 'engage-rpa' . DIRECTORY_SEPARATOR . 'downloads';
            $e_arc = $rpa_root . DIRECTORY_SEPARATOR . 'engage-rpa' . DIRECTORY_SEPARATOR . 'archive';
            if ($inflow === NULL) {
                $inflow = $this->resolve_engage_source($e_dir, $e_arc, '32a_inflow');
            }
            if ($outflow === NULL) {
                $outflow = $this->resolve_engage_source($e_dir, $e_arc, '32a_outflow');
            }
            if ($inflow && $outflow) {
                break;
            }
        }

        return array(
            'aps' => $aps_file,
            'accessories' => $accessories_file,
            'engage_32a_inflow' => $inflow,
            'engage_32a_outflow' => $outflow,
        );
    }
    private function latest_matching_file($pattern)
    {
        $patterns = is_array($pattern) ? $pattern : array($pattern);
        $files = array();
        foreach ($patterns as $item) {
            $files = array_merge($files, glob($item) ?: array());
        }
        $files = array_filter($files, 'is_file');
        $files = array_values(array_filter($files, function ($file) {
            return preg_match('/(^|\\\\|\\/)~\\$/', $file) !== 1;
        }));
        if (!$files) {
            return NULL;
        }

        usort($files, function ($a, $b) {
            return filemtime($b) <=> filemtime($a);
        });

        return $files[0];
    }

    private function latest_existing_file($paths)
    {
        $files = array_values(array_filter($paths, function ($path) {
            return is_file($path) && preg_match('/(^|\\\\|\\/)~\\$/', $path) !== 1;
        }));
        if (!$files) {
            return isset($paths[0]) ? $paths[0] : NULL;
        }

        usort($files, function ($a, $b) {
            return filemtime($b) <=> filemtime($a);
        });

        return $files[0];
    }

    private function resolve_engage_source($engage_dir, $engage_archive, $report_key)
    {
        $dirs = array();
        if (is_array($engage_dir)) {
            $dirs = array_merge($dirs, $engage_dir);
        } elseif (!empty($engage_dir)) {
            $dirs[] = $engage_dir;
        }

        if (is_array($engage_archive)) {
            $dirs = array_merge($dirs, $engage_archive);
        } elseif (!empty($engage_archive)) {
            $dirs[] = $engage_archive;
        }

        foreach ($this->rpa_root_candidates() as $rpa_root) {
            $dirs[] = $rpa_root . DIRECTORY_SEPARATOR . 'engage-rpa' . DIRECTORY_SEPARATOR . 'downloads';
            $dirs[] = $rpa_root . DIRECTORY_SEPARATOR . 'engage-rpa' . DIRECTORY_SEPARATOR . 'archive';
        }

        $dirs = array_values(array_filter(array_unique($dirs), 'is_dir'));
        $storage_code = (strpos($report_key, '32a') !== FALSE) ? '32a' : '32';

        return $this->latest_matching_file_in_trees($dirs, array(
            '*' . $report_key . '.xlsx',
            '*' . $report_key . '.xls',
            '*' . $report_key . '*.xlsx',
            '*' . $report_key . '*.xls',
            $storage_code . '_engage.xlsx',
            '*' . $storage_code . '_engage*.xlsx',
        ));
    }

    private function latest_matching_file_in_tree($base_dir, array $name_patterns)
    {
        if (!is_dir($base_dir)) {
            return NULL;
        }

        $regexes = array();
        foreach ($name_patterns as $pattern) {
            $quoted = preg_quote($pattern, '#');
            $regexes[] = '#^' . str_replace('\\*', '.*', $quoted) . '$#i';
        }

        $files = array();
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($base_dir, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $fileinfo) {
            if (!$fileinfo->isFile()) {
                continue;
            }

            $filename = $fileinfo->getFilename();
            if (preg_match('/^~\\$/', $filename)) {
                continue;
            }
            foreach ($regexes as $regex) {
                if (preg_match($regex, $filename)) {
                    $files[] = $fileinfo->getPathname();
                    break;
                }
            }
        }

        if (!$files) {
            return NULL;
        }

        usort($files, function ($a, $b) {
            return filemtime($b) <=> filemtime($a);
        });

        return $files[0];
    }

    private function latest_mtime_iso($sources)
    {
        $mtime = 0;
        foreach ($sources as $path) {
            if ($path && is_file($path)) {
                $mtime = max($mtime, filemtime($path));
            }
        }

        if ($this->has_engage_db_data()) {
            $db_iso = $this->latest_engage_db_time_iso();
            if ($db_iso) {
                $mtime = max($mtime, strtotime($db_iso));
            }
        }

        return $mtime ? date('c', $mtime) : date('c');
    }

    private function source_status_rows($sources)
    {
        $rows = array();
        $has_db = $this->has_engage_db_data();
        $db_time = $has_db ? $this->latest_engage_db_time_iso() : NULL;

        foreach ($sources as $key => $path) {
            $is_engage = (strpos($key, 'engage_') === 0);
            $file_exists = ($path && is_file($path));
            $exists = $file_exists || ($is_engage && $has_db);
            $updated_at = $file_exists ? date('c', filemtime($path)) : ($is_engage && $has_db ? $db_time : NULL);
            $display_path = $path ? $path : ($is_engage && $has_db ? 'MySQL Database (tb_engage_transactions / tb_engage_archieve)' : NULL);

            $rows[] = array(
                'key' => $key,
                'path' => $display_path,
                'exists' => $exists,
                'updated_at' => $updated_at,
            );
        }
        return $rows;
    }

    private function read_combined_engage_outflow_report($current_path)
    {
        return $this->read_combined_engage_report($current_path, '32a_outflow');
    }

    private function read_all_engage_reports($report_key)
    {
        $dirs = array_filter(array_unique(array(
            $this->rpa_module_dir('engage-rpa', 'downloads'),
            $this->rpa_module_dir('engage-rpa', 'archive'),
        )), 'is_dir');

        $is_32a = (strpos($report_key, '32a') !== FALSE);
        $is_outflow = (strpos($report_key, 'outflow') !== FALSE);
        $storage_code = $is_32a ? '32a' : '32';

        $files = $this->matching_files_in_trees($dirs, array(
            '*' . $report_key . '.xlsx',
            '*' . $report_key . '.xls',
            '*' . $report_key . '*.xlsx',
            '*' . $report_key . '*.xls',
            $storage_code . '_engage.xlsx',
            '*' . $storage_code . '_engage*.xlsx',
        ));

        $rows = array();
        $headers = array();
        foreach ($files as $path) {
            $report = $this->read_html_report($path);
            if (!$report['headers']) {
                continue;
            }
            if (!$headers) {
                $headers = $report['headers'];
            }

            $index = $this->header_index($report['headers']);
            $is_combined_file = (stripos(basename($path), '_engage') !== FALSE);

            foreach ($report['rows'] as $r) {
                if ($is_combined_file) {
                    $row_qty = $this->parse_number($this->cell($r, $index, 'Qty'));
                    $row_storage = strtolower(trim((string) $this->cell($r, $index, 'Storage Nr')));
                    if ($row_storage !== '' && $row_storage !== strtolower($storage_code)) {
                        continue;
                    }
                    if ($is_outflow && $row_qty >= 0) {
                        continue;
                    }
                    if (!$is_outflow && $row_qty <= 0) {
                        continue;
                    }
                }
                $rows[] = $r;
            }
        }

        return array('headers' => $headers, 'rows' => $rows);
    }

    private function read_combined_engage_report($current_path, $report_key)
    {
        $storage = (strpos($report_key, '32a') !== FALSE) ? '32a' : '32';
        $direction = (strpos($report_key, 'outflow') !== FALSE) ? 'outflow' : 'inflow';

        // 1. Prioritaskan pembacaan dari database MySQL (tb_engage_transactions & tb_engage_archieve)
        $db_report = $this->read_engage_report_from_db($storage, $direction);
        if (!empty($db_report['rows'])) {
            return $db_report;
        }

        // 2. Fallback: baca dari file Excel (mendukung 32_engage.xlsx / 32a_engage.xlsx atau report legacy)
        return $this->read_all_engage_reports($report_key);
    }


    private function engage_rpa_history_roots()
    {
        $roots = array();
        foreach ($this->rpa_root_candidates() as $rpa_root) {
            $roots[] = $rpa_root . DIRECTORY_SEPARATOR . 'engage-rpa';
        }

        return array_values(array_unique(array_filter($roots, 'is_dir')));
    }

    private function build_heat_data_from_rpa_sources($aps, $inflow_32a, $outflow_32a, $accessories, $date_from = NULL, $date_to = NULL)
    {
        $from_str = (is_string($date_from) && preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($date_from))) ? trim($date_from) : '';
        $to_str = (is_string($date_to) && preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($date_to))) ? trim($date_to) : '';

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

        $aps_index = $this->header_index($aps['headers']);
        $orders = array();
        $periods = array();
        $pdk_by_period = array();
        $output_by_period = array();
        $pdk_by_period_with_ofc = array();
        $output_by_period_with_ofc = array();

        // Master lookup order dari JO (mencakup semua baris JO untuk mendapatkan tanggal delivery asli)
        $jo_order_master = array();
        foreach ($aps['rows'] as $row) {
            $jo = $this->cell_any($row, $aps_index, array('JO', 'Order No.', 'Order'));
            $cust_order = $this->cell_any($row, $aps_index, array('Cust_Order_No', 'Cust Order No', 'Cust Order No.', 'Cust_Order'));
            $order = $this->normalize_order_number($jo);
            if ($order === '') {
                $order = $this->normalize_order_number($cust_order);
            }
            if ($order === '') {
                continue;
            }

            $delivery = $this->cell_any($row, $aps_index, array('Delivery Date', 'Delivery date', 'Delivery', 'Del Date', 'Del. Date', 'Delivery Date.'));
            $style = $this->cell_any($row, $aps_index, array('Factory Style', 'Cust. Style', 'Style'));
            $item = $this->cell_any($row, $aps_index, array('Item Nr', 'Item', 'Item No', 'Item No.'));
            $period = $this->period_label_from_date_value($delivery);
            $route = strtoupper($this->cell_any($row, $aps_index, array('Process Route', 'Process Route Name')));

            if (!isset($jo_order_master[$order])) {
                $jo_order_master[$order] = array(
                    'order' => $order,
                    'style' => $style,
                    'delivery' => $delivery,
                    'item' => $item,
                    'period' => $period,
                    'process' => $route,
                    'route' => $route,
                );
            } else {
                if ($jo_order_master[$order]['delivery'] === '' && $delivery !== '') {
                    $jo_order_master[$order]['delivery'] = $delivery;
                    $jo_order_master[$order]['period'] = $period;
                }
                if ($jo_order_master[$order]['style'] === '' && $style !== '') {
                    $jo_order_master[$order]['style'] = $style;
                }
                if ($jo_order_master[$order]['item'] === '' && $item !== '') {
                    $jo_order_master[$order]['item'] = $item;
                }
                if (empty($jo_order_master[$order]['process']) && $route !== '') {
                    $jo_order_master[$order]['process'] = $route;
                    $jo_order_master[$order]['route'] = $route;
                }
            }
        }

        foreach ($aps['rows'] as $row) {
            $route = strtoupper($this->cell_any($row, $aps_index, array('Process Route', 'Process Route Name')));
            $heat_plan = $this->parse_number($this->cell_any($row, $aps_index, array('HEAT TRANSFER Plan qty', 'Heat Transfer Plan Qty')));
            $heat_finished = $this->parse_number($this->cell_any($row, $aps_index, array('HEAT TRANSFER Qty.', 'Heat Transfer Qty')));
            $heat_balance = $this->parse_number($this->cell_any($row, $aps_index, array('HEAT TRANSFER Non-finished Qty', 'Heat Transfer Non Finished Qty')));
            if (!$this->is_heat_rpa_route($route) && $heat_plan <= 0 && $heat_finished <= 0 && $heat_balance <= 0) {
                continue;
            }

            $jo = $this->cell_any($row, $aps_index, array('JO', 'Order No.'));
            $cust_order = $this->cell_any($row, $aps_index, array('Cust_Order_No', 'Cust Order No', 'Cust Order No.'));
            $order = $this->normalize_order_number($jo);
            if ($order === '') {
                $order = $this->normalize_order_number($cust_order);
            }
            if ($order === '') {
                continue;
            }

            $is_ofc = $this->is_ofc_order($jo) || $this->is_ofc_order($cust_order) || $this->is_ofc_order($order);

            $qty = $this->parse_number($this->cell_any($row, $aps_index, array('Plan Qty', 'Plan qty', 'Plan Qty.')));
            if ($qty <= 0) {
                $qty = $heat_plan > 0 ? $heat_plan : $this->parse_number($this->cell($row, $aps_index, 'Qty'));
            }
            if ($qty <= 0) {
                continue;
            }

            $delivery = $this->cell_any($row, $aps_index, array('Delivery Date', 'Delivery date', 'Delivery', 'Del Date', 'Del. Date', 'Delivery Date.'));
            $period = $this->period_label_from_date_value($delivery);
            if ($period === '') {
                continue;
            }

            $finished = $heat_finished > 0 ? $heat_finished : $this->parse_number($this->cell_any($row, $aps_index, array('Finished Qty', 'Qty.')));
            $style = $this->cell($row, $aps_index, 'Factory Style');
            if ($style === '') {
                $style = $this->cell($row, $aps_index, 'Cust. Style');
            }

            $this->ensure_period_bucket($periods, $period);

            // Akumulasi termasuk OFC untuk grafik Kapasitas vs Demand
            $pdk_by_period_with_ofc[$period] = isset($pdk_by_period_with_ofc[$period]) ? $pdk_by_period_with_ofc[$period] + $qty : $qty;
            $output_by_period_with_ofc[$period] = isset($output_by_period_with_ofc[$period]) ? $output_by_period_with_ofc[$period] + $finished : $finished;

            // Akumulasi tanpa OFC untuk Target vs Aktual dan list orders
            if (!$is_ofc) {
                $pdk_by_period[$period] = isset($pdk_by_period[$period]) ? $pdk_by_period[$period] + $qty : $qty;
                $output_by_period[$period] = isset($output_by_period[$period]) ? $output_by_period[$period] + $finished : $finished;

                if (!isset($orders[$order])) {
                    $orders[$order] = array(
                        'order' => $order,
                        'style' => $style,
                        'delivery' => $delivery,
                        'period' => $period,
                        'process' => $route,
                        'route' => $route,
                        'qty_pdk' => 0,
                        'qty_out_aps' => 0,
                    );
                } else {
                    if (empty($orders[$order]['process']) && $route !== '') {
                        $orders[$order]['process'] = $route;
                        $orders[$order]['route'] = $route;
                    }
                }
                $orders[$order]['qty_pdk'] += $qty;
                $orders[$order]['qty_out_aps'] += $finished;
            }
        }

        $in_summary = $this->summarize_engage_rows_by_order($inflow_32a, $this->engage_filter_rules_32a());
        $out_summary = $this->summarize_engage_rows_by_order($outflow_32a, $this->engage_filter_rules_32a());
        $accessories_ready = $this->summarize_accessories_completed_orders($accessories);

        $ready_by_period = array();
        $ready_completed_by_period = array();
        $ready_uncompleted_by_period = array();
        $ready_by_order = array();
        foreach ($in_summary['orders'] as $order => $in) {
            if ($this->is_ofc_order($order)) {
                continue;
            }
            $out_qty = isset($out_summary['orders'][$order]) ? $out_summary['orders'][$order]['qty'] : 0;
            $ready_qty = $in['qty'] - $out_qty;
            if ($ready_qty <= 0) {
                continue;
            }

            $jo_info = isset($orders[$order]) ? $orders[$order] : (isset($jo_order_master[$order]) ? $jo_order_master[$order] : null);
            // Jika order tidak terdaftar di JO atau tidak memiliki tanggal delivery di JO, tidak usah ditampilkan
            if (!$jo_info || empty($jo_info['delivery'])) {
                continue;
            }

            $delivery_jo = $jo_info['delivery'];
            $period = !empty($jo_info['period']) ? $jo_info['period'] : $this->period_label_from_date_value($delivery_jo);
            if ($period === '') {
                continue;
            }

            $source_label = '32a';
            $is_acc_completed = !empty($accessories_ready[$order]);

            $this->ensure_period_bucket($periods, $period);
            $ready_by_period[$period] = isset($ready_by_period[$period]) ? $ready_by_period[$period] + $ready_qty : $ready_qty;
            if ($is_acc_completed) {
                $ready_completed_by_period[$period] = isset($ready_completed_by_period[$period]) ? $ready_completed_by_period[$period] + $ready_qty : $ready_qty;
            } else {
                $ready_uncompleted_by_period[$period] = isset($ready_uncompleted_by_period[$period]) ? $ready_uncompleted_by_period[$period] + $ready_qty : $ready_qty;
            }

            $ready_by_order[$order] = array(
                'order' => $order,
                'style' => (!empty($jo_info['style'])) ? $jo_info['style'] : $in['style'],
                'delivery' => $delivery_jo,
                'item' => (!empty($jo_info['item'])) ? $jo_info['item'] : $in['item'],
                'period' => $period,
                'qty' => $ready_qty,
                'source' => $source_label,
                'accessories_completed' => $is_acc_completed ? 1 : 0,
            );
        }

        $period_labels = array_keys($periods);
        usort($period_labels, array($this, 'compare_period_labels'));

        $all_qty_rows = array();
        $all_qty_rows_with_ofc = array();
        foreach ($period_labels as $period) {
            $pdk = isset($pdk_by_period[$period]) ? $pdk_by_period[$period] : 0;
            $output = isset($output_by_period[$period]) ? $output_by_period[$period] : 0;
            $pdk_ofc = isset($pdk_by_period_with_ofc[$period]) ? $pdk_by_period_with_ofc[$period] : 0;
            $output_ofc = isset($output_by_period_with_ofc[$period]) ? $output_by_period_with_ofc[$period] : 0;

            if ($pdk > 0 || $output > 0) {
                $all_qty_rows[] = array('label' => $period, 'pdk' => $pdk, 'output' => $output);
            }
            if ($pdk_ofc > 0 || $output_ofc > 0) {
                $all_qty_rows_with_ofc[] = array('label' => $period, 'pdk' => $pdk_ofc, 'output' => $output_ofc);
            }
        }

        // Filter periode yang beririsan dengan rentang tanggal [$from_str, $to_str]
        $matching_indices = array();
        foreach ($all_qty_rows as $idx => $row) {
            $range = $this->period_date_range($row['label']);
            if ($range && max($range['start'], $from_str) <= min($range['end'], $to_str)) {
                $matching_indices[] = $idx;
            }
        }

        if (!empty($matching_indices)) {
            $selected_qty_pdk_vs_output = array();
            $selected_qty_pdk_vs_output_with_ofc = array();
            foreach ($matching_indices as $idx) {
                $selected_qty_pdk_vs_output[] = $all_qty_rows[$idx];
                $selected_qty_pdk_vs_output_with_ofc[] = isset($all_qty_rows_with_ofc[$idx])
                    ? $all_qty_rows_with_ofc[$idx]
                    : $all_qty_rows[$idx];
            }

            if (count($selected_qty_pdk_vs_output) === 1) {
                $target_period = $selected_qty_pdk_vs_output[0]['label'];
                $period_type = (stripos($target_period, 'MID') !== FALSE) ? 'MID' : 'END';
                $period_range = $this->period_date_range($target_period);
                $period_display_range = $period_range
                    ? date('d M Y', strtotime($period_range['start'])) . ' - ' . date('d M Y', strtotime($period_range['end']))
                    : '';
            } else {
                $first_label = $selected_qty_pdk_vs_output[0]['label'];
                $last_label = end($selected_qty_pdk_vs_output)['label'];
                $target_period = $first_label . ' - ' . $last_label;
                $period_type = 'RANGE';
                $first_range = $this->period_date_range($first_label);
                $last_range = $this->period_date_range($last_label);
                $period_range = array(
                    'start' => $first_range ? $first_range['start'] : $from_str,
                    'end' => $last_range ? $last_range['end'] : $to_str,
                );
                $period_display_range = date('d M Y', strtotime($period_range['start'])) . ' - ' . date('d M Y', strtotime($period_range['end']));
            }
        } else {
            $from_label = $this->period_label_from_date_value($from_str);
            $to_label = $this->period_label_from_date_value($to_str);
            if ($from_label === $to_label || $to_label === '') {
                $target_period = $from_label ?: 'MID ' . date('M');
                $period_type = (stripos($target_period, 'MID') !== FALSE) ? 'MID' : 'END';
                $selected_qty_pdk_vs_output = array(array('label' => $target_period, 'pdk' => 0, 'output' => 0));
            } else {
                $target_period = $from_label . ' - ' . $to_label;
                $period_type = 'RANGE';
                $selected_qty_pdk_vs_output = array(
                    array('label' => $from_label, 'pdk' => 0, 'output' => 0),
                    array('label' => $to_label, 'pdk' => 0, 'output' => 0),
                );
            }
            $selected_qty_pdk_vs_output_with_ofc = $selected_qty_pdk_vs_output;
            $period_range = array('start' => $from_str, 'end' => $to_str);
            $period_display_range = date('d M Y', strtotime($from_str)) . ' - ' . date('d M Y', strtotime($to_str));
        }

        $qty_pdk_vs_output = $selected_qty_pdk_vs_output;
        $qty_pdk_vs_output_with_ofc = $selected_qty_pdk_vs_output_with_ofc;
        $balance_breakdown = $selected_qty_pdk_vs_output;

        $ready_to_load = array();
        foreach ($qty_pdk_vs_output as $row) {
            $period = $row['label'];
            $ready = isset($ready_by_period[$period]) ? (int) $ready_by_period[$period] : 0;
            $completed = isset($ready_completed_by_period[$period]) ? (int) $ready_completed_by_period[$period] : 0;
            $uncompleted = isset($ready_uncompleted_by_period[$period]) ? (int) $ready_uncompleted_by_period[$period] : max(0, $ready - $completed);
            $ready_to_load[] = array(
                'label' => $period,
                'ready' => $ready,
                'completed' => $completed,
                'uncompleted' => $uncompleted,
            );
        }

        $selected_ready_to_load = array();
        foreach ($selected_qty_pdk_vs_output as $row) {
            $period = $row['label'];
            $ready = isset($ready_by_period[$period]) ? (int) $ready_by_period[$period] : 0;
            $completed = isset($ready_completed_by_period[$period]) ? (int) $ready_completed_by_period[$period] : 0;
            $uncompleted = isset($ready_uncompleted_by_period[$period]) ? (int) $ready_uncompleted_by_period[$period] : max(0, $ready - $completed);
            $selected_ready_to_load[] = array(
                'label' => $period,
                'ready' => $ready,
                'completed' => $completed,
                'uncompleted' => $uncompleted,
            );
        }

        $total_pdk = array_sum(array_column($selected_qty_pdk_vs_output, 'pdk'));
        $total_output = array_sum(array_column($selected_qty_pdk_vs_output, 'output'));
        $balance_qty = max(0, $total_pdk - $total_output);
        $prod_days_left = $this->source_prod_days_left($selected_qty_pdk_vs_output);
        $selected_order_whitelist = $this->selected_delivery_order_whitelist($orders, $selected_qty_pdk_vs_output);
        $out_summary_scoped = $this->summarize_engage_rows_by_order($outflow_32a, $this->engage_filter_rules_32a(), $selected_order_whitelist);
        $list_orders = $this->build_list_orders_from_rpa($ready_by_order, $orders, $out_summary['orders'], $selected_qty_pdk_vs_output, $in_summary['orders']);
        // Grafik: output/input dari Engage. Kapasitas & Demand dari data agregat termasuk OFC.
        $daily_output_vs_capacity = $this->build_output_vs_capacity_from_engage_daily(
            $out_summary['daily'],
            $selected_qty_pdk_vs_output_with_ofc,
            $in_summary['daily'],
            $out_summary_scoped['daily'],
            $qty_pdk_vs_output_with_ofc,
            $list_orders
        );

        $today = date('Y-m-d');
        $latest_in_day = !empty($in_summary['daily']) ? max(array_keys($in_summary['daily'])) : $today;
        $latest_out_day = !empty($out_summary['daily']) ? max(array_keys($out_summary['daily'])) : $today;
        $active_chart_day = max($today, $latest_out_day, $latest_in_day);

        $hourly_chart = $this->build_today_hourly_output_vs_capacity(
            $active_chart_day,
            isset($in_summary['hourly']) ? $in_summary['hourly'] : array(),
            isset($out_summary['hourly']) ? $out_summary['hourly'] : array(),
            $daily_output_vs_capacity
        );

        // Fallback to daily chart when today's hourly data has no actual in/out values
        // (e.g., RPA sync stores all timestamps as midnight so hourly bucketing produces zeros)
        $hourly_has_data = FALSE;
        if (is_array($hourly_chart)) {
            foreach ($hourly_chart as $hr) {
                if ((isset($hr['input']) && (float)$hr['input'] > 0) ||
                    (isset($hr['output']) && (float)$hr['output'] > 0)) {
                    $hourly_has_data = TRUE;
                    break;
                }
            }
        }
        $output_vs_capacity = $hourly_has_data ? $hourly_chart : $daily_output_vs_capacity;

        $total_pdk_with_ofc = array_sum(array_column($selected_qty_pdk_vs_output_with_ofc, 'pdk'));
        $total_output_with_ofc = array_sum(array_column($selected_qty_pdk_vs_output_with_ofc, 'output'));
        $balance_qty_with_ofc = max(0, $total_pdk_with_ofc - $total_output_with_ofc);

        return array(
            'total_pdk' => $total_pdk,
            'total_output' => $total_output,
            'balance_qty' => $balance_qty,
            'balance_qty_with_ofc' => $balance_qty_with_ofc,
            'prod_days_left' => $prod_days_left,
            'qty_pdk_vs_output' => $selected_qty_pdk_vs_output,
            'selected_qty_pdk_vs_output' => $selected_qty_pdk_vs_output,
            'balance_breakdown' => $selected_qty_pdk_vs_output,
            'ready_to_load' => $selected_ready_to_load,
            'selected_ready_to_load' => $selected_ready_to_load,
            'output_vs_capacity' => $output_vs_capacity,
            'daily_output_vs_capacity' => $daily_output_vs_capacity,
            'list_orders' => $list_orders,
            'material_to_load' => $list_orders,
            'top_priority_orders' => $this->build_priority_orders_from_rpa($ready_by_order, $orders, $out_summary['orders'], $jo_order_master),
            'selected_date' => $from_str,
            'selected_date_from' => $from_str,
            'selected_date_to' => $to_str,
            'selected_period' => $target_period,
            'period_type' => $period_type,
            'period_range' => $period_range,
            'period_display_range' => $period_display_range,
            'all_periods' => $all_qty_rows,
        );
    }

    private function is_heat_rpa_route($route)
    {
        return strpos($route, 'HT') !== FALSE;
    }

    private function is_ofc_order($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return FALSE;
        }

        return stripos($value, 'OFC') !== FALSE;
    }

    private function normalize_delivery_count($delivery_count)
    {
        $delivery_count = (int) $delivery_count;
        return in_array($delivery_count, array(1, 2, 4, 6), TRUE) ? $delivery_count : 4;
    }

    private function normalize_order_number($value)
    {
        $value = $this->normalize($value);
        if (preg_match('/\d{10}-\d+/', $value, $match)) {
            return $match[0];
        }
        return $value;
    }

    private function period_label_from_date_value($value)
    {
        $timestamp = $this->parse_date_timestamp($value);
        if (!$timestamp) {
            return '';
        }
        $month = (int) date('n', $timestamp);
        $label = isset($this->months[$month - 1]) ? $this->months[$month - 1] : date('M', $timestamp);
        return ((int) date('j', $timestamp) <= 15 ? 'MID ' : 'END ') . $label;
    }

    private function parse_date_timestamp($value)
    {
        $value = $this->normalize($value);
        if ($value === '') {
            return 0;
        }
        if (is_numeric($value)) {
            $serial = (float) $value;
            $raw_ts = ($serial - 25569) * 86400;
            $m = (int) gmdate('n', (int) $raw_ts);
            $d = (int) gmdate('j', (int) $raw_ts);
            $y = (int) gmdate('Y', (int) $raw_ts);
            if ($d <= 12) {
                // Di Excel display M/D/Y (e.g. 8/9/2026), komponen pertama $m adalah Hari (8) dan $d adalah Bulan (9 = September)
                $swapped_ts = strtotime(sprintf('%04d-%02d-%02d', $y, $d, $m));
                if ($swapped_ts !== false) {
                    return $swapped_ts;
                }
            }
            return $raw_ts;
        }
        // Format DD/MM/YYYY atau DD/MM/YY
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{2,4})(?:\s+(\d{1,2}):(\d{2})(?::(\d{2}))?)?$/', $value, $match)) {
            $year = strlen($match[3]) === 2 ? '20' . $match[3] : $match[3];
            $time_part = isset($match[4]) ? (' ' . sprintf('%02d:%02d:%02d', (int) $match[4], (int) $match[5], isset($match[6]) ? (int) $match[6] : 0)) : '';
            return strtotime($year . '-' . sprintf('%02d', (int) $match[2]) . '-' . sprintf('%02d', (int) $match[1]) . $time_part);
        }
        // Format DD-MM-YYYY atau DD-MM-YY
        if (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{2,4})(?:\s+(\d{1,2}):(\d{2})(?::(\d{2}))?)?$/', $value, $match)) {
            $year = strlen($match[3]) === 2 ? '20' . $match[3] : $match[3];
            $time_part = isset($match[4]) ? (' ' . sprintf('%02d:%02d:%02d', (int) $match[4], (int) $match[5], isset($match[6]) ? (int) $match[6] : 0)) : '';
            return strtotime($year . '-' . sprintf('%02d', (int) $match[2]) . '-' . sprintf('%02d', (int) $match[1]) . $time_part);
        }
        // Format DD.MM.YYYY atau DD.MM.YY
        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{2,4})(?:\s+(\d{1,2}):(\d{2})(?::(\d{2}))?)?$/', $value, $match)) {
            $year = strlen($match[3]) === 2 ? '20' . $match[3] : $match[3];
            $time_part = isset($match[4]) ? (' ' . sprintf('%02d:%02d:%02d', (int) $match[4], (int) $match[5], isset($match[6]) ? (int) $match[6] : 0)) : '';
            return strtotime($year . '-' . sprintf('%02d', (int) $match[2]) . '-' . sprintf('%02d', (int) $match[1]) . $time_part);
        }
        // Format YYYY-MM-DD atau YYYY/MM/DD
        if (preg_match('/^(\d{4})[-\/](\d{1,2})[-\/](\d{1,2})(?:\s+(\d{1,2}):(\d{2})(?::(\d{2}))?)?$/', $value, $match)) {
            $time_part = isset($match[4]) ? (' ' . sprintf('%02d:%02d:%02d', (int) $match[4], (int) $match[5], isset($match[6]) ? (int) $match[6] : 0)) : '';
            return strtotime($match[1] . '-' . sprintf('%02d', (int) $match[2]) . '-' . sprintf('%02d', (int) $match[3]) . $time_part);
        }
        return strtotime($value) ?: 0;
    }


    private function merge_engage_summaries($summaries)
    {
        $merged = array('orders' => array(), 'daily' => array(), 'hourly' => array());

        foreach ($summaries as $summary) {
            foreach ($summary['orders'] as $order => $data) {
                if (!isset($merged['orders'][$order])) {
                    $merged['orders'][$order] = array(
                        'qty' => 0,
                        'style' => isset($data['style']) ? $data['style'] : '',
                        'date' => isset($data['date']) ? $data['date'] : '',
                        'item' => isset($data['item']) ? $data['item'] : '',
                    );
                }
                $merged['orders'][$order]['qty'] += isset($data['qty']) ? $data['qty'] : 0;
                if ($merged['orders'][$order]['style'] === '' && !empty($data['style'])) {
                    $merged['orders'][$order]['style'] = $data['style'];
                }
                if ($merged['orders'][$order]['date'] === '' && !empty($data['date'])) {
                    $merged['orders'][$order]['date'] = $data['date'];
                }
            }

            foreach ($summary['daily'] as $day => $qty) {
                $merged['daily'][$day] = isset($merged['daily'][$day]) ? $merged['daily'][$day] + $qty : $qty;
            }

            if (isset($summary['hourly']) && is_array($summary['hourly'])) {
                foreach ($summary['hourly'] as $day => $hours) {
                    if (!isset($merged['hourly'][$day])) {
                        $merged['hourly'][$day] = array();
                    }
                    foreach ($hours as $hr => $qty) {
                        $merged['hourly'][$day][$hr] = isset($merged['hourly'][$day][$hr]) ? $merged['hourly'][$day][$hr] + $qty : $qty;
                    }
                }
            }
        }

        return $merged;
    }

    private function engage_filter_rules_32()
    {
        return array(
            array('column' => 'Udef 5', 'keywords' => array('rpl')),
            array('column' => 'Udef 4', 'keywords' => array('rpl')),
            array('column' => 'Udef 6', 'keywords' => array('sk')),
            array(
                'column' => 'Text',
                'keywords' => array('rpl', 'ts', 'return', 'retutn', 'koreksi', 'sk'),
                'exclude_keywords' => array('csdb', 'csbd'),
            ),
        );
    }

    private function engage_filter_rules_32a()
    {
        return array(
            array('column' => 'Udef 5', 'keywords' => array('rpl')),
            array('column' => 'Udef 4', 'keywords' => array('rpl')),
            array('column' => 'Udef 6', 'keywords' => array('sk')),
            array('column' => 'Text', 'keywords' => array('csdb', 'csbd', 'ts')),
        );
    }


    private function summarize_engage_rows_by_order($report, $filter_rules = NULL, $order_whitelist = NULL)
    {
        if ($filter_rules === NULL) {
            $filter_rules = $this->engage_filter_rules_32a();
        }

        $index = $this->header_index($report['headers']);
        $orders = array();
        $daily = array();
        $hourly = array();
        $daily_materials = array();
        $hourly_materials = array();
        $seen = array();
        $calendar_days = $this->dashboard_calendar_days();

        foreach ($report['rows'] as $row) {
            if (!$this->engage_row_matches_filter_rules($row, $index, $filter_rules)) {
                continue;
            }

            $date = $this->cell($row, $index, 'Date');
            $timestamp = $this->parse_date_timestamp($date);
            $order = $this->normalize_order_number($this->cell($row, $index, 'Cost Center'));
            if ($order === '') {
                $order = $this->normalize_order_number($this->cell($row, $index, 'Prod. Nr'));
            }
            if ($order === '') {
                $order = $this->normalize_order_number($this->cell($row, $index, 'Udef 8'));
            }
            if ($order === '' || $this->is_ofc_order($order)) {
                continue;
            }
            $qty = abs($this->parse_number($this->cell($row, $index, 'Qty')));
            if ($qty <= 0) {
                continue;
            }
            if ($order_whitelist !== NULL && !isset($order_whitelist[$order])) {
                continue;
            }

            $item = $this->cell($row, $index, 'Item Nr');
            $material_key = $this->engage_heat_material_key($row, $index, $order);
            $identity = $date . "\n" . $material_key . "\n" . $qty . "\n" . $this->cell($row, $index, 'Udef 10');
            if (isset($seen[$identity])) {
                continue;
            }
            $seen[$identity] = TRUE;

            if (!isset($orders[$order])) {
                $orders[$order] = array(
                    'qty' => 0,
                    'style' => $this->cell($row, $index, 'Udef 1'),
                    'date' => $date,
                    'item' => $item,
                    'materials' => array(),
                );
            } elseif ($orders[$order]['item'] === '' && $item !== '') {
                $orders[$order]['item'] = $item;
            }
            $orders[$order]['materials'][$material_key] = max(isset($orders[$order]['materials'][$material_key]) ? $orders[$order]['materials'][$material_key] : 0, $qty);

            if ($timestamp) {
                $day = date('Y-m-d', $timestamp);
                $hour = date('H:00', $timestamp);
                $is_holiday = in_array($day, $calendar_days['holidays'], TRUE);
                $is_sunday = (int) date('w', $timestamp) === 0;
                $is_scheduled_sunday = in_array($day, $calendar_days['work_days'], TRUE) || in_array($day, $calendar_days['half_days'], TRUE);
                if (!$is_holiday && (!$is_sunday || $is_scheduled_sunday)) {
                    $daily_key = $day . "\n" . $material_key;
                    $daily_materials[$daily_key] = array(
                        'day' => $day,
                        'qty' => max(isset($daily_materials[$daily_key]['qty']) ? $daily_materials[$daily_key]['qty'] : 0, $qty),
                    );

                    $hourly_key = $day . "\n" . $hour . "\n" . $material_key;
                    $hourly_materials[$hourly_key] = array(
                        'day' => $day,
                        'hour' => $hour,
                        'qty' => max(isset($hourly_materials[$hourly_key]['qty']) ? $hourly_materials[$hourly_key]['qty'] : 0, $qty),
                    );
                }
            }
        }

        foreach ($orders as $order => $data) {
            $orders[$order]['qty'] = array_sum($data['materials']);
            unset($orders[$order]['materials']);
        }

        foreach ($daily_materials as $item) {
            $day = $item['day'];
            $daily[$day] = isset($daily[$day]) ? $daily[$day] + $item['qty'] : $item['qty'];
        }

        foreach ($hourly_materials as $item) {
            $day = $item['day'];
            $hour = $item['hour'];
            if (!isset($hourly[$day])) {
                $hourly[$day] = array();
            }
            $hourly[$day][$hour] = isset($hourly[$day][$hour]) ? $hourly[$day][$hour] + $item['qty'] : $item['qty'];
        }

        return array('orders' => $orders, 'daily' => $daily, 'hourly' => $hourly);
    }

    private function engage_row_matches_filter_rules($row, $index, $rules)
    {
        foreach ($rules as $rule) {
            if (empty($rule['exclude_keywords'])) {
                continue;
            }

            $value = strtolower($this->normalize($this->cell($row, $index, $rule['column'])));
            if ($value === '') {
                continue;
            }

            foreach ($rule['exclude_keywords'] as $keyword) {
                if ($keyword !== '' && strpos($value, strtolower($keyword)) !== FALSE) {
                    return FALSE;
                }
            }
        }

        foreach ($rules as $rule) {
            $value = strtolower($this->normalize($this->cell($row, $index, $rule['column'])));
            if ($value === '') {
                continue;
            }
            foreach ($rule['keywords'] as $keyword) {
                if ($keyword !== '' && strpos($value, strtolower($keyword)) !== FALSE) {
                    return TRUE;
                }
            }
        }

        return FALSE;
    }

    private function engage_heat_material_key($row, $index, $order)
    {
        $udef3 = strtoupper($this->normalize($this->cell($row, $index, 'Udef 3')));
        $udef4 = strtoupper($this->normalize($this->cell($row, $index, 'Udef 4')));

        if ($udef3 !== '' || $udef4 !== '') {
            return $order . "\n" . $udef3 . "\n" . $udef4;
        }

        return $order;
    }

    private function summarize_accessories_completed_orders($report)
    {
        if (empty($report['headers'])) {
            return array();
        }

        $index = $this->header_index($report['headers']);
        $orders = array();
        foreach ($report['rows'] as $row) {
            $order = $this->normalize_order_number($this->cell($row, $index, 'Order'));
            $status = strtoupper($this->cell($row, $index, 'Status Pesanan'));
            if ($order === '' || $this->is_ofc_order($order) || strpos($status, 'COMPLETED') === FALSE) {
                continue;
            }
            $orders[$order] = isset($orders[$order]) ? $orders[$order] + 1 : 1;
        }
        return $orders;
    }

    private function build_output_vs_capacity_from_engage_daily($engage_daily_output, $qty_rows, $engage_daily_input = array(), $engage_daily_for_balance = array(), $historical_qty_rows = NULL, $list_orders = NULL)
    {
        // 1. Gabungkan ringkasan harian dari tabel engage_daily_history agar data hari-hari sebelumnya (termasuk bulan lalu) tidak 0
        $engage_history = $this->read_engage_daily_history_rows();
        foreach ($engage_history as $hist_day => $hist) {
            if (!isset($engage_daily_output[$hist_day]) || (float) $engage_daily_output[$hist_day] <= 0) {
                $engage_daily_output[$hist_day] = $hist['output_qty'];
            }
            if (!isset($engage_daily_input[$hist_day]) || (float) $engage_daily_input[$hist_day] <= 0) {
                $engage_daily_input[$hist_day] = $hist['input_qty'];
            }
        }

        $selected_qty_rows = is_array($qty_rows) ? $qty_rows : array();
        $historical_qty_rows = is_array($historical_qty_rows) && $historical_qty_rows ? $historical_qty_rows : $selected_qty_rows;
        $selected_delivery_count = $this->active_delivery_count($selected_qty_rows);
        $selected_capacity_history = $this->read_heat_capacity_history($selected_delivery_count);
        $historical_capacity_history = $this->read_heat_capacity_history($selected_delivery_count);
        $selected_capacity_history_changed = FALSE;

        ksort($engage_daily_output);
        $latest_output_day = $engage_daily_output ? max(array_keys($engage_daily_output)) : date('Y-m-d');
        $latest_day = max($latest_output_day, date('Y-m-d'));
        $calendar_days = $this->dashboard_calendar_days();
        $holidays = isset($calendar_days['holidays']) && is_array($calendar_days['holidays']) ? $calendar_days['holidays'] : array();
        $half_days = isset($calendar_days['half_days']) && is_array($calendar_days['half_days']) ? $calendar_days['half_days'] : array();
        $quarter_days = isset($calendar_days['quarter_days']) && is_array($calendar_days['quarter_days']) ? $calendar_days['quarter_days'] : array();
        $work_days = isset($calendar_days['work_days']) && is_array($calendar_days['work_days']) ? $calendar_days['work_days'] : array();

        // 2. Kumpulkan tanggal kerja grafik: tepat 7 hari kerja ke belakang dari sekarang.
        // Berjalan menembus batas bulan sehingga jika beda bulan, data bulan sebelumnya tidak hilang/terpotong.
        $target_workdays_count = 7;
        $days = array();
        $cursor = strtotime($latest_day);

        while ($cursor && count($days) < $target_workdays_count) {
            $day = date('Y-m-d', $cursor);

            $is_holiday = in_array($day, $holidays, TRUE);
            $dow = (int) date('w', $cursor);
            $is_workday = FALSE;

            if (!$is_holiday) {
                if ($dow === 0) {
                    $is_workday = in_array($day, $work_days, TRUE);
                } elseif ($dow === 6) {
                    $is_workday = in_array($day, $work_days, TRUE)
                        || in_array($day, $half_days, TRUE)
                        || in_array($day, $quarter_days, TRUE)
                        || isset($selected_capacity_history[$day])
                        || isset($historical_capacity_history[$day]);
                } else {
                    $is_workday = TRUE;
                }
            }

            if ($is_workday) {
                array_unshift($days, $day);
            }
            $cursor = strtotime('-1 day', $cursor);
        }

        $items = array();
        $selected_balance_reference_daily = $engage_daily_for_balance ? $engage_daily_for_balance : $engage_daily_output;
        $today = date('Y-m-d');
        $heat_settings = $this->get_heat_analytics_settings();
        $backend_direct_actual = isset($heat_settings['direct_actual']) && is_numeric($heat_settings['direct_actual']) ? (float) $heat_settings['direct_actual'] : 0;

        // Baca cache SMV style langsung
        $smv_path = $this->heat_style_smv_path();
        $saved_smv = array();
        if (is_file($smv_path) && is_readable($smv_path)) {
            $json_smv = json_decode(file_get_contents($smv_path), TRUE);
            if (is_array($json_smv)) {
                $saved_smv = $json_smv;
            }
        }

        // Petakan Qty PDK per style dari $list_orders hanya untuk style yang berjalan di bulan ini
        $style_pdk_map = array();
        $running_styles_this_month = array();
        $target_month = date('Y-m');
        if (is_array($list_orders)) {
            foreach ($list_orders as $o_row) {
                $s_name = isset($o_row['style']) ? $o_row['style'] : '';
                if (empty($s_name) || $this->is_ofc_order($s_name)) {
                    continue;
                }
                $del_raw = isset($o_row['delivery']) ? $o_row['delivery'] : '';
                $del_ts = !empty($o_row['_sort_delivery']) ? (int) $o_row['_sort_delivery'] : $this->parse_date_timestamp($del_raw);
                $del_month = ($del_ts > 0) ? date('Y-m', $del_ts) : '';

                if ($del_month === $target_month || ($del_month === '' && empty($target_month))) {
                    $running_styles_this_month[$s_name] = TRUE;
                    $p_qty = isset($o_row['qty_pdk']) ? (int) $o_row['qty_pdk'] : (isset($o_row['pdk']) ? (int) $o_row['pdk'] : 0);
                    $style_pdk_map[$s_name] = (isset($style_pdk_map[$s_name]) ? $style_pdk_map[$s_name] : 0) + $p_qty;
                }
            }
        }

        $backend_weighted_sum = 0;
        $backend_total_pdk = 0;
        $backend_smv_list = array();

        foreach ($saved_smv as $s_name => $s_item) {
            if ($this->is_ofc_order($s_name)) continue;
            // Hanya style yang berjalan di bulan ini yang dihitung kalkulasi SMV-nya
            if (empty($running_styles_this_month[$s_name])) continue;

            $s_smv = isset($s_item['smv']) && is_numeric($s_item['smv']) ? (float) $s_item['smv'] : 0;
            if ($s_smv > 0) {
                $backend_smv_list[] = $s_smv;
                $s_pdk = isset($style_pdk_map[$s_name]) ? (int) $style_pdk_map[$s_name] : 0;
                if ($s_pdk > 0) {
                    $backend_weighted_sum += ($s_smv * $s_pdk);
                    $backend_total_pdk += $s_pdk;
                }
            }
        }

        if ($backend_total_pdk > 0 && $backend_weighted_sum > 0) {
            $backend_avg_smv = $backend_weighted_sum / $backend_total_pdk;
        } else {
            $backend_avg_smv = 0;
        }

        foreach ($days as $day) {
            if ($day === $today) {
                if (isset($selected_capacity_history[$day])) {
                    $snapshot = $selected_capacity_history[$day];
                } else {
                    $balance_day = $day;
                    $balance_qty = $this->balance_qty_for_capacity_day($balance_day, $selected_qty_rows, $selected_balance_reference_daily);

                    $capacity_detail = $this->capacity_from_delivery_aggregate($balance_qty, $day, $this->selected_delivery_period_end($selected_qty_rows), $selected_qty_rows);
                    $snapshot = $this->capacity_snapshot_entry($capacity_detail, $balance_day, $selected_delivery_count);
                    $selected_capacity_history[$day] = $snapshot;
                    $selected_capacity_history_changed = TRUE;
                }
            } else {
                if (isset($historical_capacity_history[$day])) {
                    $snapshot = $historical_capacity_history[$day];
                } else {
                    $balance_day = $day;
                    $balance_qty = $this->balance_qty_for_capacity_day($balance_day, $historical_qty_rows, $engage_daily_output);

                    $capacity_detail = $this->capacity_from_delivery_aggregate($balance_qty, $day, $this->selected_delivery_period_end($historical_qty_rows), $historical_qty_rows);
                    $snapshot = $this->capacity_snapshot_entry($capacity_detail, $balance_day, 0);
                }
            }

            $sisa_hari_kerja = isset($snapshot['sisa_hari_kerja']) ? (float) $snapshot['sisa_hari_kerja'] : 0;
            $daily_demand = $sisa_hari_kerja > 0 ? round($snapshot['balance_qty'] / $sisa_hari_kerja) : (float) $snapshot['capacity'];
            $output = isset($engage_daily_output[$day]) ? $engage_daily_output[$day] : 0;

            $dow = (int) date('w', strtotime($day));
            $is_holiday = in_array($day, $holidays, TRUE);

            // Cek status hari Sabtu pada minggu tanggal target
            $day_time = strtotime($day);
            $offset_to_sat = ($dow === 0) ? -1 : (6 - $dow);
            $sat_date = date('Y-m-d', strtotime("+$offset_to_sat days", $day_time));

            $is_sat_workday = in_array($sat_date, $work_days, TRUE) 
                || in_array($sat_date, $half_days, TRUE) 
                || in_array($sat_date, $quarter_days, TRUE);
            if (in_array($sat_date, $holidays, TRUE)) {
                $is_sat_workday = FALSE;
            }
            $base_hours = $is_sat_workday ? 7 : 8;

            $day_working_hours = 0;
            if (!$is_holiday) {
                if ($dow === 0) {
                    if (in_array($day, $work_days, TRUE)) {
                        $day_working_hours = $base_hours;
                    } elseif (in_array($day, $half_days, TRUE)) {
                        $day_working_hours = $base_hours * 0.5;
                    } elseif (in_array($day, $quarter_days, TRUE)) {
                        $day_working_hours = $base_hours * 0.25;
                    } else {
                        $day_working_hours = 0;
                    }
                } elseif ($dow === 6) {
                    if (in_array($day, $work_days, TRUE)) {
                        $day_working_hours = 5;
                    } elseif (in_array($day, $half_days, TRUE)) {
                        $day_working_hours = 2.5;
                    } elseif (in_array($day, $quarter_days, TRUE)) {
                        $day_working_hours = 1.25;
                    } else {
                        $day_working_hours = $is_sat_workday ? 5 : 0;
                    }
                } else {
                    $day_working_hours = $base_hours;
                    if (in_array($day, $half_days, TRUE)) {
                        $day_working_hours = $base_hours * 0.5;
                    } elseif (in_array($day, $quarter_days, TRUE)) {
                        $day_working_hours = $base_hours * 0.25;
                    }
                }
            }
            $target_per_person = ($backend_avg_smv > 0 && $day_working_hours > 0) ? ((1 * 60 / $backend_avg_smv) * $day_working_hours) * 0.70 : 0;
            $daily_capacity = round($target_per_person * $backend_direct_actual);

            $items[] = array(
                'label' => date('d M Y', strtotime($day)),
                'output' => $output,
                'input' => isset($engage_daily_input[$day]) ? $engage_daily_input[$day] : 0,
                'capacity' => $daily_capacity > 0 ? $daily_capacity : $snapshot['capacity'],
                'daily_capacity' => $daily_capacity,
                'capacity_captured_at' => isset($snapshot['captured_at']) ? $snapshot['captured_at'] : NULL,
                'capacity_breakdown' => isset($snapshot['breakdown']) ? $snapshot['breakdown'] : array(),
                'balance_qty' => $snapshot['balance_qty'],
                'capacity_balance_day' => $snapshot['capacity_balance_day'],
                'total_demand' => $snapshot['balance_qty'],
                'demand' => $daily_demand,
                'daily_demand' => $daily_demand,
                'remaining_days' => $snapshot['remaining_days'],
                'sisa_hari_kerja' => $snapshot['sisa_hari_kerja'],
                'hari_kerja' => $snapshot['sisa_hari_kerja'],
            );
        }

        if ($selected_capacity_history_changed) {
            $this->write_heat_capacity_history($selected_capacity_history);
        }

        return $items;
    }

    private function build_today_hourly_output_vs_capacity($day, $in_hourly, $out_hourly, $daily_output_vs_capacity)
    {
        $today_daily_entry = NULL;
        if (is_array($daily_output_vs_capacity)) {
            foreach ($daily_output_vs_capacity as $row) {
                if (isset($row['capacity_balance_day']) && $row['capacity_balance_day'] === $day) {
                    $today_daily_entry = $row;
                    break;
                }
            }
            if (!$today_daily_entry && !empty($daily_output_vs_capacity)) {
                $today_daily_entry = end($daily_output_vs_capacity);
            }
        }

        $daily_capacity = isset($today_daily_entry['daily_capacity']) && (float) $today_daily_entry['daily_capacity'] > 0
            ? (float) $today_daily_entry['daily_capacity']
            : (isset($today_daily_entry['capacity']) ? (float) $today_daily_entry['capacity'] : 9390);

        $calendar_days = $this->dashboard_calendar_days();
        $holidays = isset($calendar_days['holidays']) && is_array($calendar_days['holidays']) ? $calendar_days['holidays'] : array();
        $half_days = isset($calendar_days['half_days']) && is_array($calendar_days['half_days']) ? $calendar_days['half_days'] : array();
        $quarter_days = isset($calendar_days['quarter_days']) && is_array($calendar_days['quarter_days']) ? $calendar_days['quarter_days'] : array();
        $work_days = isset($calendar_days['work_days']) && is_array($calendar_days['work_days']) ? $calendar_days['work_days'] : array();

        $dow = (int) date('w', strtotime($day));
        $is_holiday = in_array($day, $holidays, TRUE);

        $day_time = strtotime($day);
        $offset_to_sat = ($dow === 0) ? -1 : (6 - $dow);
        $sat_date = date('Y-m-d', strtotime("+$offset_to_sat days", $day_time));

        $is_sat_workday = in_array($sat_date, $work_days, TRUE) 
            || in_array($sat_date, $half_days, TRUE) 
            || in_array($sat_date, $quarter_days, TRUE);
        if (in_array($sat_date, $holidays, TRUE)) {
            $is_sat_workday = FALSE;
        }
        $base_hours = $is_sat_workday ? 7 : 8;

        $day_working_hours = $base_hours;
        if (!$is_holiday) {
            if ($dow === 0) {
                if (in_array($day, $work_days, TRUE)) {
                    $day_working_hours = $base_hours;
                } elseif (in_array($day, $half_days, TRUE)) {
                    $day_working_hours = $base_hours * 0.5;
                } elseif (in_array($day, $quarter_days, TRUE)) {
                    $day_working_hours = $base_hours * 0.25;
                } else {
                    $day_working_hours = 0;
                }
            } elseif ($dow === 6) {
                if (in_array($day, $work_days, TRUE)) {
                    $day_working_hours = 5;
                } elseif (in_array($day, $half_days, TRUE)) {
                    $day_working_hours = 2.5;
                } elseif (in_array($day, $quarter_days, TRUE)) {
                    $day_working_hours = 1.25;
                } else {
                    $day_working_hours = $is_sat_workday ? 5 : 0;
                }
            } else {
                $day_working_hours = $base_hours;
                if (in_array($day, $half_days, TRUE)) {
                    $day_working_hours = $base_hours * 0.5;
                } elseif (in_array($day, $quarter_days, TRUE)) {
                    $day_working_hours = $base_hours * 0.25;
                }
            }
        } else {
            $day_working_hours = 0;
        }

        if ($day_working_hours <= 0) {
            $day_working_hours = $base_hours;
        }

        $hourly_capacity = round($daily_capacity / $day_working_hours);

        $standard_hours = array(
            '07:00', '08:00', '09:00', '10:00', '11:00',
            '12:00', '13:00', '14:00', '15:00', '16:00'
        );

        $day_in = isset($in_hourly[$day]) && is_array($in_hourly[$day]) ? $in_hourly[$day] : array();
        $day_out = isset($out_hourly[$day]) && is_array($out_hourly[$day]) ? $out_hourly[$day] : array();

        $items = array();
        foreach ($standard_hours as $hour) {
            $inp = isset($day_in[$hour]) ? (float) $day_in[$hour] : 0;
            $outp = isset($day_out[$hour]) ? (float) $day_out[$hour] : 0;

            $items[] = array(
                'label' => $hour,
                'input' => $inp,
                'output' => $outp,
                'capacity' => $hourly_capacity,
                'daily_capacity' => $hourly_capacity,
                'hourly_capacity' => $hourly_capacity,
                'is_hourly' => TRUE,
                'date' => $day,
                'capacity_balance_day' => $day,
            );
        }

        return $items;
    }

    private function previous_workday_before($day, $calendar_days, $max_lookback = 31)
    {
        $cursor = strtotime($day);
        for ($i = 0; $i < $max_lookback; $i++) {
            $cursor = strtotime('-1 day', $cursor);
            if (!$cursor) {
                break;
            }

            $candidate = date('Y-m-d', $cursor);
            if ($this->calendar_workday_value($candidate, $calendar_days) > 0) {
                return $candidate;
            }
        }

        return date('Y-m-d', strtotime($day));
    }

    private function build_output_vs_capacity_from_rpa($total_pdk, $qty_rows, $fallback)
    {
        $sources = $this->heat_rpa_sources();
        $has_db = $this->has_engage_db_data();

        if (
            !$has_db &&
            (empty($sources['engage_32a_outflow']) || !is_file($sources['engage_32a_outflow']))
        ) {
            return $fallback;
        }

        $outflow_32a = $this->read_combined_engage_report(isset($sources['engage_32a_outflow']) ? $sources['engage_32a_outflow'] : NULL, '32a_outflow');
        if (!$outflow_32a['headers']) {
            return $fallback;
        }

        $summary = $this->summarize_engage_rows_by_order($outflow_32a, $this->engage_filter_rules_32a());

        $inflow_32a = $this->read_combined_engage_report(isset($sources['engage_32a_inflow']) ? $sources['engage_32a_inflow'] : NULL, '32a_inflow');
        $input_summary = array('daily' => array());
        if ($inflow_32a['headers']) {
            $input_summary = $this->summarize_engage_rows_by_order($inflow_32a, $this->engage_filter_rules_32a());
        }

        $items = $this->build_output_vs_capacity_from_engage_daily($summary['daily'], $qty_rows, $input_summary['daily'], $summary['daily'], $qty_rows);
        return $items ? $items : $fallback;
    }

    private function build_priority_orders_from_rpa($ready_by_order, $orders, $out_orders, $jo_order_master = array())
    {
        $items = array();
        foreach ($ready_by_order as $order => $ready) {
            if ($this->is_ofc_order($order)) {
                continue;
            }
            $order_data = isset($orders[$order]) ? $orders[$order] : (isset($jo_order_master[$order]) ? $jo_order_master[$order] : array());
            $qty_pdk = isset($order_data['qty_pdk']) ? $order_data['qty_pdk'] : 0;
            $qty_out_aps = isset($order_data['qty_out_aps']) ? $order_data['qty_out_aps'] : 0;
            $qty_out_engage = isset($out_orders[$order]) ? $out_orders[$order]['qty'] : 0;

            // Prioritas tanggal delivery asli dari JO (bukan tanggal transaksi 32A)
            $jo_delivery = !empty($order_data['delivery']) ? $order_data['delivery'] : (!empty($ready['delivery']) ? $ready['delivery'] : '');
            if ($jo_delivery === '') {
                continue;
            }

            $process = isset($order_data['process']) && $order_data['process'] !== ''
                ? $order_data['process']
                : (isset($order_data['route']) && $order_data['route'] !== ''
                    ? $order_data['route']
                    : (isset($ready['process']) ? $ready['process'] : ''));

            $items[] = array(
                'order' => $order,
                'item' => isset($ready['item']) && $ready['item'] !== '' ? $ready['item'] : (isset($order_data['item']) ? $order_data['item'] : ''),
                'style' => isset($order_data['style']) && $order_data['style'] !== '' ? $order_data['style'] : $ready['style'],
                'process' => $process,
                'route' => $process,
                'delivery' => $jo_delivery !== '' ? $this->format_display_date($jo_delivery) : '-',
                'period' => isset($ready['period']) ? $ready['period'] : (isset($order_data['period']) ? $order_data['period'] : ''),
                'qty_pdk' => $qty_pdk,
                'qty_ready' => $ready['qty'],
                'qty_in' => $ready['qty'],
                'qty_out_aps' => $qty_out_aps,
                'qty_out_engage' => $qty_out_engage,
                'qty_balance' => max(0, (int) $qty_pdk - (int) $qty_out_aps),
                'source' => isset($ready['source']) ? $ready['source'] : '',
                'accessories_completed' => !empty($ready['accessories_completed']) ? 1 : 0,
                '_sort_delivery' => $jo_delivery !== '' ? $this->parse_date_timestamp($jo_delivery) : PHP_INT_MAX,
            );
        }

        usort($items, function ($a, $b) {
            if ($a['_sort_delivery'] == $b['_sort_delivery']) {
                return $b['qty_ready'] <=> $a['qty_ready'];
            }
            return $a['_sort_delivery'] <=> $b['_sort_delivery'];
        });

        $items = array_slice($items, 0, 10);
        foreach ($items as &$item) {
            unset($item['_sort_delivery']);
        }
        unset($item);

        return $items;
    }

    private function format_display_date($value)
    {
        $timestamp = $this->parse_date_timestamp($value);
        return $timestamp ? date('d M Y', $timestamp) : $value;
    }

    private function build_heat_data_from_database_delivery($database_rows, $delivery_rows, $delivery_count = 4)
    {
        $delivery_count = $this->normalize_delivery_count($delivery_count);
        $delivery_cols = array(
            'order' => $this->column_letters_to_index('G'),
            'style' => $this->column_letters_to_index('E'),
            'qty' => $this->grid_header_column($delivery_rows, 'Qty', 'K'),
            'delivery_date' => $this->grid_header_column($delivery_rows, 'Delivery Date', 'O'),
            'route' => $this->grid_header_column($delivery_rows, 'Process Route', 'R'),
            'period' => $this->grid_header_column($delivery_rows, 'DELIVERY', 'V'),
            'output' => $this->grid_header_column($delivery_rows, 'QTY OUTPUT', 'W'),
            'status' => $this->grid_header_column($delivery_rows, 'STATUS', 'X'),
        );
        $database_cols = array(
            'key' => $this->grid_header_column($database_rows, 'KEY', 'A'),
            'order' => $this->grid_header_column($database_rows, 'ORDER', 'I'),
            'style' => $this->grid_header_column($database_rows, 'STYLE', 'J'),
            'status_heat' => $this->grid_header_column($database_rows, 'STATUS HEAT', 'O'),
            'qty_pcs' => $this->grid_header_column($database_rows, 'QTY PCS', 'V'),
            'out' => $this->grid_header_column($database_rows, 'OUT PENGIRIMAN', 'Y'),
            'delivery_date' => $this->grid_header_column($database_rows, 'TGL DELIVERY', 'Z'),
            'period' => $this->grid_header_column($database_rows, 'DELIVERY', 'AC'),
        );

        $delivery_by_order = array();
        $periods = array();
        $pdk_by_period = array();
        $output_by_period = array();

        foreach (array_slice($delivery_rows, 1) as $row) {
            $order = $this->grid_value($row, $delivery_cols['order']);
            if ($this->is_ofc_order($order)) {
                continue;
            }
            $qty = $this->parse_number($this->grid_value($row, $delivery_cols['qty']));
            $period = $this->normalize_period_label($this->grid_value($row, $delivery_cols['period']));
            $route = strtoupper($this->grid_value($row, $delivery_cols['route']));
            $status = strtoupper($this->grid_value($row, $delivery_cols['status']));

            if ($period === '') {
                $period = $this->period_label_from_excel_date($this->grid_value($row, $delivery_cols['delivery_date']));
            }
            if ($period === '' || $qty <= 0 || !$this->is_heat_transfer_delivery($route, $status)) {
                continue;
            }

            $this->ensure_period_bucket($periods, $period);
            $pdk_by_period[$period] = isset($pdk_by_period[$period]) ? $pdk_by_period[$period] + $qty : $qty;
            $output = $this->parse_number($this->grid_value($row, $delivery_cols['output']));
            $output_by_period[$period] = isset($output_by_period[$period]) ? $output_by_period[$period] + $output : $output;

            if ($order !== '' && !isset($delivery_by_order[$order])) {
                $delivery_by_order[$order] = array(
                    'order' => $order,
                    'style' => $this->grid_value($row, $delivery_cols['style']),
                    'delivery' => $this->grid_value($row, $delivery_cols['delivery_date']),
                    'period' => $period,
                    'process' => $route,
                    'route' => $route,
                    'qty_pdk' => $qty,
                    'qty_out_aps' => $output,
                );
            } elseif ($order !== '') {
                $delivery_by_order[$order]['qty_pdk'] += $qty;
                $delivery_by_order[$order]['qty_out_aps'] += $output;
                if (empty($delivery_by_order[$order]['process']) && $route !== '') {
                    $delivery_by_order[$order]['process'] = $route;
                    $delivery_by_order[$order]['route'] = $route;
                }
            }
        }

        $ready_keys = array();
        $output_keys = array();
        $daily_output_keys = array();
        foreach (array_slice($database_rows, 2) as $row) {
            $key = $this->grid_value($row, $database_cols['key']);
            $order = $this->grid_value($row, $database_cols['order']);
            if ($this->is_ofc_order($order)) {
                continue;
            }
            $period = $this->normalize_period_label($this->grid_value($row, $database_cols['period']));
            $qty = $this->parse_number($this->grid_value($row, $database_cols['qty_pcs']));
            $out_value = $this->grid_value($row, $database_cols['out']);
            $status_heat = strtoupper($this->grid_value($row, $database_cols['status_heat']));

            if ($key === '' || $order === '' || $period === '' || $qty <= 0) {
                continue;
            }

            $this->ensure_period_bucket($periods, $period);
            if ($this->is_database_out($out_value)) {
                $output_key = $order . "\n" . $key;
                $output_keys[$output_key] = array(
                    'order' => $order,
                    'qty' => max(isset($output_keys[$output_key]['qty']) ? $output_keys[$output_key]['qty'] : 0, $qty),
                );

                $day_serial = $this->excel_date_serial($out_value);
                if ($day_serial > 0) {
                    $daily_key = $day_serial . "\n" . $key;
                    $daily_output_keys[$daily_key] = array(
                        'day' => $day_serial,
                        'qty' => max(isset($daily_output_keys[$daily_key]['qty']) ? $daily_output_keys[$daily_key]['qty'] : 0, $qty),
                    );
                }
            } elseif (strpos($status_heat, 'INCOMPLITED') === FALSE) {
                $ready_key = $period . "\n" . $key;
                $ready_keys[$ready_key] = array(
                    'period' => $period,
                    'order' => $order,
                    'style' => $this->grid_value($row, $database_cols['style']),
                    'delivery' => $this->grid_value($row, $database_cols['delivery_date']),
                    'qty' => max(isset($ready_keys[$ready_key]['qty']) ? $ready_keys[$ready_key]['qty'] : 0, $qty),
                );
            }
        }

        if (!array_filter($output_by_period)) {
            foreach ($output_keys as $item) {
                $order = $item['order'];
                if (!isset($delivery_by_order[$order])) {
                    continue;
                }
                $period = $delivery_by_order[$order]['period'];
                $output_by_period[$period] = isset($output_by_period[$period]) ? $output_by_period[$period] + $item['qty'] : $item['qty'];
            }
        }

        $ready_by_period = array();
        $ready_by_order = array();
        foreach ($ready_keys as $item) {
            $period = $item['period'];
            $ready_by_period[$period] = isset($ready_by_period[$period]) ? $ready_by_period[$period] + $item['qty'] : $item['qty'];
            $order = $item['order'];
            if (!isset($ready_by_order[$order])) {
                $ready_by_order[$order] = $item;
            } else {
                $ready_by_order[$order]['qty'] += $item['qty'];
            }
        }

        $period_labels = array_keys($periods);
        usort($period_labels, array($this, 'compare_period_labels'));

        $all_qty_rows = array();
        foreach ($period_labels as $period) {
            $pdk = isset($pdk_by_period[$period]) ? $pdk_by_period[$period] : 0;
            $output = isset($output_by_period[$period]) ? $output_by_period[$period] : 0;
            if ($pdk <= 0 && $output <= 0) {
                continue;
            }
            $all_qty_rows[] = array('label' => $period, 'pdk' => $pdk, 'output' => $output);
        }

        $current_index = $this->current_delivery_index($all_qty_rows, $ready_by_period);
        $selected_qty_pdk_vs_output = array_slice($all_qty_rows, $current_index, $delivery_count);
        $qty_pdk_vs_output = array_slice($all_qty_rows, $current_index, 4);
        $balance_breakdown = $qty_pdk_vs_output;

        $ready_to_load = array();
        foreach ($qty_pdk_vs_output as $row) {
            $period = $row['label'];
            $ready = isset($ready_by_period[$period]) ? (int) $ready_by_period[$period] : 0;
            $ready_to_load[] = array(
                'label' => $period,
                'ready' => $ready,
                'completed' => $ready,
                'uncompleted' => 0,
            );
        }

        $selected_ready_to_load = array();
        foreach ($selected_qty_pdk_vs_output as $row) {
            $period = $row['label'];
            $ready = isset($ready_by_period[$period]) ? (int) $ready_by_period[$period] : 0;
            $selected_ready_to_load[] = array(
                'label' => $period,
                'ready' => $ready,
                'completed' => $ready,
                'uncompleted' => 0,
            );
        }

        $top_priority_orders = $this->build_priority_orders_from_source($ready_by_order, $delivery_by_order, $output_keys);
        $list_orders = $this->build_list_orders_from_source($ready_by_order, $delivery_by_order, $output_keys, $selected_qty_pdk_vs_output);
        $total_pdk = array_sum(array_column($selected_qty_pdk_vs_output, 'pdk'));
        $total_output = array_sum(array_column($selected_qty_pdk_vs_output, 'output'));
        $balance_qty = max(0, $total_pdk - $total_output);

        return array(
            'total_pdk' => $total_pdk,
            'total_output' => $total_output,
            'total_output_balance' => $total_output,
            'balance_qty' => $balance_qty,
            'prod_days_left' => $this->source_prod_days_left($selected_qty_pdk_vs_output),
            'qty_pdk_vs_output' => $selected_qty_pdk_vs_output,
            'selected_qty_pdk_vs_output' => $selected_qty_pdk_vs_output,
            'balance_breakdown' => $selected_qty_pdk_vs_output,
            'ready_to_load' => $selected_ready_to_load,
            'selected_ready_to_load' => $selected_ready_to_load,
            'output_vs_capacity' => $this->build_output_vs_capacity_from_source($daily_output_keys, $selected_qty_pdk_vs_output, $qty_pdk_vs_output),
            'list_orders' => $list_orders,
            'top_priority_orders' => $top_priority_orders,
        );
    }

    private function grid_header_column($rows, $header, $fallback_column)
    {
        $fallback = $this->column_letters_to_index($fallback_column);
        $needle = strtolower($this->normalize($header));

        for ($row = 0; $row < min(3, count($rows)); $row++) {
            foreach ($rows[$row] as $index => $value) {
                if (strtolower($this->normalize($value)) === $needle) {
                    return $index;
                }
            }
        }

        return $fallback;
    }

    private function grid_value($row, $index)
    {
        return isset($row[$index]) ? $this->normalize($row[$index]) : '';
    }

    private function dedupe_grid_rows($rows)
    {
        if (count($rows) <= 2) {
            return $rows;
        }

        $result = array();
        $seen = array();

        foreach ($rows as $row_index => $row) {
            if ($row_index === 0) {
                $result[] = $row;
                continue;
            }

            $normalized = array();
            $last_value_index = -1;
            foreach ($row as $index => $value) {
                $cell = $this->normalize($value);
                $normalized[$index] = $cell;
                if ($cell !== '') {
                    $last_value_index = max($last_value_index, (int) $index);
                }
            }

            if ($last_value_index < 0) {
                continue;
            }

            $key_parts = array();
            for ($index = 0; $index <= $last_value_index; $index++) {
                $key_parts[] = isset($normalized[$index]) ? $normalized[$index] : '';
            }
            $key = md5(json_encode($key_parts));

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = TRUE;
            $result[] = $row;
        }

        return $result;
    }

    private function ensure_period_bucket(&$periods, $period)
    {
        if ($period !== '' && !isset($periods[$period])) {
            $periods[$period] = TRUE;
        }
    }

    private function normalize_period_label($label)
    {
        $label = trim(preg_replace('/\s+/', ' ', $this->normalize($label)));
        if ($label === '') {
            return '';
        }

        $label = str_ireplace('Mei', 'May', $label);
        if (preg_match('/^(MID|END)\s+([A-Za-z]+)$/i', $label, $match)) {
            $month_index = $this->month_label_to_index($match[2]);
            if ($month_index !== NULL) {
                return strtoupper($match[1]) . ' ' . $this->months[$month_index];
            }
        }

        return $label;
    }

    private function is_heat_transfer_delivery($route, $status)
    {
        if ($status === 'HEAT TRANSFER') {
            return TRUE;
        }

        return strpos($route, 'HT') !== FALSE || strpos($route, 'HEAT') !== FALSE;
    }

    private function is_database_out($value)
    {
        $value = $this->normalize($value);
        return $value !== '' && strcasecmp($value, 'Belum Out') !== 0;
    }

    private function excel_date_serial($value)
    {
        if (!is_numeric($value)) {
            return 0;
        }

        return (int) floor((float) $value);
    }

    private function period_label_from_excel_date($value)
    {
        if (!is_numeric($value)) {
            return '';
        }

        $timestamp = (((float) $value) - 25569) * 86400;
        $day = (int) gmdate('j', (int) $timestamp);
        $month = (int) gmdate('n', (int) $timestamp);
        if ($month < 1 || $month > 12) {
            return '';
        }

        return ($day <= 15 ? 'MID ' : 'END ') . $this->months[$month - 1];
    }

    private function compare_period_labels($a, $b)
    {
        return $this->period_sort_key($a) <=> $this->period_sort_key($b);
    }

    private function period_sort_key($label)
    {
        $label = $this->normalize_period_label($label);
        if (!preg_match('/^(MID|END)\s+([A-Za-z]+)$/i', $label, $match)) {
            return PHP_INT_MAX;
        }

        $month_index = $this->month_label_to_index($match[2]);
        if ($month_index === NULL) {
            return PHP_INT_MAX;
        }

        $current_month = (int) date('n') - 1;
        $offset = ($month_index - $current_month + 12) % 12;
        return ($offset * 2) + (strtoupper($match[1]) === 'END' ? 1 : 0);
    }

    private function period_is_near_slice($period, $rows)
    {
        foreach ($rows as $row) {
            if (strcasecmp($period, $row['label']) === 0) {
                return TRUE;
            }
        }

        return FALSE;
    }
    private function current_delivery_index($qty_rows, $ready_by_period = array())
    {
        $today = date('Y-m-d');
        $fallback = 0;

        foreach ($qty_rows ?: array() as $index => $row) {
            $label = isset($row['label']) ? $row['label'] : '';
            $range = $this->period_date_range($label);
            if ($range && $range['end'] >= $today) {
                return $index;
            }

            $ready = isset($ready_by_period[$label]) ? $ready_by_period[$label] : 0;
            if (($row['pdk'] - $row['output']) > 0 || $ready > 0) {
                $fallback = $index;
            }
        }

        return $fallback;
    }

    private function remaining_delivery_workdays($qty_rows, $as_of_date)
    {
        $calendar_days = $this->dashboard_calendar_days();
        $remaining_days = 0.0;

        foreach ($qty_rows ?: array() as $row) {
            $range = !empty($row['label']) ? $this->period_date_range($row['label']) : NULL;
            if (!$range || $as_of_date > $range['end']) {
                continue;
            }

            $start = max($as_of_date, $range['start']);
            $period_remaining = $this->count_workdays($start, $range['end'], $calendar_days);
            $remaining_days += $period_remaining;
        }

        $delivery_count = $this->active_delivery_count($qty_rows);
        $buffer_export = $this->export_prep_workdays($delivery_count);

        return array(
            'remaining_days' => round($remaining_days, 1),
            'export_prep_days' => (float) $buffer_export,
            'sisa_hari_kerja' => max(0, round($remaining_days - $buffer_export, 1)),
        );
    }

    private function source_prod_days_left($qty_rows)
    {
        $detail = $this->remaining_delivery_workdays($qty_rows, date('Y-m-d'));
        return round($detail['sisa_hari_kerja'], 1);
    }

    private function source_daily_capacity($qty_rows, $daily_output = NULL)
    {
        $detail = $this->source_daily_capacity_detail($qty_rows, $daily_output);
        return $detail['capacity'];
    }

    private function selected_delivery_period_end($qty_rows)
    {
        if (!$qty_rows) {
            return '';
        }

        $labels = array();
        foreach ($qty_rows as $row) {
            if (!empty($row['label'])) {
                $labels[] = $row['label'];
            }
        }

        if (!$labels) {
            return '';
        }

        usort($labels, array($this, 'compare_period_labels'));
        $range = $this->period_date_range(end($labels));
        return $range ? $range['end'] : '';
    }

    private function selected_delivery_order_whitelist($orders, $qty_rows)
    {
        $period_labels = array();
        foreach ($qty_rows ?: array() as $row) {
            if (!empty($row['label'])) {
                $period_labels[$row['label']] = TRUE;
            }
        }

        $whitelist = array();
        foreach ($orders ?: array() as $order => $data) {
            $period = isset($data['period']) ? $data['period'] : '';
            if ($period !== '' && isset($period_labels[$period])) {
                $whitelist[$order] = TRUE;
            }
        }

        return $whitelist;
    }

    private function aps_balance_qty_for_qty_rows($qty_rows)
    {
        $balance = 0.0;
        foreach ($qty_rows ?: array() as $row) {
            $balance += max(0, (isset($row['pdk']) ? (float) $row['pdk'] : 0) - (isset($row['output']) ? (float) $row['output'] : 0));
        }

        return $balance;
    }

    private function balance_qty_for_capacity_day($day, $qty_rows, $scoped_daily, $today = NULL)
    {
        $today = $today ? date('Y-m-d', strtotime($today)) : date('Y-m-d');
        $day = date('Y-m-d', strtotime($day));
        $aps_balance = $this->aps_balance_qty_for_qty_rows($qty_rows);

        if ($day >= $today) {
            return $aps_balance;
        }

        $output_after = 0.0;
        foreach ($scoped_daily ?: array() as $output_day => $qty) {
            if ($output_day > $day) {
                $output_after += max(0, (float) $qty);
            }
        }

        return max(0, $aps_balance + $output_after);
    }

    private function capacity_from_delivery_aggregate($balance_qty, $as_of_date, $period_end, $qty_rows = array())
    {
        $balance_qty = max(0, (float) $balance_qty);
        $workday_detail = $this->remaining_delivery_workdays($qty_rows, $as_of_date);
        $remaining_days = $workday_detail['remaining_days'];
        $export_prep = $workday_detail['export_prep_days'];
        $sisa_hari_kerja = $workday_detail['sisa_hari_kerja'];
        $capacity = $sisa_hari_kerja > 0 ? round($balance_qty / $sisa_hari_kerja) : 0;

        $breakdown = array();
        foreach ($qty_rows ?: array() as $row) {
            $breakdown[] = array(
                'label' => $row['label'],
                'pdk' => isset($row['pdk']) ? $row['pdk'] : 0,
                'output' => isset($row['output']) ? $row['output'] : 0,
                'balance' => max(0, (isset($row['pdk']) ? $row['pdk'] : 0) - (isset($row['output']) ? $row['output'] : 0)),
            );
        }

        return array(
            'capacity' => $capacity,
            'balance_qty' => $balance_qty,
            'total_demand' => $balance_qty,
            'remaining_days' => $remaining_days,
            'export_prep_days' => $export_prep,
            'sisa_hari_kerja' => $sisa_hari_kerja,
            'hari_kerja' => $sisa_hari_kerja,
            'breakdown' => $breakdown,
        );
    }

    private function capacity_breakdown_for_history($capacity_detail)
    {
        $sisa_hari_kerja = isset($capacity_detail['sisa_hari_kerja'])
            ? $capacity_detail['sisa_hari_kerja']
            : (isset($capacity_detail['hari_kerja']) ? $capacity_detail['hari_kerja'] : 0);
        $balance_qty = isset($capacity_detail['balance_qty'])
            ? $capacity_detail['balance_qty']
            : (isset($capacity_detail['total_demand']) ? $capacity_detail['total_demand'] : 0);

        return array(array(
            'label' => 'Aggregate',
            'capacity_formula' => 'balance_qty_v2',
            'balance_qty' => $balance_qty,
            'total_demand' => $balance_qty,
            'remaining_days' => $capacity_detail['remaining_days'],
            'export_prep_days' => $capacity_detail['export_prep_days'],
            'sisa_hari_kerja' => $sisa_hari_kerja,
            'hari_kerja' => $sisa_hari_kerja,
            'daily_capacity' => $capacity_detail['capacity'],
            'total_balance' => $balance_qty,
            'total_days_left' => $sisa_hari_kerja,
            'days_left' => $sisa_hari_kerja,
            'balance' => $balance_qty,
        ));
    }

    private function source_daily_capacity_detail($qty_rows, $scoped_daily = NULL)
    {
        $period_end = $this->selected_delivery_period_end($qty_rows);
        $today = date('Y-m-d');
        $balance_qty = $this->balance_qty_for_capacity_day($today, $qty_rows, is_array($scoped_daily) ? $scoped_daily : array());

        return $this->capacity_from_delivery_aggregate($balance_qty, $today, $period_end, $qty_rows);
    }

    private function auto_remaining_workdays($label)
    {
        $range = $this->period_date_range($label);
        if (!$range) {
            return 0;
        }

        $today = date('Y-m-d');
        $start = max($today, $range['start']);
        if ($start > $range['end']) {
            return 0;
        }

        return $this->export_workdays_from_remaining($this->count_workdays($start, $range['end'], $this->dashboard_calendar_days()));
    }

    private function build_output_vs_capacity_from_source($daily_output_keys, $qty_rows, $historical_qty_rows = NULL, $daily_input_keys = array())
    {
        $calendar_days = $this->dashboard_calendar_days();
        $daily = array();
        foreach ($daily_output_keys as $item) {
            $date = $this->excel_serial_to_date($item['day']);
            if ($this->calendar_workday_value($date, $calendar_days) > 0) {
                $daily[$date] = isset($daily[$date]) ? $daily[$date] + $item['qty'] : $item['qty'];
            }
        }

        $daily_input = array();
        foreach ($daily_input_keys as $item) {
            $date = $this->excel_serial_to_date($item['day']);
            if ($this->calendar_workday_value($date, $calendar_days) > 0) {
                $daily_input[$date] = isset($daily_input[$date]) ? $daily_input[$date] + $item['qty'] : $item['qty'];
            }
        }

        return $this->build_output_vs_capacity_from_engage_daily($daily, $qty_rows, $daily_input, $daily, $historical_qty_rows);
    }

    private function format_output_day_label_from_serial($serial)
    {
        $timestamp = $this->excel_serial_to_timestamp($serial);
        $month = (int) gmdate('n', $timestamp);
        $month_label = $month >= 1 && $month <= 12 ? $this->months[$month - 1] : '';

        return trim(gmdate('j', $timestamp) . ' ' . $month_label . ' ' . gmdate('Y', $timestamp));
    }

    private function excel_serial_to_date($serial)
    {
        return gmdate('Y-m-d', $this->excel_serial_to_timestamp($serial));
    }

    private function excel_serial_to_timestamp($serial)
    {
        return ((int) $serial - 25569) * 86400;
    }

    private function heat_active_period_label($selected_qty_pdk_vs_output)
    {
        if (!is_array($selected_qty_pdk_vs_output) || !$selected_qty_pdk_vs_output) {
            return '';
        }

        $first = reset($selected_qty_pdk_vs_output);
        if (is_array($first) && !empty($first['label'])) {
            return trim((string) $first['label']);
        }

        return '';
    }

    private function selected_period_whitelist($selected_qty_pdk_vs_output)
    {
        $periods = array();
        foreach ($selected_qty_pdk_vs_output ?: array() as $row) {
            if (empty($row['label'])) {
                continue;
            }
            $periods[$this->normalize_period_label($row['label'])] = TRUE;
        }
        return $periods;
    }

    private function build_list_orders_from_rpa($ready_by_order, $orders, $out_orders, $selected_qty_pdk_vs_output, $qty_in_by_order = NULL)
    {
        $active_periods = $this->selected_period_whitelist($selected_qty_pdk_vs_output);
        $active_label = $this->heat_active_period_label($selected_qty_pdk_vs_output);
        $today_start = strtotime(date('Y-m-d'));
        $items = array();

        foreach ($orders as $order => $order_data) {
            $period = isset($order_data['period']) ? $order_data['period'] : '';
            $normalized_period = $this->normalize_period_label($period);
            if ($active_periods && !isset($active_periods[$normalized_period])) {
                continue;
            }

            $ready = isset($ready_by_order[$order]) ? $ready_by_order[$order] : array();
            $qty_pdk = isset($order_data['qty_pdk']) ? (int) $order_data['qty_pdk'] : 0;
            $qty_out_aps = isset($order_data['qty_out_aps']) ? (int) $order_data['qty_out_aps'] : 0;
            $qty_out_engage = isset($out_orders[$order]) && isset($out_orders[$order]['qty']) ? (int) $out_orders[$order]['qty'] : 0;
            $qty_in = 0;
            if (is_array($qty_in_by_order) && isset($qty_in_by_order[$order])) {
                $qty_in = is_array($qty_in_by_order[$order])
                    ? (int) (isset($qty_in_by_order[$order]['qty']) ? $qty_in_by_order[$order]['qty'] : 0)
                    : (int) $qty_in_by_order[$order];
            } elseif (isset($ready['qty'])) {
                $qty_in = (int) $ready['qty'];
            }
            $delivery = isset($order_data['delivery']) ? $order_data['delivery'] : (isset($ready['delivery']) ? $ready['delivery'] : '');
            $process = isset($order_data['process']) && $order_data['process'] !== ''
                ? $order_data['process']
                : (isset($order_data['route']) && $order_data['route'] !== ''
                    ? $order_data['route']
                    : (isset($ready['process']) ? $ready['process'] : ''));

            $items[] = array(
                'order' => $order,
                'cost_center' => $order,
                'cost_centre' => $order,
                'style' => isset($order_data['style']) && $order_data['style'] !== '' ? $order_data['style'] : (isset($ready['style']) ? $ready['style'] : ''),
                'process' => $process,
                'route' => $process,
                'delivery' => $this->format_display_date($delivery),
                'period' => $active_label !== '' ? $active_label : $period,
                'qty_pdk' => $qty_pdk,
                'qty_ready' => $qty_in,
                'qty_in' => $qty_in,
                'qty_out' => $qty_out_aps,
                'qty_out_aps' => $qty_out_aps,
                'qty_out_engage' => $qty_out_engage,
                'qty_balance' => max(0, $qty_pdk - $qty_out_aps),
                'source' => 'rpa',
                '_sort_delivery' => $this->parse_date_timestamp($delivery),
                '_sort_balance' => max(0, $qty_pdk - $qty_out_aps),
                '_sort_priority' => (max(0, $qty_pdk - $qty_out_aps) <= 0 ? 1 : 0),
            );
        }

        usort($items, function ($a, $b) {
            if ($a['_sort_priority'] !== $b['_sort_priority']) {
                return $a['_sort_priority'] <=> $b['_sort_priority'];
            }
            if ($a['_sort_delivery'] === $b['_sort_delivery']) {
                if ($a['_sort_balance'] === $b['_sort_balance']) {
                    return strnatcasecmp((string) $a['order'], (string) $b['order']);
                }
                return $b['_sort_balance'] <=> $a['_sort_balance'];
            }
            return $a['_sort_delivery'] <=> $b['_sort_delivery'];
        });

        foreach ($items as &$item) {
            unset($item['_sort_delivery'], $item['_sort_balance'], $item['_sort_priority']);
        }
        unset($item);

        return $items;
    }

    private function build_list_orders_from_source($ready_by_order, $delivery_by_order, $output_keys, $selected_qty_pdk_vs_output)
    {
        $active_periods = $this->selected_period_whitelist($selected_qty_pdk_vs_output);
        $active_label = $this->heat_active_period_label($selected_qty_pdk_vs_output);
        $today_start = strtotime(date('Y-m-d'));
        $engage_output = array();
        foreach ($output_keys as $item) {
            $order = $item['order'];
            $engage_output[$order] = isset($engage_output[$order]) ? $engage_output[$order] + $item['qty'] : $item['qty'];
        }

        $items = array();
        foreach ($delivery_by_order as $order => $delivery) {
            $ready = isset($ready_by_order[$order]) ? $ready_by_order[$order] : array();
            $period = isset($delivery['period']) && $delivery['period'] !== '' ? $delivery['period'] : (isset($ready['period']) ? $ready['period'] : $active_label);
            $normalized_period = $this->normalize_period_label($period);
            if ($active_periods && !isset($active_periods[$normalized_period])) {
                continue;
            }

            $qty_pdk = isset($delivery['qty_pdk']) ? (int) $delivery['qty_pdk'] : 0;
            $qty_out_aps = isset($delivery['qty_out_aps']) ? (int) $delivery['qty_out_aps'] : 0;
            $qty_in = isset($ready['qty']) ? (int) $ready['qty'] : 0;
            $delivery_value = isset($delivery['delivery']) ? $delivery['delivery'] : (isset($ready['delivery']) ? $ready['delivery'] : '');
            $sort_delivery = $this->parse_date_timestamp($delivery_value);
            $sort_balance = max(0, $qty_pdk - $qty_out_aps);
            $process = isset($delivery['route']) && $delivery['route'] !== ''
                ? $delivery['route']
                : (isset($delivery['process']) && $delivery['process'] !== ''
                    ? $delivery['process']
                    : (isset($ready['process']) ? $ready['process'] : ''));

            $items[] = array(
                'order' => $order,
                'cost_center' => $order,
                'cost_centre' => $order,
                'style' => isset($delivery['style']) && $delivery['style'] !== '' ? $delivery['style'] : (isset($ready['style']) ? $ready['style'] : ''),
                'process' => $process,
                'route' => $process,
                'delivery' => $this->format_excel_date($delivery_value),
                'period' => $active_label !== '' ? $active_label : $period,
                'qty_pdk' => $qty_pdk,
                'qty_ready' => $qty_in,
                'qty_in' => $qty_in,
                'qty_out' => $qty_out_aps,
                'qty_out_aps' => $qty_out_aps,
                'qty_out_engage' => isset($engage_output[$order]) ? (int) $engage_output[$order] : 0,
                'qty_balance' => max(0, $qty_pdk - $qty_out_aps),
                'source' => 'source',
                '_sort_delivery' => $sort_delivery,
                '_sort_balance' => max(0, $qty_pdk - $qty_out_aps),
                '_sort_priority' => (max(0, $qty_pdk - $qty_out_aps) <= 0 ? 1 : 0),
            );
        }

        usort($items, function ($a, $b) {
            if ($a['_sort_priority'] !== $b['_sort_priority']) {
                return $a['_sort_priority'] <=> $b['_sort_priority'];
            }
            if ($a['_sort_delivery'] === $b['_sort_delivery']) {
                if ($a['_sort_balance'] === $b['_sort_balance']) {
                    return strnatcasecmp((string) $a['order'], (string) $b['order']);
                }
                return $b['_sort_balance'] <=> $a['_sort_balance'];
            }
            return $a['_sort_delivery'] <=> $b['_sort_delivery'];
        });

        foreach ($items as &$item) {
            unset($item['_sort_delivery'], $item['_sort_balance'], $item['_sort_priority']);
        }
        unset($item);

        return $items;
    }
    private function build_priority_orders_from_source($ready_by_order, $delivery_by_order, $output_keys)
    {
        $engage_output = array();
        foreach ($output_keys as $item) {
            $order = $item['order'];
            $engage_output[$order] = isset($engage_output[$order]) ? $engage_output[$order] + $item['qty'] : $item['qty'];
        }

        $items = array();
        foreach ($ready_by_order as $order => $ready) {
            $delivery = isset($delivery_by_order[$order]) ? $delivery_by_order[$order] : array();
            $qty_pdk = isset($delivery['qty_pdk']) ? $delivery['qty_pdk'] : 0;
            $qty_out_aps = isset($delivery['qty_out_aps']) ? $delivery['qty_out_aps'] : 0;
            $qty_out_engage = isset($engage_output[$order]) ? $engage_output[$order] : 0;

            $items[] = array(
                'order' => $order,
                'item' => isset($ready['item']) && $ready['item'] !== '' ? $ready['item'] : (isset($delivery['item']) ? $delivery['item'] : ''),
                'style' => isset($delivery['style']) && $delivery['style'] !== '' ? $delivery['style'] : $ready['style'],
                'delivery' => $this->format_excel_date(isset($delivery['delivery']) ? $delivery['delivery'] : $ready['delivery']),
                'period' => isset($delivery['period']) ? $delivery['period'] : (isset($ready['period']) ? $ready['period'] : ''),
                'qty_pdk' => $qty_pdk,
                'qty_ready' => $ready['qty'],
                'qty_in' => $ready['qty'],
                'qty_out_aps' => $qty_out_aps,
                'qty_out_engage' => $qty_out_engage,
                'qty_balance' => max(0, (int) $qty_pdk - (int) $qty_out_aps),
                'source' => isset($ready['source']) ? $ready['source'] : '',
                '_sort_delivery' => $this->parse_number(isset($delivery['delivery']) ? $delivery['delivery'] : $ready['delivery']),
            );
        }

        usort($items, function ($a, $b) {
            if ($a['_sort_delivery'] == $b['_sort_delivery']) {
                return $b['qty_ready'] <=> $a['qty_ready'];
            }
            return $a['_sort_delivery'] <=> $b['_sort_delivery'];
        });

        $items = array_slice($items, 0, 10);
        foreach ($items as &$item) {
            unset($item['_sort_delivery']);
        }

        return $items;
    }

    private function extract_latest_database_rows($rows, $limit = 5)
    {
        if (count($rows) < 3) {
            return array();
        }

        $headers = isset($rows[1]) ? $this->header_index($rows[1]) : array();
        $items = array();

        for ($i = count($rows) - 1; $i >= 2 && count($items) < $limit; $i--) {
            $row = $rows[$i];
            $order = isset($row[8]) ? $row[8] : '';
            $style = isset($row[9]) ? $row[9] : '';
            $item = isset($row[3]) ? $row[3] : '';

            if ($order === '' && $style === '' && $item === '') {
                continue;
            }

            $items[] = array(
                'date' => $this->format_excel_datetime(isset($row[1]) ? $row[1] : ''),
                'order' => $order,
                'style' => $style,
                'item' => $item,
                'color' => isset($row[4]) ? $row[4] : '',
                'qty' => $this->parse_number(isset($row[6]) ? $row[6] : ''),
                'qty_pcs' => $this->parse_number(isset($row[21]) ? $row[21] : ''),
                'pot' => isset($row[11]) ? $row[11] : '',
                'bundle' => isset($row[12]) ? $row[12] : '',
                'status_heat' => isset($row[14]) ? $row[14] : '',
                'scan_in' => $this->format_excel_datetime(isset($row[22]) ? $row[22] : ''),
                'out_pengiriman' => $this->format_excel_datetime(isset($row[24]) ? $row[24] : ''),
                'delivery' => $this->format_excel_date(isset($row[25]) ? $row[25] : ''),
                'period' => isset($row[28]) ? $row[28] : '',
                'pengambil' => isset($row[30]) ? $row[30] : '',
            );
        }

        return $items;
    }

    private function extract_latest_database_rows_from_file($path, $limit = 5)
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== TRUE) {
            return array();
        }

        $shared_strings = $this->read_xlsx_shared_strings($zip);
        $sheet_path = $this->xlsx_sheet_path_by_name($zip, 'DATABASE');
        $sheet_xml = $sheet_path ? $zip->getFromName($sheet_path) : FALSE;
        $zip->close();

        if ($sheet_xml === FALSE) {
            return array();
        }

        $xml = simplexml_load_string($sheet_xml);
        if (!$xml) {
            return array();
        }

        $latest_rows = array();
        foreach ($xml->sheetData->row as $row_node) {
            $row_number = (int) $row_node['r'];
            if ($row_number <= 2) {
                continue;
            }

            $row = array();
            foreach ($row_node->c as $cell_node) {
                $ref = (string) $cell_node['r'];
                $index = $this->xlsx_column_index($ref);
                $row[$index] = $this->normalize($this->xlsx_cell_value($cell_node, $shared_strings));
            }

            if (!$this->row_has_value($row)) {
                continue;
            }

            $latest_rows[] = $this->database_row_summary($row);
            if (count($latest_rows) > $limit) {
                array_shift($latest_rows);
            }
        }

        return array_reverse($latest_rows);
    }

    private function database_row_summary($row)
    {
        return array(
            'date' => $this->format_excel_datetime(isset($row[1]) ? $row[1] : ''),
            'order' => isset($row[8]) ? $row[8] : '',
            'style' => isset($row[9]) ? $row[9] : '',
            'item' => isset($row[3]) ? $row[3] : '',
            'color' => isset($row[4]) ? $row[4] : '',
            'qty' => $this->parse_number(isset($row[6]) ? $row[6] : ''),
            'qty_pcs' => $this->parse_number(isset($row[21]) ? $row[21] : ''),
            'pot' => isset($row[11]) ? $row[11] : '',
            'bundle' => isset($row[12]) ? $row[12] : '',
            'status_heat' => isset($row[14]) ? $row[14] : '',
            'scan_in' => $this->format_excel_datetime(isset($row[22]) ? $row[22] : ''),
            'out_pengiriman' => $this->format_excel_datetime(isset($row[24]) ? $row[24] : ''),
            'delivery' => $this->format_excel_date(isset($row[25]) ? $row[25] : ''),
            'period' => isset($row[28]) ? $row[28] : '',
            'pengambil' => isset($row[30]) ? $row[30] : '',
        );
    }

    private function build_management_analytics($total_pdk, $total_output_balance, $balance_qty, $prod_days_left, $qty_rows, $ready_rows, $capacity_rows, $priority_orders)
    {
        $data_accuracy = $this->build_data_accuracy($qty_rows, $ready_rows);
        $current_period = isset($data_accuracy['current_period']) ? $data_accuracy['current_period'] : NULL;
        $total_chart_pdk = array_sum(array_column($qty_rows, 'pdk'));
        $total_chart_output = array_sum(array_column($qty_rows, 'output'));
        $pdk_base = $current_period && $current_period['pdk'] > 0 ? $current_period['pdk'] : ($total_pdk > 0 ? $total_pdk : $total_chart_pdk);
        $output_base = $current_period ? $current_period['output'] : ($total_output_balance > 0 ? $total_output_balance : $total_chart_output);
        $achievement_rate = $pdk_base > 0 ? ($output_base / $pdk_base) * 100 : 0;

        $total_ready = array_sum(array_column($ready_rows, 'ready'));
        $total_capacity = array_sum(array_column($capacity_rows, 'capacity'));
        $total_daily_output = array_sum(array_column($capacity_rows, 'output'));
        $capacity_days = count(array_filter($capacity_rows, function ($row) {
            return $row['capacity'] > 0 || $row['output'] > 0;
        }));
        $avg_daily_output = $capacity_days ? $total_daily_output / $capacity_days : 0;
        $avg_daily_capacity = $capacity_days ? $total_capacity / $capacity_days : 0;
        $capacity_utilization = $total_capacity > 0 ? ($total_daily_output / $total_capacity) * 100 : 0;
        $capacity_gap = $total_capacity - $total_daily_output;
        $ready_coverage_days = $avg_daily_capacity > 0 ? $total_ready / $avg_daily_capacity : 0;
        $period_calendar = $this->build_current_period_calendar($current_period);
        $current_balance_qty = $current_period ? $current_period['balance'] : $balance_qty;
        $effective_days_left = isset($period_calendar['export_remaining_workdays']) ? $period_calendar['export_remaining_workdays'] : $prod_days_left;
        $latest_capacity = $capacity_rows ? end($capacity_rows) : array();
        if (!empty($latest_capacity['capacity'])) {
            $required_daily_output = (int) $latest_capacity['capacity'];
            $period_required_daily_output = $required_daily_output;
            $latest_sisa_hari_kerja = isset($latest_capacity['sisa_hari_kerja'])
                ? $latest_capacity['sisa_hari_kerja']
                : (isset($latest_capacity['hari_kerja']) ? $latest_capacity['hari_kerja'] : NULL);
            if ($latest_sisa_hari_kerja !== NULL) {
                $effective_days_left = $latest_sisa_hari_kerja;
            }
            if (isset($latest_capacity['balance_qty'])) {
                $current_balance_qty = $latest_capacity['balance_qty'];
            } elseif (isset($latest_capacity['total_demand'])) {
                $current_balance_qty = $latest_capacity['total_demand'];
            }
            if (isset($latest_capacity['remaining_days'])) {
                $period_calendar['remaining_workdays'] = $latest_capacity['remaining_days'];
                $period_calendar['export_remaining_workdays'] = $latest_sisa_hari_kerja;
            }
        } else {
            $period_required_daily_output = $effective_days_left > 0 ? ceil($current_balance_qty / $effective_days_left) : 0;
            $required_daily_output = $period_required_daily_output;
        }

        $critical_orders = 0;
        foreach ($priority_orders as $order) {
            if ($this->delivery_workdays_left($order['delivery']) <= 5) {
                $critical_orders++;
            }
        }

        $insights = array();
        $capacity_gap_text = $capacity_gap >= 0
            ? 'gap ' . $this->format_compact_number($capacity_gap) . ' pcs'
            : 'surplus ' . $this->format_compact_number(abs($capacity_gap)) . ' pcs';

        $insights[] = $this->analytics_insight(
            $achievement_rate >= 90 ? 'good' : ($achievement_rate >= 75 ? 'watch' : 'risk'),
            'Achievement output',
            'Output sudah mencapai ' . $this->format_percent($achievement_rate) . ' dari total PDK.'
        );
        $insights[] = $this->analytics_insight(
            $ready_coverage_days >= 10 ? 'good' : ($ready_coverage_days >= 5 ? 'watch' : 'risk'),
            'Coverage ready load',
            'Stok ready setara ' . number_format($ready_coverage_days, 1) . ' hari kapasitas.'
        );
        $insights[] = $this->analytics_insight(
            $critical_orders > 0 ? 'risk' : 'good',
            'Critical orders',
            $critical_orders > 0 ? $critical_orders . ' order prioritas berada di horizon 5 hari.' : 'Tidak ada order prioritas dalam horizon 5 hari.'
        );
        $insights[] = $this->analytics_insight(
            $data_accuracy['status'],
            'Akurasi urutan data',
            $data_accuracy['message']
        );
        $action_plan = $this->build_management_action_plan(
            $achievement_rate,
            $ready_coverage_days,
            $required_daily_output,
            $avg_daily_output,
            $critical_orders,
            $data_accuracy,
            $total_ready,
            $avg_daily_capacity,
            $ready_rows
        );
        $overall_condition = $this->build_overall_condition(
            $achievement_rate,
            $ready_coverage_days,
            $period_required_daily_output,
            $avg_daily_output,
            $avg_daily_capacity,
            $critical_orders,
            $data_accuracy,
            $current_period,
            $period_calendar
        );

        return array(
            'overall_condition' => $overall_condition,
            'metrics' => array(
                array('label' => 'Output Achievement', 'value' => $this->floor_decimal($achievement_rate, 2), 'suffix' => '%', 'status' => $achievement_rate >= 90 ? 'good' : ($achievement_rate >= 75 ? 'watch' : 'risk')),
                array('label' => 'Ready Coverage', 'value' => round($ready_coverage_days, 1), 'suffix' => 'Days', 'status' => $ready_coverage_days >= 10 ? 'good' : ($ready_coverage_days >= 5 ? 'watch' : 'risk')),
                array('label' => 'Req. Daily Output', 'value' => round($required_daily_output), 'suffix' => 'Pcs/Day', 'status' => $avg_daily_output >= $required_daily_output ? 'good' : ($avg_daily_output >= ($required_daily_output * 0.9) ? 'watch' : 'risk')),
                array('label' => 'Data Accuracy', 'value' => $data_accuracy['score'], 'suffix' => '%', 'status' => $data_accuracy['status']),
            ),
            'summary' => array(
                'total_ready' => $total_ready,
                'avg_daily_output' => $avg_daily_output,
                'avg_daily_capacity' => $avg_daily_capacity,
                'capacity_gap' => $capacity_gap,
                'critical_orders' => $critical_orders,
                'data_accuracy_score' => $data_accuracy['score'],
                'sequence_issues' => count($data_accuracy['issues']),
            ),
            'details' => array(
                'output' => array(
                    'total_pdk' => $pdk_base,
                    'total_output' => $output_base,
                    'balance_qty' => $current_period ? $current_period['balance'] : $balance_qty,
                    'achievement_rate' => $achievement_rate,
                    'current_period' => $current_period,
                ),
                'ready' => array(
                    'total_ready' => $total_ready,
                    'avg_daily_capacity' => $avg_daily_capacity,
                    'coverage_days' => $ready_coverage_days,
                    'periods' => $ready_rows,
                ),
                'daily_requirement' => array(
                    'balance_qty' => $balance_qty,
                    'prod_days_left' => $prod_days_left,
                    'required_daily_output' => $required_daily_output,
                    'avg_daily_output' => $avg_daily_output,
                    'avg_daily_capacity' => $avg_daily_capacity,
                    'period_balance_qty' => $current_balance_qty,
                    'period_days_left' => $effective_days_left,
                    'period_required_daily_output' => $period_required_daily_output,
                    'period_calendar' => $period_calendar,
                ),
                'priority' => array(
                    'critical_orders' => $critical_orders,
                    'orders' => array_slice($priority_orders, 0, 5),
                ),
            ),
            'insights' => $insights,
            'data_accuracy' => $data_accuracy,
            'action_plan' => $action_plan,
        );
    }

    private function build_overall_condition($achievement_rate, $ready_coverage_days, $required_daily_output, $avg_daily_output, $avg_daily_capacity, $critical_orders, $data_accuracy, $current_period, $period_calendar)
    {
        $risk_points = 0;
        $watch_points = 0;
        $drivers = array();
        $current_label = $current_period && isset($current_period['label']) ? $current_period['label'] : '-';
        $current_balance = $current_period && isset($current_period['balance']) ? $current_period['balance'] : 0;
        $remaining_days = isset($period_calendar['export_remaining_workdays']) ? $period_calendar['export_remaining_workdays'] : 0;
        $total_days = isset($period_calendar['export_total_workdays']) ? $period_calendar['export_total_workdays'] : 0;
        $calendar_remaining_days = isset($period_calendar['remaining_workdays']) ? $period_calendar['remaining_workdays'] : 0;
        $export_prep_days = isset($period_calendar['export_prep_days']) ? $period_calendar['export_prep_days'] : $this->export_prep_workdays();

        if ($achievement_rate < 75) {
            $risk_points++;
            $drivers[] = 'output achievement rendah';
        } elseif ($achievement_rate < 90) {
            $watch_points++;
            $drivers[] = 'output achievement perlu dipantau';
        }

        if ($ready_coverage_days < 5) {
            $risk_points++;
            $drivers[] = 'ready load rendah';
        } elseif ($ready_coverage_days < 10) {
            $watch_points++;
            $drivers[] = 'ready load perlu dijaga';
        }

        if ($remaining_days > 11) {
            $risk_points += 3;
            $drivers[] = 'sisa hari kerja lebih dari 11 hari';
        } elseif ($remaining_days >= 5) {
            $watch_points += 2;
            $drivers[] = 'sisa hari kerja 5-11 hari';
        } elseif ($remaining_days < 4) {
            $drivers[] = 'sisa hari kerja aman';
        }

        if ($remaining_days <= 0 && $current_balance > 0) {
            $risk_points++;
            $drivers[] = 'sisa hari kerja periode sudah habis';
        } elseif ($avg_daily_capacity < $required_daily_output) {
            $risk_points++;
            $drivers[] = 'kapasitas harian tidak cukup mengejar export';
        } elseif ($avg_daily_output < $required_daily_output) {
            $watch_points++;
            $drivers[] = 'output harian perlu dikejar';
        } elseif ($avg_daily_output < ($required_daily_output * 1.1)) {
            $watch_points++;
            $drivers[] = 'margin output harian tipis';
        }

        if ($critical_orders > 0) {
            $risk_points++;
            $drivers[] = 'ada order delivery kritis';
        }

        if ($data_accuracy['score'] < 75) {
            $risk_points++;
            $drivers[] = 'akurasi urutan data rendah';
        } elseif ($data_accuracy['score'] < 90) {
            $watch_points++;
            $drivers[] = 'akurasi urutan data perlu dipantau';
        }

        if ($remaining_days > 11) {
            $status = 'risk';
            $level = 'High Risk';
        } elseif ($remaining_days >= 5) {
            $status = 'watch';
            $level = 'Medium Risk';
        } elseif ($remaining_days < 4) {
            $status = 'good';
            $level = 'Low Risk';
        } elseif ($risk_points >= 3) {
            $status = 'risk';
            $level = 'High Risk';
        } elseif ($risk_points > 0 || $watch_points >= 2) {
            $status = 'watch';
            $level = 'Medium Risk';
        } elseif ($watch_points > 0) {
            $status = 'watch';
            $level = 'Medium Risk';
        } else {
            $status = 'good';
            $level = 'Low Risk';
        }

        return array(
            'status' => $status,
            'level' => $level,
            'title' => 'Status Export ' . $current_label . ' :',
            'delivery' => $current_label,
            'summary' => 'Periode ' . $current_label . ': kurang ' . $this->format_compact_number($current_balance) . ' pcs, sisa export ' . number_format($remaining_days, 1) . ' dari ' . number_format($total_days, 1) . ' hari kerja. Kalender masih ' . number_format($calendar_remaining_days, 1) . ' hari, dengan buffer export ' . number_format($export_prep_days, 1) . ' hari. Butuh ' . $this->format_compact_number($required_daily_output) . ' pcs/hari; avg output ' . $this->format_compact_number($avg_daily_output) . ' pcs/hari, kapasitas ' . $this->format_compact_number($avg_daily_capacity) . ' pcs/hari. ' . ($drivers ? 'Faktor utama: ' . implode(', ', array_slice($drivers, 0, 2)) . '.' : 'Export periode berjalan masih terkejar.'),
            'risk_points' => $risk_points,
            'watch_points' => $watch_points,
        );
    }

    private function build_delivery_workdays($qty_rows, $ready_rows)
    {
        $labels = array();
        foreach (array_merge($qty_rows ?: array(), $ready_rows ?: array()) as $row) {
            if (!empty($row['label'])) {
                $labels[] = $row['label'];
            }
        }

        $labels = array_values(array_unique($labels));
        usort($labels, array($this, 'compare_period_labels'));

        $items = array();
        foreach ($labels as $label) {
            $items[] = $this->build_period_calendar($label);
        }

        return $items;
    }

    private function build_current_period_calendar($current_period)
    {
        if (!$current_period || empty($current_period['label'])) {
            return $this->empty_period_calendar();
        }

        return $this->build_period_calendar($current_period['label']);
    }

    private function empty_period_calendar()
    {
        return array(
            'label' => '',
            'start_date' => '',
            'end_date' => '',
            'total_workdays' => 0,
            'elapsed_workdays' => 0,
            'remaining_workdays' => 0,
            'export_prep_days' => $this->export_prep_workdays(),
            'export_total_workdays' => 0,
            'export_remaining_workdays' => 0,
            'holidays' => array(),
            'half_days' => array(),
            'quarter_days' => array(),
            'work_days' => array(),
            'manual_remaining' => FALSE,
            'is_saturday_workday' => TRUE,
            'working_hours_saturday' => 5,
            'working_hours_weekday' => 7,
        );
    }

    private function is_saturday_workday($calendar_days, $date = NULL)
    {
        $target = $date ? strtotime($date) : time();
        $day_of_week = (int) date('w', $target);
        $offset = $day_of_week === 0 ? -1 : (6 - $day_of_week);
        $sat_date = date('Y-m-d', strtotime(($offset >= 0 ? '+' : '') . $offset . ' days', $target));

        $holidays = isset($calendar_days['holidays']) && is_array($calendar_days['holidays']) ? $calendar_days['holidays'] : array();
        return !in_array($sat_date, $holidays, TRUE);
    }

    private function build_period_calendar($label, $current_date = NULL)
    {
        if (trim((string) $label) === '') {
            return $this->empty_period_calendar();
        }

        $range = $this->period_date_range($label);
        $calendar_days = $this->dashboard_calendar_days();
        $today = $current_date ? date('Y-m-d', strtotime($current_date)) : date('Y-m-d');
        $start = $range ? max($range['start'], $today) : '';
        $remaining = $range && $start <= $range['end'] ? $this->count_workdays($start, $range['end'], $calendar_days) : 0;
        $total = $range ? $this->count_workdays($range['start'], $range['end'], $calendar_days) : 0;
        $export_prep_days = $this->export_prep_workdays();
        $export_total = $this->export_workdays_from_remaining($total);
        $export_remaining = $this->export_workdays_from_remaining($remaining);
        $elapsed = max(0, $total - $remaining);

        $is_sat_workday = $this->is_saturday_workday($calendar_days, $today);
        $working_hours_saturday = $is_sat_workday ? 5 : 0;
        $working_hours_weekday = $is_sat_workday ? 7 : 8;

        return array(
            'label' => $label,
            'start_date' => $range ? $range['start'] : '',
            'end_date' => $range ? $range['end'] : '',
            'total_workdays' => $total,
            'elapsed_workdays' => $elapsed,
            'remaining_workdays' => $remaining,
            'export_prep_days' => $export_prep_days,
            'export_total_workdays' => $export_total,
            'export_remaining_workdays' => $export_remaining,
            'holidays' => $calendar_days['holidays'],
            'half_days' => $calendar_days['half_days'],
            'quarter_days' => $calendar_days['quarter_days'],
            'work_days' => $calendar_days['work_days'],
            'manual_remaining' => FALSE,
            'is_saturday_workday' => $is_sat_workday,
            'working_hours_saturday' => $working_hours_saturday,
            'working_hours_weekday' => $working_hours_weekday,
        );
    }

    private function export_prep_workdays_per_delivery()
    {
        return 4;
    }

    private function active_delivery_count($qty_rows = NULL)
    {
        $count = 0;
        foreach ($qty_rows ?: array() as $row) {
            if (!empty($row['label'])) {
                $count++;
            }
        }

        return $count;
    }

    private function export_prep_workdays($delivery_count = 1)
    {
        $count = (int) $delivery_count;
        if ($count <= 1) {
            return 4;
        }
        if ($count === 2) {
            return 8;
        }
        if ($count >= 4) {
            return 14;
        }
        return 4;
    }
    private function export_prep_workdays_for_qty_rows($qty_rows)
    {
        return $this->export_prep_workdays($this->active_delivery_count($qty_rows));
    }

    private function export_workdays_from_remaining($workdays, $delivery_count = 1)
    {
        return max(0, $workdays - $this->export_prep_workdays($delivery_count));
    }

    private function period_date_range($label)
    {
        if (preg_match('/^([A-Za-z]+)\s+(\d{4})$/i', trim($label), $month_match)) {
            $month_index = $this->month_label_to_index($month_match[1]);
            if ($month_index === NULL) {
                return NULL;
            }

            $year = (int) $month_match[2];
            $month = $month_index + 1;

            return array(
                'start' => sprintf('%04d-%02d-01', $year, $month),
                'end' => sprintf('%04d-%02d-%02d', $year, $month, (int) date('t', strtotime(sprintf('%04d-%02d-01', $year, $month)))),
            );
        }

        if (!preg_match('/^(MID|END)\s+([A-Za-z]+)$/i', trim($label), $match)) {
            return NULL;
        }

        $month_index = $this->month_label_to_index($match[2]);
        if ($month_index === NULL) {
            return NULL;
        }

        $year = (int) date('Y');
        $month = $month_index + 1;
        $start_day = strtoupper($match[1]) === 'MID' ? 1 : 16;
        $end_day = strtoupper($match[1]) === 'MID' ? 15 : (int) date('t', strtotime(sprintf('%04d-%02d-01', $year, $month)));

        return array(
            'start' => sprintf('%04d-%02d-%02d', $year, $month, $start_day),
            'end' => sprintf('%04d-%02d-%02d', $year, $month, $end_day),
        );
    }

    private function count_workdays($start, $end, $calendar_days)
    {
        if ($start > $end) {
            return 0;
        }

        $count = 0.0;
        foreach ($this->date_range($start, $end) as $date) {
            $count += $this->calendar_workday_value($date, $calendar_days);
        }

        return $count;
    }

    private function calendar_workday_value($date, $calendar_days)
    {
        $holidays = isset($calendar_days['holidays']) && is_array($calendar_days['holidays']) ? $calendar_days['holidays'] : array();
        $half_days = isset($calendar_days['half_days']) && is_array($calendar_days['half_days']) ? $calendar_days['half_days'] : array();
        $quarter_days = isset($calendar_days['quarter_days']) && is_array($calendar_days['quarter_days']) ? $calendar_days['quarter_days'] : array();
        $work_days = isset($calendar_days['work_days']) && is_array($calendar_days['work_days']) ? $calendar_days['work_days'] : array();

        if (in_array($date, $holidays, TRUE)) {
            return 0;
        }

        if (in_array($date, $half_days, TRUE)) {
            return 0.5;
        }

        if (in_array($date, $quarter_days, TRUE)) {
            return 0.25;
        }

        $day_of_week = (int) date('w', strtotime($date));
        if ($day_of_week === 0 && !in_array($date, $work_days, TRUE)) {
            return 0;
        }

        return 1;
    }

    private function date_range($start, $end)
    {
        $dates = array();
        $current = strtotime($start);
        $last = strtotime($end);
        while ($current !== FALSE && $current <= $last) {
            $dates[] = date('Y-m-d', $current);
            $current = strtotime('+1 day', $current);
        }

        return $dates;
    }

    private function dashboard_holidays()
    {
        return $this->dashboard_calendar_days()['holidays'];
    }

    private function dashboard_calendar_days()
    {
        $config = $this->dashboard_config();
        $holidays = isset($config['dashboard_heat_holidays']) && is_array($config['dashboard_heat_holidays'])
            ? $config['dashboard_heat_holidays']
            : array();
        $calendar = $this->get_heat_holiday_settings();
        $holidays = array_merge($holidays, $calendar['holidays']);

        $holidays = array_values(array_unique(array_filter(array_map(function ($date) {
            $timestamp = strtotime($date);
            return $timestamp ? date('Y-m-d', $timestamp) : NULL;
        }, $holidays))));
        $half_days = array_values(array_diff($calendar['half_days'], $holidays));
        $quarter_days = array_values(array_diff($calendar['quarter_days'], array_merge($holidays, $half_days)));
        $work_days = array_values(array_diff($calendar['work_days'], array_merge($holidays, $half_days, $quarter_days)));
        sort($holidays);
        sort($half_days);
        sort($quarter_days);
        sort($work_days);

        return array(
            'holidays' => $holidays,
            'half_days' => $half_days,
            'quarter_days' => $quarter_days,
            'work_days' => $work_days,
        );
    }

    private function build_management_action_plan($achievement_rate, $ready_coverage_days, $required_daily_output, $avg_daily_output, $critical_orders, $data_accuracy, $total_ready = 0, $avg_daily_capacity = 0, $ready_rows = array())
    {
        $items = array();

        // 1. Data Accuracy
        if ($data_accuracy['score'] < 90) {
            $items[] = array(
                'status'     => $data_accuracy['status'],
                'title'      => '1. Data Accuracy',
                'masalah'    => 'Akurasi dibawah 90%',
                'penyebab'   => implode('; ', array(
                    '1. Jalan produksi tidak sesuai delivery prioritas',
                    '2. Panel delivery prioritas datang sesudah delivery berikutnya di proses produksi',
                )),
                'prevention' => 'Sebelum jalan produksi diwajibkan melihat ready to load agar jalan sesuai delivery prioritas.',
                'handling'   => 'Review issue yang muncul, tahan proses periode berikutnya bila perlu, lalu selesaikan atau koreksi data periode yang masih tersisa.',
            );
        }

        // 2. Output Achievement
        if ($achievement_rate < 90) {
            $ready_period_list = array();
            foreach ($ready_rows as $row) {
                if (!empty($row['label']) && isset($row['ready']) && $row['ready'] > 0) {
                    $ready_period_list[] = $row['label'] . ': ' . $this->format_compact_number($row['ready']) . ' pcs';
                }
            }
            $ready_info = $ready_period_list
                ? 'Ready to load tersedia: ' . implode(', ', $ready_period_list) . '.'
                : 'Ready to load tidak tersedia sesuai kapasitas.';

            $items[] = array(
                'status'     => $achievement_rate >= 75 ? 'watch' : 'risk',
                'title'      => '2. Output Achievement',
                'masalah'    => 'Pencapaian output tidak sesuai kapasitas (bisa lebih/kurang)',
                'penyebab'   => implode('; ', array(
                    '1. Pemakaian operator tidak sesuai dengan kapasitas (bisa kelebihan operator/kekurangan operator)',
                    '2. Efisiensi operator tdk tercapai (dibawah 83%)',
                    '3. Efisiensi operator diatas 83%',
                    '4. Ready to load tidak tersedia sesuai kapasitas',
                )),
                'prevention' => implode(' ', array(
                    '1. Pemakaian operator disesuaikan dengan kapasitas,',
                    '2. Pantau target harian per jam,',
                    '3. Jaga buffer ready to load minimal untuk 5 hari kapasitas',
                )),
                'handling'   => 'Fokuskan kapasitas ke balance terbesar, pecah bottleneck material/loading, dan tambah jam kerja bila target harian tidak tercapai. ' . $ready_info,
            );
        }

        // 3. Coverage Ready Load (dipisahkan, bisa dari output achievement)
        if ($ready_coverage_days < 10) {
            $coverage_from_output = ($achievement_rate < 90) ? ' (lihat juga CAP Output Achievement)' : '';
            $items[] = array(
                'status'     => $ready_coverage_days >= 5 ? 'watch' : 'risk',
                'title'      => '3. Coverage Ready Load',
                'masalah'    => 'Buffer ready load hanya ' . number_format($ready_coverage_days, 1) . ' hari kapasitas (target minimal 5 hari)',
                'penyebab'   => implode('; ', array(
                    '1. Ready to load tidak tersedia sesuai kapasitas',
                    '2. Output achievement rendah sehingga stok ready tidak terbentuk',
                )),
                'prevention' => 'Jaga buffer ready to load minimal untuk 5 hari kapasitas' . $coverage_from_output . '.',
                'handling'   => 'Prioritaskan picking/material untuk order delivery terdekat dan order dengan balance terbesar.',
            );
        }

        // 4. Kebutuhan output harian
        if ($avg_daily_output < $required_daily_output) {
            $items[] = array(
                'status'     => $avg_daily_output >= ($required_daily_output * 0.9) ? 'watch' : 'risk',
                'title'      => '4. Kebutuhan Output Harian',
                'masalah'    => 'Rata-rata output harian belum memenuhi kebutuhan ' . $this->format_compact_number($required_daily_output) . ' pcs/hari',
                'penyebab'   => 'Output harian tidak mencapai target kapasitas yang dibutuhkan.',
                'prevention' => 'Bandingkan required daily output dengan plan kapasitas sebelum mengunci komitmen delivery.',
                'handling'   => 'Naikkan output harian lewat tambahan slot produksi, penyesuaian prioritas, atau negosiasi delivery bila gap tidak tertutup.',
            );
        }

        // 5. Order delivery kritis
        if ($critical_orders > 0) {
            $items[] = array(
                'status'     => 'risk',
                'title'      => '5. Order Delivery Kritis',
                'masalah'    => $critical_orders . ' order prioritas berada dalam horizon delivery 5 hari',
                'penyebab'   => 'Order mendekati due date masih belum ready atau belum selesai produksi.',
                'prevention' => 'Gunakan aging delivery harian agar order mendekati due date tidak tertinggal di queue.',
                'handling'   => 'Tarik order kritis ke prioritas pertama, pastikan material ready, dan monitor output order tersebut per shift.',
            );
        }

        // Jika semua kondisi baik
        if (!$items) {
            $items[] = array(
                'status'     => 'good',
                'title'      => 'Kondisi terkendali',
                'masalah'    => '',
                'penyebab'   => '',
                'prevention' => 'Validasi order/delivery, buffer ready load minimal 5 hari, dan monitoring output harian.',
                'handling'   => 'Lanjutkan produksi sesuai prioritas berjalan dan review ulang saat data dashboard berikutnya masuk.',
            );
        }

        return $items;
    }

    private function build_data_accuracy($qty_rows, $ready_rows)
    {
        $ready_by_label = array();
        foreach ($ready_rows as $row) {
            $ready_by_label[strtolower($row['label'])] = $row['ready'];
        }

        $periods = array();
        foreach ($qty_rows as $row) {
            $label = $row['label'];
            $pdk = isset($row['pdk']) ? $row['pdk'] : 0;
            $output = isset($row['output']) ? $row['output'] : 0;
            $periods[] = array(
                'label' => $label,
                'pdk' => $pdk,
                'output' => $output,
                'balance' => max(0, $pdk - $output),
                'ready' => isset($ready_by_label[strtolower($label)]) ? $ready_by_label[strtolower($label)] : 0,
            );
        }

        $issues = array();
        $current_period = $this->current_running_period($periods);
        $previous_labels = $current_period ? $this->previous_period_labels($current_period['label'], 2) : array();

        foreach ($previous_labels as $previous_label) {
            $previous = $this->find_period_by_label($periods, $previous_label);
            if (!$previous) {
                continue;
            }

            $blocking_qty = $previous['balance'] + $previous['ready'];
            if ($blocking_qty <= 0) {
                continue;
            }

            $issues[] = array(
                'status' => 'risk',
                'title' => $previous['label'] . ' belum clear',
                'text' => 'Periode berjalan ' . $current_period['label'] . ', sementara ' . $previous['label'] . ' masih punya sisa/ready ' . $this->format_compact_number($blocking_qty) . ' pcs.',
            );
        }

        $score = max(0, 100 - (count($issues) * 20));
        $status = $score >= 90 ? 'good' : ($score >= 75 ? 'watch' : 'risk');
        $message = count($issues)
            ? count($issues) . ' potensi periode sebelumnya belum clear ditemukan.'
            : ($current_period ? 'Periode berjalan ' . $current_period['label'] . ' sudah konsisten terhadap periode sebelumnya.' : 'Urutan data antar periode sudah konsisten.');

        return array(
            'score' => $score,
            'status' => $status,
            'message' => $message,
            'issues' => $issues,
            'periods' => $periods,
            'current_period' => $current_period,
            'checked_periods' => $previous_labels,
        );
    }

        private function current_running_period($periods)
    {
        if (!$periods) {
            return NULL;
        }

        $today = date('Y-m-d');
        $fallback = NULL;
        foreach ($periods as $period) {
            if ($fallback === NULL && ((isset($period['balance']) && $period['balance'] > 0) || (isset($period['ready']) && $period['ready'] > 0) || (isset($period['pdk']) && $period['pdk'] > 0) || (isset($period['output']) && $period['output'] > 0))) {
                $fallback = $period;
            }

            $range = isset($period['label']) ? $this->period_date_range($period['label']) : NULL;
            if (!$range) {
                continue;
            }

            if ($range['end'] >= $today) {
                return $period;
            }
        }

        if ($fallback !== NULL) {
            return $fallback;
        }

        return isset($periods[0]) ? $periods[0] : NULL;
    }

    private function previous_period_labels($label, $count = 2)
    {
        if (preg_match('/^([A-Za-z]+)\s+(\d{4})$/i', trim($label), $match)) {
            $month_index = $this->month_label_to_index($match[1]);
            if ($month_index === NULL) {
                return array();
            }

            $year = (int) $match[2];
            $labels = array();
            while (count($labels) < $count) {
                $month_index--;
                if ($month_index < 0) {
                    $month_index = 11;
                    $year--;
                }

                $labels[] = $this->months[$month_index] . ' ' . $year;
            }

            return $labels;
        }

        if (!preg_match('/^(MID|END)\s+([A-Za-z]+)$/i', trim($label), $match)) {
            return array();
        }

        $type = strtoupper($match[1]);
        $month_index = $this->month_label_to_index($match[2]);
        if ($month_index === NULL) {
            return array();
        }

        $labels = array();
        while (count($labels) < $count) {
            if ($type === 'END') {
                $type = 'MID';
            } else {
                $type = 'END';
                $month_index--;
                if ($month_index < 0) {
                    $month_index = 11;
                }
            }

            $labels[] = $type . ' ' . $this->months[$month_index];
        }

        return $labels;
    }

    private function find_period_by_label($periods, $label)
    {
        foreach ($periods as $period) {
            if (strcasecmp($period['label'], $label) === 0) {
                return $period;
            }
        }

        return NULL;
    }

    private function month_label_to_index($label)
    {
        foreach ($this->months as $index => $month) {
            if (strcasecmp($month, $label) === 0) {
                return $index;
            }
        }

        return NULL;
    }

    private function analytics_insight($status, $title, $text)
    {
        return array('status' => $status, 'title' => $title, 'text' => $text);
    }

    private function delivery_workdays_left($delivery)
    {
        $timestamp = strtotime($delivery);
        if (!$timestamp) {
            return PHP_INT_MAX;
        }

        $today = date('Y-m-d');
        $delivery_date = date('Y-m-d', $timestamp);
        if ($delivery_date < $today) {
            return 0;
        }

        return $this->count_workdays($today, $delivery_date, $this->dashboard_calendar_days());
    }

    private function format_percent($value)
    {
        return number_format($value, 1) . '%';
    }

    private function floor_decimal($value, $decimals)
    {
        $factor = pow(10, $decimals);
        return floor($value * $factor) / $factor;
    }

    private function format_compact_number($value)
    {
        return number_format($value, 0, '.', ',');
    }

    private function grid_cell($rows, $row_number, $column)
    {
        $row_index = $row_number - 1;
        $column_index = $this->column_letters_to_index($column);

        return isset($rows[$row_index][$column_index]) ? $rows[$row_index][$column_index] : '';
    }

    private function column_letters_to_index($letters)
    {
        $letters = strtoupper($letters);
        $index = 0;

        for ($i = 0; $i < strlen($letters); $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - 64);
        }

        return $index - 1;
    }

    private function format_excel_date($value)
    {
        $timestamp = $this->parse_date_timestamp($value);
        if ($timestamp) {
            return date('d M Y', (int) $timestamp);
        }
        return $value;
    }

    private function format_excel_datetime($value)
    {
        if (is_numeric($value)) {
            $timestamp = ((float) $value - 25569) * 86400;
            return gmdate('d M Y H:i', (int) round($timestamp));
        }

        $timestamp = $this->parse_date_timestamp($value);
        if ($timestamp) {
            return date('d M Y', (int) $timestamp);
        }

        return $value;
    }

    private function xlsx_sheet_name_for_hint($zip, $sheet_hint)
    {
        if (!$sheet_hint) {
            return NULL;
        }

        $sheet_map = $this->xlsx_sheet_map($zip);
        if (!$sheet_map) {
            return NULL;
        }

        $hint = strtolower(pathinfo($sheet_hint, PATHINFO_FILENAME));
        foreach ($sheet_map as $title => $path) {
            $normalized_title = strtolower(str_replace(array(' ', '-'), '_', $title));
            if ($normalized_title === $hint || strpos($normalized_title, $hint) !== FALSE) {
                return $path;
            }
        }

        if (strpos($hint, 'outflow') !== FALSE) {
            foreach ($sheet_map as $title => $path) {
                if (strpos(strtolower($title), 'out') !== FALSE) {
                    return $path;
                }
            }
        }

        if (strpos($hint, 'inflow') !== FALSE) {
            foreach ($sheet_map as $title => $path) {
                if (strpos(strtolower($title), 'in') !== FALSE) {
                    return $path;
                }
            }
        }

        return NULL;
    }

    private function xlsx_sheet_map($zip)
    {
        $workbook_xml = $zip->getFromName('xl/workbook.xml');
        $rels_xml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($workbook_xml === FALSE || $rels_xml === FALSE) {
            return array();
        }

        $workbook = simplexml_load_string($workbook_xml);
        $rels = simplexml_load_string($rels_xml);
        if (!$workbook || !$rels) {
            return array();
        }

        $relations = array();
        foreach ($rels->Relationship as $relation) {
            $target = (string) $relation['Target'];
            $relations[(string) $relation['Id']] = 'xl/' . ltrim($target, '/');
        }

        $workbook->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $map = array();
        foreach ($workbook->sheets->sheet as $sheet) {
            $attributes = $sheet->attributes('r', TRUE);
            $relation_id = (string) $attributes['id'];
            if (isset($relations[$relation_id])) {
                $map[(string) $sheet['name']] = $relations[$relation_id];
            }
        }

        return $map;
    }

    private function read_xlsx_shared_strings($zip)
    {
        $xml_string = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml_string === FALSE) {
            return array();
        }

        $xml = simplexml_load_string($xml_string);
        if (!$xml) {
            return array();
        }

        $strings = array();
        foreach ($xml->si as $item) {
            if (isset($item->t)) {
                $strings[] = (string) $item->t;
                continue;
            }

            $parts = array();
            foreach ($item->r as $run) {
                $parts[] = (string) $run->t;
            }
            $strings[] = implode('', $parts);
        }

        return $strings;
    }

    private function first_xlsx_sheet_name($zip)
    {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (preg_match('/^xl\/worksheets\/sheet\d+\.xml$/', $name)) {
                return $name;
            }
        }

        return NULL;
    }

    private function xlsx_cell_value($cell_node, $shared_strings)
    {
        $type = (string) $cell_node['t'];

        if ($type === 's') {
            $index = (int) $cell_node->v;
            return isset($shared_strings[$index]) ? $shared_strings[$index] : '';
        }

        if ($type === 'inlineStr') {
            return isset($cell_node->is->t) ? (string) $cell_node->is->t : '';
        }

        return isset($cell_node->v) ? (string) $cell_node->v : '';
    }

    private function xlsx_column_index($ref)
    {
        preg_match('/^[A-Z]+/i', $ref, $match);
        $letters = strtoupper($match ? $match[0] : 'A');
        $index = 0;

        for ($i = 0; $i < strlen($letters); $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - 64);
        }

        return $index - 1;
    }

    private function parse_table_rows($html)
    {
        preg_match_all('/<tr\b[^>]*>(.*?)<\/tr>/is', $html, $row_matches);
        $rows = array();

        foreach ($row_matches[1] as $row_html) {
            preg_match_all('/<td\b[^>]*>(.*?)<\/td>/is', $row_html, $cell_matches);
            $cells = array();
            foreach ($cell_matches[1] as $cell) {
                $cells[] = $this->normalize(html_entity_decode(strip_tags($cell), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            }
            if (count($cells) > 2) {
                $rows[] = $cells;
            }
        }

        return $rows;
    }

    private function count_report_rows($path)
    {
        return count($this->read_html_report($path)['rows']);
    }

    private function summarize_32a_inflow_periods()
    {
        $periods = array();
        $paths = $this->matching_files_in_trees($this->all_data_dirs(), array(
            '32a_inflow.xlsx',
            '32a_inflow.xls',
            '32a_inflow_*.xlsx',
            '32a_inflow_*.xls',
        ));
        foreach ($paths as $path) {
            $month_key = basename(dirname($path));
            if (!preg_match('/^\\d{4}-\\d{2}$/', $month_key) || isset($periods[$month_key])) {
                continue;
            }

            $report = $this->read_html_report($path);
            $index = $this->header_index($report['headers']);
            $periods[$month_key] = array(
                'key' => $month_key,
                'label' => $this->format_period_label($month_key),
                'qty' => $this->sum_qty($report['rows'], $index),
                'rows' => count($report['rows']),
            );
        }

        return array_values($periods);
    }

    private function summarize_monthly_periods()
    {
        $month_keys = array();
        $candidate_paths = $this->matching_files_in_trees($this->all_data_dirs(), array(
            '32a_inflow.xlsx',
            '32a_inflow.xls',
            '32a_inflow_*.xlsx',
            '32a_inflow_*.xls',
            '32a_outflow.xlsx',
            '32a_outflow.xls',
            '32a_outflow_*.xlsx',
            '32a_outflow_*.xls',
        ));

        foreach ($candidate_paths as $path) {
            $key = basename(dirname($path));
            if (preg_match('/^\d{4}-\d{2}$/', $key)) {
                $month_keys[$key] = TRUE;
            }
        }

        $month_keys = array_keys($month_keys);
        sort($month_keys);

        if (!$month_keys) {
            $month_keys[] = date('Y-m', strtotime('-1 month'));
            $month_keys[] = date('Y-m');
        }

        $month_keys = array_slice(array_values(array_unique($month_keys)), -2);
        while (count($month_keys) < 2) {
            array_unshift($month_keys, date('Y-m', strtotime($month_keys[0] . '-01 -1 month')));
            $month_keys = array_values(array_unique($month_keys));
        }

        $periods = array();

        foreach ($month_keys as $month_key) {
            $inflow = NULL;
            $outflow = NULL;
            foreach ($candidate_paths as $path) {
                if (basename(dirname($path)) !== $month_key) {
                    continue;
                }

                $basename = basename($path);
                if ($inflow === NULL && preg_match('/32a_inflow/i', $basename)) {
                    $inflow = $path;
                }
                if ($outflow === NULL && preg_match('/32a_outflow/i', $basename)) {
                    $outflow = $path;
                }
                if ($inflow !== NULL && $outflow !== NULL) {
                    break;
                }
            }

            $summary = $this->summarize_rows_ready(
                $this->read_html_report($inflow),
                $this->read_html_report($outflow)
            );

            $periods[] = array(
                'key' => $month_key,
                'label' => $this->format_period_label($month_key),
                'ready_qty' => $summary['ready_qty'],
                'ready_pdk' => $summary['ready_pdk'],
                'ready_panels' => $summary['ready_panels'],
                'in_qty' => $summary['in_qty'],
                'out_qty' => $summary['out_qty'],
            );
        }

        return $periods;
    }

    private function summarize_rows_ready($inflow, $outflow)
    {
        if (!$inflow['headers']) {
            return array('ready_qty' => 0, 'ready_pdk' => 0, 'ready_panels' => 0, 'in_qty' => 0, 'out_qty' => 0);
        }

        $in_index = $this->header_index($inflow['headers']);
        $out_index = $this->header_index($outflow['headers']);
        $groups = array();

        foreach ($inflow['rows'] as $row) {
            $udef4 = $this->cell($row, $in_index, 'Udef 4');
            if ($udef4 === '') {
                continue;
            }
            if (!isset($groups[$udef4])) {
                $groups[$udef4] = array('in_qty' => 0, 'out_qty' => 0, 'in_rows' => 0, 'out_rows' => 0);
            }
            $groups[$udef4]['in_qty'] += $this->parse_number($this->cell($row, $in_index, 'Qty'));
            $groups[$udef4]['in_rows']++;
        }

        foreach ($outflow['rows'] as $row) {
            $udef4 = $this->cell($row, $out_index, 'Udef 4');
            if ($udef4 === '') {
                continue;
            }
            if (!isset($groups[$udef4])) {
                $groups[$udef4] = array('in_qty' => 0, 'out_qty' => 0, 'in_rows' => 0, 'out_rows' => 0);
            }
            $groups[$udef4]['out_qty'] += abs($this->parse_number($this->cell($row, $out_index, 'Qty')));
            $groups[$udef4]['out_rows']++;
        }

        $ready_qty = 0;
        $ready_pdk = 0;
        $ready_panels = 0;

        foreach ($groups as $group) {
            $qty = $group['in_qty'] - $group['out_qty'];
            if ($qty <= 0) {
                continue;
            }
            $ready_qty += $qty;
            $ready_pdk++;
            $ready_panels += max(0, $group['in_rows'] - $group['out_rows']);
        }

        return array(
            'ready_qty' => $ready_qty,
            'ready_pdk' => $ready_pdk,
            'ready_panels' => $ready_panels,
            'in_qty' => $this->sum_qty($inflow['rows'], $in_index),
            'out_qty' => abs($this->sum_qty($outflow['rows'], $out_index)),
        );
    }

    private function format_period_label($key)
    {
        if (preg_match('/^(\d{4})-(\d{2})$/', $key, $month_match)) {
            $month = (int) $month_match[2];
            $label = isset($this->months[$month - 1]) ? $this->months[$month - 1] : $month_match[2];
            return $label . ' ' . $month_match[1];
        }

        if (!preg_match('/^(\d{4})-(\d{2})_(mid|end)$/i', $key, $match)) {
            return $key;
        }

        $month = (int) $match[2];
        $label = isset($this->months[$month - 1]) ? $this->months[$month - 1] : $match[2];
        return strtoupper($match[3]) . ' ' . $label;
    }

    private function empty_group($udef4)
    {
        return array('udef4' => $udef4, 'item' => '', 'prod' => '', 'in_qty' => 0, 'out_qty' => 0, 'in_rows' => 0, 'out_rows' => 0);
    }

    private function header_index($headers)
    {
        $index = array();
        foreach ($headers as $key => $header) {
            $index[strtolower($header)] = $key;
        }
        return $index;
    }

    private function cell($row, $index, $name)
    {
        $key = strtolower($name);
        return isset($index[$key], $row[$index[$key]]) ? $row[$index[$key]] : '';
    }

    private function cell_any($row, $index, $names)
    {
        foreach ($names as $name) {
            $value = $this->cell($row, $index, $name);
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function sum_qty($rows, $index)
    {
        $sum = 0;
        foreach ($rows as $row) {
            $sum += $this->parse_number($this->cell($row, $index, 'Qty'));
        }
        return $sum;
    }

    private function parse_number($value)
    {
        $clean = str_replace(',', '', $this->normalize($value));
        return preg_match('/-?\d+(?:\.\d+)?/', $clean, $match) ? (float) $match[0] : 0;
    }

    private function normalize($value)
    {
        return trim(preg_replace('/\s+/', ' ', (string) $value));
    }

    private function format_bytes($size)
    {
        $units = array('B', 'KB', 'MB', 'GB');
        $value = (float) $size;
        foreach ($units as $unit) {
            if ($value < 1024 || $unit === 'GB') {
                return $unit === 'B' ? (int) $value . ' ' . $unit : number_format($value, 1) . ' ' . $unit;
            }
            $value /= 1024;
        }
        return number_format($value, 1) . ' GB';
    }
}












