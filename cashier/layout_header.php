<?php

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Cashier Panel' ?> – School Payment System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        :root {
            --sidebar-bg: #064e3b;
            --sidebar-width: 250px;
            --header-h: 64px;
        }
        body { background: #f0fdf4; margin: 0; }


        .sidebar {
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            height: 100vh;
            position: fixed;
            left: 0; top: 0;
            display: flex; flex-direction: column;
            z-index: 300;
            border-right: 1px solid rgba(255,255,255,0.05);
            transition: transform 0.28s cubic-bezier(0.4,0,0.2,1);
        }
        .sidebar-logo {
            padding: 22px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            position: relative;
        }
        .sidebar-logo h1 { color: #fff; font-weight: 800; font-size: 15px; line-height: 1.2; margin: 0; }
        .sidebar-logo p  { color: rgba(255,255,255,0.4); font-size: 11px; margin: 2px 0 0; }
        .logo-badge {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, #10b981, #059669);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 12px;
            box-shadow: 0 4px 16px rgba(16,185,129,0.4);
        }
        .nav-section { padding: 14px 12px 6px; }
        .nav-label { color: rgba(255,255,255,0.25); font-size: 10px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase; padding: 0 8px; margin-bottom: 4px; }
        .nav-item {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 12px; border-radius: 10px;
            color: rgba(255,255,255,0.55);
            text-decoration: none; font-size: 13.5px; font-weight: 500;
            transition: all 0.15s; margin-bottom: 2px;
        }
        .nav-item:hover  { background: rgba(255,255,255,0.08); color: #fff; }
        .nav-item.active { background: rgba(16,185,129,0.25); color: #6ee7b7; }
        .nav-icon { width: 18px; height: 18px; flex-shrink: 0; }
        .sidebar-footer { margin-top: auto; padding: 14px 12px; border-top: 1px solid rgba(255,255,255,0.07); }


        .sidebar-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 299;
            backdrop-filter: blur(2px);
            -webkit-backdrop-filter: blur(2px);
        }
        .sidebar-overlay.show { display: block; }

    
        .btn-sidebar-close {
            display: none;
            position: absolute;
            top: 14px; right: 14px;
            width: 30px; height: 30px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 8px;
            cursor: pointer;
            align-items: center; justify-content: center;
            color: rgba(255,255,255,0.6);
            font-size: 13px;
            transition: all 0.15s;
        }
        .btn-sidebar-close:hover { background: rgba(255,255,255,0.15); color: #fff; }

        /* ── HAMBURGER (mobile only) ── */
        .btn-hamburger {
            display: none;
            align-items: center; justify-content: center;
            width: 40px; height: 40px;
            background: #f0fdf4;
            border: 1.5px solid #d1fae5;
            border-radius: 10px;
            cursor: pointer;
            color: #065f46;
            font-size: 16px;
            transition: all 0.15s;
            flex-shrink: 0;
        }
        .btn-hamburger:hover { background: #d1fae5; }

 
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: margin-left 0.28s cubic-bezier(0.4,0,0.2,1);
        }
        .topbar {
            height: var(--header-h); background: #fff;
            border-bottom: 1px solid #d1fae5;
            display: flex; align-items: center;
            padding: 0 24px; justify-content: space-between;
            position: sticky; top: 0; z-index: 50;
            gap: 12px;
        }
        .page-area { padding: 28px; }

  
        .card { background: #fff; border-radius: 16px; border: 1px solid #d1fae5; }
        .stat-card { background: #fff; border-radius: 16px; padding: 22px; border: 1px solid #d1fae5; transition: all 0.2s; }
        .stat-card:hover { box-shadow: 0 4px 20px rgba(16,185,129,0.1); transform: translateY(-1px); }


        .table-header { background: #f0fdf4; }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 13px 16px; text-align: left; font-size: 12px; font-weight: 700; color: #6b7280; letter-spacing: 0.5px; text-transform: uppercase; }
        td { padding: 13px 16px; font-size: 14px; color: #374151; border-top: 1px solid #f0fdf4; }
        tr:hover td { background: #f0fdf4; }

  
        .badge { padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-success { background: #dcfce7; color: #16a34a; }
        .badge-danger  { background: #fee2e2; color: #dc2626; }
        .badge-warning { background: #fef9c3; color: #ca8a04; }
        .badge-info    { background: #dbeafe; color: #2563eb; }
        .badge-green   { background: #d1fae5; color: #059669; }


        .btn-primary {
            background: #10b981; color: #fff;
            padding: 10px 20px; border-radius: 10px;
            font-weight: 700; font-size: 13px; border: none; cursor: pointer;
            display: inline-flex; align-items: center; gap: 6px;
            transition: all 0.15s; text-decoration: none;
        }
        .btn-primary:hover { background: #059669; box-shadow: 0 4px 14px rgba(16,185,129,0.35); }
        .btn-secondary {
            background: #f1f5f9; color: #475569;
            padding: 9px 16px; border-radius: 10px;
            font-weight: 600; font-size: 13px;
            border: 1px solid #e2e8f0; cursor: pointer;
            display: inline-flex; align-items: center; gap: 6px;
            transition: all 0.15s; text-decoration: none;
        }
        .btn-secondary:hover { background: #e2e8f0; }
        .btn-blue {
            background: #3b82f6; color: #fff;
            padding: 9px 18px; border-radius: 10px;
            font-weight: 600; font-size: 13px; border: none; cursor: pointer;
            display: inline-flex; align-items: center; gap: 6px; transition: all 0.15s;
        }
        .btn-blue:hover { background: #2563eb; }


        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; color: #374151; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
        .form-control {
            width: 100%; padding: 11px 14px; border-radius: 10px;
            border: 1.5px solid #d1fae5; font-size: 14px;
            color: #1e293b; transition: all 0.15s; outline: none;
            box-sizing: border-box; font-family: inherit; background: #fff;
        }
        .form-control:focus { border-color: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,0.1); }

 
        .modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.5); z-index: 999;
            align-items: center; justify-content: center;
        }
        .modal-overlay.active { display: flex; }
        .modal {
            background: #fff; border-radius: 20px;
            width: 90%; max-width: 580px; max-height: 92vh;
            overflow-y: auto; padding: 32px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.25);
        }

  
        .alert { padding: 12px 16px; border-radius: 10px; font-size: 14px; margin-bottom: 16px; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error   { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

        
        .search-wrap { position: relative; }
        .search-wrap svg { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af; }
        .search-input {
            padding: 11px 14px 11px 40px; border-radius: 10px;
            border: 1.5px solid #d1fae5; font-size: 14px;
            outline: none; transition: all 0.15s; width: 100%;
            box-sizing: border-box; font-family: inherit;
        }
        .search-input:focus { border-color: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,0.1); }

   
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.open {
                transform: translateX(0);
                box-shadow: 8px 0 32px rgba(0,0,0,0.3);
            }
            .btn-sidebar-close {
                display: flex;
            }
            .btn-hamburger {
                display: flex;
            }
            .main-content {
                margin-left: 0 !important;
            }
            .page-area {
                padding: 16px;
            }
            .topbar {
                padding: 0 16px;
            }
        }
    </style>
</head>
<body>


<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>


<aside class="sidebar" id="sidebar">

    <div class="sidebar-logo">

        <button class="btn-sidebar-close" onclick="closeSidebar()" aria-label="Close menu">
            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    
        <h1>School Payment</h1>
        <p>Cashier Panel</p>
    </div>

    <nav class="flex-1 overflow-y-auto py-2">
        <div class="nav-section">
            <p class="nav-label">Main</p>
            <a href="dashboard.php" class="nav-item <?= ($active_page??'') === 'dashboard' ? 'active' : '' ?>" onclick="closeSidebar()">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                Dashboard
            </a>
        </div>
        <div class="nav-section">
            <p class="nav-label">Payments</p>
            <a href="process_payment.php" class="nav-item <?= ($active_page??'') === 'process_payment' ? 'active' : '' ?>" onclick="closeSidebar()">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                Process Payment
            </a>
            <a href="history.php" class="nav-item <?= ($active_page??'') === 'history' ? 'active' : '' ?>" onclick="closeSidebar()">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Payment History
            </a>
        </div>
    </nav>

    <div class="sidebar-footer">
        <div style="background:rgba(255,255,255,0.07); border-radius:12px; padding:12px; display:flex; align-items:center; gap:10px; margin-bottom:10px;">
            <div style="width:36px; height:36px; background:linear-gradient(135deg,#10b981,#059669); border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <span style="color:white; font-weight:700; font-size:14px;"><?= strtoupper(substr($_SESSION['full_name']??'C',0,1)) ?></span>
            </div>
            <div style="overflow:hidden; flex:1;">
                <p style="color:#fff; font-size:13px; font-weight:600; margin:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars($_SESSION['full_name']??'Cashier') ?></p>
                <p style="color:rgba(255,255,255,0.35); font-size:11px; margin:0;">Cashier</p>
            </div>
        </div>
        <a href="../logout.php"
           style="display:flex; align-items:center; gap:8px; padding:10px 12px; border-radius:10px; color:rgba(255,150,150,0.7); font-size:13px; font-weight:500; text-decoration:none; transition:all 0.15s;"
           onmouseover="this.style.background='rgba(239,68,68,0.1)'; this.style.color='#f87171'"
           onmouseout="this.style.background=''; this.style.color='rgba(255,150,150,0.7)'">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            Sign Out
        </a>
    </div>
</aside>


<main class="main-content">
    <div class="topbar">
        <div style="display:flex; align-items:center; gap:12px;">


            <button class="btn-hamburger" onclick="openSidebar()" aria-label="Open menu">
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <h2 style="font-size:18px; font-weight:700; color:#064e3b; margin:0;">
                <?= htmlspecialchars($page_title ?? 'Dashboard') ?>
            </h2>
        </div>

       
    </div>

    <div class="page-area">

<script>
function openSidebar() {
    document.getElementById('sidebar').classList.add('open');
    document.getElementById('sidebarOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('show');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeSidebar();
});
</script>
