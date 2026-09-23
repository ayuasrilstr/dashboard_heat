<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= html_escape($title ?? 'Dashboard GM Admin') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">
    <style>
        :root {
            /* Palette: Solid Navy & Gray, No Soft Colors */
            --primary: #1e3a8a;          /* Solid Navy Blue */
            --primary-dark: #0f172a;     /* Dark Slate Navy */
            --primary-hover: #172554;    /* Deep Navy Hover */
            --primary-light: #e2e8f0;    /* Solid Crisp Slate Tint for active contrast */
            
            --secondary: #475569;        /* Solid Slate Gray */
            --secondary-dark: #334155;   /* Darker Slate */
            --border-color: #cbd5e1;     /* Solid Slate Border */
            --border-light: #e2e8f0;     /* Solid Light Border */
            
            --success: #16a34a;          /* Solid Green */
            --danger: #dc2626;           /* Solid Red */
            --warning: #d97706;          /* Solid Amber / Orange */
            --info: #0284c7;             /* Solid Blue */
            
            --body-bg: #f1f5f9;          /* Solid Light Slate Gray Background */
            --card-bg: #ffffff;          /* Pure White Card */
            
            --text-heading: #0f172a;     /* Dark Navy Slate Heading */
            --text-body: #334155;        /* Slate Body Text */
            --text-muted: #64748b;       /* Muted Slate Text */
            
            --shadow-card: 0 2px 6px 0 rgba(15, 23, 42, 0.08);
            --shadow-hover: 0 6px 16px 0 rgba(15, 23, 42, 0.12);
            --shadow-modal: 0 16px 36px 0 rgba(15, 23, 42, 0.25);
            
            --radius-sm: 6px;
            --radius-md: 8px;
            --radius-lg: 10px;
            --radius-pill: 50rem;
            
            --brand: #1e3a8a;
            --brand-deep: #0f172a;
            --ok: #16a34a;
            --warn: #d97706;
            --risk: #dc2626;
            --ink: #0f172a;
            --muted: #64748b;
            --line: #cbd5e1;
            --panel-soft: #f8fafc;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Public Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            color: var(--text-body);
            background-color: var(--body-bg);
            font-size: 0.875rem;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }
        a { color: var(--primary); text-decoration: none; }
        a:hover { color: var(--primary-hover); }
        
        .shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 260px minmax(0, 1fr);
        }

        /* Sidebar: Solid Navy & Gray */
        .sidebar {
            display: flex;
            flex-direction: column;
            padding: 0 0 16px 0;
            background: #ffffff;
            border-right: 1px solid var(--border-color);
            box-shadow: 1px 0 4px rgba(15, 23, 42, 0.04);
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            overflow-x: hidden;
            z-index: 100;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 20px 20px 16px 20px;
            border-bottom: 1px solid var(--border-light);
        }
        .brand-logo-icon {
            width: 36px;
            height: 36px;
            border-radius: var(--radius-sm);
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            flex-shrink: 0;
        }
        .brand-text {
            display: flex;
            flex-direction: column;
        }
        .brand-text h1 {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--primary-dark);
            letter-spacing: -0.4px;
            line-height: 1.2;
        }
        .brand-text span {
            font-size: 0.72rem;
            color: var(--text-muted);
            font-weight: 600;
        }

        .menu-header {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--text-muted);
            padding: 16px 20px 6px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .menu-header::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border-color);
        }

        .nav-card {
            display: flex;
            flex-direction: column;
            gap: 4px;
            padding: 4px 12px;
        }
        .nav-card button {
            border: 0;
            border-radius: var(--radius-sm);
            background: transparent;
            color: var(--text-heading);
            font-family: inherit;
            font-size: 0.875rem;
            font-weight: 500;
            text-align: left;
            padding: 9px 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.15s ease;
        }
        .nav-card button svg {
            color: var(--secondary);
            flex-shrink: 0;
            transition: color 0.15s ease;
        }
        .nav-card button:hover {
            background-color: var(--border-light);
            color: var(--primary);
        }
        .nav-card button:hover svg {
            color: var(--primary);
        }
        .nav-card button.active {
            background-color: var(--primary);
            color: #ffffff;
            font-weight: 600;
        }
        .nav-card button.active svg {
            color: #ffffff;
        }

        .sidebar-footer {
            margin-top: auto;
            padding: 14px 14px 8px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            border-top: 1px solid var(--border-light);
        }
        .user-mini-card {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 10px;
            background: var(--body-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
        }
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: var(--radius-sm);
            background: var(--primary);
            color: #ffffff;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            flex-shrink: 0;
        }
        .user-info {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }
        .user-info strong {
            font-size: 0.82rem;
            color: var(--text-heading);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .user-info small {
            font-size: 0.7rem;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 600;
        }

        .btn-sidebar-primary {
            width: 100%;
            height: 36px;
            border-radius: var(--radius-sm);
            border: 0;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            background: var(--primary);
            color: #ffffff;
            transition: background 0.15s ease;
        }
        .btn-sidebar-primary:hover {
            background: var(--primary-hover);
            color: #ffffff;
        }
        .btn-sidebar-secondary {
            width: 100%;
            height: 34px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-color);
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
            color: var(--text-heading);
            transition: all 0.15s ease;
        }
        .btn-sidebar-secondary:hover {
            background: var(--body-bg);
            border-color: var(--danger);
            color: var(--danger);
        }

        /* Main Content */
        .content {
            min-width: 0;
            padding: 16px 20px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        /* Topbar: Navy & Gray */
        .topbar {
            background: #ffffff;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-card);
            padding: 10px 16px;
            display: flex;
            gap: 14px;
            align-items: center;
            justify-content: space-between;
        }
        .topbar-scope {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #f8fafc;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            padding: 4px 10px;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--text-heading);
        }
        .topbar-scope input[type="date"] {
            border: 1px solid var(--border-color);
            background: #ffffff;
            border-radius: var(--radius-sm);
            height: 28px;
            padding: 0 8px;
            font-family: inherit;
            font-size: 0.8rem;
            color: var(--text-heading);
            font-weight: 500;
            outline: none;
        }
        .topbar-scope input[type="date"]:focus {
            border-color: var(--primary);
        }
        .scope-spinner {
            width: 14px;
            height: 14px;
            border: 2px solid var(--border-color);
            border-top-color: var(--primary);
            border-radius: 50%;
            display: none;
            animation: spin 0.7s linear infinite;
        }
        .topbar-scope.loading .scope-spinner {
            display: inline-block;
        }
        @keyframes spin { 100% { transform: rotate(360deg); } }

        .period-pill {
            display: inline-flex;
            align-items: center;
            padding: 3px 8px;
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            font-weight: 700;
            background: var(--primary);
            color: #ffffff;
        }

        .topbar-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .btn {
            height: 36px;
            border-radius: var(--radius-sm);
            border: 0;
            padding: 0 14px;
            font-family: inherit;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.15s ease;
        }
        .btn-sm {
            height: 30px;
            padding: 0 10px;
            font-size: 0.75rem;
        }
        .btn-primary {
            background: var(--primary);
            color: #ffffff;
        }
        .btn-primary:hover {
            background: var(--primary-hover);
            color: #ffffff;
        }
        .btn-outline-primary {
            background: #ffffff;
            color: var(--primary);
            border: 1px solid var(--primary);
        }
        .btn-outline-primary:hover {
            background: var(--primary);
            color: #ffffff;
        }
        .btn-secondary {
            background: #ffffff;
            color: var(--text-heading);
            border: 1px solid var(--border-color);
        }
        .btn-secondary:hover {
            background: var(--body-bg);
            border-color: var(--secondary);
        }

        /* Sections */
        .section {
            display: none;
            flex-direction: column;
            gap: 16px;
        }
        .section.active {
            display: flex;
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: -4px;
        }
        .section-header h2 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--text-heading);
            letter-spacing: -0.3px;
        }

        /* Solid Cards */
        .card {
            background: #ffffff;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-card);
            padding: 18px 20px;
            position: relative;
        }

        /* Overview Row 1: Welcome Banner + 2 Mini KPI Cards */
        .overview-hero-row {
            display: grid;
            grid-template-columns: 1.6fr 1fr;
            gap: 16px;
        }
        
        .welcome-card {
            background: #ffffff;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-card);
            padding: 22px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }
        .welcome-content {
            max-width: 65%;
            z-index: 1;
        }
        .welcome-content h3 {
            margin: 0 0 6px 0;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary);
        }
        .welcome-content p {
            margin: 0 0 16px 0;
            font-size: 0.85rem;
            color: var(--text-body);
            line-height: 1.4;
        }
        .welcome-illustration {
            width: 140px;
            height: 105px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1;
            flex-shrink: 0;
        }

        /* 2 Top KPI Cards */
        .welcome-kpis {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .sneat-kpi-card {
            background: #ffffff;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-card);
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            position: relative;
        }
        .sneat-kpi-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        /* SOLID Icon Avatars (No soft colors) */
        .kpi-icon-avatar {
            width: 36px;
            height: 36px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            flex-shrink: 0;
        }
        .kpi-icon-avatar.primary { background: var(--primary); }
        .kpi-icon-avatar.secondary { background: var(--secondary); }
        .kpi-icon-avatar.success { background: var(--success); }
        .kpi-icon-avatar.warning { background: var(--warning); }
        .kpi-icon-avatar.danger { background: var(--danger); }
        .kpi-icon-avatar.info { background: var(--info); }

        .sneat-kpi-card .kpi-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .sneat-kpi-card .kpi-val {
            font-size: 1.55rem;
            font-weight: 700;
            color: var(--text-heading);
            line-height: 1.1;
            display: flex;
            align-items: baseline;
            gap: 4px;
        }
        .sneat-kpi-card .kpi-val small {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-muted);
        }
        .sneat-kpi-card .kpi-sub {
            font-size: 0.75rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Overview Row 2: 4 KPI Cards Grid */
        .overview-metrics-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }

        /* Overview Row 3: Capacity Analysis & Shortcuts */
        .overview-split-row {
            display: grid;
            grid-template-columns: 1.4fr 1fr;
            gap: 16px;
        }

        .card-header-clean {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
        }
        .card-header-clean h4 {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text-heading);
        }

        .capacity-stats-row {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 12px;
            padding: 12px 0;
            border-top: 1px solid var(--border-light);
            border-bottom: 1px solid var(--border-light);
        }
        .cap-stat-item {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .cap-stat-item span {
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
        }
        .cap-stat-item strong {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-heading);
        }
        .cap-stat-item small {
            font-size: 0.72rem;
            color: var(--text-muted);
        }

        /* Action List */
        .action-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .action-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 9px 12px;
            border-radius: var(--radius-sm);
            background: #f8fafc;
            border: 1px solid var(--border-light);
            transition: border-color 0.15s ease;
        }
        .action-item:hover {
            border-color: var(--primary);
        }
        .action-item-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .action-icon {
            width: 32px;
            height: 32px;
            border-radius: var(--radius-sm);
            background: var(--primary);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .action-info strong {
            font-size: 0.85rem;
            color: var(--text-heading);
            display: block;
        }
        .action-info small {
            font-size: 0.72rem;
            color: var(--text-muted);
        }

        /* SOLID Badges (No soft colors, solid fills with white text) */
        .badge-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            border-radius: var(--radius-sm);
            font-size: 0.7rem;
            font-weight: 700;
            background: var(--secondary);
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .badge-pill.good { background: var(--success); color: #ffffff; }
        .badge-pill.warn { background: var(--warning); color: #ffffff; }
        .badge-pill.risk { background: var(--danger); color: #ffffff; }
        .badge-pill.info { background: var(--info); color: #ffffff; }
        .badge-pill.primary { background: var(--primary); color: #ffffff; }

        /* Tables & Details: Solid Clean Borders */
        .table-card {
            background: #ffffff;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-card);
            overflow: hidden;
        }
        .table-scroll {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.84rem;
            text-align: left;
        }
        thead th {
            background: #e2e8f0;
            padding: 11px 14px;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-heading);
            border-bottom: 2px solid var(--border-color);
        }
        tbody td {
            padding: 11px 14px;
            border-bottom: 1px solid var(--border-light);
            color: var(--text-heading);
        }
        tbody tr:last-child td {
            border-bottom: 0;
        }
        tbody tr:hover {
            background: #f8fafc;
        }
        th.num, td.num {
            text-align: right;
        }

        .progress-bar-wrap {
            width: 100%;
            height: 6px;
            background: var(--border-color);
            border-radius: var(--radius-sm);
            overflow: hidden;
            margin-top: 4px;
        }
        .progress-bar-fill {
            height: 100%;
            background: var(--primary);
            border-radius: var(--radius-sm);
            transition: width 0.3s ease;
        }

        /* Formula & Chips */
        .summary-detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .formula-list {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .formula-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px;
            border-radius: var(--radius-sm);
            background: #f8fafc;
            border: 1px solid var(--border-light);
            font-size: 0.82rem;
        }
        .formula-item span {
            color: var(--text-muted);
            font-weight: 600;
        }
        .formula-item strong {
            color: var(--text-heading);
            font-weight: 700;
        }

        .chip-container {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            max-height: 220px;
            overflow-y: auto;
            padding: 4px 0;
        }
        .style-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: var(--radius-sm);
            background: #ffffff;
            border: 1px solid var(--border-color);
            font-size: 0.8rem;
        }
        .style-chip span { font-weight: 600; color: var(--text-heading); }
        .style-chip b { color: var(--primary); font-weight: 700; }

        /* SMV Data Table */
        .table-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            border-bottom: 1px solid var(--border-color);
            gap: 12px;
            flex-wrap: wrap;
        }
        .datatable-meta {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .datatable-meta input[type="search"] {
            height: 32px;
            padding: 0 10px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-family: inherit;
            font-size: 0.82rem;
            outline: none;
        }
        .datatable-meta input[type="search"]:focus {
            border-color: var(--primary);
        }
        .datatable-meta select {
            height: 32px;
            padding: 0 8px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-family: inherit;
            font-size: 0.82rem;
            outline: none;
        }
        .datatable-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 16px;
            border-top: 1px solid var(--border-color);
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        .datatable-pagination {
            display: flex;
            gap: 4px;
        }
        .datatable-pagination button {
            border: 1px solid var(--border-color);
            background: #ffffff;
            border-radius: var(--radius-sm);
            padding: 4px 10px;
            font-size: 0.78rem;
            cursor: pointer;
            color: var(--text-heading);
            font-weight: 600;
        }
        .datatable-pagination button.active {
            background: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
        }
        .datatable-pagination button:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .smv-input {
            width: 78px;
            height: 30px;
            padding: 0 8px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-family: inherit;
            font-size: 0.82rem;
            text-align: right;
            outline: none;
        }
        .smv-input:focus {
            border-color: var(--primary);
        }
        .smv-process-list {
            display: flex;
            flex-direction: column;
            gap: 4px;
            align-items: flex-end;
        }
        .smv-process-row {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .smv-process-label {
            font-size: 0.72rem;
            color: var(--text-muted);
            font-weight: 600;
        }
        .smv-total-badge {
            font-size: 0.75rem;
            color: var(--primary);
            font-weight: 700;
            padding-top: 2px;
        }
        .smv-status {
            font-size: 0.75rem;
            font-weight: 700;
        }
        .smv-status.good { color: var(--success); }
        .smv-status.warn { color: var(--warning); }

        .save-row {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            padding: 12px 16px;
            border-top: 1px solid var(--border-color);
            background: #f8fafc;
        }
        .helper {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .analytics-setting-meta {
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid var(--border-color);
        }
        .analytics-setting-meta label {
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--text-heading);
        }
        .analytics-setting-meta input, .analytics-setting-meta select {
            height: 32px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            padding: 0 10px;
            font-family: inherit;
            font-size: 0.82rem;
        }
        .analytics-settings-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            padding: 16px;
        }
        .analytics-card {
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            padding: 12px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: #ffffff;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .analytics-card.analytics-card-active {
            border-color: var(--primary);
            background: #f8fafc;
            border-left: 3px solid var(--primary);
        }

        /* Workday Calendar Modal */
        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(3px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1050;
            padding: 16px;
        }
        .modal-backdrop.open {
            display: flex;
        }
        .detail-modal {
            background: #ffffff;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-modal);
            width: 100%;
            max-width: 640px;
            overflow: hidden;
            animation: modalFadeIn 0.15s ease;
        }
        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .modal-head {
            padding: 14px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border-color);
            background: #f8fafc;
        }
        .modal-head h3 {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text-heading);
        }
        .modal-close {
            background: transparent;
            border: 0;
            font-size: 1.3rem;
            color: var(--text-muted);
            cursor: pointer;
            padding: 4px;
            line-height: 1;
        }
        .modal-close:hover {
            color: var(--danger);
        }
        .modal-body {
            padding: 16px 18px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .calendar-tools {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .calendar-tools button {
            border: 1px solid var(--border-color);
            background: #ffffff;
            border-radius: var(--radius-sm);
            padding: 5px 12px;
            font-family: inherit;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-heading);
            cursor: pointer;
        }
        .calendar-tools button:hover {
            background: var(--body-bg);
            border-color: var(--primary);
            color: var(--primary);
        }
        .calendar-month-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text-heading);
        }
        .calendar-summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
        }
        .calendar-summary-item {
            padding: 8px;
            background: #f8fafc;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            text-align: center;
            display: flex;
            flex-direction: column;
        }
        .calendar-summary-item span {
            font-size: 0.7rem;
            color: var(--text-muted);
            font-weight: 700;
            text-transform: uppercase;
        }
        .calendar-summary-item b {
            font-size: 1.15rem;
            color: var(--text-heading);
        }
        .calendar-summary-item small {
            font-size: 0.65rem;
            color: var(--text-muted);
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 4px;
        }
        .calendar-head {
            text-align: center;
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--text-heading);
            padding: 4px 0;
            text-transform: uppercase;
        }
        .calendar-day {
            min-height: 48px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            padding: 4px;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: #ffffff;
            transition: all 0.1s ease;
        }
        .calendar-day:hover {
            border-color: var(--primary);
            box-shadow: 0 0 0 1px var(--primary);
        }
        .calendar-day.out {
            opacity: 0.25;
            cursor: default;
            background: var(--body-bg);
        }
        /* SOLID Calendar Days (No soft colors) */
        .calendar-day.sunday {
            background: #fee2e2;
            border-color: #ef4444;
        }
        .calendar-day.work {
            background: var(--success);
            border-color: var(--success);
            color: #ffffff;
        }
        .calendar-day.work .calendar-day-num {
            color: #ffffff;
        }
        .calendar-day.half {
            background: var(--warning);
            border-color: var(--warning);
            color: #ffffff;
        }
        .calendar-day.half .calendar-day-num {
            color: #ffffff;
        }
        .calendar-day.quarter {
            background: #b45309;
            border-color: #b45309;
            color: #ffffff;
        }
        .calendar-day.quarter .calendar-day-num {
            color: #ffffff;
        }
        .calendar-day.holiday {
            background: var(--danger);
            border-color: var(--danger);
            color: #ffffff;
        }
        .calendar-day.holiday .calendar-day-num {
            color: #ffffff;
        }
        .calendar-day-num {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--text-heading);
        }
        .calendar-day-badge {
            font-size: 0.62rem;
            font-weight: 700;
            border-radius: 2px;
            padding: 1px 3px;
            text-align: center;
            text-transform: uppercase;
        }
        .calendar-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            font-size: 0.72rem;
            color: var(--text-heading);
            font-weight: 600;
            align-items: center;
        }
        .calendar-legend span {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .calendar-legend i {
            width: 10px;
            height: 10px;
            border-radius: 2px;
            display: inline-block;
        }
        .legend-sunday { background: var(--danger); }
        .legend-work { background: var(--success); }
        .legend-half { background: var(--warning); }
        .legend-quarter { background: #b45309; }
        .legend-off { background: var(--danger); }
        .calendar-help {
            font-size: 0.72rem;
            color: var(--text-muted);
        }

        /* Global Loading Overlay */
        .global-loading-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(2px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
        }
        .global-loading-overlay.active {
            display: flex;
        }
        .global-loading-card {
            background: #ffffff;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-color);
            padding: 24px 32px;
            box-shadow: var(--shadow-modal);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }
        .global-loading-card span {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text-heading);
        }
        .global-loading-card small {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        /* Responsive Breakpoints */
        @media (max-width: 1080px) {
            .overview-hero-row, .overview-split-row, .summary-detail-grid {
                grid-template-columns: 1fr;
            }
            .overview-metrics-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 860px) {
            .shell {
                grid-template-columns: 1fr;
            }
            .sidebar {
                position: relative;
                height: auto;
            }
            .overview-metrics-grid {
                grid-template-columns: 1fr;
            }
            .welcome-card {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }
            .welcome-content {
                max-width: 100%;
            }
            .welcome-kpis {
                grid-template-columns: 1fr;
            }
            .topbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            .topbar-actions {
                width: 100%;
                justify-content: space-between;
            }
        }
    </style>
</head>
<body>
<?php
$user = isset($user) && is_array($user) ? $user : array();
$user_name = !empty($user['full_name']) ? $user['full_name'] : (!empty($user['username']) ? $user['username'] : 'Administrator');
$user_role = !empty($user['role']) ? $user['role'] : 'admin';
$dashboard_data = isset($initial_dashboard_payload['dashboard_data']) && is_array($initial_dashboard_payload['dashboard_data'])
    ? $initial_dashboard_payload['dashboard_data']
    : array();
$holiday_settings = isset($dashboard_data['holiday_settings']) && is_array($dashboard_data['holiday_settings'])
    ? $dashboard_data['holiday_settings']
    : array('holidays' => array(), 'half_days' => array(), 'quarter_days' => array(), 'work_days' => array());
$raw_style_catalog = isset($style_smv_catalog['styles']) && is_array($style_smv_catalog['styles']) ? $style_smv_catalog['styles'] : array();
$style_catalog = array();
$running_styles_only = array();
foreach ($raw_style_catalog as $s_item) {
    $s_name = isset($s_item['style']) ? (string)$s_item['style'] : '';
    if ($s_name !== '' && stripos($s_name, 'OFC') === FALSE) {
        $style_catalog[] = $s_item;
        if (!empty($s_item['is_running'])) {
            $running_styles_only[] = $s_item;
        }
    }
}
$style_smv_catalog = isset($style_smv_catalog) && is_array($style_smv_catalog) ? $style_smv_catalog : array('styles' => array());
$selected_date = isset($selected_date) && !empty($selected_date) ? $selected_date : date('Y-m-d');
if (is_numeric($selected_date)) {
    $selected_date = date('Y-m-d');
}

$kpis = isset($dashboard_data['kpis']) && is_array($dashboard_data['kpis']) ? $dashboard_data['kpis'] : array();
$initial_total_output = isset($kpis['total_output']) ? (float) $kpis['total_output'] : 0;
$initial_balance_qty = isset($kpis['balance_qty']) ? (float) $kpis['balance_qty'] : 0;
$initial_balance_qty_ofc = isset($kpis['balance_qty_with_ofc']) ? (float) $kpis['balance_qty_with_ofc'] : $initial_balance_qty;
$initial_remaining_days = isset($kpis['prod_days_left']) ? (float) $kpis['prod_days_left'] : (isset($kpis['remaining_days']) ? (float) $kpis['remaining_days'] : 0);

$initial_active_smv = array();
$initial_total_processes = 0;
$initial_total_smv = 0;
$running_target_list = !empty($style_smv_catalog['running_styles']) && is_array($style_smv_catalog['running_styles'])
    ? $style_smv_catalog['running_styles']
    : $running_styles_only;

foreach ($running_target_list as $s) {
    $s_name = isset($s['style']) ? (string) $s['style'] : '';
    if (stripos($s_name, 'OFC') !== FALSE) {
        continue;
    }
    if (isset($s['smv']) && is_numeric($s['smv']) && (float) $s['smv'] > 0) {
        $initial_active_smv[] = $s;
        $p_smvs = isset($s['process_smvs']) && is_array($s['process_smvs']) ? $s['process_smvs'] : array();
        $p_cnt = isset($s['process_count']) && (int) $s['process_count'] > 0 ? (int) $s['process_count'] : 1;
        $initial_total_processes += (!empty($p_smvs) ? count($p_smvs) : $p_cnt);
        $initial_total_smv += (float) $s['smv'];
    }
}

$initial_total_aps_styles = count($running_target_list);
$initial_smv_nums = array();
foreach ($initial_active_smv as $s) {
    $initial_smv_nums[] = (float) $s['smv'];
}
$initial_is_complete = ($initial_total_aps_styles > 0 && count($initial_active_smv) === $initial_total_aps_styles);
$initial_avg_smv = $initial_is_complete ? (array_sum($initial_smv_nums) / count($initial_smv_nums)) : null;
$initial_avg_process = $initial_is_complete ? ($initial_total_processes / count($initial_active_smv)) : null;
$initial_direct_actual = isset($analytics_settings['direct_actual']) && $analytics_settings['direct_actual'] !== NULL && $analytics_settings['direct_actual'] !== '' ? (float) $analytics_settings['direct_actual'] : null;

$initial_balance_breakdown = !empty($dashboard_data['balance_breakdown']) && is_array($dashboard_data['balance_breakdown'])
    ? $dashboard_data['balance_breakdown']
    : (!empty($dashboard_data['qty_pdk_vs_output']) && is_array($dashboard_data['qty_pdk_vs_output']) ? $dashboard_data['qty_pdk_vs_output'] : array());

$source_label = !empty($dashboard_data['source']) ? $dashboard_data['source'] : 'RPA Engage';
$source_updated_at = !empty($dashboard_data['source_updated_at']) ? $dashboard_data['source_updated_at'] : '';
?>
<div class="global-loading-overlay" id="globalLoadingOverlay" aria-live="polite" aria-busy="true">
    <div class="global-loading-card">
        <svg class="scope-spinner" style="display:inline-block;width:32px;height:32px;border-width:3px;" viewBox="0 0 24 24"></svg>
        <span id="globalLoadingText">Memuat Data Periode...</span>
        <small>Menyesuaikan data produksi &amp; SMV</small>
    </div>
</div>

<div class="shell">
    <!-- Sidebar: Solid Navy & Slate -->
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-logo-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                </svg>
            </div>
            <div class="brand-text">
                <h1>GM Admin</h1>
                <span>Heat Transfer Dashboard</span>
            </div>
        </div>

        <div class="menu-header">Dashboard</div>
        <nav class="nav-card" aria-label="Navigasi admin">
            <button type="button" class="active" data-section="overviewSection">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/>
                </svg>
                <span>Overview</span>
            </button>
            <button type="button" data-section="summarySection">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" x2="18" y1="20" y2="10"/><line x1="12" x2="12" y1="20" y2="4"/><line x1="6" x2="6" y1="20" y2="14"/>
                </svg>
                <span>Summary</span>
            </button>
            <button type="button" data-section="style-smv">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="4" x2="20" y1="21" y2="21"/><line x1="4" x2="20" y1="14" y2="14"/><line x1="4" x2="20" y1="7" y2="7"/><circle cx="14" cy="7" r="2"/><circle cx="8" cy="14" r="2"/><circle cx="16" cy="21" r="2"/>
                </svg>
                <span>SMV &amp; Direct</span>
            </button>
            <button type="button" data-section="workdays">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/>
                </svg>
                <span>Kalender</span>
            </button>
        </nav>

        <div class="menu-header">Modul &amp; Data</div>
        <nav class="nav-card">
            <button type="button" data-section="analytics">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10z"/>
                </svg>
                <span>Analytics</span>
            </button>
            <button type="button" data-section="data">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5V19A9 3 0 0 0 21 19V5"/><path d="M3 12A9 3 0 0 0 21 12"/>
                </svg>
                <span>Data Prioritas</span>
            </button>
            <button type="button" data-section="notes">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/>
                </svg>
                <span>Catatan</span>
            </button>
        </nav>

        <div class="menu-header">Pengaturan</div>
        <nav class="nav-card">
            <button type="button" data-section="usersSection">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                <span>Kelola Akun</span>
            </button>
        </nav>

        <div class="sidebar-footer">
            <div class="user-mini-card">
                <div class="user-avatar">
                    <?= html_escape(strtoupper(substr($user_name, 0, 1))) ?>
                </div>
                <div class="user-info">
                    <strong><?= html_escape($user_name) ?></strong>
                    <small><?= html_escape($user_role) ?></small>
                </div>
            </div>
            <a class="btn-sidebar-primary" href="<?= html_escape($dashboard_url ?? '#') ?>" target="_blank">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6M15 3h6v6M10 14L21 3"/>
                </svg>
                <span>Dashboard Publik</span>
            </a>
            <button type="button" class="btn-sidebar-secondary" id="logoutButton">
                Logout
            </button>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="content">
        <!-- Floating Topbar: Solid Navy & Gray -->
        <header class="topbar">
            <div class="topbar-scope" title="Pilih rentang tanggal periode">
                <span class="scope-spinner" aria-hidden="true"></span>
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--secondary);">
                    <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/>
                </svg>
                <span>Periode:</span>
                <input type="date" id="adminDateFrom" value="<?= html_escape($selected_date_from ?? date('Y-m-01')) ?>">
                <span style="color:var(--text-muted);">&ndash;</span>
                <input type="date" id="adminDateTo" value="<?= html_escape($selected_date_to ?? date('Y-m-15')) ?>">
                <span class="period-pill" id="adminPeriodPill">MID Sept</span>
            </div>

            <div class="topbar-actions">
                <button type="button" class="btn btn-secondary" id="refreshButton" title="Perbarui Data">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
                    </svg>
                    <span>Refresh</span>
                </button>
                <a class="btn btn-primary" href="<?= html_escape($dashboard_url ?? '#') ?>" target="_blank">
                    Buka Publik &rarr;
                </a>
            </div>
        </header>

        <!-- SECTION 1: OVERVIEW -->
        <section class="section active" id="overviewSection">
            <!-- Row 1: Welcome Banner + 2 Mini KPI Cards -->
            <div class="overview-hero-row">
                <!-- Welcome Card -->
                <div class="welcome-card">
                    <div class="welcome-content">
                        <h3>Halo, <?= html_escape($user_name) ?>! 👋</h3>
                        <p>Performa dan ringkasan operasional produksi heat transfer periode aktif.</p>
                        <button type="button" class="btn btn-outline-primary" data-jump="summarySection">
                            Lihat Detail Summary
                        </button>
                    </div>
                    <div class="welcome-illustration">
                        <svg width="130" height="95" viewBox="0 0 160 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect x="25" y="30" width="110" height="70" rx="4" fill="#1e3a8a"/>
                            <rect x="35" y="42" width="50" height="6" rx="2" fill="#ffffff"/>
                            <rect x="35" y="54" width="70" height="4" rx="2" fill="#93c5fd"/>
                            <rect x="35" y="64" width="40" height="4" rx="2" fill="#93c5fd"/>
                            <circle cx="112" cy="65" r="16" fill="#3b82f6"/>
                            <path d="M106 65L110 69L118 61" stroke="#ffffff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="45" cy="18" r="9" fill="#16a34a"/>
                            <path d="M42 18L44 20L48 16" stroke="#ffffff" stroke-width="2" stroke-linecap="round"/>
                            <circle cx="135" cy="24" r="6" fill="#d97706"/>
                        </svg>
                    </div>
                </div>

                <!-- 2 Top KPI Cards with Solid Icon Avatars -->
                <div class="welcome-kpis">
                    <article class="sneat-kpi-card">
                        <div class="sneat-kpi-top">
                            <div class="kpi-icon-avatar success">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" x2="12" y1="22.08" y2="12"/>
                                </svg>
                            </div>
                            <span class="badge-pill good">Selesai</span>
                        </div>
                        <div class="kpi-label">Total Output</div>
                        <div class="kpi-val">
                            <strong class="kpi-total-output"><?= number_format($initial_total_output, 0, ',', '.') ?></strong>
                            <small>Pcs</small>
                        </div>
                        <div class="kpi-sub">Akumulasi output selesai</div>
                    </article>

                    <article class="sneat-kpi-card">
                        <div class="sneat-kpi-top">
                            <div class="kpi-icon-avatar warning">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                    <line x1="12" x2="12" y1="9" y2="13"/><line x1="12" x2="12.01" y1="17" y2="17"/>
                                </svg>
                            </div>
                            <span class="badge-pill warn">Non OFC</span>
                        </div>
                        <div class="kpi-label">Balance Qty</div>
                        <div class="kpi-val">
                            <strong class="kpi-balance-qty"><?= number_format($initial_balance_qty, 0, ',', '.') ?></strong>
                            <small>Pcs</small>
                        </div>
                        <div class="kpi-sub">Sisa order produksi</div>
                    </article>
                </div>
            </div>

            <!-- Row 2: 4 Solid Metric Cards -->
            <div class="overview-metrics-grid">
                <article class="sneat-kpi-card">
                    <div class="sneat-kpi-top">
                        <div class="kpi-icon-avatar danger">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
                            </svg>
                        </div>
                        <span class="badge-pill warn">OFC Mix</span>
                    </div>
                    <div class="kpi-label">Balance Inc. OFC</div>
                    <div class="kpi-val">
                        <strong class="kpi-balance-qty-ofc"><?= number_format($initial_balance_qty_ofc, 0, ',', '.') ?></strong>
                        <small>Pcs</small>
                    </div>
                    <div class="kpi-sub">Termasuk pesanan OFC</div>
                </article>

                <article class="sneat-kpi-card">
                    <div class="sneat-kpi-top">
                        <div class="kpi-icon-avatar info">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                            </svg>
                        </div>
                        <span class="badge-pill info">Produksi</span>
                    </div>
                    <div class="kpi-label">Sisa Hari Kerja</div>
                    <div class="kpi-val">
                        <strong class="kpi-remaining-days"><?= number_format($initial_remaining_days, 1, ',', '.') ?></strong>
                        <small>Hari</small>
                    </div>
                    <div class="kpi-sub">Sisa hari efektif</div>
                </article>

                <article class="sneat-kpi-card">
                    <div class="sneat-kpi-top">
                        <div class="kpi-icon-avatar primary">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                            </svg>
                        </div>
                        <span class="badge-pill primary">Manpower</span>
                    </div>
                    <div class="kpi-label">Direct Plan</div>
                    <div class="kpi-val">
                        <strong class="kpi-direct-plan">-</strong>
                        <small>Orang</small>
                    </div>
                    <div class="kpi-sub kpi-target-per-person">Target/orang: -</div>
                </article>

                <article class="sneat-kpi-card">
                    <div class="sneat-kpi-top">
                        <div class="kpi-icon-avatar success">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                            </svg>
                        </div>
                        <span class="badge-pill good">Aktual</span>
                    </div>
                    <div class="kpi-label">Direct Actual</div>
                    <div class="kpi-val">
                        <strong class="kpi-direct-actual"><?= $initial_direct_actual !== null ? html_escape(rtrim(rtrim(number_format($initial_direct_actual, 2, '.', ''), '0'), '.')) : '-' ?></strong>
                        <small>Orang</small>
                    </div>
                    <div class="kpi-sub">Manpower terdaftar</div>
                </article>
            </div>

            <!-- Row 3: Capacity Analysis & Action Shortcuts -->
            <div class="overview-split-row">
                <!-- Left: Capacity & Demand Analysis -->
                <div class="card">
                    <div class="card-header-clean">
                        <h4>Analisis Kapasitas &amp; Demand</h4>
                        <span class="badge-pill good" id="kpiCapacityBadge">Output</span>
                    </div>
                    <div class="capacity-stats-row">
                        <div class="cap-stat-item">
                            <span>Demand Harian</span>
                            <div>
                                <strong class="kpi-daily-demand">-</strong>
                                <small>Pcs/hari</small>
                            </div>
                        </div>
                        <div class="cap-stat-item">
                            <span>Estimasi Kapasitas</span>
                            <div>
                                <strong class="kpi-estimated-capacity">-</strong>
                                <small>Pcs/hari</small>
                            </div>
                        </div>
                        <div class="cap-stat-item">
                            <span>Rata-rata SMV</span>
                            <div>
                                <strong class="kpi-avg-smv"><?= $initial_avg_smv !== null ? number_format($initial_avg_smv, 2, '.', '') : '-' ?></strong>
                                <small class="kpi-active-styles-count"><?php if ($initial_is_complete): ?><?= $initial_total_aps_styles ?> style berjalan (Lengkap)<?php elseif ($initial_total_aps_styles > 0): ?><?= count($initial_active_smv) ?> / <?= $initial_total_aps_styles ?> style terisi (Belum lengkap)<?php else: ?>0 style aktif<?php endif; ?></small>
                            </div>
                        </div>
                        <div class="cap-stat-item">
                            <span>Rata-rata Proses</span>
                            <div>
                                <strong class="kpi-avg-process"><?= $initial_avg_process !== null ? number_format($initial_avg_process, 1, '.', '') : '-' ?></strong>
                                <small class="kpi-avg-process-sub"><?php if ($initial_is_complete): ?><?= (int) $initial_total_processes ?> proses / <?= $initial_total_aps_styles ?> style<?php elseif ($initial_total_aps_styles > 0): ?><?= count($initial_active_smv) ?> / <?= $initial_total_aps_styles ?> style terisi (Belum lengkap)<?php else: ?>0 style aktif<?php endif; ?></small>
                            </div>
                        </div>
                        <div class="cap-stat-item">
                            <span>Kebutuhan Mesin</span>
                            <div>
                                <strong class="kpi-machine-req">-</strong>
                                <small class="kpi-machine-req-sub">-</small>
                            </div>
                        </div>
                    </div>
                    <div class="kpi-capacity-gap" style="font-weight:600;margin-top:12px;padding:8px 12px;background:#f8fafc;border:1px solid var(--border-light);border-radius:var(--radius-sm);font-size:0.8rem;">
                        <?php if (!$initial_is_complete && $initial_total_aps_styles > 0): ?>
                            <span style="color:var(--risk);">SMV belum lengkap (<?= count($initial_active_smv) ?>/<?= $initial_total_aps_styles ?> style terisi)</span>
                        <?php else: ?>
                            Estimasi vs Demand
                        <?php endif; ?>
                    </div>

                    <!-- 3 Pilar Kapasitas: Mesin, Manpower, Pcs -->
                    <div style="margin-top:14px;padding-top:12px;border-top:1px solid var(--border-light);">
                        <div style="font-size:0.8rem;font-weight:800;color:var(--text-heading);text-transform:uppercase;margin-bottom:8px;display:flex;align-items:center;justify-content:space-between;">
                            <span>Analisis 3 Kapasitas</span>
                            <span class="badge-pill info" style="font-size:0.68rem;padding:2px 8px;">Mesin &bull; Manpower &bull; Pcs</span>
                        </div>
                        <div style="display:grid;grid-template-columns:repeat(3, 1fr);gap:10px;">
                            <!-- Pilar 1: Kapasitas Mesin -->
                            <div style="background:#f8fafc;border:1px solid var(--border-light);border-radius:var(--radius-sm);padding:10px;">
                                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                                    <span style="font-size:0.72rem;font-weight:800;color:var(--brand);text-transform:uppercase;">1. Kapasitas Mesin</span>
                                    <span class="badge-pill" style="font-size:0.65rem;padding:1px 5px;">Unit Fisik</span>
                                </div>
                                <div style="font-size:1.15rem;font-weight:800;color:var(--text-heading);line-height:1.2;">
                                    <span id="tierMachineVal">-</span> <small style="font-size:0.72rem;font-weight:600;color:var(--text-muted);">Mesin</small>
                                </div>
                                <div style="font-size:0.75rem;color:var(--text-muted);margin-top:3px;" id="tierMachineSub">-</div>
                                <div style="font-size:0.7rem;color:var(--text-muted);margin-top:5px;border-top:1px dashed var(--border-light);padding-top:3px;" id="tierMachineRate">
                                    Rate: M1 = - pcs | M2 = - pcs
                                </div>
                            </div>

                            <!-- Pilar 2: Kapasitas Manpower -->
                            <div style="background:#f8fafc;border:1px solid var(--border-light);border-radius:var(--radius-sm);padding:10px;">
                                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                                    <span style="font-size:0.72rem;font-weight:800;color:#0284c7;text-transform:uppercase;">2. Kapasitas Manpower</span>
                                    <span class="badge-pill" style="font-size:0.65rem;padding:1px 5px;">Operator</span>
                                </div>
                                <div style="font-size:1.15rem;font-weight:800;color:var(--text-heading);line-height:1.2;">
                                    <span id="tierManpowerActualVal">-</span> <small style="font-size:0.72rem;font-weight:600;color:var(--text-muted);">Aktual</small>
                                    <span style="font-size:0.8rem;font-weight:600;color:var(--text-muted);">/</span>
                                    <span id="tierManpowerPlanVal">-</span> <small style="font-size:0.72rem;font-weight:600;color:var(--text-muted);">Plan</small>
                                </div>
                                <div style="font-size:0.75rem;margin-top:3px;" id="tierManpowerGapSub">-</div>
                                <div style="font-size:0.7rem;color:var(--text-muted);margin-top:5px;border-top:1px dashed var(--border-light);padding-top:3px;" id="tierManpowerTargetSub">
                                    Target: - pcs/orang/hari
                                </div>
                            </div>

                            <!-- Pilar 3: Kapasitas Pcs (Output) -->
                            <div style="background:#f8fafc;border:1px solid var(--border-light);border-radius:var(--radius-sm);padding:10px;">
                                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                                    <span style="font-size:0.72rem;font-weight:800;color:#16a34a;text-transform:uppercase;">3. Kapasitas Pcs (Output)</span>
                                    <span class="badge-pill good" style="font-size:0.65rem;padding:1px 5px;" id="tierPcsBadge">Produksi</span>
                                </div>
                                <div style="font-size:1.15rem;font-weight:800;color:var(--text-heading);line-height:1.2;">
                                    <span id="tierPcsVal">-</span> <small style="font-size:0.72rem;font-weight:600;color:var(--text-muted);">Pcs/hari</small>
                                </div>
                                <div style="font-size:0.75rem;margin-top:3px;" id="tierPcsGapSub">-</div>
                                <div style="font-size:0.7rem;color:var(--text-muted);margin-top:5px;border-top:1px dashed var(--border-light);padding-top:3px;" id="tierPcsDemandSub">
                                    Demand: - pcs/hari
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Module Shortcuts -->
                <div class="card">
                    <div class="card-header-clean">
                        <h4>Pintasan Cepat</h4>
                    </div>
                    <div class="action-list">
                        <div class="action-item">
                            <div class="action-item-left">
                                <div class="action-icon">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="4" x2="20" y1="21" y2="21"/><line x1="4" x2="20" y1="14" y2="14"/><line x1="4" x2="20" y1="7" y2="7"/><circle cx="14" cy="7" r="2"/><circle cx="8" cy="14" r="2"/><circle cx="16" cy="21" r="2"/>
                                    </svg>
                                </div>
                                <div class="action-info">
                                    <strong>SMV per Style</strong>
                                    <small>Input &amp; simpan SMV</small>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-primary" data-jump="style-smv">Buka</button>
                        </div>

                        <div class="action-item">
                            <div class="action-item-left">
                                <div class="action-icon">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/>
                                    </svg>
                                </div>
                                <div class="action-info">
                                    <strong>Kalender Kerja</strong>
                                    <small>Hari kerja &amp; libur</small>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-primary" id="overviewOpenCalendar">Buka</button>
                        </div>

                        <div class="action-item">
                            <div class="action-item-left">
                                <div class="action-icon">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5V19A9 3 0 0 0 21 19V5"/><path d="M3 12A9 3 0 0 0 21 12"/>
                                    </svg>
                                </div>
                                <div class="action-info">
                                    <strong>Data Prioritas</strong>
                                    <small>Order ready to load</small>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-primary" data-jump="data">Buka</button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- SECTION 2: SUMMARY -->
        <section class="section" id="summarySection">
            <div class="section-header">
                <h2>Summary Operasional</h2>
                <div style="display:flex;gap:8px;align-items:center;">
                    <div class="topbar-scope" style="padding:3px 10px;">
                        <label for="summaryDateFrom">From:</label>
                        <input type="date" id="summaryDateFrom" value="<?= html_escape($selected_date_from ?? date('Y-m-01')) ?>">
                        <label for="summaryDateTo">To:</label>
                        <input type="date" id="summaryDateTo" value="<?= html_escape($selected_date_to ?? date('Y-m-15')) ?>">
                        <span class="period-pill" id="summaryPeriodPill">MID Sept</span>
                    </div>
                    <span class="badge-pill good" id="connectionState">Online</span>
                </div>
            </div>

            <!-- Summary KPI Grid -->
            <div class="overview-metrics-grid" id="statsGrid">
                <article class="sneat-kpi-card">
                    <div class="sneat-kpi-top">
                        <div class="kpi-icon-avatar success"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg></div>
                        <span class="badge-pill good">Selesai</span>
                    </div>
                    <div class="kpi-label">Total Output</div>
                    <div class="kpi-val">
                        <strong class="kpi-total-output" id="totalOutput"><?= number_format($initial_total_output, 0, ',', '.') ?></strong>
                        <small>Pcs</small>
                    </div>
                </article>

                <article class="sneat-kpi-card">
                    <div class="sneat-kpi-top">
                        <div class="kpi-icon-avatar warning"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg></div>
                        <span class="badge-pill warn">Non OFC</span>
                    </div>
                    <div class="kpi-label">Balance Qty</div>
                    <div class="kpi-val">
                        <strong class="kpi-balance-qty" id="balanceQty"><?= number_format($initial_balance_qty, 0, ',', '.') ?></strong>
                        <small>Pcs</small>
                    </div>
                </article>

                <article class="sneat-kpi-card">
                    <div class="sneat-kpi-top">
                        <div class="kpi-icon-avatar danger"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg></div>
                        <span class="badge-pill warn">OFC Mix</span>
                    </div>
                    <div class="kpi-label">Balance Inc. OFC</div>
                    <div class="kpi-val">
                        <strong class="kpi-balance-qty-ofc" id="balanceQtyOfc"><?= number_format($initial_balance_qty_ofc, 0, ',', '.') ?></strong>
                        <small>Pcs</small>
                    </div>
                </article>

                <article class="sneat-kpi-card">
                    <div class="sneat-kpi-top">
                        <div class="kpi-icon-avatar info"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
                        <span class="badge-pill info">Produksi</span>
                    </div>
                    <div class="kpi-label">Sisa Hari Kerja</div>
                    <div class="kpi-val">
                        <strong class="kpi-remaining-days" id="remainingDays"><?= number_format($initial_remaining_days, 1, ',', '.') ?></strong>
                        <small>Hari</small>
                    </div>
                </article>
            </div>

            <!-- Detail Formula & Running Styles -->
            <div class="summary-detail-grid">
                <div class="card">
                    <div class="card-header-clean">
                        <h4>Perhitungan 3 Kapasitas</h4>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-jump="style-smv">Kelola SMV &rarr;</button>
                    </div>
                    <div class="formula-list">
                        <div class="formula-item">
                            <span>Jam Kerja Hari Ini</span>
                            <strong id="summaryWorkingHours">- Jam</strong>
                        </div>
                        <div class="formula-item">
                            <span>Formula Target per Orang</span>
                            <strong style="font-size:0.75rem;color:var(--text-muted);">((1 &times; 60 / SMV) &times; Jam) &times; 70%</strong>
                        </div>

                        <!-- 1. KAPASITAS PCS -->
                        <div class="formula-item" style="border-top:1px solid var(--border-light);padding-top:6px;background:#f0fdf4;">
                            <span style="font-weight:800;color:#16a34a;">[1] Kapasitas Pcs (Output)</span>
                            <strong id="summaryPcsCalculation" style="color:#16a34a;">- Pcs/hari</strong>
                        </div>
                        <div class="formula-item">
                            <span>Target per Orang</span>
                            <strong id="summaryTargetPerPerson">- pcs/hari</strong>
                        </div>
                        <div class="formula-item">
                            <span>Selisih Kapasitas vs Demand</span>
                            <strong id="summaryDirectGap">-</strong>
                        </div>

                        <!-- 2. KAPASITAS MANPOWER -->
                        <div class="formula-item" style="border-top:1px solid var(--border-light);padding-top:6px;background:#f0f9ff;">
                            <span style="font-weight:800;color:#0284c7;">[2] Kapasitas Manpower</span>
                            <strong id="summaryManpowerCalculation" style="color:#0284c7;">- Orang</strong>
                        </div>
                        <div class="formula-item">
                            <span>Kebutuhan Direct Plan</span>
                            <strong id="summaryDirectPlanCalculation">- Orang</strong>
                        </div>
                        <div class="formula-item">
                            <span>Direct Actual Tersedia</span>
                            <strong id="summaryDirectActualCalculation">- Orang</strong>
                        </div>

                        <!-- 3. KAPASITAS MESIN -->
                        <div class="formula-item" style="border-top:1px solid var(--border-light);padding-top:6px;background:#f8fafc;">
                            <span style="font-weight:800;color:var(--brand);">[3] Kapasitas Mesin (Fisik)</span>
                            <strong id="summaryMachineReqCalculation">- Mesin</strong>
                        </div>
                        <div class="formula-item">
                            <span>Rate Output Mesin (Harian)</span>
                            <strong id="summaryMachineRateCalculation" style="font-size:0.78rem;color:var(--text-muted);">1 M1: - pcs | 1 M2: - pcs</strong>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header-clean">
                        <h4>Style &amp; SMV Berjalan (APS)</h4>
                        <span class="badge-pill <?= $initial_is_complete ? 'good' : 'warn' ?>" id="summaryRunningStyleBadge"><?= $initial_is_complete ? count($running_target_list).' Style Aktif (Lengkap)' : count($initial_active_smv).' / '.count($running_target_list).' Style Terisi (Belum Lengkap)' ?></span>
                    </div>
                    <div class="table-scroll" style="max-height:280px;overflow-y:auto;">
                        <table style="width:100%;font-size:0.83rem;">
                            <thead>
                                <tr>
                                    <th style="width:40px;">No.</th>
                                    <th>Style</th>
                                    <th class="num" style="width:100px;">Jml Proses</th>
                                    <th class="num" style="width:110px;">Total SMV</th>
                                </tr>
                            </thead>
                            <tbody id="summaryStyleTableBody">
                                <?php if (!empty($running_target_list)): ?>
                                    <?php $s_idx = 0; ?>
                                    <?php foreach ($running_target_list as $s): ?>
                                        <?php
                                            $s_name = isset($s['style']) ? $s['style'] : '';
                                            if ($s_name === '' || stripos($s_name, 'OFC') !== FALSE) continue;
                                            $s_idx++;
                                            $p_smvs = isset($s['process_smvs']) && is_array($s['process_smvs']) ? $s['process_smvs'] : array();
                                            $p_cnt = isset($s['process_count']) && (int) $s['process_count'] > 0 ? (int) $s['process_count'] : (count($p_smvs) > 0 ? count($p_smvs) : 1);
                                            $s_smv = isset($s['smv']) && is_numeric($s['smv']) && (float)$s['smv'] > 0 ? (float)$s['smv'] : null;
                                        ?>
                                        <tr>
                                            <td class="num" style="color:var(--text-muted);"><?= $s_idx ?></td>
                                            <td style="font-weight:600;"><?= html_escape($s_name) ?></td>
                                            <td class="num"><?= (int)$p_cnt ?></td>
                                            <td class="num" style="<?= $s_smv !== null ? 'font-weight:600;color:var(--primary);' : '' ?>">
                                                <?= $s_smv !== null ? number_format($s_smv, 2, '.', '') : '<span style="color:var(--risk);font-weight:600;font-size:0.78rem;">Belum diisi</span>' ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" style="text-align:center;color:var(--text-muted);padding:14px;">Tidak ada style berjalan di APS periode ini.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot style="font-weight:700;background:var(--bg-light,#f8fafc);border-top:2px solid var(--border-light);">
                                <tr>
                                    <td colspan="2">TOTAL</td>
                                    <td class="num" id="summaryFooterTotalProcesses"><?= (int) $initial_total_processes ?><?= !$initial_is_complete ? ' <small style="color:var(--risk);font-weight:600;">(Belum lengkap)</small>' : '' ?></td>
                                    <td class="num" id="summaryFooterTotalSmv"><?= number_format($initial_total_smv, 2, '.', '') ?><?= !$initial_is_complete ? ' <small style="color:var(--risk);font-weight:600;">(Belum lengkap)</small>' : '' ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div style="margin-top:auto;padding-top:10px;border-top:1px solid var(--border-light);font-size:0.75rem;color:var(--text-muted);display:flex;justify-content:space-between;">
                        <span>Sumber: <b id="summarySourceLabel"><?= html_escape($source_label) ?></b></span>
                        <span>Update: <b id="summarySourceUpdated"><?= html_escape($source_updated_at ? $source_updated_at : '-') ?></b></span>
                    </div>
                </div>
            </div>

            <!-- Breakdown Table -->
            <div class="table-card">
                <div class="card-header-clean" style="padding:14px 18px 8px;">
                    <h4>Rincian Balance per Periode</h4>
                </div>
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:40px;">No.</th>
                                <th>Periode Tanggal</th>
                                <th class="num">Target</th>
                                <th class="num">Aktual</th>
                                <th class="num">Balance</th>
                                <th style="width:200px;">Progress</th>
                            </tr>
                        </thead>
                        <tbody id="summaryBreakdownRows">
                            <?php if (!empty($initial_balance_breakdown)): ?>
                                <?php foreach ($initial_balance_breakdown as $bIdx => $bRow): ?>
                                    <?php
                                        $bPdk = isset($bRow['pdk']) ? (float) $bRow['pdk'] : 0;
                                        $bOut = isset($bRow['output']) ? (float) $bRow['output'] : 0;
                                        $bBal = isset($bRow['balance']) ? (float) $bRow['balance'] : max(0, $bPdk - $bOut);
                                        $bPct = $bPdk > 0 ? min(100, max(0, round(($bOut / $bPdk) * 100, 1))) : 0;
                                    ?>
                                    <tr>
                                        <td><?= (int) ($bIdx + 1) ?></td>
                                        <td><strong><?= html_escape(isset($bRow['label']) ? $bRow['label'] : '-') ?></strong></td>
                                        <td class="num"><?= number_format($bPdk, 0, ',', '.') ?> pcs</td>
                                        <td class="num" style="color:var(--primary);font-weight:700;"><?= number_format($bOut, 0, ',', '.') ?> pcs</td>
                                        <td class="num" style="color:var(--warning);font-weight:700;"><?= number_format($bBal, 0, ',', '.') ?> pcs</td>
                                        <td>
                                            <div style="display:flex;justify-content:space-between;font-size:0.75rem;font-weight:700;">
                                                <span><?= $bPct ?>%</span>
                                                <small style="color:var(--text-muted);"><?= number_format($bOut, 0, ',', '.') ?> / <?= number_format($bPdk, 0, ',', '.') ?></small>
                                            </div>
                                            <div class="progress-bar-wrap">
                                                <div class="progress-bar-fill" style="width:<?= $bPct ?>%;"></div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align:center;color:var(--text-muted);padding:14px;">Data periode belum tersedia.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- SECTION 3: STYLE SMV & DIRECT -->
        <section class="section" id="style-smv">
            <div class="section-header">
                <h2>SMV per Style</h2>
                <span class="badge-pill primary" id="smvCountPill"><?= (int) count($running_target_list) ?> Style Aktif (<?= (int) $initial_total_processes ?> Proses Terisi)</span>
            </div>

            <div class="table-card">
                <div class="table-toolbar">
                    <div class="datatable-meta">
                        <input type="search" id="smvSearch" placeholder="Cari style...">
                        <select id="smvStatusFilter" title="Filter Status Style">
                            <option value="running" selected>Hanya Aktif Bulan Ini (<?= count($running_target_list) ?>)</option>
                            <option value="all">Semua Style (<?= count($style_catalog) ?>)</option>
                        </select>
                        <select id="smvPageSize">
                            <option value="10">10 baris</option>
                            <option value="15">15 baris</option>
                            <option value="25">25 baris</option>
                            <option value="50">50 baris</option>
                        </select>
                    </div>
                    <div class="helper" id="smvTableInfo">-</div>
                </div>
                <div class="table-scroll">
                    <table id="smvDataTable">
                        <thead>
                            <tr>
                                <th style="width:50px;">No.</th>
                                <th>Style</th>
                                <th style="width:130px;" class="num">Jml Proses</th>
                                <th style="width:200px;" class="num">SMV Proses</th>
                            </tr>
                        </thead>
                        <tbody id="smvRows">
                            <?php if ($style_catalog): ?>
                                <?php $valid_index = 0; ?>
                                <?php foreach ($style_catalog as $row): ?>
                                    <?php
                                        $row_style = isset($row['style']) ? $row['style'] : '';
                                        if ($row_style === '' || stripos($row_style, 'OFC') !== FALSE) {
                                            continue;
                                        }
                                        $valid_index++;
                                        $is_row_running = !empty($row['is_running']);
                                        $p_smvs = isset($row['process_smvs']) && is_array($row['process_smvs']) ? $row['process_smvs'] : array();
                                        if (empty($p_smvs) && isset($row['smv']) && $row['smv'] !== NULL && (float) $row['smv'] > 0) {
                                            $p_smvs = array((float) $row['smv']);
                                        }
                                        $p_count = isset($row['process_count']) && $row['process_count'] !== NULL && (int) $row['process_count'] > 0
                                            ? (int) $row['process_count']
                                            : (count($p_smvs) > 0 ? count($p_smvs) : 1);
                                        $row_total_smv = !empty($p_smvs) ? array_sum($p_smvs) : (isset($row['smv']) && $row['smv'] !== NULL ? (float) $row['smv'] : NULL);
                                    ?>
                                    <tr data-style-row="<?= html_escape($row_style) ?>" data-is-running="<?= $is_row_running ? '1' : '0' ?>">
                                        <td class="num smv-row-number"><?= (int) $valid_index ?></td>
                                        <td>
                                            <div style="display:flex;align-items:center;gap:6px;font-weight:600;flex-wrap:wrap;">
                                                <span><?= html_escape($row_style !== '' ? $row_style : '-') ?></span>
                                                <?php if ($is_row_running): ?>
                                                    <span class="badge-pill good" style="font-size:0.68rem;padding:2px 6px;font-weight:700;">Aktif Bulan Ini</span>
                                                <?php else: ?>
                                                    <span class="badge-pill" style="font-size:0.68rem;padding:2px 6px;background:var(--bg-light,#f1f5f9);color:var(--text-muted);border:1px solid var(--border-light,#e2e8f0);">Tidak Aktif Bulan Ini</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="num">
                                            <input
                                                type="number"
                                                step="1"
                                                min="1"
                                                max="10"
                                                class="smv-input smv-process-input"
                                                data-process-style="<?= html_escape($row_style) ?>"
                                                value="<?= (int) $p_count ?>"
                                                placeholder="1"
                                            >
                                        </td>
                                        <td class="num">
                                            <div class="smv-process-list" data-smv-container="<?= html_escape($row_style) ?>">
                                                <?php for ($p = 0; $p < max(1, $p_count); $p++): ?>
                                                    <div class="smv-process-row">
                                                        <span class="smv-process-label"><?= $p_count > 1 ? 'P' . ($p + 1) : 'SMV' ?></span>
                                                        <input
                                                            type="number"
                                                            step="0.01"
                                                            min="0.001"
                                                            class="smv-input smv-sub-input"
                                                            data-style="<?= html_escape($row_style) ?>"
                                                            data-process-idx="<?= $p ?>"
                                                            value="<?= isset($p_smvs[$p]) ? html_escape(rtrim(rtrim(number_format((float) $p_smvs[$p], 3, '.', ''), '0'), '.')) : '' ?>"
                                                            placeholder="0.00"
                                                        >
                                                    </div>
                                                <?php endfor; ?>
                                                <?php if ($p_count > 1): ?>
                                                    <div class="smv-total-badge" data-total-for="<?= html_escape($row_style) ?>">Total: <b><?= $row_total_smv !== NULL ? rtrim(rtrim(number_format((float) $row_total_smv, 3, '.', ''), '0'), '.') : '0.00' ?></b></div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <tr class="smv-empty-row" id="smvEmptyRow" style="<?= $style_catalog ? 'display:none;' : '' ?>">
                                <td colspan="4" style="text-align:center;padding:16px;color:var(--text-muted);">Tidak ada style yang ditemukan.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="datatable-footer">
                    <div id="smvPageInfo">-</div>
                    <div class="datatable-pagination" id="smvPagination"></div>
                </div>
                <div class="analytics-setting-meta" style="display:flex;align-items:center;gap:18px;flex-wrap:wrap;">
                    <div>
                        <label for="directActualInput">Jumlah Mesin / Direct Aktif:</label>
                        <input type="number" id="directActualInput" min="0" step="1" placeholder="0" style="width:100px;"
                            value="<?= $initial_direct_actual !== null ? html_escape(rtrim(rtrim(number_format((float)$initial_direct_actual, 2, '.', ''), '0'), '.')) : '' ?>">
                        <span class="helper">Jumlah unit mesin (atau operator) untuk kalkulasi Kapasitas pada grafik.</span>
                    </div>
                    <div>
                        <label for="doubleMachineActiveInput">Kesiapan Mesin Ganda (M2):</label>
                        <select id="doubleMachineActiveInput" style="height:32px;border:1px solid var(--border-color);border-radius:var(--radius-sm);padding:0 8px;font-family:inherit;font-size:0.85rem;">
                            <option value="2" <?= (int) ($analytics_settings['double_machine_active'] ?? 2) === 2 ? 'selected' : '' ?>>2 Unit M2 Siap (Max 4 Orang)</option>
                            <option value="1" <?= (int) ($analytics_settings['double_machine_active'] ?? 2) === 1 ? 'selected' : '' ?>>1 Unit M2 Siap (Max 2 Orang)</option>
                            <option value="0" <?= (int) ($analytics_settings['double_machine_active'] ?? 2) === 0 ? 'selected' : '' ?>>0 Unit M2 (Semua M1 Tunggal)</option>
                        </select>
                        <span class="helper">Kondisi kesiapan M2 (mesin ganda) di lapangan.</span>
                    </div>
                    <div>
                        <label for="defaultCapacityModeInput">Default Kapasitas di TV:</label>
                        <select id="defaultCapacityModeInput" style="height:32px;border:1px solid var(--border-color);border-radius:var(--radius-sm);padding:0 8px;font-family:inherit;font-size:0.85rem;">
                            <option value="mesin" <?= ($analytics_settings['default_capacity_mode'] ?? 'mesin') === 'mesin' ? 'selected' : '' ?>>Mesin (Kebutuhan Mesin)</option>
                            <option value="minutes" <?= ($analytics_settings['default_capacity_mode'] ?? 'mesin') === 'minutes' ? 'selected' : '' ?>>Minutes (Kapasitas Menit)</option>
                        </select>
                        <span class="helper">Pilihan awal kapasitas pada TV dashboard.</span>
                    </div>
                </div>
                <div class="save-row">
                    <span id="smvMessage" class="helper" style="margin-right:auto;"></span>
                    <button type="button" class="btn btn-secondary" id="smvResetButton">Reset</button>
                    <button type="button" class="btn btn-primary" id="smvSaveButton">Simpan SMV</button>
                </div>
            </div>
        </section>

        <!-- SECTION 4: WORKDAYS -->
        <section class="section" id="workdays">
            <div class="section-header">
                <h2>Kalender Kerja</h2>
                <button type="button" class="btn btn-primary" id="workdayOpenButtonInline">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/></svg>
                    <span>Buka Kalender Kerja</span>
                </button>
            </div>
            <div class="card" style="text-align:center;padding:40px 20px;">
                <div class="kpi-icon-avatar primary" style="width:52px;height:52px;margin:0 auto 14px;">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                </div>
                <h3 style="margin:0 0 6px;font-size:1.15rem;color:var(--text-heading);">Atur Hari Kerja &amp; Libur</h3>
                <p style="margin:0 auto 16px;max-width:400px;color:var(--text-muted);font-size:0.85rem;">Kalender interaktif untuk mengatur status hari kerja penuh, 1/2 hari, 1/4 hari, dan libur.</p>
                <div>
                    <button type="button" class="btn btn-primary" onclick="document.getElementById('workdayOpenButtonInline').click()">
                        Buka Modal Kalender &rarr;
                    </button>
                </div>
            </div>
        </section>

        <!-- SECTION 5: ANALYTICS -->
        <section class="section" id="analytics">
            <div class="section-header">
                <h2>Pengaturan Analytics</h2>
                <span class="badge-pill primary" id="analyticsUpdatedPill">Default</span>
            </div>

            <div class="table-card">
                <div class="analytics-setting-meta">
                    <label for="analyticsLanguageSelect">Bahasa Tampilan:</label>
                    <select id="analyticsLanguageSelect">
                        <option value="id">Indonesia</option>
                        <option value="en">English</option>
                    </select>
                </div>
                <div class="analytics-settings-grid" id="analyticsCards"></div>
                <div class="save-row">
                    <span id="analyticsMessage" class="helper" style="margin-right:auto;"></span>
                    <button type="button" class="btn btn-secondary" id="analyticsResetButton">Reset</button>
                    <button type="button" class="btn btn-primary" id="analyticsSaveButton">Simpan Analytics</button>
                </div>
            </div>
        </section>

        <!-- SECTION 6: DATA PRIORITAS -->
        <section class="section" id="data">
            <div class="section-header">
                <h2>Data Prioritas</h2>
                <div class="topbar-actions">
                    <a class="btn btn-secondary" href="<?= html_escape($download_url ?? '#') ?>">Download Excel</a>
                    <a class="btn btn-secondary" href="<?= html_escape($material_to_load_download_url ?? '#') ?>">Material to Load</a>
                </div>
            </div>

            <div class="table-card">
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:40px;">No.</th>
                                <th>Order</th>
                                <th>Style</th>
                                <th>Tanggal</th>
                                <th class="num">Qty Ready</th>
                                <th>Source</th>
                            </tr>
                        </thead>
                        <tbody id="priorityRows">
                            <?php if (!empty($dashboard_data['material_to_load']) && is_array($dashboard_data['material_to_load'])): ?>
                                <?php foreach (array_slice($dashboard_data['material_to_load'], 0, 8) as $index => $row): ?>
                                    <tr>
                                        <td><?= (int) ($index + 1) ?></td>
                                        <td><strong><?= html_escape(isset($row['order']) ? $row['order'] : '-') ?></strong></td>
                                        <td><?= html_escape(isset($row['style']) ? $row['style'] : '-') ?></td>
                                        <td><?= html_escape(isset($row['delivery']) ? $row['delivery'] : '-') ?></td>
                                        <td class="num" style="font-weight:700;color:var(--primary);"><?= number_format((float) (isset($row['qty_ready']) ? $row['qty_ready'] : 0), 0, ',', '.') ?></td>
                                        <td><span class="badge-pill"><?= html_escape(isset($row['source']) ? $row['source'] : '-') ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="padding:16px;color:var(--text-muted);text-align:center;">Tidak ada data prioritas.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- SECTION: USERS CRUD -->
        <section class="section" id="usersSection">
            <div class="section-header">
                <h2>Kelola Akun Pengguna</h2>
                <button type="button" class="btn btn-primary" id="btnAddNewUser">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <span>+ Tambah Akun</span>
                </button>
            </div>

            <div class="table-card">
                <div class="table-toolbar">
                    <div class="datatable-meta">
                        <input type="search" id="userSearch" placeholder="Cari username / nama...">
                    </div>
                    <div class="helper" id="userTableInfo">-</div>
                </div>
                <div class="table-scroll">
                    <table id="usersTable">
                        <thead>
                            <tr>
                                <th style="width:48px;">No.</th>
                                <th>Username</th>
                                <th>Nama Lengkap</th>
                                <th style="width:110px;">Role</th>
                                <th style="width:110px;">Status</th>
                                <th style="width:160px;">Terakhir Login</th>
                                <th style="width:130px;" class="num">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="usersRows">
                            <tr>
                                <td colspan="7" style="text-align:center;padding:16px;color:var(--text-muted);">Memuat data akun...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- SECTION 7: NOTES -->
        <section class="section" id="notes">
            <div class="section-header">
                <h2>Catatan &amp; Info</h2>
            </div>
            <div class="card">
                <h4 style="margin:0 0 6px;color:var(--text-heading);">Informasi Sistem</h4>
                <p style="margin:0;color:var(--text-muted);font-size:0.85rem;">Panel admin ini mengelola data operasional heat transfer, input SMV master data, dan penyesuaian kalender kerja manual.</p>
            </div>
        </section>
    </main>
</div>

<!-- User Add/Edit Modal -->
<div class="modal-backdrop" id="userModal" role="dialog" aria-modal="true" aria-labelledby="userModalTitle">
    <section class="detail-modal" style="max-width:460px;">
        <div class="modal-head">
            <h3 id="userModalTitle">Tambah Akun Baru</h3>
            <button type="button" class="modal-close" id="userModalClose">&times;</button>
        </div>
        <form id="userForm" style="display:flex;flex-direction:column;gap:12px;padding:18px 20px;">
            <input type="hidden" id="userId" value="">
            <div style="display:flex;flex-direction:column;gap:4px;">
                <label for="userUsername" style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;">Username *</label>
                <input type="text" id="userUsername" required style="height:34px;border:1px solid var(--border-color);border-radius:var(--radius-sm);padding:0 10px;font-family:inherit;font-size:0.85rem;" placeholder="Contoh: user123">
            </div>
            <div style="display:flex;flex-direction:column;gap:4px;">
                <label for="userFullName" style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;">Nama Lengkap</label>
                <input type="text" id="userFullName" style="height:34px;border:1px solid var(--border-color);border-radius:var(--radius-sm);padding:0 10px;font-family:inherit;font-size:0.85rem;" placeholder="Contoh: Budi Santoso">
            </div>
            <div style="display:flex;gap:12px;">
                <div style="flex:1;display:flex;flex-direction:column;gap:4px;">
                    <label for="userRole" style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;">Role</label>
                    <select id="userRole" style="height:34px;border:1px solid var(--border-color);border-radius:var(--radius-sm);padding:0 8px;font-family:inherit;font-size:0.85rem;">
                        <option value="admin">Admin</option>
                        <option value="planning">Planning</option>
                        <option value="production">Production</option>
                        <option value="viewer">Viewer</option>
                    </select>
                </div>
                <div style="flex:1;display:flex;flex-direction:column;gap:4px;">
                    <label for="userIsActive" style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;">Status</label>
                    <select id="userIsActive" style="height:34px;border:1px solid var(--border-color);border-radius:var(--radius-sm);padding:0 8px;font-family:inherit;font-size:0.85rem;">
                        <option value="1">Aktif</option>
                        <option value="0">Nonaktif</option>
                    </select>
                </div>
            </div>
            <div style="display:flex;flex-direction:column;gap:4px;">
                <label for="userPassword" id="userPasswordLabel" style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;">Password *</label>
                <input type="password" id="userPassword" style="height:34px;border:1px solid var(--border-color);border-radius:var(--radius-sm);padding:0 10px;font-family:inherit;font-size:0.85rem;" placeholder="Minimal 4 karakter">
                <small id="userPasswordHelp" style="font-size:0.72rem;color:var(--text-muted);display:none;">Kosongkan bila tidak ingin mengubah password.</small>
            </div>
            <div id="userModalMessage" style="font-size:0.8rem;font-weight:600;min-height:16px;"></div>
            <div style="display:flex;justify-content:flex-end;gap:8px;padding-top:8px;border-top:1px solid var(--border-light);">
                <button type="button" class="btn btn-secondary" id="userModalCancel">Batal</button>
                <button type="submit" class="btn btn-primary" id="userModalSave">Simpan Akun</button>
            </div>
        </form>
    </section>
</div>

<!-- User Delete Confirmation Modal -->
<div class="modal-backdrop" id="userDeleteModal" role="dialog" aria-modal="true" aria-labelledby="userDeleteModalTitle">
    <section class="detail-modal" style="max-width:380px;">
        <div class="modal-head">
            <h3 id="userDeleteModalTitle">Hapus Akun</h3>
            <button type="button" class="modal-close" id="userDeleteModalClose">&times;</button>
        </div>
        <div class="modal-body" style="padding:18px 20px;">
            <p style="margin:0 0 12px;font-size:0.88rem;color:var(--text-heading);">
                Apakah Anda yakin ingin menghapus akun <strong id="deleteUserTargetName">-</strong>?
            </p>
            <div id="userDeleteMessage" style="font-size:0.8rem;font-weight:600;min-height:16px;color:var(--danger);"></div>
            <div style="display:flex;justify-content:flex-end;gap:8px;padding-top:10px;border-top:1px solid var(--border-light);">
                <button type="button" class="btn btn-secondary" id="userDeleteCancel">Batal</button>
                <button type="button" class="btn" style="background:var(--danger);color:#ffffff;" id="userDeleteConfirm">Hapus Akun</button>
            </div>
        </div>
    </section>
</div>

<!-- Calendar Modal -->
<div class="modal-backdrop" id="workdayModal" role="dialog" aria-modal="true" aria-labelledby="workdayModalTitle">
    <section class="detail-modal">
        <div class="modal-head">
            <h3 id="workdayModalTitle">Pengaturan Kalender Kerja</h3>
            <button type="button" class="modal-close" id="workdayModalClose" aria-label="Tutup kalender">&times;</button>
        </div>
        <div class="modal-body">
            <div class="calendar-tools">
                <button type="button" id="workdayPrevButton">&larr; Sebelumnya</button>
                <div class="calendar-month-title" id="workdayCalendarTitle">-</div>
                <button type="button" id="workdayNextButton">Berikutnya &rarr;</button>
            </div>

            <div class="calendar-summary" id="workdaySummary">
                <div class="calendar-summary-item">
                    <span>Libur</span>
                    <b id="countHoliday" style="color:var(--danger);">0</b>
                    <small>Hari libur</small>
                </div>
                <div class="calendar-summary-item">
                    <span>1/2 Hari</span>
                    <b id="countHalf" style="color:var(--warning);">0</b>
                    <small>Setengah hari</small>
                </div>
                <div class="calendar-summary-item">
                    <span>1/4 Hari</span>
                    <b id="countQuarter" style="color:#b45309;">0</b>
                    <small>Seperempat hari</small>
                </div>
                <div class="calendar-summary-item">
                    <span>Kerja</span>
                    <b id="countWork" style="color:var(--success);">0</b>
                    <small>Hari kerja</small>
                </div>
            </div>

            <div class="calendar-grid" id="workdayCalendar"></div>

            <div class="calendar-legend">
                <span><i class="legend-sunday"></i>Minggu/off</span>
                <span><i class="legend-work"></i>Kerja</span>
                <span><i class="legend-half"></i>1/2 Hari</span>
                <span><i class="legend-quarter"></i>1/4 Hari</span>
                <span><i class="legend-off"></i>Libur</span>
            </div>

            <div class="calendar-help">Klik tanggal untuk mengganti status: libur &rarr; 1/2 hari &rarr; 1/4 hari &rarr; kerja.</div>

            <div class="save-row" style="padding:0;background:transparent;border:0;">
                <span id="workdayMessage" class="helper" style="margin-right:auto;"></span>
                <button type="button" class="btn btn-secondary" id="workdayClearButton">Kosongkan</button>
                <button type="button" class="btn btn-primary" id="workdaySaveButton">Simpan Kalender</button>
            </div>
        </div>
    </section>
</div>

<script>
    const urls = {
        status: <?= json_encode($status_url ?? '') ?>,
        portalLogout: <?= json_encode($portal_logout_url ?? '') ?>,
        dashboard: <?= json_encode($dashboard_url ?? '') ?>,
        saveStyleSmv: <?= json_encode($save_style_smv_url ?? '') ?>,
        saveWorkdays: <?= json_encode($save_workdays_url ?? '') ?>,
        saveAnalyticsSettings: <?= json_encode($save_analytics_settings_url ?? '') ?>,
        users: <?= json_encode($users_url ?? '') ?>,
        saveUser: <?= json_encode($save_user_url ?? '') ?>,
        deleteUser: <?= json_encode($delete_user_url ?? '') ?>,
        toggleUserStatus: <?= json_encode($toggle_user_status_url ?? '') ?>,
    };

    const currentUserId = <?= isset($user['id']) ? (int) $user['id'] : 0 ?>;

    const initialPayload = <?= json_encode($initial_dashboard_payload ?? array()) ?>;
    const initialHolidaySettings = <?= json_encode($holiday_settings ?? array('holidays' => array(), 'half_days' => array(), 'quarter_days' => array(), 'work_days' => array())) ?>;
    const initialAnalyticsSettings = <?= json_encode($analytics_settings ?? array('visible_cards' => array('production_status', 'output_achievement', 'data_accuracy'), 'language' => 'id', 'direct_actual' => NULL)) ?>;
    const analyticsCardDefinitions = [
        {key:'production_status', label:'Production Status', note:'Status utama area produksi.'},
        {key:'output_achievement', label:'Output Achievement', note:'Perbandingan output terhadap total PDK.'},
        {key:'data_accuracy', label:'Data Accuracy', note:'Akurasi urutan dan validasi data.'},
        {key:'ready_to_load', label:'Ready to Load & Coverage', note:'Total qty ready to load beserta cakupan hari.'},
        {key:'avg_daily_capacity', label:'Avg. Daily Demand', note:'Rata-rata demand harian.'},
        {key:'critical_orders', label:'Critical Orders', note:'Order prioritas yang kritis.'},
    ];
    const monthNames = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    const weekdayNames = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];
    let selectedDateFrom = <?= json_encode($selected_date_from ?? ($selected_date ?? date('Y-m-01'))) ?>;
    let selectedDateTo = <?= json_encode($selected_date_to ?? ($selected_date ?? date('Y-m-15'))) ?>;
    let selectedDate = selectedDateFrom;
    let currentDashboard = initialPayload?.dashboard_data || null;
    let currentStyleCatalog = initialPayload?.style_smv_catalog || <?= json_encode($style_smv_catalog ?? array('styles' => array())) ?>;
    let currentServerTime = initialPayload?.server_time || null;
    let analyticsSettingsState = {
        visible_cards: Array.isArray(initialAnalyticsSettings?.visible_cards) ? initialAnalyticsSettings.visible_cards : ['production_status', 'output_achievement', 'data_accuracy'],
        language: initialAnalyticsSettings?.language === 'en' ? 'en' : 'id',
    };
    // Seed directActualState from the input value that was pre-filled by PHP (most reliable source)
    const _directActualInput = document.getElementById('directActualInput');
    let directActualState = (function() {
        const fromInput = _directActualInput ? String(_directActualInput.value || '').trim() : '';
        if (fromInput !== '') return Number(fromInput);
        const fromSettings = initialAnalyticsSettings?.direct_actual;
        if (fromSettings !== null && fromSettings !== undefined && fromSettings !== '') return Number(fromSettings);
        return null;
    })();
    let calendarCursor = new Date();
    calendarCursor.setDate(1);
    let calendarState = {
        holidays: new Set(Array.isArray(initialHolidaySettings?.holidays) ? initialHolidaySettings.holidays : []),
        halfDays: new Set(Array.isArray(initialHolidaySettings?.half_days) ? initialHolidaySettings.half_days : []),
        quarterDays: new Set(Array.isArray(initialHolidaySettings?.quarter_days) ? initialHolidaySettings.quarter_days : []),
        workDays: new Set(Array.isArray(initialHolidaySettings?.work_days) ? initialHolidaySettings.work_days : []),
    };

    function fmt(value) {
        const number = Number(value) || 0;
        return number.toLocaleString('id-ID');
    }

    function fmtDecimal(value, decimals = 2) {
        const number = Number(value);
        if (!Number.isFinite(number)) return '-';
        return number.toLocaleString('id-ID', {
            minimumFractionDigits: 0,
            maximumFractionDigits: decimals,
        });
    }

    function esc(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function balanceValue(data) {
        return Number(data?.kpis?.balance_qty || 0);
    }

    function isoDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function monthTitle(date) {
        return `${monthNames[date.getMonth()]} ${date.getFullYear()}`;
    }

    function calendarLabel(iso) {
        if (calendarState.holidays.has(iso)) return 'Libur';
        if (calendarState.halfDays.has(iso)) return '1/2 Hari';
        if (calendarState.quarterDays.has(iso)) return '1/4 Hari';
        if (calendarState.workDays.has(iso)) return 'Kerja';
        return 'Default';
    }

    function clearCalendarSelection(iso) {
        calendarState.holidays.delete(iso);
        calendarState.halfDays.delete(iso);
        calendarState.quarterDays.delete(iso);
        calendarState.workDays.delete(iso);
    }

    function updateCalendarSummary() {
        document.getElementById('countHoliday').textContent = fmt(calendarState.holidays.size);
        document.getElementById('countHalf').textContent = fmt(calendarState.halfDays.size);
        document.getElementById('countQuarter').textContent = fmt(calendarState.quarterDays.size);
        document.getElementById('countWork').textContent = fmt(calendarState.workDays.size);
    }

    function selectedStyleSmvSummary(catalog) {
        let items = [];
        if (Array.isArray(catalog?.running_styles) && catalog.running_styles.length) {
            items = catalog.running_styles.filter(s => !(s.style || '').toUpperCase().includes('OFC'));
        } else if (Array.isArray(catalog?.styles)) {
            items = catalog.styles.filter(s => !(s.style || '').toUpperCase().includes('OFC') && Boolean(s.is_running));
        }

        const totalRunning = items.length;
        const filledItems = items.filter(s => Number(s?.smv) > 0);
        const isComplete = totalRunning > 0 && filledItems.length === totalRunning;

        let totalProcesses = 0;
        filledItems.forEach(item => {
            if (Array.isArray(item.process_smvs) && item.process_smvs.length) {
                totalProcesses += item.process_smvs.length;
            } else if (item.process_count && Number(item.process_count) > 0) {
                totalProcesses += Number(item.process_count);
            } else {
                totalProcesses += 1;
            }
        });

        const totalSmv = filledItems.reduce((sum, item) => sum + Number(item.smv), 0);

        return {
            items: filledItems,
            totalRunningStyles: totalRunning,
            filledCount: filledItems.length,
            isComplete: isComplete,
            totalSmv: totalSmv,
            totalProcesses: totalProcesses,
            average: isComplete ? (totalSmv / totalRunning) : 0,
            averageProcess: isComplete ? (totalProcesses / totalRunning) : 0,
            count: filledItems.length,
            totalApsStyles: totalRunning,
            total: totalSmv,
        };
    }

    function calculateMachineRequirement(directCount, doubleMachineActive = 2) {
        const direct = Math.max(0, Math.ceil(Number(directCount) || 0));
        const m2Active = Math.max(0, Math.min(2, parseInt(doubleMachineActive, 10) || 0));

        const directForM2 = Math.min(direct, m2Active * 2);
        const m2Used = Math.ceil(directForM2 / 2);
        const directSingle = Math.max(0, direct - (m2Used * 2));
        const m1Used = directSingle;

        const totalMachines = m2Used + m1Used;
        const minM2Used = Math.ceil(Math.min(direct, 4) / 2);
        const minTotal = minM2Used + Math.max(0, direct - (minM2Used * 2));
        const maxTotal = direct;

        return {
            direct,
            m2Active,
            m2Used,
            m1Used,
            totalMachines,
            minTotal,
            maxTotal,
        };
    }

    function isSaturdayWorkday(dashboard, targetDate = null) {
        const calendar = dashboard?.management_analytics?.details?.daily_requirement?.period_calendar || {};
        const satHours = Number(calendar?.working_hours_saturday);
        const d = targetDate ? new Date(targetDate) : (currentServerTime ? new Date(currentServerTime) : new Date());
        const iso = isoDate(d);

        if (calendarState.workDays.has(iso)) return true;
        if (calendarState.holidays.has(iso)) return false;
        if (Number.isFinite(satHours)) return satHours > 0;
        return false;
    }

    function currentWorkingHours(dashboard = {}, targetDate = null) {
        const d = targetDate ? new Date(targetDate) : (currentServerTime ? new Date(currentServerTime) : new Date());
        const dayOfWeek = d.getDay();
        const iso = isoDate(d);

        if (calendarState.holidays.has(iso)) return 0;
        const satWorkday = isSaturdayWorkday(dashboard, d);

        if (dayOfWeek === 0) {
            if (calendarState.workDays.has(iso)) return satWorkday ? 7 : 8;
            if (calendarState.halfDays.has(iso)) return (satWorkday ? 7 : 8) * 0.5;
            if (calendarState.quarterDays.has(iso)) return (satWorkday ? 7 : 8) * 0.25;
            return 0;
        }

        if (dayOfWeek === 6) {
            if (!satWorkday || calendarState.holidays.has(iso)) return 0;
            if (calendarState.halfDays.has(iso)) return 2.5;
            if (calendarState.quarterDays.has(iso)) return 1.25;
            return 5;
        }

        const baseHours = satWorkday ? 7 : 8;
        if (calendarState.halfDays.has(iso)) return baseHours * 0.5;
        if (calendarState.quarterDays.has(iso)) return baseHours * 0.25;
        return baseHours;
    }

    function calculateDirectRequirement(dashboard, targetDate = null) {
        const db = dashboard || currentDashboard || initialPayload?.dashboard_data || {};
        const styleSummary = selectedStyleSmvSummary(currentStyleCatalog);
        const rawDirectActual = directActualState
            ?? db?.analytics_settings?.direct_actual
            ?? initialAnalyticsSettings?.direct_actual;

        const directActual = (rawDirectActual === null || rawDirectActual === undefined || rawDirectActual === '' || isNaN(Number(rawDirectActual)))
            ? 0
            : Number(rawDirectActual);

        let parsedDate = null;
        if (targetDate instanceof Date) {
            parsedDate = targetDate;
        } else if (typeof targetDate === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(targetDate)) {
            parsedDate = new Date(targetDate + 'T12:00:00');
        } else if (targetDate) {
            parsedDate = new Date(targetDate);
        }

        const smv = Number(styleSummary?.average) || 0;
        const workingHours = currentWorkingHours(db, parsedDate);
        const satWorkday = isSaturdayWorkday(db, parsedDate);

        const targetPerPerson = smv > 0 && workingHours > 0 ? ((1 * 60 / smv) * workingHours) * 0.70 : 0;
        const capacity = Math.round(targetPerPerson * directActual);

        const analyticsDetails = db?.management_analytics?.details || {};
        const balanceQty = Number(
            db?.kpis?.balance_qty ??
            analyticsDetails?.daily_requirement?.balance_qty ??
            analyticsDetails?.output?.balance_qty ?? 0
        );
        const daysLeft = Number(
            db?.kpis?.prod_days_left ??
            db?.kpis?.remaining_days ??
            analyticsDetails?.daily_requirement?.period_days_left ??
            analyticsDetails?.daily_requirement?.prod_days_left ?? 0
        );
        const rows = db?.output_vs_capacity || [];
        let todayDemand = 0;
        if (rows.length > 0) {
            const todayDate = currentServerTime ? new Date(currentServerTime) : new Date();
            const todayDay = todayDate.getDate();
            const todayRow = rows.find(r => {
                const m = String(r?.label || '').match(/^0?(\d+)/);
                return m && parseInt(m[1], 10) === todayDay;
            }) || rows[rows.length - 1];

            if (todayRow) {
                todayDemand = Number(todayRow.daily_demand ?? todayRow.demand ?? (todayRow.sisa_hari_kerja > 0 ? Math.round(todayRow.total_demand / todayRow.sisa_hari_kerja) : 0)) || 0;
            }
        }

        const demand = todayDemand > 0 ? todayDemand : (
            Number(analyticsDetails?.daily_requirement?.period_required_daily_output ?? analyticsDetails?.daily_requirement?.required_daily_output)
            || (daysLeft > 0 ? (balanceQty / daysLeft) : 0)
        );
        const directPlan = targetPerPerson > 0 ? (demand / targetPerPerson) : 0;

        return {
            hasCapacity: Boolean(styleSummary?.isComplete) && smv > 0 && directActual > 0,
            isComplete: Boolean(styleSummary?.isComplete),
            filledCount: styleSummary?.filledCount || 0,
            totalRunningStyles: styleSummary?.totalRunningStyles || 0,
            capacity: capacity,
            directActual: directActual,
            smv: smv,
            styleCount: styleSummary?.count || 0,
            totalProcesses: styleSummary?.totalProcesses || 0,
            averageProcess: styleSummary?.averageProcess || 0,
            totalApsStyles: styleSummary?.totalApsStyles || 0,
            workingHours: workingHours,
            isSatWorkday: satWorkday,
            targetPerPerson: targetPerPerson,
            demand: demand,
            direct: directPlan,
            gap: capacity - demand
        };
    }

    function renderRunningStylesSummary(catalog) {
        const tbody = document.getElementById('summaryStyleTableBody');
        const badge = document.getElementById('summaryRunningStyleBadge');
        const footerProc = document.getElementById('summaryFooterTotalProcesses');
        const footerSmv = document.getElementById('summaryFooterTotalSmv');
        const smvPill = document.getElementById('smvCountPill');

        let items = [];
        if (Array.isArray(catalog?.running_styles) && catalog.running_styles.length) {
            items = catalog.running_styles.filter(s => !(s.style || '').toUpperCase().includes('OFC'));
        } else if (Array.isArray(catalog?.styles)) {
            items = catalog.styles.filter(s => !(s.style || '').toUpperCase().includes('OFC') && Boolean(s.is_running));
        }

        const totalRunning = items.length;
        let totalProcesses = 0;
        let totalSmv = 0;
        let filledCount = 0;

        items.forEach(s => {
            const smvVal = Number(s.smv);
            if (smvVal > 0) {
                totalSmv += smvVal;
                filledCount++;
                if (Array.isArray(s.process_smvs) && s.process_smvs.length) {
                    totalProcesses += s.process_smvs.length;
                } else if (s.process_count && Number(s.process_count) > 0) {
                    totalProcesses += Number(s.process_count);
                } else {
                    totalProcesses += 1;
                }
            }
        });

        const isComplete = totalRunning > 0 && filledCount === totalRunning;

        if (badge) {
            if (isComplete) {
                badge.className = 'badge-pill good';
                badge.textContent = `${totalRunning} Style Aktif (Lengkap)`;
            } else if (totalRunning > 0) {
                badge.className = 'badge-pill warn';
                badge.textContent = `${filledCount} / ${totalRunning} Style Terisi (Belum Lengkap)`;
            } else {
                badge.className = 'badge-pill';
                badge.textContent = '0 Style Aktif';
            }
        }

        if (smvPill) {
            smvPill.textContent = `${totalRunning} Style (${totalProcesses} Proses Terisi)`;
        }

        if (footerProc) {
            footerProc.innerHTML = isComplete
                ? String(totalProcesses)
                : `${totalProcesses} <small style="color:var(--risk);font-weight:600;">(Belum lengkap)</small>`;
        }

        if (footerSmv) {
            footerSmv.innerHTML = isComplete
                ? fmtDecimal(totalSmv, 2)
                : `${fmtDecimal(totalSmv, 2)} <small style="color:var(--risk);font-weight:600;">(Belum lengkap)</small>`;
        }

        if (!tbody) return;

        if (!items.length) {
            tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:14px;">Tidak ada style berjalan di APS periode ini.</td></tr>';
            return;
        }

        tbody.innerHTML = items.map((s, idx) => {
            let pCount = 1;
            if (Array.isArray(s.process_smvs) && s.process_smvs.length) {
                pCount = s.process_smvs.length;
            } else if (s.process_count && Number(s.process_count) > 0) {
                pCount = Number(s.process_count);
            }
            const hasSmv = Number(s.smv) > 0;
            const smvText = hasSmv ? fmtDecimal(s.smv, 2) : '<span style="color:var(--risk);font-weight:600;font-size:0.78rem;">Belum diisi</span>';
            const smvStyle = hasSmv ? 'font-weight:600;color:var(--primary);' : '';

            return `<tr>
                <td class="num" style="color:var(--text-muted);">${idx + 1}</td>
                <td style="font-weight:600;">${esc(s.style || '-')}</td>
                <td class="num">${pCount}</td>
                <td class="num" style="${smvStyle}">${smvText}</td>
            </tr>`;
        }).join('');
    }

    function renderBalanceBreakdown(rows) {
        const tbody = document.getElementById('summaryBreakdownRows');
        if (!tbody) return;

        const items = Array.isArray(rows) ? rows : [];
        if (!items.length) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--muted);padding:14px;">Data periode belum tersedia.</td></tr>';
            return;
        }

        tbody.innerHTML = items.map((row, idx) => {
            const pdk = Number(row.pdk) || 0;
            const out = Number(row.output) || 0;
            const bal = row.balance !== undefined ? Number(row.balance) : Math.max(0, pdk - out);
            const pct = pdk > 0 ? Math.min(100, Math.max(0, Math.round((out / pdk) * 1000) / 10)) : 0;
            return `
                <tr>
                    <td>${idx + 1}</td>
                    <td><strong>${esc(row.label || '-')}</strong></td>
                    <td class="num">${fmt(pdk)} pcs</td>
                    <td class="num" style="color:var(--brand);font-weight:800;">${fmt(out)} pcs</td>
                    <td class="num" style="color:var(--warn);font-weight:800;">${fmt(bal)} pcs</td>
                    <td>
                        <div style="display:flex;justify-content:space-between;font-size:11px;font-weight:800;">
                            <span>${pct}%</span>
                            <small style="color:var(--muted);">${fmt(out)} / ${fmt(pdk)}</small>
                        </div>
                        <div class="progress-bar-wrap">
                            <div class="progress-bar-fill" style="width:${pct}%;"></div>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function renderPriorityRows(rows) {
        const tbody = document.getElementById('priorityRows');
        if (!tbody) return;
        const items = (rows || []).slice(0, 10);
        if (!items.length) {
            tbody.innerHTML = '<tr><td colspan="6" style="padding:16px;color:var(--muted);text-align:center;">Tidak ada data prioritas.</td></tr>';
            return;
        }
        tbody.innerHTML = items.map((row, index) => `
            <tr>
                <td>${index + 1}</td>
                <td>${esc(row.order || '-')}</td>
                <td>${esc(row.style || '-')}</td>
                <td>${esc(row.delivery || '-')}</td>
                <td class="num">${fmt(Number(row.qty_ready) || 0)}</td>
                <td>${esc(row.source || '-')}</td>
            </tr>
        `).join('');
    }

    function renderStats(payload) {
        const dashboard = payload?.dashboard_data || {};
        const kpis = dashboard?.kpis || {};
        const latestCapacity = (dashboard?.output_vs_capacity || []).slice(-1)[0] || {};
        const directData = calculateDirectRequirement(dashboard);

        currentDashboard = dashboard;
        if (payload?.server_time) {
            currentServerTime = payload.server_time;
        }

        const totalOutput = Math.round(Number(kpis.total_output || 0));
        const balanceQty = Math.round(Number(kpis.balance_qty || 0));
        const balanceQtyOfc = Math.round(Number(kpis.balance_qty_with_ofc ?? kpis.balance_qty ?? 0));
        const remainingDays = Number(kpis.prod_days_left ?? kpis.remaining_days ?? 0);

        // Update all elements with class or ID
        document.querySelectorAll('.kpi-total-output').forEach(el => el.textContent = fmt(totalOutput));
        document.querySelectorAll('.kpi-balance-qty').forEach(el => el.textContent = fmt(balanceQty));
        document.querySelectorAll('.kpi-balance-qty-ofc').forEach(el => el.textContent = fmt(balanceQtyOfc));
        document.querySelectorAll('.kpi-remaining-days').forEach(el => el.textContent = Number(remainingDays).toLocaleString('id-ID', {minimumFractionDigits: 1, maximumFractionDigits: 1}));

        const isComplete = Boolean(directData?.isComplete);
        const filledCount = directData?.filledCount || 0;
        const totalRunning = directData?.totalRunningStyles || directData?.totalApsStyles || 0;

        // Direct Plan
        const directPlanText = (isComplete && directData && directData.direct > 0) ? fmt(Math.ceil(directData.direct)) : '-';
        document.querySelectorAll('.kpi-direct-plan').forEach(el => el.textContent = directPlanText);

        // Target per person
        let targetPerPersonText = 'Target per orang: -';
        if (isComplete && directData && directData.targetPerPerson > 0) {
            targetPerPersonText = `Target per orang: ${fmt(Math.ceil(directData.targetPerPerson))} pcs/hari`;
        } else if (!isComplete && totalRunning > 0) {
            targetPerPersonText = 'Target per orang: - (SMV belum lengkap)';
        }
        document.querySelectorAll('.kpi-target-per-person').forEach(el => el.textContent = targetPerPersonText);

        // Direct Actual
        const directActualText = (directData && directData.directActual > 0)
            ? fmtDecimal(directData.directActual, 2)
            : (directActualState !== null && directActualState !== undefined ? fmtDecimal(directActualState, 2) : '-');
        document.querySelectorAll('.kpi-direct-actual').forEach(el => el.textContent = directActualText);

        // SMV Average & Active Styles Subtext
        if (isComplete && directData && directData.smv > 0) {
            document.querySelectorAll('.kpi-avg-smv').forEach(el => el.textContent = fmtDecimal(directData.smv, 2));
            document.querySelectorAll('.kpi-active-styles-count').forEach(el => el.textContent = `${totalRunning} style berjalan (Lengkap)`);
        } else {
            document.querySelectorAll('.kpi-avg-smv').forEach(el => el.textContent = '-');
            const subText = totalRunning > 0 ? `${filledCount} / ${totalRunning} style terisi (Belum lengkap)` : '0 style aktif';
            document.querySelectorAll('.kpi-active-styles-count').forEach(el => el.textContent = subText);
        }

        // Rata-rata Proses & Total Proses
        const totalProc = directData ? (directData.totalProcesses || 0) : 0;
        if (isComplete && directData && directData.averageProcess > 0) {
            document.querySelectorAll('.kpi-avg-process').forEach(el => el.textContent = fmtDecimal(directData.averageProcess, 1));
            document.querySelectorAll('.kpi-avg-process-sub').forEach(el => el.textContent = `${totalProc} proses / ${totalRunning} style`);
        } else {
            document.querySelectorAll('.kpi-avg-process').forEach(el => el.textContent = '-');
            const subText = totalRunning > 0 ? `${filledCount} / ${totalRunning} style terisi (Belum lengkap)` : '0 style aktif';
            document.querySelectorAll('.kpi-avg-process-sub').forEach(el => el.textContent = subText);
        }

        document.querySelectorAll('.kpi-total-processes').forEach(el => el.textContent = String(totalProc));
        document.querySelectorAll('.kpi-filled-processes-sub').forEach(el => el.textContent = `${filledCount} / ${totalRunning} style aktif`);

        // 3 PILAR KAPASITAS: MESIN, MANPOWER, PCS
        const doubleMachineInput = document.getElementById('doubleMachineActiveInput');
        const m2Active = doubleMachineInput ? parseInt(doubleMachineInput.value, 10) : 2;
        const baseDirect = (isComplete && directData && directData.direct > 0)
            ? Math.ceil(directData.direct)
            : (directData && directData.directActual > 0 ? Math.ceil(directData.directActual) : 0);

        const m1Rate = directData && directData.targetPerPerson > 0 ? Math.ceil(directData.targetPerPerson) : 0;
        const m2Rate = m1Rate * 2;

        // Pilar 1: Mesin
        if (baseDirect > 0) {
            const mWith = calculateMachineRequirement(baseDirect, 2);
            const mWithout = calculateMachineRequirement(baseDirect, 0);

            document.querySelectorAll('.kpi-machine-req').forEach(el => {
                el.innerHTML = `${fmt(mWith.totalMachines)} <small style="font-size:0.72rem;color:var(--text-muted);font-weight:600;">(M2)</small> / ${fmt(mWithout.totalMachines)} <small style="font-size:0.72rem;color:var(--text-muted);font-weight:600;">(M1 Saja)</small>`;
            });
            const subText = `Pakai M2: ${mWith.m2Used} M2 + ${mWith.m1Used} M1 | Tanpa M2: ${mWithout.totalMachines} M1`;
            document.querySelectorAll('.kpi-machine-req-sub').forEach(el => {
                el.textContent = subText;
                el.title = `M1: Mesin Tunggal, M2: Mesin Ganda. Direct: ${baseDirect} orang.`;
            });

            const tierMVal = document.getElementById('tierMachineVal');
            if (tierMVal) tierMVal.innerHTML = `${fmt(mWith.totalMachines)} <small style="font-size:0.75rem;color:var(--brand);font-weight:700;">(M2)</small> / ${fmt(mWithout.totalMachines)} <small style="font-size:0.75rem;color:var(--text-muted);font-weight:700;">(M1)</small>`;

            const tierMSub = document.getElementById('tierMachineSub');
            if (tierMSub) tierMSub.textContent = `Pakai M2: ${mWith.m2Used} M2 + ${mWith.m1Used} M1 | M1 Saja: ${mWithout.totalMachines} M1`;

            const tierMRate = document.getElementById('tierMachineRate');
            if (tierMRate) tierMRate.textContent = m1Rate > 0 ? `Rate: 1 M1 = ${fmt(m1Rate)} pcs | 1 M2 = ${fmt(m2Rate)} pcs/hari` : 'Rate: - pcs/mesin/hari';

            const smrcEl = document.getElementById('summaryMachineReqCalculation');
            if (smrcEl) {
                smrcEl.textContent = `Pakai M2: ${fmt(mWith.totalMachines)} Mesin (${mWith.m2Used} M2 + ${mWith.m1Used} M1) | Tanpa M2: ${fmt(mWithout.totalMachines)} Mesin (${mWithout.totalMachines} M1)`;
            }

            const smRateEl = document.getElementById('summaryMachineRateCalculation');
            if (smRateEl) {
                smRateEl.textContent = m1Rate > 0 ? `1 M1: ${fmt(m1Rate)} pcs | 1 M2: ${fmt(m2Rate)} pcs/hari` : '1 M1: - pcs | 1 M2: - pcs';
            }
        } else {
            document.querySelectorAll('.kpi-machine-req').forEach(el => el.textContent = '-');
            document.querySelectorAll('.kpi-machine-req-sub').forEach(el => el.textContent = 'Direct belum diatur');
            const tierMVal = document.getElementById('tierMachineVal');
            if (tierMVal) tierMVal.textContent = '-';
            const tierMSub = document.getElementById('tierMachineSub');
            if (tierMSub) tierMSub.textContent = 'Direct belum diatur';
            const tierMRate = document.getElementById('tierMachineRate');
            if (tierMRate) tierMRate.textContent = 'Rate: -';
            const smrcEl = document.getElementById('summaryMachineReqCalculation');
            if (smrcEl) smrcEl.textContent = '- Mesin';
        }

        // Demand Harian
        const dailyDemandText = (directData && directData.demand > 0) ? fmt(Math.round(directData.demand)) : '-';
        document.querySelectorAll('.kpi-daily-demand').forEach(el => el.textContent = dailyDemandText);

        // Estimated Capacity & Gap
        const estCapText = (isComplete && directData && directData.capacity > 0) ? fmt(directData.capacity) : '-';
        document.querySelectorAll('.kpi-estimated-capacity').forEach(el => el.textContent = estCapText);

        // Pilar 2: Manpower
        const tierMpActual = document.getElementById('tierManpowerActualVal');
        if (tierMpActual) tierMpActual.textContent = directActualText;
        const tierMpPlan = document.getElementById('tierManpowerPlanVal');
        if (tierMpPlan) tierMpPlan.textContent = directPlanText;

        const tierMpGap = document.getElementById('tierManpowerGapSub');
        const smMpCalc = document.getElementById('summaryManpowerCalculation');
        if (directData && directData.directActual > 0 && directData.direct > 0 && isComplete) {
            const mpDiff = Math.round(directData.directActual) - Math.ceil(directData.direct);
            const mpStatus = mpDiff > 0 ? 'Lebih' : (mpDiff === 0 ? 'Pas' : 'Kurang');
            const mpText = `${mpDiff > 0 ? '+' : ''}${mpDiff} Orang (${mpStatus})`;
            const mpColor = mpDiff >= 0 ? 'var(--ok)' : 'var(--risk)';
            if (tierMpGap) {
                tierMpGap.innerHTML = `<span style="color:${mpColor};font-weight:700;">${mpText}</span>`;
            }
            if (smMpCalc) {
                smMpCalc.innerHTML = `${directActualText} Aktual vs ${directPlanText} Plan (<span style="color:${mpColor};font-weight:700;">${mpText}</span>)`;
            }
        } else {
            if (tierMpGap) tierMpGap.textContent = (totalRunning > 0 && !isComplete) ? 'SMV belum lengkap' : '-';
            if (smMpCalc) smMpCalc.textContent = `${directActualText} Aktual vs ${directPlanText} Plan`;
        }

        const tierMpTarget = document.getElementById('tierManpowerTargetSub');
        if (tierMpTarget) {
            tierMpTarget.textContent = m1Rate > 0 ? `Target: ${fmt(m1Rate)} pcs/orang/hari` : 'Target: - pcs/orang/hari';
        }

        // Pilar 3: Pcs (Output)
        const tierPcsVal = document.getElementById('tierPcsVal');
        if (tierPcsVal) tierPcsVal.textContent = estCapText;

        const tierPcsDemand = document.getElementById('tierPcsDemandSub');
        if (tierPcsDemand) tierPcsDemand.textContent = `Demand: ${dailyDemandText} pcs/hari`;

        const tierPcsGap = document.getElementById('tierPcsGapSub');
        const smPcsCalc = document.getElementById('summaryPcsCalculation');
        if (smPcsCalc) smPcsCalc.textContent = (isComplete && directData && directData.capacity > 0) ? `${fmt(directData.capacity)} pcs/hari (Demand: ${dailyDemandText} pcs)` : '- pcs/hari';

        let gapText = 'Estimasi vs Demand';
        if (directData && directData.hasCapacity) {
            const isMore = directData.gap > 0;
            const isExact = directData.gap === 0;
            const pcsStatus = isMore ? 'Lebih' : (isExact ? 'Pas' : 'Kurang');
            gapText = `Selisih: ${isMore ? '+' : ''}${fmt(directData.gap)} pcs (${pcsStatus})`;
            if (tierPcsGap) {
                tierPcsGap.innerHTML = `<span style="color:${directData.gap >= 0 ? 'var(--ok)' : 'var(--risk)'};font-weight:700;">${isMore ? '+' : ''}${fmt(directData.gap)} pcs (${pcsStatus})</span>`;
            }
        } else if (totalRunning > 0 && !isComplete) {
            gapText = `SMV belum lengkap (${filledCount}/${totalRunning} style terisi)`;
            if (tierPcsGap) tierPcsGap.textContent = `SMV belum lengkap (${filledCount}/${totalRunning} style)`;
        } else {
            if (tierPcsGap) tierPcsGap.textContent = '-';
        }
        document.querySelectorAll('.kpi-capacity-gap').forEach(el => el.textContent = gapText);

        const capBadge = document.getElementById('kpiCapacityBadge');
        if (capBadge) {
            if (directData && directData.hasCapacity) {
                capBadge.className = `badge-pill ${directData.gap >= 0 ? 'good' : 'warn'}`;
                capBadge.textContent = directData.gap >= 0 ? 'Kapasitas Cukup (Lebih)' : 'Kapasitas Kurang';
            } else if (totalRunning > 0 && !isComplete) {
                capBadge.className = 'badge-pill warn';
                capBadge.textContent = 'SMV Belum Lengkap';
            } else {
                capBadge.className = 'badge-pill';
                capBadge.textContent = 'Data Siap';
            }
        }
        const capBadgeSum = document.getElementById('kpiCapacityBadgeSummary');
        if (capBadgeSum) {
            if (directData && directData.hasCapacity) {
                capBadgeSum.className = `badge-pill ${directData.gap >= 0 ? 'good' : 'warn'}`;
                capBadgeSum.textContent = directData.gap >= 0 ? 'Lebih (Aman)' : 'Kurang';
            } else if (totalRunning > 0 && !isComplete) {
                capBadgeSum.className = 'badge-pill warn';
                capBadgeSum.textContent = 'SMV Belum Lengkap';
            } else {
                capBadgeSum.className = 'badge-pill';
                capBadgeSum.textContent = 'Data Siap';
            }
        }

        // Summary Formula Box
        const whEl = document.getElementById('summaryWorkingHours');
        if (whEl) whEl.textContent = `${directData ? directData.workingHours : 8} Jam`;

        const tppEl = document.getElementById('summaryTargetPerPerson');
        if (tppEl) {
            if (isComplete && directData && directData.targetPerPerson > 0) {
                tppEl.textContent = `${fmt(Math.ceil(directData.targetPerPerson))} pcs/orang/hari`;
            } else if (totalRunning > 0 && !isComplete) {
                tppEl.textContent = '- pcs/hari (SMV belum lengkap)';
            } else {
                tppEl.textContent = '- pcs/hari';
            }
        }

        const dpcEl = document.getElementById('summaryDirectPlanCalculation');
        if (dpcEl) {
            if (isComplete && directData && directData.direct > 0) {
                dpcEl.textContent = `${fmt(Math.ceil(directData.direct))} Orang (Demand: ${fmt(Math.round(directData.demand))} pcs)`;
            } else if (totalRunning > 0 && !isComplete) {
                dpcEl.textContent = `- Orang (Perlu isi SMV ${totalRunning - filledCount} style lagi)`;
            } else {
                dpcEl.textContent = '- Orang';
            }
        }

        const dacEl = document.getElementById('summaryDirectActualCalculation');
        if (dacEl) dacEl.textContent = (directData && directData.directActual > 0) ? `${fmtDecimal(directData.directActual, 2)} Orang (Kapasitas: ${isComplete ? fmt(directData.capacity) : '-'} pcs)` : '- Orang';

        const dgapEl = document.getElementById('summaryDirectGap');
        if (dgapEl) {
            if (directData && directData.hasCapacity) {
                const isMore = directData.gap > 0;
                const isExact = directData.gap === 0;
                const pcsStatus = isMore ? 'Kapasitas Lebih / Aman' : (isExact ? 'Kapasitas Pas' : 'Kapasitas Kurang');
                dgapEl.textContent = `${isMore ? '+' : ''}${fmt(directData.gap)} pcs/hari (${pcsStatus})`;
                dgapEl.style.color = directData.gap >= 0 ? 'var(--ok)' : 'var(--risk)';
            } else if (totalRunning > 0 && !isComplete) {
                dgapEl.textContent = `Selisih Lebih/Kurang belum bisa dihitung (SMV belum lengkap: ${filledCount}/${totalRunning} style)`;
                dgapEl.style.color = 'var(--warn)';
            } else {
                dgapEl.textContent = '-';
                dgapEl.style.color = '';
            }
        }

        // Running Styles & Balance Breakdown
        renderRunningStylesSummary(currentStyleCatalog);
        renderBalanceBreakdown(dashboard?.balance_breakdown || dashboard?.qty_pdk_vs_output || []);

        // Status info
        const srcLabel = document.getElementById('summarySourceLabel');
        if (srcLabel) srcLabel.textContent = dashboard?.source || 'RPA Engage';

        const srcUpdated = document.getElementById('summarySourceUpdated');
        if (srcUpdated) srcUpdated.textContent = dashboard?.source_updated_at || '-';

        const calStatus = document.getElementById('calendarStatus');
        if (calStatus) calStatus.textContent = payload?.calendar_authenticated ? 'Active' : 'Inactive';

        const srvTime = document.getElementById('serverTime');
        if (srvTime) srvTime.textContent = payload?.server_time ? `Updated ${payload.server_time}` : '-';

        const connState = document.getElementById('connectionState');
        if (connState) connState.textContent = latestCapacity?.label ? 'Online' : 'Ready';
    }

    async function loadStatus(showOverlay = false) {
        const overlay = document.getElementById('globalLoadingOverlay');
        const loadingText = document.getElementById('globalLoadingText');
        if (showOverlay) {
            if (loadingText) loadingText.textContent = 'Memperbarui Data...';
            if (overlay) overlay.classList.add('active');
            document.querySelectorAll('.topbar-scope').forEach(div => div.classList.add('loading'));
            document.body.style.cursor = 'wait';
        }
        try {
            const response = await fetch(`${urls.status}?from=${encodeURIComponent(selectedDateFrom)}&to=${encodeURIComponent(selectedDateTo)}`, {cache:'no-store'});
            const data = await response.json();
            currentDashboard = data?.dashboard_data || null;
            if (currentDashboard?.selected_period) {
                updatePeriodPills(selectedDateFrom, selectedDateTo, currentDashboard.selected_period, currentDashboard.period_type);
            }
            if (data?.style_smv_catalog) {
                currentStyleCatalog = data.style_smv_catalog;
            }
            if (data?.analytics_settings && Object.prototype.hasOwnProperty.call(data.analytics_settings, 'direct_actual')) {
                directActualState = data.analytics_settings.direct_actual === null || data.analytics_settings.direct_actual === undefined || data.analytics_settings.direct_actual === '' ? null : Number(data.analytics_settings.direct_actual);
                renderDirectActualInput();
            }
            if (data?.analytics_settings && Object.prototype.hasOwnProperty.call(data.analytics_settings, 'double_machine_active')) {
                const dSelect = document.getElementById('doubleMachineActiveInput');
                if (dSelect && data.analytics_settings.double_machine_active !== undefined && data.analytics_settings.double_machine_active !== null) {
                    dSelect.value = String(data.analytics_settings.double_machine_active);
                }
            }
            if (data?.analytics_settings && Object.prototype.hasOwnProperty.call(data.analytics_settings, 'default_capacity_mode')) {
                const capModeSelect = document.getElementById('defaultCapacityModeInput');
                if (capModeSelect && data.analytics_settings.default_capacity_mode) {
                    capModeSelect.value = String(data.analytics_settings.default_capacity_mode);
                }
            }
            renderStats(data);
            if (currentDashboard?.material_to_load || currentDashboard?.top_priority_orders) {
                renderPriorityRows(currentDashboard.material_to_load || currentDashboard.top_priority_orders || []);
            }
        } catch (error) {
            const conn = document.getElementById('connectionState');
            if (conn) conn.textContent = 'Offline';
        } finally {
            if (showOverlay) {
                if (overlay) overlay.classList.remove('active');
                document.querySelectorAll('.topbar-scope').forEach(div => div.classList.remove('loading'));
                document.body.style.cursor = '';
            }
        }
    }

    const monthShortNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'June', 'July', 'Aug', 'Sept', 'Oct', 'Nov', 'Dec'];
    function getSinglePeriodInfo(dateStr) {
        if (!dateStr || !/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) {
            return { label: 'MID Sept', type: 'MID' };
        }
        const parts = dateStr.split('-');
        const day = parseInt(parts[2], 10);
        const monthIdx = parseInt(parts[1], 10) - 1;
        const month = monthShortNames[monthIdx] || 'Sept';
        const type = day <= 15 ? 'MID' : 'END';
        return {
            label: `${type} ${month}`,
            type: type
        };
    }

    function resolvePeriodInfo(fromStr, toStr) {
        const fromInfo = getSinglePeriodInfo(fromStr);
        if (!toStr || toStr === fromStr) {
            return fromInfo;
        }
        const toInfo = getSinglePeriodInfo(toStr);
        if (fromInfo.label === toInfo.label) {
            return fromInfo;
        }
        return {
            label: `${fromInfo.label} - ${toInfo.label}`,
            type: 'RANGE'
        };
    }

    function updatePeriodPills(fromStr, toStr, customLabel = null, customType = null) {
        const info = resolvePeriodInfo(fromStr, toStr);
        const label = customLabel || info.label;
        const type = customType || info.type;
        ['adminPeriodPill', 'summaryPeriodPill'].forEach(id => {
            const pill = document.getElementById(id);
            if (pill) {
                pill.textContent = label;
                pill.className = `period-pill ${type.toLowerCase()}`;
            }
        });
    }

    function syncDateInputs(fromVal, toVal) {
        const adminFrom = document.getElementById('adminDateFrom');
        const adminTo = document.getElementById('adminDateTo');
        const summaryFrom = document.getElementById('summaryDateFrom');
        const summaryTo = document.getElementById('summaryDateTo');
        if (adminFrom && adminFrom.value !== fromVal) adminFrom.value = fromVal;
        if (adminTo && adminTo.value !== toVal) adminTo.value = toVal;
        if (summaryFrom && summaryFrom.value !== fromVal) summaryFrom.value = fromVal;
        if (summaryTo && summaryTo.value !== toVal) summaryTo.value = toVal;
        updatePeriodPills(fromVal, toVal);
    }

    function handleDateChange(fromVal, toVal) {
        if (!fromVal || !toVal) return;
        if (fromVal > toVal) {
            toVal = fromVal;
        }
        selectedDateFrom = fromVal;
        selectedDateTo = toVal;
        selectedDate = selectedDateFrom;
        syncDateInputs(selectedDateFrom, selectedDateTo);
        document.cookie = `heatDateFrom=${selectedDateFrom}; path=/; max-age=31536000`;
        document.cookie = `heatDateTo=${selectedDateTo}; path=/; max-age=31536000`;
        localStorage.setItem('heatDateFrom', selectedDateFrom);
        localStorage.setItem('heatDateTo', selectedDateTo);

        // Tampilkan lambang loading overlay dan spinner
        document.querySelectorAll('.topbar-scope').forEach(div => div.classList.add('loading'));
        const overlay = document.getElementById('globalLoadingOverlay');
        const loadingText = document.getElementById('globalLoadingText');
        const info = resolvePeriodInfo(selectedDateFrom, selectedDateTo);
        if (loadingText) {
            loadingText.textContent = `Memuat Data ${info.label}...`;
        }
        if (overlay) {
            overlay.classList.add('active');
        }
        document.body.style.cursor = 'wait';

        const currentSection = document.querySelector('.section.active')?.id || 'overviewSection';
        const url = new URL(window.location.href);
        url.searchParams.delete('delivery_count');
        url.searchParams.delete('date');
        url.searchParams.set('from', selectedDateFrom);
        url.searchParams.set('to', selectedDateTo);
        url.hash = currentSection;
        window.location.href = url.toString();
    }

    function setStatusForStyle(style, text, kind = 'good') {
        const node = document.querySelector(`[data-status-for="${CSS.escape(style)}"]`);
        if (!node) return;
        node.textContent = text;
        node.className = `smv-status ${kind}`;
    }

    function renderWorkdayCalendar() {
        const container = document.getElementById('workdayCalendar');
        const title = document.getElementById('workdayCalendarTitle');
        if (!container || !title) return;

        title.textContent = monthTitle(calendarCursor);
        const startDay = new Date(calendarCursor.getFullYear(), calendarCursor.getMonth(), 1).getDay();
        const daysInMonth = new Date(calendarCursor.getFullYear(), calendarCursor.getMonth() + 1, 0).getDate();
        const todayIso = isoDate(new Date());
        const cells = weekdayNames.map(day => `<div class="calendar-head">${day}</div>`);

        for (let i = 0; i < startDay; i++) {
            cells.push('<div class="calendar-day out" aria-hidden="true"></div>');
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const date = new Date(calendarCursor.getFullYear(), calendarCursor.getMonth(), day);
            const iso = isoDate(date);
            const isSunday = date.getDay() === 0;
            const classes = ['calendar-day'];
            if (isSunday) classes.push('sunday');
            if (calendarState.workDays.has(iso)) classes.push('work');
            if (calendarState.halfDays.has(iso)) classes.push('half');
            if (calendarState.quarterDays.has(iso)) classes.push('quarter');
            if (calendarState.holidays.has(iso)) classes.push('holiday');
            if (iso === todayIso) classes.push('today');

            cells.push(`
                <button type="button" class="${classes.join(' ')}" data-calendar-date="${iso}">
                    <b>${day}</b>
                    <span>${calendarLabel(iso)}</span>
                </button>
            `);
        }

        container.innerHTML = cells.join('');
        container.querySelectorAll('[data-calendar-date]').forEach(button => {
            button.addEventListener('click', () => {
                const iso = button.dataset.calendarDate;
                const currentSunday = new Date(`${iso}T00:00:00`).getDay() === 0;
                const hadHoliday = calendarState.holidays.has(iso);
                const hadHalf = calendarState.halfDays.has(iso);
                const hadQuarter = calendarState.quarterDays.has(iso);
                const hadWork = calendarState.workDays.has(iso);

                clearCalendarSelection(iso);

                if (currentSunday) {
                    if (!hadWork) {
                        calendarState.workDays.add(iso);
                    }
                } else if (hadHoliday) {
                    calendarState.halfDays.add(iso);
                } else if (hadHalf) {
                    calendarState.quarterDays.add(iso);
                } else if (hadQuarter) {
                    calendarState.workDays.add(iso);
                } else if (hadWork) {
                    // Kembali ke default kerja
                } else {
                    calendarState.holidays.add(iso);
                }

                renderWorkdayCalendar();
            });
        });

        updateCalendarSummary();
    }

    function openWorkdayModal() {
        const modal = document.getElementById('workdayModal');
        if (!modal) return;
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
        renderWorkdayCalendar();
    }

    function closeWorkdayModal() {
        const modal = document.getElementById('workdayModal');
        if (!modal) return;
        modal.classList.remove('open');
        document.body.style.overflow = '';
    }

    function updateSmvTotalForStyle(style) {
        const container = document.querySelector(`[data-smv-container="${CSS.escape(style)}"]`);
        if (!container) return 0;
        const inputs = Array.from(container.querySelectorAll('.smv-sub-input'));
        let sum = 0;
        let anyFilled = false;
        inputs.forEach(inp => {
            const val = parseFloat(inp.value);
            if (!isNaN(val) && val > 0) {
                sum += val;
                anyFilled = true;
            }
        });
        const totalBadge = container.querySelector(`[data-total-for="${CSS.escape(style)}"] b`);
        if (totalBadge) {
            totalBadge.textContent = anyFilled ? (Math.round(sum * 1000) / 1000).toString() : '0.00';
        }
        return sum;
    }

    function renderSmvInputsForStyle(container, style, count, existingValues = []) {
        let rowsHtml = '';
        for (let i = 0; i < count; i++) {
            let val = (existingValues[i] !== undefined && existingValues[i] !== null && existingValues[i] !== '') ? existingValues[i] : '';
            if (typeof val === 'string') val = val.replace(/-/g, '');
            const label = count > 1 ? `P${i + 1}` : 'SMV';
            rowsHtml += `
                <div class="smv-process-row">
                    <span class="smv-process-label">${label}</span>
                    <input
                        type="number"
                        step="0.01"
                        min="0.001"
                        class="smv-input smv-sub-input"
                        data-style="${esc(style)}"
                        data-process-idx="${i}"
                        value="${esc(String(val))}"
                        placeholder="0.00"
                    >
                </div>
            `;
        }
        if (count > 1) {
            rowsHtml += `<div class="smv-total-badge" data-total-for="${esc(style)}">Total: <b>0.00</b></div>`;
        }
        container.innerHTML = rowsHtml;
        updateSmvTotalForStyle(style);
    }

    function handleProcessCountChange(input) {
        const style = input.dataset.processStyle;
        if (!style) return;
        const row = input.closest('tr');
        if (!row) return;

        let raw = String(input.value || '').replace(/-/g, '').trim();
        let count = parseInt(raw, 10);
        if (isNaN(count) || count < 1) count = 1;
        if (count > 20) count = 20;
        input.value = count;

        const container = row.querySelector(`[data-smv-container="${CSS.escape(style)}"]`);
        if (!container) return;

        const currentValues = Array.from(container.querySelectorAll('.smv-sub-input')).map(inp => inp.value);
        renderSmvInputsForStyle(container, style, count, currentValues);
        setStatusForStyle(style, 'Belum disimpan', 'warn');
    }

    function refreshSmvStatus() {}
    function setStatusForStyle() {}

    const smvTableState = {
        page: 1,
        pageSize: 10,
    };

    function getSmvRows() {
        return Array.from(document.querySelectorAll('#smvRows tr[data-style-row]')).filter(row => {
            const s = (row.dataset.styleRow || '').toUpperCase();
            return !s.includes('OFC') && row.dataset.isRunning !== '0';
        });
    }

    function getSmvRowText(row) {
        return [
            row.dataset.styleRow || '',
            row.textContent || '',
        ].join(' ').toLowerCase();
    }

    function renderSmvPagination(totalRows, totalPages) {
        const pagination = document.getElementById('smvPagination');
        if (!pagination) return;

        const buttons = [];
        const createButton = (label, page, disabled = false, active = false) => {
            buttons.push(`<button type="button" data-smv-page="${page}" ${disabled ? 'disabled' : ''} class="${active ? 'active' : ''}">${label}</button>`);
        };

        createButton('Prev', Math.max(1, smvTableState.page - 1), smvTableState.page <= 1);

        const windowSize = 5;
        const halfWindow = Math.floor(windowSize / 2);
        let startPage = Math.max(1, smvTableState.page - halfWindow);
        let endPage = Math.min(totalPages, startPage + windowSize - 1);
        startPage = Math.max(1, endPage - windowSize + 1);

        for (let page = startPage; page <= endPage; page++) {
            createButton(String(page), page, false, page === smvTableState.page);
        }

        createButton('Next', Math.min(totalPages, smvTableState.page + 1), smvTableState.page >= totalPages);

        pagination.innerHTML = buttons.join('');
        pagination.querySelectorAll('[data-smv-page]').forEach(button => {
            button.addEventListener('click', () => {
                if (button.disabled) return;
                smvTableState.page = Number(button.dataset.smvPage) || 1;
                renderSmvDataTable();
            });
        });
    }

    function renderSmvDataTable() {
        const searchInput = document.getElementById('smvSearch');
        const pageSizeSelect = document.getElementById('smvPageSize');
        const statusFilter = document.getElementById('smvStatusFilter');
        const statusVal = statusFilter ? statusFilter.value : 'running';
        const infoNode = document.getElementById('smvTableInfo');
        const pageInfoNode = document.getElementById('smvPageInfo');
        const emptyRow = document.getElementById('smvEmptyRow');
        const rows = getSmvRows();
        const search = String(searchInput ? searchInput.value : '').trim().toLowerCase();
        const pageSize = Math.max(1, Number(pageSizeSelect ? pageSizeSelect.value : smvTableState.pageSize) || 10);

        smvTableState.pageSize = pageSize;

        const filteredRows = rows.filter(row => {
            if (statusVal === 'running' && row.dataset.isRunning !== '1') {
                return false;
            }
            return !search || getSmvRowText(row).includes(search);
        });
        const totalRows = filteredRows.length;
        const totalPages = Math.max(1, Math.ceil(totalRows / pageSize));

        if (smvTableState.page > totalPages) {
            smvTableState.page = totalPages;
        }
        if (smvTableState.page < 1) {
            smvTableState.page = 1;
        }

        const startIndex = (smvTableState.page - 1) * pageSize;
        const endIndex = startIndex + pageSize;

        rows.forEach(row => {
            row.style.display = 'none';
        });

        filteredRows.forEach((row, index) => {
            const visible = index >= startIndex && index < endIndex;
            row.style.display = visible ? '' : 'none';
            if (visible) {
                const noCell = row.querySelector('.smv-row-number');
                if (noCell) {
                    noCell.textContent = String(index + 1);
                }
            }
        });

        if (emptyRow) {
            emptyRow.style.display = totalRows ? 'none' : '';
        }

        const fromRow = totalRows ? startIndex + 1 : 0;
        const toRow = totalRows ? Math.min(startIndex + pageSize, totalRows) : 0;

        if (infoNode) {
            infoNode.textContent = totalRows ? `${totalRows} style ditemukan` : 'Tidak ada hasil pencarian';
        }
        if (pageInfoNode) {
            pageInfoNode.textContent = totalRows ? `Menampilkan ${fromRow}-${toRow} dari ${totalRows}` : '0 style ditampilkan';
        }

        renderSmvPagination(totalRows, totalPages);
    }

    async function saveStyleSmv() {
        const message = document.getElementById('smvMessage');
        const button = document.getElementById('smvSaveButton');
        const directActualInput = document.getElementById('directActualInput');
        const directActualValue = directActualInput ? String(directActualInput.value || '').trim() : '';
        const rows = Array.from(document.querySelectorAll('#smvRows tr[data-style-row]'));

        const items = [];
        for (const row of rows) {
            const style = row.dataset.styleRow || '';
            if (!style) continue;

            const processInput = row.querySelector(`[data-process-style="${CSS.escape(style)}"]`);
            const rawProcess = processInput ? String(processInput.value || '').trim() : '';
            if (rawProcess !== '' && (isNaN(Number(rawProcess)) || Number(rawProcess) < 1)) {
                message.textContent = `Style ${style}: Jumlah proses minimal 1 dan tidak boleh bernilai minus atau 0.`;
                message.className = 'helper risk';
                if (processInput) {
                    processInput.focus();
                    processInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return;
            }
            const processCount = rawProcess !== '' && !isNaN(Number(rawProcess)) ? Math.max(1, parseInt(rawProcess, 10)) : 1;

            const subInputs = Array.from(row.querySelectorAll(`input.smv-sub-input[data-style="${CSS.escape(style)}"]`));
            const processSmvs = [];
            let anyFilled = false;
            const missingIndices = [];
            let negativeIndex = -1;

            subInputs.forEach((inp, idx) => {
                const val = String(inp.value || '').trim();
                if (val !== '') {
                    anyFilled = true;
                    const num = parseFloat(val);
                    if (!isNaN(num) && num > 0) {
                        processSmvs.push(num);
                    } else {
                        if (!isNaN(num) && num <= 0) {
                            negativeIndex = idx;
                        }
                        missingIndices.push(idx + 1);
                    }
                } else {
                    missingIndices.push(idx + 1);
                }
            });

            if (negativeIndex >= 0) {
                message.textContent = `Style ${style}: SMV Proses ${negativeIndex + 1} tidak boleh bernilai 0 atau minus.`;
                message.className = 'helper risk';
                const negInput = subInputs[negativeIndex];
                if (negInput) {
                    negInput.focus();
                    negInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return;
            }

            // Validasi: jika ada SMV yang diisi, pastikan seluruh SMV proses terisi
            if (anyFilled) {
                if (processSmvs.length < processCount || missingIndices.length > 0) {
                    message.textContent = `Style ${style}: Jumlah proses ${processCount}, maka harus memasukkan ${processCount} SMV. (Proses ${missingIndices.join(', ')} belum diisi).`;
                    message.className = 'helper risk';
                    const firstMissing = subInputs[missingIndices[0] - 1];
                    if (firstMissing) {
                        firstMissing.focus();
                        firstMissing.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    return;
                }
            }

            const totalSmv = processSmvs.length > 0 ? (Math.round(processSmvs.reduce((a, b) => a + b, 0) * 1000) / 1000) : null;

            items.push({
                style: style,
                process_count: processCount,
                process_smvs: processSmvs,
                smv: totalSmv !== null ? totalSmv : '',
            });
        }

        button.disabled = true;
        message.className = 'helper';
        message.textContent = 'Menyimpan SMV dan proses ke database...';

        const doubleMachineInput = document.getElementById('doubleMachineActiveInput');
        const doubleMachineValue = doubleMachineInput ? parseInt(doubleMachineInput.value, 10) : 2;
        const defaultCapModeInput = document.getElementById('defaultCapacityModeInput');
        const defaultCapModeValue = defaultCapModeInput ? defaultCapModeInput.value : 'mesin';

        try {
            const response = await fetch(urls.saveStyleSmv, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    items,
                    direct_actual: directActualValue,
                    double_machine_active: doubleMachineValue,
                    default_capacity_mode: defaultCapModeValue,
                })
            });
            const result = await response.json();
            if (!response.ok || !result.ok) {
                throw new Error(result.message || 'Gagal menyimpan SMV.');
            }
            message.textContent = result.message || `SMV dan proses tersimpan di database. ${Number(result.saved || 0)} baris diperbarui.`;
            message.className = 'helper good';
            refreshSmvStatus(result.styles || {});
            if (result.analytics_settings && Object.prototype.hasOwnProperty.call(result.analytics_settings, 'direct_actual')) {
                directActualState = result.analytics_settings.direct_actual === null || result.analytics_settings.direct_actual === undefined || result.analytics_settings.direct_actual === '' ? null : Number(result.analytics_settings.direct_actual);
                renderDirectActualInput();
                if (directActualState === null || directActualState === undefined || directActualState === '') {
                    localStorage.removeItem('heatDirectActual');
                } else {
                    localStorage.setItem('heatDirectActual', String(directActualState));
                }
            }
            renderSmvDataTable();
            await loadStatus();
        } catch (error) {
            message.textContent = error.message || 'Gagal menyimpan SMV.';
            message.className = 'helper risk';
        } finally {
            button.disabled = false;
        }
    }

    function calendarPayload() {
        return {
            holidays: Array.from(calendarState.holidays).sort(),
            half_days: Array.from(calendarState.halfDays).sort(),
            quarter_days: Array.from(calendarState.quarterDays).sort(),
            work_days: Array.from(calendarState.workDays).sort(),
        };
    }

    async function saveWorkdays() {
        const message = document.getElementById('workdayMessage');
        const button = document.getElementById('workdaySaveButton');
        const payload = calendarPayload();

        button.disabled = true;
        message.textContent = 'Menyimpan hari kerja...';

        try {
            const response = await fetch(urls.saveWorkdays, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(payload)
            });
            const result = await response.json();
            if (!response.ok || !result.ok) {
                throw new Error(result.message || 'Gagal menyimpan hari kerja.');
            }
            message.textContent = 'Hari kerja tersimpan.';
            await loadStatus();
        } catch (error) {
            message.textContent = error.message || 'Gagal menyimpan hari kerja.';
        } finally {
            button.disabled = false;
        }
    }

    function normalizeAnalyticsCards(cards) {
        const allowed = new Set(analyticsCardDefinitions.map(item => item.key));
        return Array.from(new Set((Array.isArray(cards) ? cards : []).filter(card => allowed.has(card))));
    }

    function renderAnalyticsSettings() {
        const selected = new Set(normalizeAnalyticsCards(analyticsSettingsState.visible_cards));
        const languageSelect = document.getElementById('analyticsLanguageSelect');
        const cardsNode = document.getElementById('analyticsCards');
        const updatedPill = document.getElementById('analyticsUpdatedPill');

        if (languageSelect) {
            languageSelect.value = analyticsSettingsState.language === 'en' ? 'en' : 'id';
        }
        if (cardsNode) {
            cardsNode.innerHTML = analyticsCardDefinitions.map(item => `
                <div class="analytics-setting-card">
                    <label>
                        <input type="checkbox" value="${item.key}" ${selected.has(item.key) ? 'checked' : ''}>
                        <span>${item.label}</span>
                    </label>
                    <small>${item.note}</small>
                </div>
            `).join('');
        }
        if (updatedPill) {
            updatedPill.textContent = analyticsSettingsState.language === 'en' ? 'EN' : 'ID';
        }
    }

    function renderDirectActualInput() {
        const input = document.getElementById('directActualInput');
        if (!input) return;
        input.value = directActualState === null || directActualState === undefined || directActualState === '' ? '' : String(directActualState);
    }

    async function saveAnalyticsSettings() {
        const message = document.getElementById('analyticsMessage');
        const button = document.getElementById('analyticsSaveButton');
        const languageSelect = document.getElementById('analyticsLanguageSelect');
        const selectedCards = Array.from(document.querySelectorAll('#analyticsCards input:checked')).map(input => input.value);
        const visibleCards = normalizeAnalyticsCards(selectedCards);

        if (!visibleCards.length) {
            message.textContent = 'Pilih minimal 1 kartu analytics.';
            return;
        }

        button.disabled = true;
        message.textContent = 'Menyimpan analytics...';

        const doubleMachineInput = document.getElementById('doubleMachineActiveInput');
        const doubleMachineValue = doubleMachineInput ? parseInt(doubleMachineInput.value, 10) : 2;
        const defaultCapModeInput = document.getElementById('defaultCapacityModeInput');
        const defaultCapModeValue = defaultCapModeInput ? defaultCapModeInput.value : 'mesin';

        try {
            const response = await fetch(urls.saveAnalyticsSettings, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    visible_cards: visibleCards,
                    language: languageSelect && languageSelect.value === 'en' ? 'en' : 'id',
                    direct_actual: directActualState === null || directActualState === undefined ? null : directActualState,
                    double_machine_active: doubleMachineValue,
                    default_capacity_mode: defaultCapModeValue,
                })
            });
            const result = await response.json();
            if (!response.ok || !result.ok) {
                throw new Error(result.message || 'Gagal menyimpan analytics.');
            }

            analyticsSettingsState = {
                visible_cards: Array.isArray(result.analytics_settings?.visible_cards) ? result.analytics_settings.visible_cards : visibleCards,
                language: result.analytics_settings?.language === 'en' ? 'en' : 'id',
            };
            if (result.analytics_settings && Object.prototype.hasOwnProperty.call(result.analytics_settings, 'direct_actual')) {
                directActualState = result.analytics_settings.direct_actual === null || result.analytics_settings.direct_actual === undefined || result.analytics_settings.direct_actual === ''
                    ? null
                    : Number(result.analytics_settings.direct_actual);
                renderDirectActualInput();
                if (directActualState === null || directActualState === undefined || directActualState === '') {
                    localStorage.removeItem('heatDirectActual');
                } else {
                    localStorage.setItem('heatDirectActual', String(directActualState));
                }
            }
            renderAnalyticsSettings();
            message.textContent = 'Analytics tersimpan.';
        } catch (error) {
            message.textContent = error.message || 'Gagal menyimpan analytics.';
        } finally {
            button.disabled = false;
        }
    }

    function resetAnalyticsSettings() {
        analyticsSettingsState = {
            visible_cards: ['production_status', 'output_achievement', 'data_accuracy'],
            language: 'id',
        };
        renderAnalyticsSettings();
        document.getElementById('analyticsMessage').textContent = '';
    }

    function resetWorkdayFields() {
        calendarState.holidays = new Set();
        calendarState.halfDays = new Set();
        calendarState.quarterDays = new Set();
        calendarState.workDays = new Set();
        renderWorkdayCalendar();
        document.getElementById('workdayMessage').textContent = '';
    }

    function setAdminSection(sectionId) {
        document.querySelectorAll('.section').forEach(section => {
            section.classList.toggle('active', section.id === sectionId);
        });
        document.querySelectorAll('[data-section]').forEach(button => {
            button.classList.toggle('active', button.dataset.section === sectionId);
        });
        // Re-apply pagination whenever SMV tab becomes visible
        if (sectionId === 'style-smv') {
            smvTableState.page = 1;
            renderSmvDataTable();
        }
        if (sectionId === 'usersSection') {
            loadUsers();
        }
    }

    async function logout() {
        try {
            await fetch(urls.portalLogout, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({})
            });
        } finally {
            window.location.href = urls.dashboard || '';
        }
    }

    document.querySelectorAll('[data-section]').forEach(button => {
        button.addEventListener('click', () => {
            setAdminSection(button.dataset.section);
            if (button.dataset.section === 'workdays') {
                openWorkdayModal();
            }
        });
    });
    document.querySelectorAll('[data-jump]').forEach(button => {
        button.addEventListener('click', () => {
            setAdminSection(button.dataset.jump);
            if (button.dataset.jump === 'workdays') {
                openWorkdayModal();
            }
        });
    });

    document.getElementById('logoutButton').addEventListener('click', logout);
    document.getElementById('refreshButton').addEventListener('click', () => loadStatus(true));
    const workdayOpenButton = document.getElementById('workdayOpenButton');
    if (workdayOpenButton) {
        workdayOpenButton.addEventListener('click', openWorkdayModal);
    }
    const workdayOpenButtonInline = document.getElementById('workdayOpenButtonInline');
    if (workdayOpenButtonInline) {
        workdayOpenButtonInline.addEventListener('click', openWorkdayModal);
    }
    const overviewOpenCalendar = document.getElementById('overviewOpenCalendar');
    if (overviewOpenCalendar) {
        overviewOpenCalendar.addEventListener('click', openWorkdayModal);
    }
    document.getElementById('smvSaveButton').addEventListener('click', saveStyleSmv);
    document.getElementById('smvResetButton').addEventListener('click', () => {
        document.querySelectorAll('#smvRows tr[data-style-row]').forEach(row => {
            const style = row.dataset.styleRow;
            if (!style) return;
            const processInput = row.querySelector('.smv-process-input');
            if (processInput) processInput.value = '1';
            const container = row.querySelector(`[data-smv-container="${CSS.escape(style)}"]`);
            if (container) {
                renderSmvInputsForStyle(container, style, 1, ['']);
            }
        });
        smvTableState.page = 1;
        renderSmvDataTable();
        const msg = document.getElementById('smvMessage');
        if (msg) {
            msg.textContent = '';
            msg.className = 'helper';
        }
    });
    const smvRows = document.getElementById('smvRows');
    if (smvRows) {
        smvRows.addEventListener('keydown', event => {
            if (event.target && (event.target.classList.contains('smv-process-input') || event.target.classList.contains('smv-sub-input'))) {
                // Blokir tombol minus (-), plus (+), dan huruf 'e' / 'E' (notasi eksponensial)
                if (event.key === '-' || event.key === 'Minus' || event.key === '+' || event.key === 'e' || event.key === 'E') {
                    event.preventDefault();
                }
            }
        });
        smvRows.addEventListener('change', event => {
            if (event.target && event.target.classList.contains('smv-process-input')) {
                handleProcessCountChange(event.target);
            }
        });
        smvRows.addEventListener('input', event => {
            if (event.target && event.target.classList.contains('smv-sub-input')) {
                let val = String(event.target.value || '');
                if (val.includes('-')) {
                    event.target.value = val.replace(/-/g, '');
                }
                const num = parseFloat(event.target.value);
                if (!isNaN(num) && num < 0) {
                    event.target.value = Math.abs(num);
                }
                const style = event.target.dataset.style;
                if (style) {
                    updateSmvTotalForStyle(style);
                }
            } else if (event.target && event.target.classList.contains('smv-process-input')) {
                let val = String(event.target.value || '');
                if (val.includes('-')) {
                    event.target.value = val.replace(/-/g, '');
                }
                const num = parseInt(event.target.value, 10);
                if (!isNaN(num) && num < 1) {
                    event.target.value = '1';
                }
                handleProcessCountChange(event.target);
            }
        });
    }
    document.getElementById('smvSearch').addEventListener('input', () => {
        smvTableState.page = 1;
        renderSmvDataTable();
    });
    document.getElementById('smvPageSize').addEventListener('change', () => {
        smvTableState.page = 1;
        renderSmvDataTable();
    });
    const smvStatusFilterEl = document.getElementById('smvStatusFilter');
    if (smvStatusFilterEl) {
        smvStatusFilterEl.addEventListener('change', () => {
            smvTableState.page = 1;
            renderSmvDataTable();
        });
    }
    document.getElementById('workdaySaveButton').addEventListener('click', saveWorkdays);
    document.getElementById('analyticsSaveButton').addEventListener('click', saveAnalyticsSettings);
    document.getElementById('analyticsResetButton').addEventListener('click', resetAnalyticsSettings);
    document.getElementById('analyticsLanguageSelect').addEventListener('change', event => {
        analyticsSettingsState.language = event.target.value === 'en' ? 'en' : 'id';
        const updatedPill = document.getElementById('analyticsUpdatedPill');
        if (updatedPill) {
            updatedPill.textContent = analyticsSettingsState.language === 'en' ? 'EN' : 'ID';
        }
    });
    document.getElementById('workdayClearButton').addEventListener('click', resetWorkdayFields);
    document.getElementById('workdayPrevButton').addEventListener('click', () => {
        calendarCursor = new Date(calendarCursor.getFullYear(), calendarCursor.getMonth() - 1, 1);
        renderWorkdayCalendar();
    });
    document.getElementById('workdayNextButton').addEventListener('click', () => {
        calendarCursor = new Date(calendarCursor.getFullYear(), calendarCursor.getMonth() + 1, 1);
        renderWorkdayCalendar();
    });
    document.getElementById('workdayModalClose').addEventListener('click', closeWorkdayModal);
    document.getElementById('workdayModal').addEventListener('click', event => {
        if (event.target.id === 'workdayModal') {
            closeWorkdayModal();
        }
    });
    document.getElementById('directActualInput').addEventListener('input', event => {
        const value = String(event.target.value || '').trim();
        directActualState = value === '' ? null : Number(value);
    });
    const doubleMachineSelect = document.getElementById('doubleMachineActiveInput');
    if (doubleMachineSelect) {
        doubleMachineSelect.addEventListener('change', () => {
            if (currentDashboard) {
                renderStats({ dashboard_data: currentDashboard });
            }
        });
    }

    function attachDateRangeListeners(fromId, toId) {
        const fromEl = document.getElementById(fromId);
        const toEl = document.getElementById(toId);
        if (fromEl) {
            fromEl.addEventListener('change', () => {
                let fromVal = fromEl.value;
                let toVal = toEl ? toEl.value : fromVal;
                if (toVal < fromVal) toVal = fromVal;
                handleDateChange(fromVal, toVal);
            });
        }
        if (toEl) {
            toEl.addEventListener('change', () => {
                let toVal = toEl.value;
                let fromVal = fromEl ? fromEl.value : toVal;
                if (fromVal > toVal) fromVal = toVal;
                handleDateChange(fromVal, toVal);
            });
        }
    }

    attachDateRangeListeners('adminDateFrom', 'adminDateTo');
    attachDateRangeListeners('summaryDateFrom', 'summaryDateTo');
    updatePeriodPills(selectedDateFrom, selectedDateTo);

    try { renderStats(initialPayload); } catch(e) { console.error('[admin] renderStats:', e); }
<?php
$saved_style_status_map = array();
if (is_array($style_smv_catalog) && isset($style_smv_catalog['styles'])) {
    foreach ($style_smv_catalog['styles'] as $s_item) {
        $st = isset($s_item['style']) ? $s_item['style'] : '';
        if ($st !== '' && ((isset($s_item['smv']) && $s_item['smv'] !== NULL) || (isset($s_item['process_count']) && $s_item['process_count'] !== NULL))) {
            $saved_style_status_map[$st] = TRUE;
        }
    }
}
?>
    try { refreshSmvStatus(<?= json_encode($saved_style_status_map) ?>); } catch(e) { console.error('[admin] refreshSmvStatus:', e); }
    try { renderSmvDataTable(); } catch(e) { console.error('[admin] renderSmvDataTable:', e); }
    try { renderDirectActualInput(); } catch(e) { console.error('[admin] renderDirectActualInput:', e); }
    try { renderWorkdayCalendar(); } catch(e) { console.error('[admin] renderWorkdayCalendar:', e); }
    try { renderAnalyticsSettings(); } catch(e) { console.error('[admin] renderAnalyticsSettings:', e); }
    const initialHash = window.location.hash ? window.location.hash.substring(1) : '';
    const initialSection = (initialHash && document.getElementById(initialHash)) ? initialHash : 'overviewSection';
    setAdminSection(initialSection);
    loadStatus();

    // --- User Management CRUD ---
    let usersState = [];
    let deleteTargetId = null;

    async function loadUsers() {
        const tbody = document.getElementById('usersRows');
        const info = document.getElementById('userTableInfo');
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:16px;color:var(--text-muted);">Memuat data akun...</td></tr>';
        }
        try {
            const res = await fetch(urls.users, {cache: 'no-store'});
            const data = await res.json();
            if (data?.ok && Array.isArray(data.users)) {
                usersState = data.users;
                renderUsersTable(usersState);
            } else {
                if (tbody) tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:16px;color:var(--danger);">${esc(data?.message || 'Gagal memuat akun.')}</td></tr>`;
            }
        } catch (e) {
            if (tbody) tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:16px;color:var(--danger);">Terjadi kesalahan saat memuat akun.</td></tr>';
        }
    }

    function renderUsersTable(users) {
        const tbody = document.getElementById('usersRows');
        const info = document.getElementById('userTableInfo');
        if (!tbody) return;

        const searchVal = (document.getElementById('userSearch')?.value || '').toLowerCase().trim();
        const filtered = users.filter(u => {
            if (!searchVal) return true;
            return (u.username || '').toLowerCase().includes(searchVal) ||
                   (u.full_name || '').toLowerCase().includes(searchVal) ||
                   (u.role || '').toLowerCase().includes(searchVal);
        });

        if (info) {
            info.textContent = `Total: ${filtered.length} akun`;
        }

        if (!filtered.length) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:16px;color:var(--text-muted);">Tidak ada akun ditemukan.</td></tr>';
            return;
        }

        tbody.innerHTML = filtered.map((u, idx) => {
            const isActive = Number(u.is_active) === 1;
            const isSelf = Number(u.id) === currentUserId;
            let roleBadge = '';
            if (u.role === 'admin') {
                roleBadge = '<span class="badge-pill primary">Admin</span>';
            } else if (u.role === 'planning') {
                roleBadge = '<span class="badge-pill info">Planning</span>';
            } else if (u.role === 'production') {
                roleBadge = '<span class="badge-pill warn">Production</span>';
            } else if (u.role === 'viewer') {
                roleBadge = '<span class="badge-pill">Viewer</span>';
            } else {
                roleBadge = `<span class="badge-pill">${esc(u.role || 'viewer')}</span>`;
            }
            const statusBadge = isActive
                ? `<span class="badge-pill good" style="cursor:pointer;" title="Klik untuk ubah status" onclick="toggleUserActive(${u.id}, 0)">Aktif</span>`
                : `<span class="badge-pill risk" style="cursor:pointer;" title="Klik untuk ubah status" onclick="toggleUserActive(${u.id}, 1)">Nonaktif</span>`;
            const lastLogin = u.last_login_at ? esc(u.last_login_at) : '<span style="color:var(--text-muted);">-</span>';

            return `
                <tr>
                    <td class="num">${idx + 1}</td>
                    <td>
                        <strong>${esc(u.username)}</strong>
                        ${isSelf ? '<span class="badge-pill info" style="margin-left:4px;font-size:0.6rem;">Anda</span>' : ''}
                    </td>
                    <td>${esc(u.full_name || '-')}</td>
                    <td>${roleBadge}</td>
                    <td>${statusBadge}</td>
                    <td><small style="color:var(--text-muted);">${lastLogin}</small></td>
                    <td class="num" style="white-space:nowrap;">
                        <button type="button" class="btn btn-sm btn-secondary" style="padding:0 8px;" onclick="openEditUserModal(${u.id})">Edit</button>
                        ${!isSelf ? `<button type="button" class="btn btn-sm" style="padding:0 8px;background:var(--danger);color:#ffffff;" onclick="openDeleteUserModal(${u.id}, '${esc(u.username)}')">Hapus</button>` : ''}
                    </td>
                </tr>
            `;
        }).join('');
    }

    function openAddUserModal() {
        document.getElementById('userModalTitle').textContent = 'Tambah Akun Baru';
        document.getElementById('userId').value = '';
        document.getElementById('userUsername').value = '';
        document.getElementById('userUsername').readOnly = false;
        document.getElementById('userFullName').value = '';
        document.getElementById('userRole').value = 'admin';
        document.getElementById('userIsActive').value = '1';
        document.getElementById('userPassword').value = '';
        document.getElementById('userPassword').required = true;
        document.getElementById('userPasswordLabel').textContent = 'Password *';
        document.getElementById('userPasswordHelp').style.display = 'none';
        document.getElementById('userModalMessage').textContent = '';
        document.getElementById('userModalMessage').className = '';
        document.getElementById('userModal').classList.add('open');
        document.getElementById('userUsername').focus();
    }

    function openEditUserModal(id) {
        const u = usersState.find(item => Number(item.id) === Number(id));
        if (!u) return;

        document.getElementById('userModalTitle').textContent = 'Edit Akun: ' + u.username;
        document.getElementById('userId').value = u.id;
        document.getElementById('userUsername').value = u.username;
        document.getElementById('userFullName').value = u.full_name || '';
        document.getElementById('userRole').value = u.role || 'admin';
        document.getElementById('userIsActive').value = String(u.is_active || 1);
        document.getElementById('userPassword').value = '';
        document.getElementById('userPassword').required = false;
        document.getElementById('userPasswordLabel').textContent = 'Password Baru (Opsional)';
        document.getElementById('userPasswordHelp').style.display = 'block';
        document.getElementById('userModalMessage').textContent = '';
        document.getElementById('userModalMessage').className = '';
        document.getElementById('userModal').classList.add('open');
    }

    function closeUserModal() {
        document.getElementById('userModal').classList.remove('open');
    }

    function openDeleteUserModal(id, username) {
        deleteTargetId = id;
        document.getElementById('deleteUserTargetName').textContent = username;
        document.getElementById('userDeleteMessage').textContent = '';
        document.getElementById('userDeleteModal').classList.add('open');
    }

    function closeDeleteUserModal() {
        deleteTargetId = null;
        document.getElementById('userDeleteModal').classList.remove('open');
    }

    async function toggleUserActive(id, newStatus) {
        try {
            const res = await fetch(urls.toggleUserStatus, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ id: id, is_active: newStatus })
            });
            const data = await res.json();
            if (data?.ok) {
                await loadUsers();
            } else {
                alert(data?.message || 'Gagal mengubah status akun.');
            }
        } catch (e) {
            alert('Terjadi kesalahan jaringan.');
        }
    }

    // Modal Events
    document.getElementById('btnAddNewUser')?.addEventListener('click', openAddUserModal);
    document.getElementById('userModalClose')?.addEventListener('click', closeUserModal);
    document.getElementById('userModalCancel')?.addEventListener('click', closeUserModal);
    document.getElementById('userDeleteModalClose')?.addEventListener('click', closeDeleteUserModal);
    document.getElementById('userDeleteCancel')?.addEventListener('click', closeDeleteUserModal);

    document.getElementById('userSearch')?.addEventListener('input', () => {
        renderUsersTable(usersState);
    });

    document.getElementById('userForm')?.addEventListener('submit', async event => {
        event.preventDefault();
        const msgEl = document.getElementById('userModalMessage');
        const saveBtn = document.getElementById('userModalSave');
        msgEl.textContent = 'Menyimpan...';
        msgEl.style.color = 'var(--text-muted)';
        saveBtn.disabled = true;

        const payload = {
            id: document.getElementById('userId').value,
            username: document.getElementById('userUsername').value.trim(),
            full_name: document.getElementById('userFullName').value.trim(),
            role: document.getElementById('userRole').value,
            is_active: Number(document.getElementById('userIsActive').value),
            password: document.getElementById('userPassword').value
        };

        try {
            const res = await fetch(urls.saveUser, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data?.ok) {
                msgEl.textContent = data.message || 'Akun berhasil disimpan.';
                msgEl.style.color = 'var(--success)';
                await loadUsers();
                setTimeout(() => {
                    closeUserModal();
                }, 600);
            } else {
                msgEl.textContent = data?.message || 'Gagal menyimpan akun.';
                msgEl.style.color = 'var(--danger)';
            }
        } catch (e) {
            msgEl.textContent = 'Terjadi kesalahan server/jaringan.';
            msgEl.style.color = 'var(--danger)';
        } finally {
            saveBtn.disabled = false;
        }
    });

    document.getElementById('userDeleteConfirm')?.addEventListener('click', async () => {
        if (!deleteTargetId) return;
        const msgEl = document.getElementById('userDeleteMessage');
        const btn = document.getElementById('userDeleteConfirm');
        msgEl.textContent = 'Menghapus...';
        btn.disabled = true;

        try {
            const res = await fetch(urls.deleteUser, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ id: deleteTargetId })
            });
            const data = await res.json();
            if (data?.ok) {
                await loadUsers();
                closeDeleteUserModal();
            } else {
                msgEl.textContent = data?.message || 'Gagal menghapus akun.';
            }
        } catch (e) {
            msgEl.textContent = 'Terjadi kesalahan server/jaringan.';
        } finally {
            btn.disabled = false;
        }
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            closeWorkdayModal();
            closeUserModal();
            closeDeleteUserModal();
        }
    });
</script>
</body>
</html>
