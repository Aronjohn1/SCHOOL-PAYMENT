<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireAdmin();

$page_title = 'Dashboard';
$active_page = 'dashboard';

// Stats
$total_collections  = $conn->query("SELECT COALESCE(SUM(amount),0) as total FROM transactions")->fetch_assoc()['total'];
$today_collections  = $conn->query("SELECT COALESCE(SUM(amount),0) as total FROM transactions WHERE payment_date = CURDATE()")->fetch_assoc()['total'];
$total_transactions = $conn->query("SELECT COUNT(*) as cnt FROM transactions")->fetch_assoc()['cnt'];
$total_students     = $conn->query("SELECT COUNT(*) as cnt FROM students WHERE status='active'")->fetch_assoc()['cnt'];
$pending_balance    = $conn->query("SELECT COALESCE(SUM(balance),0) as total FROM students WHERE status='active'")->fetch_assoc()['total'];
$total_cashiers     = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role='cashier' AND status='active'")->fetch_assoc()['cnt'];

// Monthly chart data
$monthly_data = $conn->query("
    SELECT DATE_FORMAT(payment_date,'%b %Y') as month,
           DATE_FORMAT(payment_date,'%Y-%m') as sort_key,
           SUM(amount) as total
    FROM transactions
    WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY month, sort_key ORDER BY sort_key ASC
");
$chart_labels = []; $chart_values = [];
while ($row = $monthly_data->fetch_assoc()) {
    $chart_labels[] = $row['month'];
    $chart_values[] = (float)$row['total'];
}

// Payment type breakdown
$type_data = $conn->query("
    SELECT pt.type_name, SUM(t.amount) as total
    FROM transactions t
    JOIN payment_types pt ON t.payment_type_id = pt.id
    GROUP BY pt.type_name ORDER BY total DESC LIMIT 5
");
$type_labels = []; $type_values = [];
while ($row = $type_data->fetch_assoc()) {
    $type_labels[] = $row['type_name'];
    $type_values[] = (float)$row['total'];
}

// Recent transactions
$recent_tx = $conn->query("
    SELECT t.receipt_no, s.full_name as student_name, s.student_id,
           pt.type_name, t.amount, t.payment_date, u.full_name as cashier_name
    FROM transactions t
    JOIN students s ON t.student_id = s.id
    JOIN payment_types pt ON t.payment_type_id = pt.id
    JOIN users u ON t.cashier_id = u.id
    ORDER BY t.created_at DESC LIMIT 8
");
//  Color map
$type_colors = [
    'Tuition Fee'       => ['bg'=>'#ede9fe','text'=>'#6d28d9','icon'=>'#7c3aed'],
    'Books & Materials' => ['bg'=>'#dbeafe','text'=>'#1d4ed8','icon'=>'#2563eb'],
    'Sports Fee'        => ['bg'=>'#d1fae5','text'=>'#065f46','icon'=>'#10b981'],
    'Technology Fee'    => ['bg'=>'#fef3c7','text'=>'#92400e','icon'=>'#f59e0b'],
    'Enrollment Fee'    => ['bg'=>'#fce7f3','text'=>'#9d174d','icon'=>'#ec4899'],
    'Miscellaneous'     => ['bg'=>'#e0f2fe','text'=>'#0369a1','icon'=>'#0ea5e9'],
    'Laboratory Fee'    => ['bg'=>'#fff1f2','text'=>'#9f1239','icon'=>'#f43f5e'],
    'Library Fee'       => ['bg'=>'#f0fdf4','text'=>'#14532d','icon'=>'#22c55e'],
    'Uniform Fee'       => ['bg'=>'#fff7ed','text'=>'#9a3412','icon'=>'#f97316'],
    'ID Fee'            => ['bg'=>'#f8fafc','text'=>'#334155','icon'=>'#64748b'],
];
$fallback_colors = [
    ['bg'=>'#f3e8ff','text'=>'#6b21a8','icon'=>'#a855f7'],
    ['bg'=>'#ffedd5','text'=>'#9a3412','icon'=>'#f97316'],
    ['bg'=>'#ecfdf5','text'=>'#065f46','icon'=>'#34d399'],
    ['bg'=>'#fdf2f8','text'=>'#831843','icon'=>'#e879f9'],
    ['bg'=>'#eff6ff','text'=>'#1e40af','icon'=>'#60a5fa'],
    ['bg'=>'#fefce8','text'=>'#713f12','icon'=>'#facc15'],
];
function getTypeColor($type_name, $type_colors, $fallback_colors) {
    if (isset($type_colors[$type_name])) return $type_colors[$type_name];
    return $fallback_colors[abs(crc32($type_name)) % count($fallback_colors)];
}


include 'layout_header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    @keyframes fadeUp {
        from { opacity:0; transform:translateY(16px); }
        to   { opacity:1; transform:translateY(0); }
    }
    .fade-up   { animation: fadeUp 0.4s ease forwards; }
    .fade-up-2 { animation: fadeUp 0.4s ease 0.05s forwards; opacity:0; }
    .fade-up-3 { animation: fadeUp 0.4s ease 0.10s forwards; opacity:0; }
    .fade-up-4 { animation: fadeUp 0.4s ease 0.15s forwards; opacity:0; }
    .fade-up-5 { animation: fadeUp 0.4s ease 0.20s forwards; opacity:0; }
    .fade-up-6 { animation: fadeUp 0.4s ease 0.25s forwards; opacity:0; }

    .stat-hover {
        transition: all 0.2s ease;
    }
    .stat-hover:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 28px rgba(0,0,0,0.08);
    }
</style>

<!-- WELCOME BANNER -->
<div class="fade-up rounded-2xl lg:items-center lg:justify-center mb-8 w-[300px] h-[50px] flex flex-col sm:flex-row items-center sm:items-center justify-center gap-4"
     style="background: linear-gradient(135deg, #1e3a5f, #1a2980, #1a2980);">
    <div>
       
        <h2 class="text-white text-2xl font-extrabold tracking-tight">
            Welcome back, Admin
        </h2>
     
    </div>

</div>

<!-- STATS GRID -->
<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 mb-6">

    <!-- Total Collections -->
    <div class="stat-hover fade-up bg-white rounded-2xl p-5 border border-slate-100 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider">Total Collections</p>
     
        </div>
        <p class="text-2xl font-extrabold text-slate-800">₱<?= number_format($total_collections, 2) ?></p>
        <p class="text-slate-400 text-xs mt-1"><i class="fa-solid fa-clock-rotate-left mr-1"></i>All time total</p>
    </div>

    <!-- Today -->
    <div class="stat-hover fade-up-2 bg-white rounded-2xl p-5 border border-slate-100 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider">Today's Collections</p>
      
        </div>
        <p class="text-2xl font-extrabold text-slate-800">₱<?= number_format($today_collections, 2) ?></p>
        <p class="text-slate-400 text-xs mt-1"><i class="fa-regular fa-calendar mr-1"></i><?= date('F j, Y') ?></p>
    </div>

    <!-- Transactions -->
    <div class="stat-hover fade-up-3 bg-white rounded-2xl p-5 border border-slate-100 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider">Total Transactions</p>
       
        </div>
        <p class="text-2xl font-extrabold text-slate-800"><?= number_format($total_transactions) ?></p>
        <p class="text-slate-400 text-xs mt-1"><i class="fa-solid fa-check-circle mr-1 text-emerald-400"></i>Recorded payments</p>
    </div>

    <!-- Students -->
    <div class="stat-hover fade-up-4 bg-white rounded-2xl p-5 border border-slate-100 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider">Active Students</p>
    
        </div>
        <p class="text-2xl font-extrabold text-slate-800"><?= number_format($total_students) ?></p>
        <p class="text-slate-400 text-xs mt-1"><i class="fa-solid fa-school mr-1 text-amber-400"></i>Currently enrolled</p>
    </div>

    <!-- Pending Balance -->
    <div class="stat-hover fade-up-5 bg-white rounded-2xl p-5 border border-red-100 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider">Pending Balance</p>
       
        </div>
        <p class="text-2xl font-extrabold text-red-500">₱<?= number_format($pending_balance, 2) ?></p>
        <p class="text-slate-400 text-xs mt-1"><i class="fa-solid fa-hourglass-half mr-1 text-red-300"></i>Uncollected fees</p>
    </div>

    <!-- Cashiers -->
    <div class="stat-hover fade-up-6 bg-white rounded-2xl p-5 border border-slate-100 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider">Active Cashiers</p>
        
        </div>
        <p class="text-2xl font-extrabold text-slate-800"><?= $total_cashiers ?></p>
        <p class="text-slate-400 text-xs mt-1"><i class="fa-solid fa-circle-check mr-1 text-sky-400"></i>Cashier accounts</p>
    </div>

</div>

<!-- CHARTS -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 fade-up md:col-span-2">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h3 class="text-slate-800 font-bold text-base">Monthly Collections</h3>
                <p class="text-slate-400 text-xs mt-0.5">Last 6 months overview</p>
            </div>
           
        </div>
        <canvas id="monthlyChart" height="100"></canvas>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 fade-up-2">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h3 class="text-slate-800 font-bold text-base">By Payment Type</h3>
                <p class="text-slate-400 text-xs mt-0.5">Breakdown per category</p>
            </div>
            <span class="text-xs font-semibold text-purple-500 bg-purple-50 px-3 py-1 rounded-full">
   
            </span>
        </div>
        <canvas id="typeChart" height="180"></canvas>
    </div>

</div>

<!-- RECENT TRANSACTIONS -->
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm fade-up">
    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-50">
        <div>
            <h3 class="text-slate-800 font-bold text-base">
                <i class="fa-solid fa-clock-rotate-left text-indigo-400 mr-2"></i>Recent Transactions
            </h3>
            <p class="text-slate-400 text-xs mt-0.5">Latest 8 payment records</p>
        </div>
        <a href="transactions.php"
           class="text-sm font-semibold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 transition px-4 py-2 rounded-xl">
            View All <i class="fa-solid fa-arrow-right ml-1 text-xs"></i>
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-slate-50 text-left">
                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Receipt No.</th>
                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Student</th>
                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Payment Type</th>
                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Amount</th>
                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Cashier</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php $cnt = 0; while ($tx = $recent_tx->fetch_assoc()): $cnt++; ?>
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-6 py-4">
                        <span class="font-mono text-xs bg-slate-100 text-slate-600 px-2.5 py-1 rounded-lg font-semibold">
                            <?= htmlspecialchars($tx['receipt_no']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <p class="font-semibold text-slate-800 text-sm"><?= htmlspecialchars($tx['student_name']) ?></p>
                        <p class="text-slate-400 text-xs mt-0.5">
                            <i class="fa-solid fa-id-card mr-1"></i><?= htmlspecialchars($tx['student_id']) ?>
                        </p>
                    </td>
               <?php $c = getTypeColor($tx['type_name'], $type_colors, $fallback_colors); ?>
<td class="px-6 py-4">
    <span style="background:<?= $c['bg'] ?>; color:<?= $c['text'] ?>;" class="text-xs font-semibold px-2.5 py-1 rounded-full inline-flex items-center gap-1 whitespace-nowrap">
        <i class="fa-solid fa-tag text-[10px]" style="color:<?= $c['icon'] ?>;"></i>
        <?= htmlspecialchars($tx['type_name']) ?>
    </span>
</td>
                    <td class="px-6 py-4">
                        <span class="text-emerald-600 font-extrabold text-sm">
                            ₱<?= number_format($tx['amount'], 2) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-slate-500 text-sm">
                        <i class="fa-regular fa-calendar mr-1 text-slate-300"></i>
                        <?= date('M j, Y', strtotime($tx['payment_date'])) ?>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 bg-indigo-100 rounded-lg flex items-center justify-center text-indigo-600 text-xs font-bold flex-shrink-0">
                                <?= strtoupper(substr($tx['cashier_name'], 0, 1)) ?>
                            </div>
                            <span class="text-slate-600 text-sm font-medium"><?= htmlspecialchars($tx['cashier_name']) ?></span>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if ($cnt === 0): ?>
                <tr>
                    <td colspan="6" class="px-6 py-16 text-center text-slate-400">
                        <i class="fa-solid fa-inbox text-3xl mb-3 block text-slate-200"></i>
                        No transactions yet.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Monthly Chart
new Chart(document.getElementById('monthlyChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
            label: 'Collections (₱)',
            data: <?= json_encode($chart_values) ?>,
            backgroundColor: 'rgba(99,102,241,0.12)',
            borderColor: '#6366f1',
            borderWidth: 2,
            borderRadius: 8,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: '#f8fafc' },
                ticks: { callback: v => '₱' + v.toLocaleString(), font: { size: 11 }, color: '#94a3b8' }
            },
            x: {
                grid: { display: false },
                ticks: { font: { size: 11 }, color: '#94a3b8' }
            }
        }
    }
});

// Doughnut Chart
new Chart(document.getElementById('typeChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($type_labels) ?>,
        datasets: [{
            data: <?= json_encode($type_values) ?>,
            backgroundColor: ['#6366f1','#10b981','#f59e0b','#ef4444','#8b5cf6'],
            borderWidth: 3,
            borderColor: '#fff',
            hoverOffset: 6,
        }]
    },
    options: {
        responsive: true,
        cutout: '65%',
        plugins: {
            legend: {
                position: 'bottom',
                labels: { font: { size: 11 }, boxWidth: 10, padding: 12, color: '#64748b' }
            }
        }
    }
});
</script>

<?php include 'layout_footer.php'; ?>