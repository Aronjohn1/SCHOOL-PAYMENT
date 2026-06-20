<?php
// Admin layout header - include at top of each admin page
// Usage: include 'layout_header.php'; (set $page_title and $active_page before including)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Admin Panel' ?> – School Payment System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        :root {
            --sidebar-bg: #020538;
            --sidebar-width: 260px;
            --header-h: 64px;
            --accent: #3b82f6;
        }
        body { background: #f1f5f9; margin: 0; }

        /* ── SIDEBAR ── */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            height: 100vh;
            position: fixed;
            left: 0; top: 0;
            display: flex;
            flex-direction: column;
            z-index: 300;
            border-right: 1px solid rgba(255,255,255,0.05);
            transition: transform 0.28s cubic-bezier(0.4,0,0.2,1);
        }
        .sidebar-logo {
            padding: 24px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.07);
        }
        .sidebar-logo h1 { color: #fff; font-weight: 800; font-size: 16px; line-height: 1.2; margin: 0; }
        .sidebar-logo p  { color: rgba(255,255,255,0.35); font-size: 11px; margin: 2px 0 0; }
        .logo-badge {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, #3b82f6, #06b6d4);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 12px;
            box-shadow: 0 4px 16px rgba(59,130,246,0.4);
        }

        /* ── NAV ── */
        .nav-section { padding: 16px 12px 8px; }
        .nav-label {
            color: rgba(255,255,255,0.25);
            font-size: 10px; font-weight: 700;
            letter-spacing: 1.2px; text-transform: uppercase;
            padding: 0 8px; margin-bottom: 4px;
        }
        .nav-item {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 12px; border-radius: 10px;
            color: rgba(255,255,255,0.5);
            text-decoration: none;
            font-size: 13.5px; font-weight: 500;
            transition: all 0.15s;
            margin-bottom: 2px;
        }
        .nav-item:hover  { background: rgba(255,255,255,0.07); color: #fff; }
        .nav-item.active { background: rgba(59,130,246,0.2);   color: #93c5fd; }
        .nav-item.active .nav-icon { color: #3b82f6; }
        .nav-item.active .nav-fa   { color: #60a5fa; }
        .nav-icon { width: 18px; height: 18px; flex-shrink: 0; }
        .nav-fa   { width: 18px; text-align: center; font-size: 14px; color: rgba(255,255,255,0.4); flex-shrink: 0; }

        .sidebar-footer {
            margin-top: auto;
            padding: 16px 12px;
            border-top: 1px solid rgba(255,255,255,0.07);
        }

        /* ── OVERLAY ── */
        .sidebar-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 299;
            backdrop-filter: blur(2px);
            -webkit-backdrop-filter: blur(2px);
        }
        .sidebar-overlay.show { display: block; }

        /* ── MAIN ── */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: margin-left 0.28s cubic-bezier(0.4,0,0.2,1);
        }
        .topbar {
            height: var(--header-h);
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            display: flex; align-items: center;
            padding: 0 24px;
            justify-content: space-between;
            position: sticky; top: 0; z-index: 50;
            gap: 12px;
        }
        .page-area { padding: 28px; }

        /* ── HAMBURGER ── */
        .btn-hamburger {
            display: none;
            align-items: center; justify-content: center;
            width: 40px; height: 40px;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            cursor: pointer;
            color: #475569;
            font-size: 16px;
            transition: all 0.15s;
            flex-shrink: 0;
        }
        .btn-hamburger:hover { background: #e2e8f0; color: #0f172a; }

        /* ── CLOSE BUTTON inside sidebar (mobile) ── */
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

        /* ── CARDS ── */
        .stat-card {
            background: #fff; border-radius: 16px;
            padding: 24px; border: 1px solid #e2e8f0;
            transition: all 0.2s;
        }
        .stat-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.08); transform: translateY(-1px); }
        .card { background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; }

        /* ── TABLE ── */
        .table-header { background: #f8fafc; }
        table  { width: 100%; border-collapse: collapse; }
        th { padding: 13px 16px; text-align: left; font-size: 12px; font-weight: 700; color: #94a3b8; letter-spacing: 0.5px; text-transform: uppercase; }
        td { padding: 14px 16px; font-size: 14px; color: #334155; border-top: 1px solid #f1f5f9; }
        tr:hover td { background: #f8fafc; }

        /* ── BADGES ── */
        .badge { padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-success { background: #dcfce7; color: #16a34a; }
        .badge-danger  { background: #fee2e2; color: #dc2626; }
        .badge-warning { background: #fef9c3; color: #ca8a04; }
        .badge-info    { background: #dbeafe; color: #2563eb; }

        /* ── BUTTONS ── */
        .btn-primary {
            background: #3b82f6; color: #fff;
            padding: 9px 18px; border-radius: 10px;
            font-weight: 600; font-size: 13px;
            border: none; cursor: pointer;
            display: inline-flex; align-items: center; gap: 6px;
            transition: all 0.15s; text-decoration: none;
        }
        .btn-primary:hover { background: #2563eb; box-shadow: 0 4px 14px rgba(59,130,246,0.35); }
        .btn-secondary {
            background: #f1f5f9; color: #475569;
            padding: 9px 16px; border-radius: 10px;
            font-weight: 600; font-size: 13px;
            border: 1px solid #e2e8f0; cursor: pointer;
            display: inline-flex; align-items: center; gap: 6px;
            transition: all 0.15s; text-decoration: none;
        }
        .btn-secondary:hover { background: #e2e8f0; }
        .btn-danger {
            background: #fee2e2; color: #dc2626;
            padding: 7px 14px; border-radius: 8px;
            font-weight: 600; font-size: 12px;
            border: none; cursor: pointer; transition: all 0.15s;
        }
        .btn-danger:hover { background: #fca5a5; }
        .btn-edit {
            background: #dbeafe; color: #2563eb;
            padding: 7px 14px; border-radius: 8px;
            font-weight: 600; font-size: 12px;
            border: none; cursor: pointer; transition: all 0.15s;
        }
        .btn-edit:hover { background: #bfdbfe; }

        /* ── MODAL ── */
        .modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.5); z-index: 999;
            align-items: center; justify-content: center;
        }
        .modal-overlay.active { display: flex; }
        .modal {
            background: #fff; border-radius: 20px;
            width: 90%; max-width: 540px; max-height: 90vh;
            overflow-y: auto; padding: 32px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.3);
        }

        /* ── FORMS ── */
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; color: #475569; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
        .form-control {
            width: 100%; padding: 11px 14px; border-radius: 10px;
            border: 1.5px solid #e2e8f0; font-size: 14px;
            color: #1e293b; transition: all 0.15s; outline: none;
            box-sizing: border-box; font-family: inherit;
        }
        .form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(60,103,172,0.1); }
        .search-box {
            padding: 10px 14px 10px 40px; border-radius: 10px;
            border: 1.5px solid #e2e8f0; font-size: 14px;
            outline: none; transition: all 0.15s; width: 260px;
            font-family: inherit;
        }
        .search-box:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }

        /* ── ALERTS ── */
        .alert { padding: 12px 16px; border-radius: 10px; font-size: 14px; margin-bottom: 16px; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error   { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

        /* 
           RESPONSIVE
        */
        @media (max-width: 768px) {
            /* Sidebar slides off-screen by default */
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.open {
                transform: translateX(0);
                box-shadow: 8px 0 32px rgba(0,0,0,0.35);
            }
            /* Show close X inside sidebar */
            .btn-sidebar-close {
                display: flex;
            }
            /* Main takes full width */
            .main-content {
                margin-left: 0 !important;
            }
            /* Show hamburger */
            .btn-hamburger {
                display: flex;
            }
            /* Tighter page padding */
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

<!-- Dark overlay when sidebar opens on mobile -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- ═══════════════════════════════════════
     SIDEBAR
═══════════════════════════════════════ -->
<aside class="sidebar" id="sidebar">

    <!-- Close button (visible on mobile only) -->
    <button class="btn-sidebar-close" onclick="closeSidebar()" aria-label="Close menu">
        <i class="fa-solid fa-xmark"></i>
    </button>

    <!-- Logo -->
    <div class="sidebar-logo">
       
        <h1>School Payment</h1>
        <p>Admin Panel</p>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto py-2">

        <div class="nav-section">
            <p class="nav-label">Main</p>
            <a href="dashboard.php" class="nav-item <?= ($active_page ?? '') === 'dashboard' ? 'active' : '' ?>" onclick="closeSidebar()">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7" rx="1"/>
                    <rect x="14" y="3" width="7" height="7" rx="1"/>
                    <rect x="3" y="14" width="7" height="7" rx="1"/>
                    <rect x="14" y="14" width="7" height="7" rx="1"/>
                </svg>
                Dashboard
            </a>
        </div>

        <div class="nav-section">
            <p class="nav-label">Management</p>
            <a href="students.php" class="nav-item <?= ($active_page ?? '') === 'students' ? 'active' : '' ?>" onclick="closeSidebar()">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/>
                </svg>
                Students
            </a>
            <a href="cashiers.php" class="nav-item <?= ($active_page ?? '') === 'cashiers' ? 'active' : '' ?>" onclick="closeSidebar()">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                Cashier Accounts
            </a>
            <a href="payment_types.php" class="nav-item <?= ($active_page ?? '') === 'payment_types' ? 'active' : '' ?>" onclick="closeSidebar()">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/>
                </svg>
                Payment Types
            </a>
        </div>

        <div class="nav-section">
            <p class="nav-label">Finance</p>
            <a href="transactions.php" class="nav-item <?= ($active_page ?? '') === 'transactions' ? 'active' : '' ?>" onclick="closeSidebar()">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Transactions
            </a>
            <a href="reports.php" class="nav-item <?= ($active_page ?? '') === 'reports' ? 'active' : '' ?>" onclick="closeSidebar()">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Reports
            </a>
        </div>

    </nav>

    <!-- User Footer -->
    <div class="sidebar-footer">
        <div style="background:rgba(255,255,255,0.05); border-radius:12px; padding:12px; display:flex; align-items:center; gap:10px; margin-bottom:10px;">
            <div style="width:36px; height:36px; background:linear-gradient(135deg,#3b82f6,#06b6d4); border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <span style="color:white; font-weight:700; font-size:14px;">
                    <?= strtoupper(substr($_SESSION['full_name'] ?? 'A', 0, 1)) ?>
                </span>
            </div>
            <div style="overflow:hidden; flex:1;">
                <p style="color:#fff; font-size:13px; font-weight:600; margin:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                    <?= htmlspecialchars($_SESSION['full_name'] ?? 'Admin') ?>
                </p>
                <p style="color:rgba(255,255,255,0.35); font-size:11px; margin:0; text-transform:capitalize;">
                    <?= htmlspecialchars($_SESSION['role'] ?? '') ?>
                </p>
            </div>
        </div>
        <a href="../logout.php"
           style="display:flex; align-items:center; gap:8px; padding:10px 12px; border-radius:10px; color:rgba(255,100,100,0.7); font-size:13px; font-weight:500; text-decoration:none; transition:all 0.15s;"
           onmouseover="this.style.background='rgba(239,68,68,0.1)'; this.style.color='#f87171'"
           onmouseout="this.style.background=''; this.style.color='rgba(255,100,100,0.7)'">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            Sign Out
        </a>
    </div>

</aside>

<!-- ═══════════════════════════════════════
     MAIN CONTENT
═══════════════════════════════════════ -->
<main class="main-content">

    <!-- Top Bar -->
    <div class="topbar">
        <div style="display:flex; align-items:center; gap:12px;">

            <!--  Hamburger — only visible on mobile -->
            <button class="btn-hamburger" onclick="openSidebar()" aria-label="Open menu">
                <i class="fa-solid fa-bars"></i>
            </button>

            <h2 style="font-size:18px; font-weight:700; color:#0f172a; margin:0;">
                <?= htmlspecialchars($page_title ?? 'Dashboard') ?>
            </h2>
        </div>

      
    </div>

    <!-- Page Content -->
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
// Close on ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeSidebar();
});
</script>