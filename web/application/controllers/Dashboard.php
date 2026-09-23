<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/Dashboard_base.php';

class Dashboard extends Dashboard_base
{
    public function index()
    {
        $this->load->view('dashboard_portal', array(
            'title' => 'Dashboard GM Portal',
            'dashboard_url' => site_url('dashboard_heat'),
            'admin_url' => site_url('dashboard_heat/admin'),
            'portal_login_url' => site_url('dashboard_heat/api/portal-login'),
            'portal_logout_url' => site_url('dashboard_heat/api/portal-logout'),
        ));
    }
}
