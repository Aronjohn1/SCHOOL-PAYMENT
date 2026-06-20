<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireLogin();

$page_title  = 'Payment History';
$active_page = 'history';
$cashier_id  = $_SESSION['user_id'];

$search    = trim($_GET['search'] ?? '');
$date_from = $_GET['date_from'] ?? '';
$date_to   = $_GET['date_to']   ?? '';

$where = "WHERE t.cashier_id = $cashier_id";
if ($search) {
    $q = $conn->real_escape_string($search);
    $where .= " AND (s.full_name LIKE '%$q%' OR s.student_id LIKE '%$q%' OR t.receipt_no LIKE '%$q%')";
}
if ($date_from) $where .= " AND t.payment_date >= '" . $conn->real_escape_string($date_from) . "'";
if ($date_to)   $where .= " AND t.payment_date <= '" . $conn->real_escape_string($date_to) . "'";

$transactions = $conn->query("
    SELECT t.*, s.full_name as student_name, s.student_id as stud_id, pt.type_name
    FROM transactions t
    JOIN students s      ON t.student_id      = s.id
    JOIN payment_types pt ON t.payment_type_id = pt.id
    $where
    ORDER BY t.payment_date DESC, t.payment_time DESC
");

$total_amount = (float)$conn->query("
    SELECT COALESCE(SUM(t.amount),0) as tot
    FROM transactions t
    JOIN students s      ON t.student_id      = s.id
    JOIN payment_types pt ON t.payment_type_id = pt.id
    $where
")->fetch_assoc()['tot'];

$total_count = (int)$conn->query("
    SELECT COUNT(*) as cnt
    FROM transactions t
    JOIN students s      ON t.student_id      = s.id
    JOIN payment_types pt ON t.payment_type_id = pt.id
    $where
")->fetch_assoc()['cnt'];


$cashier_name = $conn->query("SELECT full_name FROM users WHERE id=$cashier_id")->fetch_assoc()['full_name'] ?? 'Cashier';

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

<style>
@keyframes fadeUp {
    from { opacity:0; transform:translateY(14px); }
    to   { opacity:1; transform:translateY(0); }
}
.fade-up  { animation: fadeUp 0.35s ease forwards; }
.fade-up2 { animation: fadeUp 0.35s ease 0.07s forwards; opacity:0; }
.fade-up3 { animation: fadeUp 0.35s ease 0.14s forwards; opacity:0; }

.field-input {
    padding: 11px 14px 11px 40px;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    font-size: 14px;
    color: #1e293b;
    background: #fff;
    transition: all 0.2s;
    font-family: inherit;
    width: 100%;
    box-sizing: border-box;
}
.field-input:focus {
    outline: none;
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16,185,129,0.1);
}
.field-input::placeholder { color: #94a3b8; }
.field-group { position: relative; }
.field-icon {
    position: absolute; left: 13px; top: 50%;
    transform: translateY(-50%);
    color: #94a3b8; font-size: 13px; pointer-events: none;
}

tbody tr { transition: background 0.15s; }
tbody tr:hover { background: #f8fffe; }


@media print {
 
    body * { visibility: hidden; }
    #print-area, #print-area * { visibility: visible; }
    #print-area {
        position: fixed;
        inset: 0;
        padding: 24px 32px;
        background: white;
    }
    .no-print { display: none !important; }
}
</style>


<div id="print-area" style="display:none;">
    <div style="border-bottom:2px solid #064e3b; padding-bottom:14px; margin-bottom:20px;">
        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
            <div>
                <p style="font-size:10px; font-weight:800; color:#6b7280; letter-spacing:1px; text-transform:uppercase; margin:0 0 4px;">Payment History</p>
                <h1 style="font-size:22px; font-weight:900; color:#064e3b; margin:0;">School Payment  System</h1>
                <p style="font-size:12px; color:#6b7280; margin:4px 0 0;">
                    Cashier: <strong><?= htmlspecialchars($cashier_name) ?></strong>
                    <?php if ($date_from || $date_to): ?>
                    &nbsp;·&nbsp; Period:
                    <?= $date_from ? date('M j, Y', strtotime($date_from)) : 'Start' ?>
                    – <?= $date_to ? date('M j, Y', strtotime($date_to)) : 'Today' ?>
                    <?php endif; ?>
                    <?php if ($search): ?>
                    &nbsp;·&nbsp; Search: "<?= htmlspecialchars($search) ?>"
                    <?php endif; ?>
                </p>
            </div>
            <div style="text-align:right;">
                <p style="font-size:11px; color:#6b7280; margin:0;">Generated on</p>
                <p style="font-size:13px; font-weight:700; color:#064e3b; margin:2px 0 0;"><?= date('F j, Y · h:i A') ?></p>
            </div>
        </div>
    </div>

    <table style="width:100%; border-collapse:collapse; font-size:12px;">
        <thead>
            <tr style="background:#064e3b; color:white;">
                <th style="padding:9px 12px; text-align:left; font-weight:700;">#</th>
                <th style="padding:9px 12px; text-align:left; font-weight:700;">Receipt No.</th>
                <th style="padding:9px 12px; text-align:left; font-weight:700;">Student</th>
                <th style="padding:9px 12px; text-align:left; font-weight:700;">Student ID</th>
                <th style="padding:9px 12px; text-align:left; font-weight:700;">Payment Type</th>
                <th style="padding:9px 12px; text-align:right; font-weight:700;">Amount</th>
                <th style="padding:9px 12px; text-align:left; font-weight:700;">Date</th>
                <th style="padding:9px 12px; text-align:left; font-weight:700;">Time</th>
            </tr>
        </thead>
        <tbody id="print-tbody">

        </tbody>
        <tfoot>
            <tr style="background:#f0fdf4; border-top:2px solid #064e3b;">
                <td colspan="5" style="padding:10px 12px; text-align:right; font-weight:800; color:#064e3b;">GRAND TOTAL</td>
                <td style="padding:10px 12px; text-align:right; font-weight:900; font-size:15px; color:#059669;">₱<?= number_format($total_amount,2) ?></td>
                <td colspan="2" style="padding:10px 12px; font-size:11px; color:#6b7280;"><?= number_format($total_count) ?> record(s)</td>
            </tr>
        </tfoot>
    </table>
</div>


<div class="fade-up no-print" style="display:flex; align-items:center; justify-content:space-between; margin-bottom:22px;">
    <div>
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:4px;">
            <div style="width:40px; height:40px; background:linear-gradient(135deg,#10b981,#059669); border-radius:12px; display:flex; align-items:center; justify-content:center;">
                <i class="fa-solid fa-clock-rotate-left" style="color:white; font-size:17px;"></i>
            </div>
            <h2 style="font-size:20px; font-weight:800; color:#064e3b; margin:0;">Payment History</h2>
        </div>
        <p style="color:#94a3b8; font-size:13px; margin:0 0 0 52px;">Your processed transactions</p>
    </div>
    <div style="text-align:right;">
        <p style="font-size:12px; color:#94a3b8; margin:0;"><i class="fa-regular fa-calendar-days" style="margin-right:5px;"></i><?= date('l, F j, Y') ?></p>
        <p style="font-size:12px; color:#10b981; font-weight:700; margin:4px 0 0;"><i class="fa-regular fa-clock" style="margin-right:5px;"></i><?= date('h:i A') ?></p>
    </div>
</div>


<div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 mb-5 fade-up no-print">
    <form method="GET">
        <div style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">


            <div style="flex:1; min-width:220px;">
                <label style="display:block; font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.8px; margin-bottom:7px;">
                    <i class="fa-solid fa-magnifying-glass" style="margin-right:4px; color:#10b981;"></i>Search
                </label>
                <div class="field-group">
                    <i class="fa-solid fa-magnifying-glass field-icon"></i>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                        placeholder="Receipt no., student name, or ID..."
                        class="field-input">
                </div>
            </div>


            <div>
                <label style="display:block; font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.8px; margin-bottom:7px;">
                    <i class="fa-regular fa-calendar" style="margin-right:4px; color:#10b981;"></i>From
                </label>
                <div class="field-group">
                    <i class="fa-regular fa-calendar field-icon"></i>
                    <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>"
                        class="field-input" style="width:170px;">
                </div>
            </div>

  
            <div>
                <label style="display:block; font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.8px; margin-bottom:7px;">
                    <i class="fa-regular fa-calendar" style="margin-right:4px; color:#10b981;"></i>To
                </label>
                <div class="field-group">
                    <i class="fa-regular fa-calendar field-icon"></i>
                    <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>"
                        class="field-input" style="width:170px;">
                </div>
            </div>

            <div style="display:flex; gap:8px;">
                <button type="submit"
                    style="display:flex; align-items:center; gap:7px; background:#059669; color:white; border:none; padding:11px 20px; border-radius:12px; font-size:14px; font-weight:700; cursor:pointer; transition:all 0.2s; font-family:inherit;">
                    <i class="fa-solid fa-filter"></i> Filter
                </button>
                <a href="history.php"
                    style="display:flex; align-items:center; gap:7px; background:#f1f5f9; color:#64748b; padding:11px 16px; border-radius:12px; font-size:14px; font-weight:600; text-decoration:none; transition:all 0.2s;">
                    <i class="fa-solid fa-rotate-left"></i> Clear
                </a>
            </div>
        </div>
    </form>
</div>



<div class="bg-white rounded-2xl border border-slate-100 shadow-sm fade-up3 no-print">
    <div style="display:flex; align-items:center; justify-content:space-between; padding:16px 24px; border-bottom:1px solid #f0fdf4;">
        <div>
            <h3 style="font-size:15px; font-weight:800; color:#064e3b; margin:0;">
                <i class="fa-solid fa-list" style="color:#10b981; margin-right:8px;"></i>My Transactions
            </h3>
            <p style="font-size:12px; color:#94a3b8; margin:3px 0 0;">
                <?= number_format($total_count) ?> record<?= $total_count != 1 ? 's' : '' ?> found
            </p>
        </div>
        <button onclick="doPrint()"
            style="display:flex; align-items:center; gap:8px; background:#f0fdf4; color:#059669; border:1.5px solid #d1fae5; padding:9px 18px; border-radius:12px; font-size:13px; font-weight:700; cursor:pointer; transition:all 0.2s; font-family:inherit;">
            <i class="fa-solid fa-print"></i> Print History
        </button>
    </div>

    <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="background:#f8fffe;">
                    <th style="padding:12px 20px; text-align:left; font-size:11px; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:0.8px;">#</th>
                    <th style="padding:12px 20px; text-align:left; font-size:11px; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:0.8px;">Receipt No.</th>
                    <th style="padding:12px 20px; text-align:left; font-size:11px; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:0.8px;">Student</th>
                    <th style="padding:12px 20px; text-align:left; font-size:11px; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:0.8px;">Payment Type</th>
                    <th style="padding:12px 20px; text-align:left; font-size:11px; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:0.8px;">Amount</th>
                    <th style="padding:12px 20px; text-align:left; font-size:11px; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:0.8px;">Date & Time</th>
                    <th style="padding:12px 20px; text-align:left; font-size:11px; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:0.8px;">Action</th>
                </tr>
            </thead>
            <tbody id="main-tbody">
            <?php $n = 0; $rows_data = []; while ($tx = $transactions->fetch_assoc()): $n++; $rows_data[] = $tx; ?>
            <tr data-receipt="<?= htmlspecialchars($tx['receipt_no']) ?>"
                data-student="<?= htmlspecialchars($tx['student_name']) ?>"
                data-stud-id="<?= htmlspecialchars($tx['stud_id']) ?>"
                data-type="<?= htmlspecialchars($tx['type_name']) ?>"
                data-amount="<?= number_format($tx['amount'],2) ?>"
                data-date="<?= date('M j, Y', strtotime($tx['payment_date'])) ?>"
                data-time="<?= date('h:i A', strtotime($tx['payment_time'])) ?>">

                <td style="padding:14px 20px; color:#94a3b8; font-size:13px;"><?= $n ?></td>
                <td style="padding:14px 20px;">
                    <span style="font-family:monospace; font-size:12px; font-weight:700; background:#f0fdf4; color:#059669; padding:4px 10px; border-radius:8px; border:1px solid #d1fae5;">
                        <?= htmlspecialchars($tx['receipt_no']) ?>
                    </span>
                </td>
                <td style="padding:14px 20px;">
                    <div style="display:flex; align-items:center; gap:10px;">
                       
                        <div>
                            <p style="margin:0; font-weight:700; color:#1e293b; font-size:14px;"><?= htmlspecialchars($tx['student_name']) ?></p>
                            <p style="margin:2px 0 0; font-size:11px; color:#94a3b8; font-family:monospace;">
                                ID-<?= htmlspecialchars($tx['stud_id']) ?>
                            </p>
                        </div>
                    </div>
                </td>
<?php $c = getTypeColor($tx['type_name'], $type_colors, $fallback_colors); ?>
<td style="padding:14px 20px;">
    <span style="font-size:12px; font-weight:700; background:<?= $c['bg'] ?>; color:<?= $c['text'] ?>; padding:4px 12px; border-radius:20px; display:inline-flex; align-items:center; gap:5px; white-space:nowrap;">
        <i class="fa-solid fa-tag" style="font-size:10px; color:<?= $c['icon'] ?>;"></i>
        <?= htmlspecialchars($tx['type_name']) ?>
    </span>
</td>
                <td style="padding:14px 20px;">
                    <span style="font-weight:900; font-size:16px; color:#059669;">₱<?= number_format($tx['amount'],2) ?></span>
                </td>
                <td style="padding:14px 20px;">
                    <p style="margin:0; font-size:13px; font-weight:600; color:#374151;">
                        <i class="fa-regular fa-calendar" style="color:#94a3b8; margin-right:5px;"></i><?= date('M j, Y', strtotime($tx['payment_date'])) ?>
                    </p>
                    <p style="margin:3px 0 0; font-size:12px; color:#94a3b8;">
                        <i class="fa-regular fa-clock" style="margin-right:5px;"></i><?= date('h:i A', strtotime($tx['payment_time'])) ?>
                    </p>
                </td>
                <td style="padding:14px 20px;">
                    <a href="receipt.php?receipt=<?= urlencode($tx['receipt_no']) ?>" target="_blank"
                        style="display:inline-flex; align-items:center; gap:6px; background:#eff6ff; color:#2563eb; padding:7px 14px; border-radius:10px; font-size:12px; font-weight:700; text-decoration:none; border:1px solid #bfdbfe; transition:all 0.15s;">
                        <i class="fa-solid fa-print"></i> Receipt
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>

            <?php if ($n === 0): ?>
            <tr>
                <td colspan="7" style="text-align:center; padding:60px 20px;">
                    <div style="width:64px; height:64px; background:#f0fdf4; border-radius:18px; display:flex; align-items:center; justify-content:center; margin:0 auto 14px;">
                        <i class="fa-solid fa-receipt" style="font-size:26px; color:#a7f3d0;"></i>
                    </div>
                    <p style="font-size:15px; font-weight:700; color:#475569; margin:0 0 6px;">No transactions found</p>
                    <p style="font-size:13px; color:#94a3b8; margin:0;">Try adjusting your filters or date range.</p>
                </td>
            </tr>
            <?php endif; ?>
            </tbody>

            <?php if ($n > 0): ?>
            <tfoot>
                <tr style="background:linear-gradient(90deg,#f0fdf4,#ecfdf5); border-top:2px solid #d1fae5;">
                    <td colspan="4" style="padding:14px 20px; text-align:right; font-size:13px; font-weight:800; color:#064e3b; text-transform:uppercase; letter-spacing:0.5px;">
                        Grand Total (<?= number_format($n) ?> records)
                    </td>
                    <td style="padding:14px 20px;">
                        <span style="font-size:20px; font-weight:900; color:#059669;">₱<?= number_format($total_amount,2) ?></span>
                    </td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<script>
function doPrint() {

    const rows = document.querySelectorAll('#main-tbody tr[data-receipt]');
    let html = '';
    rows.forEach((r, i) => {
        const bg = i % 2 === 0 ? '#fff' : '#f9fafb';
        html += `<tr style="background:${bg};">
            <td style="padding:8px 12px; border-bottom:1px solid #e5e7eb;">${i+1}</td>
            <td style="padding:8px 12px; border-bottom:1px solid #e5e7eb; font-family:monospace; font-weight:700; color:#059669;">${r.dataset.receipt}</td>
            <td style="padding:8px 12px; border-bottom:1px solid #e5e7eb; font-weight:600;">${r.dataset.student}</td>
            <td style="padding:8px 12px; border-bottom:1px solid #e5e7eb; color:#6b7280;">${r.dataset.studId}</td>
            <td style="padding:8px 12px; border-bottom:1px solid #e5e7eb;">${r.dataset.type}</td>
            <td style="padding:8px 12px; border-bottom:1px solid #e5e7eb; text-align:right; font-weight:800; color:#059669;">₱${r.dataset.amount}</td>
            <td style="padding:8px 12px; border-bottom:1px solid #e5e7eb;">${r.dataset.date}</td>
            <td style="padding:8px 12px; border-bottom:1px solid #e5e7eb;">${r.dataset.time}</td>
        </tr>`;
    });
    document.getElementById('print-tbody').innerHTML = html || '<tr><td colspan="8" style="text-align:center;padding:20px;color:#9ca3af;">No records.</td></tr>';

 
    const pa = document.getElementById('print-area');
    pa.style.display = 'block';
    window.print();
    pa.style.display = 'none';
}
</script>

<?php include 'layout_footer.php'; ?>
