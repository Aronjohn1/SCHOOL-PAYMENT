<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireLogin();

$page_title = 'Dashboard';
$active_page = 'dashboard';
$cashier_id = $_SESSION['user_id'];

$today_total   = $conn->query("SELECT COALESCE(SUM(amount),0) as t FROM transactions WHERE cashier_id=$cashier_id AND payment_date=CURDATE()")->fetch_assoc()['t'];
$today_count   = $conn->query("SELECT COUNT(*) as c FROM transactions WHERE cashier_id=$cashier_id AND payment_date=CURDATE()")->fetch_assoc()['c'];
$month_total   = $conn->query("SELECT COALESCE(SUM(amount),0) as t FROM transactions WHERE cashier_id=$cashier_id AND DATE_FORMAT(payment_date,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m')")->fetch_assoc()['t'];
$overall_total = $conn->query("SELECT COALESCE(SUM(amount),0) as t FROM transactions WHERE cashier_id=$cashier_id")->fetch_assoc()['t'];

//  Today's total (used for % in breakdown)
$total = $today_total;

//  Breakdown by payment type (today only)
$pt_breakdown = $conn->query("
    SELECT pt.type_name, COALESCE(SUM(t.amount), 0) AS total
    FROM transactions t
    JOIN payment_types pt ON t.payment_type_id = pt.id
    WHERE t.cashier_id = $cashier_id
      AND t.payment_date = CURDATE()
    GROUP BY pt.id, pt.type_name
    ORDER BY total DESC
");

//  Recent transactions
$recent = $conn->query("
    SELECT t.receipt_no, s.full_name AS student_name, s.student_id AS stud_id,
           pt.type_name, t.amount, t.payment_date, t.payment_time
    FROM transactions t
    JOIN students s  ON t.student_id      = s.id
    JOIN payment_types pt ON t.payment_type_id = pt.id
    WHERE t.cashier_id = $cashier_id
    ORDER BY t.created_at DESC
    LIMIT 10
");

// Color map by payment type name 
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
    if (isset($type_colors[$type_name])) {
        return $type_colors[$type_name];
    }
    $idx = abs(crc32($type_name)) % count($fallback_colors);
    return $fallback_colors[$idx];
}

//  Pre-fetch breakdown rows 
$pt_rows = [];
$has_pt  = false;
while ($pb = $pt_breakdown->fetch_assoc()) {
    $pt_rows[] = $pb;
    $has_pt    = true;
}

include 'layout_header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<!-- WELCOME BANNER -->
<div class="fade-up rounded-2xl mb-8 inline-flex items-center justify-center px-6 py-4"
     style="background: linear-gradient(135deg, #064e3b, #065f46);">
    <h2 style="color:#fff; font-size:22px; font-weight:800; margin:0; text-align:center; white-space:nowrap;">
        Welcome back, <?= htmlspecialchars($_SESSION['full_name']) ?>
    </h2>
</div>

<!--STATS -->
<div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:18px; margin-bottom:24px;">
    <div class="stat-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
            <p style="color:#6b7280; font-size:12px; font-weight:600; margin:0;">Today's Collection</p>
        </div>
        <p style="font-size:24px; font-weight:800; color:#064e3b; margin:0;">₱<?= number_format($today_total,2) ?></p>
        <p style="font-size:12px; color:#9ca3af; margin:4px 0 0;"><?= date('F j, Y') ?></p>
    </div>
    <div class="stat-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
            <p style="color:#6b7280; font-size:12px; font-weight:600; margin:0;">Today's Transactions</p>
        </div>
        <p style="font-size:24px; font-weight:800; color:#064e3b; margin:0;"><?= $today_count ?></p>
        <p style="font-size:12px; color:#9ca3af; margin:4px 0 0;">Payments recorded</p>
    </div>
    <div class="stat-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
            <p style="color:#6b7280; font-size:12px; font-weight:600; margin:0;">This Month</p>
        </div>
        <p style="font-size:24px; font-weight:800; color:#064e3b; margin:0;">₱<?= number_format($month_total,2) ?></p>
        <p style="font-size:12px; color:#9ca3af; margin:4px 0 0;"><?= date('F Y') ?></p>
    </div>
    <div class="stat-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
            <p style="color:#6b7280; font-size:12px; font-weight:600; margin:0;">All Time Total</p>
        </div>
        <p style="font-size:24px; font-weight:800; color:#064e3b; margin:0;">₱<?= number_format($overall_total,2) ?></p>
        <p style="font-size:12px; color:#9ca3af; margin:4px 0 0;">My total collections</p>
    </div>
</div>

<!--RECENT TRANSACTIONS -->
<div class="card">
    <div style="padding:18px 24px; border-bottom:1px solid #d1fae5; display:flex; justify-content:space-between; align-items:center;">
        <h3 style="font-size:15px; font-weight:700; color:#064e3b; margin:0;">
            <i class="fa-solid fa-clock-rotate-left" style="color:#10b981; margin-right:7px;"></i>My Recent Transactions
        </h3>
        <a href="history.php" class="btn-secondary" style="font-size:12px; padding:7px 14px;">View All</a>
    </div>
    <div style="overflow-x:auto;">
        <table>
            <thead class="table-header">
                <tr>
                    <th>Receipt No.</th>
                    <th>Student</th>
                    <th>Payment Type</th>
                    <th>Amount</th>
                    <th>Date & Time</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php $count = 0; while ($r = $recent->fetch_assoc()): $count++;
                    $c = getTypeColor($r['type_name'], $type_colors, $fallback_colors);
                ?>
                <tr>
                    <td>
                        <span style="font-family:monospace; font-size:12px; background:#f0fdf4; padding:3px 8px; border-radius:6px; font-weight:600; color:#059669;">
                            <?= htmlspecialchars($r['receipt_no']) ?>
                        </span>
                    </td>
                    <td>
                        <p style="margin:0; font-weight:600; color:#111827;"><?= htmlspecialchars($r['student_name']) ?></p>
                        <p style="margin:0; font-size:12px; color:#9ca3af;"><?= htmlspecialchars($r['stud_id']) ?></p>
                    </td>
                    <td>
                        <span style="background:<?= $c['bg'] ?>; color:<?= $c['text'] ?>; padding:5px 12px; border-radius:20px; font-size:12px; font-weight:600; display:inline-flex; align-items:center; gap:6px; white-space:nowrap;">
                            <i class="fa-solid fa-tag" style="color:<?= $c['icon'] ?>; font-size:10px;"></i>
                            <?= htmlspecialchars($r['type_name']) ?>
                        </span>
                    </td>
                    <td style="font-weight:700; color:#16a34a; font-size:15px;">
                        ₱<?= number_format($r['amount'], 2) ?>
                    </td>
                    <td>
                        <p style="margin:0; font-size:13px;"><?= date('M j, Y', strtotime($r['payment_date'])) ?></p>
                        <p style="margin:0; font-size:12px; color:#9ca3af;"><?= date('h:i A', strtotime($r['payment_time'])) ?></p>
                    </td>
                    <td>
                        <a href="receipt.php?receipt=<?= urlencode($r['receipt_no']) ?>" target="_blank"
                           style="background:#dbeafe; color:#2563eb; padding:6px 12px; border-radius:8px; font-size:12px; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                            <i class="fa-solid fa-print"></i> Print
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>

                <?php if ($count === 0): ?>
                <tr>
                    <td colspan="6" style="text-align:center; color:#9ca3af; padding:40px;">
                        <i class="fa-solid fa-inbox" style="font-size:28px; display:block; margin-bottom:10px; color:#d1d5db;"></i>
                        No transactions yet today.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'layout_footer.php'; ?>