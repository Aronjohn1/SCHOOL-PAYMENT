<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireAdmin();

$page_title  = 'All Transactions';
$active_page = 'transactions';

$search      = trim($_GET['search'] ?? '');
$date_from   = $_GET['date_from'] ?? '';
$date_to     = $_GET['date_to']   ?? '';
$type_filter = intval($_GET['type_id'] ?? 0);

$where = "WHERE 1";
if ($search)      $where .= " AND (s.full_name LIKE '%".($conn->real_escape_string($search))."%' OR s.student_id LIKE '%".($conn->real_escape_string($search))."%' OR t.receipt_no LIKE '%".($conn->real_escape_string($search))."%')";
if ($date_from)   $where .= " AND t.payment_date >= '".$conn->real_escape_string($date_from)."'";
if ($date_to)     $where .= " AND t.payment_date <= '".$conn->real_escape_string($date_to)."'";
if ($type_filter) $where .= " AND t.payment_type_id = $type_filter";

$transactions = $conn->query("
    SELECT t.*, s.full_name as student_name, s.student_id as stud_id,
           pt.type_name, u.full_name as cashier_name
    FROM transactions t
    JOIN students s      ON t.student_id      = s.id
    JOIN payment_types pt ON t.payment_type_id = pt.id
    JOIN users u          ON t.cashier_id      = u.id
    $where
    ORDER BY t.payment_date DESC, t.payment_time DESC
");

$total_amount = (float)$conn->query("
    SELECT COALESCE(SUM(t.amount),0) as tot
    FROM transactions t
    JOIN students s      ON t.student_id      = s.id
    JOIN payment_types pt ON t.payment_type_id = pt.id
    JOIN users u          ON t.cashier_id      = u.id
    $where
")->fetch_assoc()['tot'];

$total_count = (int)$conn->query("
    SELECT COUNT(*) as cnt
    FROM transactions t
    JOIN students s      ON t.student_id      = s.id
    JOIN payment_types pt ON t.payment_type_id = pt.id
    JOIN users u          ON t.cashier_id      = u.id
    $where
")->fetch_assoc()['cnt'];

$today_amount = (float)$conn->query("SELECT COALESCE(SUM(amount),0) as tot FROM transactions WHERE payment_date = CURDATE()")->fetch_assoc()['tot'];

$payment_types = $conn->query("SELECT * FROM payment_types WHERE status='active' ORDER BY type_name");
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

<style>
@keyframes fadeUp {
    from { opacity:0; transform:translateY(14px); }
    to   { opacity:1; transform:translateY(0); }
}
.fade-up  { animation: fadeUp 0.35s ease forwards; }
.fade-up2 { animation: fadeUp 0.35s ease 0.07s forwards; opacity:0; }
.fade-up3 { animation: fadeUp 0.35s ease 0.14s forwards; opacity:0; }

.field-input:focus {
    outline: none;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99,102,241,0.12);
}
tbody tr { transition: background 0.15s; }


@media print {
    body * { visibility: hidden; }
    #print-area, #print-area * { visibility: visible; }
    #print-area {
        position: fixed;
        inset: 0;
        padding: 28px 36px;
        background: white;
    }
}
</style>

<!-- PRINT AREA (hidden on screen, shown on print) -->
<div id="print-area" style="display:none;">
    <div style="border-bottom:2px solid #4f46e5; padding-bottom:14px; margin-bottom:20px;">
        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
            <div>
                <p style="font-size:10px; font-weight:800; color:#6b7280; letter-spacing:1px; text-transform:uppercase; margin:0 0 4px;">
                    Transaction Records
                </p>
                <h1 style="font-size:22px; font-weight:900; color:#1e1b4b; margin:0;">School Payment  System</h1>
                <p style="font-size:12px; color:#6b7280; margin:5px 0 0;">
                    <?php if ($search): ?>Search: "<?= htmlspecialchars($search) ?>"<?php endif; ?>
                    <?php if ($date_from || $date_to): ?>
                        <?= $search ? ' · ' : '' ?>Period:
                        <?= $date_from ? date('M j, Y', strtotime($date_from)) : 'Start' ?>
                        – <?= $date_to   ? date('M j, Y', strtotime($date_to))   : 'Present' ?>
                    <?php endif; ?>
                    <?php if (!$search && !$date_from && !$date_to): ?>All Transactions<?php endif; ?>
                </p>
            </div>
            <div style="text-align:right;">
                <p style="font-size:11px; color:#6b7280; margin:0;">Generated on</p>
                <p style="font-size:13px; font-weight:700; color:#4f46e5; margin:3px 0 0;"><?= date('F j, Y · h:i A') ?></p>
            </div>
        </div>
    </div>

    <table style="width:100%; border-collapse:collapse; font-size:12px;">
        <thead>
            <tr style="background:#4f46e5; color:white;">
                <th style="padding:9px 10px; text-align:left; font-weight:700;">#</th>
                <th style="padding:9px 10px; text-align:left; font-weight:700;">Receipt No.</th>
                <th style="padding:9px 10px; text-align:left; font-weight:700;">Student</th>
                <th style="padding:9px 10px; text-align:left; font-weight:700;">Student ID</th>
                <th style="padding:9px 10px; text-align:left; font-weight:700;">Payment Type</th>
                <th style="padding:9px 10px; text-align:right; font-weight:700;">Amount</th>
                <th style="padding:9px 10px; text-align:left; font-weight:700;">Date</th>
                <th style="padding:9px 10px; text-align:left; font-weight:700;">Time</th>
                <th style="padding:9px 10px; text-align:left; font-weight:700;">Cashier</th>
            </tr>
        </thead>
        <tbody id="print-tbody">
            <!-- filled by JS before print -->
        </tbody>
        <tfoot>
            <tr style="background:#eef2ff; border-top:2px solid #4f46e5;">
                <td colspan="5" style="padding:10px; text-align:right; font-weight:800; color:#1e1b4b;">GRAND TOTAL</td>
                <td style="padding:10px; text-align:right; font-weight:900; font-size:14px; color:#4f46e5;">
                    ₱<?= number_format($total_amount,2) ?>
                </td>
                <td colspan="3" style="padding:10px; font-size:11px; color:#6b7280;">
                    <?= number_format($total_count) ?> record(s)
                </td>
            </tr>
        </tfoot>
    </table>
</div>



<!-- FILTER CARD  -->
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 mb-5 fade-up2">
    <div class="flex items-center gap-2 mb-4">
        <i class="fa-solid fa-filter text-indigo-400"></i>
        <h3 class="text-slate-700 font-bold text-sm">Filter Transactions</h3>
    </div>
    <form method="GET">
        <div class="flex gap-3 items-end flex-wrap">

            <div class="flex-1 min-w-48">
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Search</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-solid fa-magnifying-glass text-sm"></i>
                    </span>
                    <input type="text" name="search"
                        placeholder="Receipt / Student / ID..."
                        value="<?= htmlspecialchars($search) ?>"
                        class="field-input w-full pl-9 pr-4 py-2.5 border-2 border-slate-200 rounded-xl text-sm text-slate-700 placeholder-slate-300 transition-all">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">From</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-regular fa-calendar text-sm"></i>
                    </span>
                    <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>"
                        class="field-input pl-9 pr-4 py-2.5 border-2 border-slate-200 rounded-xl text-sm text-slate-700 transition-all">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">To</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-regular fa-calendar text-sm"></i>
                    </span>
                    <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>"
                        class="field-input pl-9 pr-4 py-2.5 border-2 border-slate-200 rounded-xl text-sm text-slate-700 transition-all">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Payment Type</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-solid fa-tag text-sm"></i>
                    </span>
                    <select name="type_id"
                        class="field-input pl-9 pr-4 py-2.5 border-2 border-slate-200 rounded-xl text-sm text-slate-700 transition-all appearance-none">
                        <option value="">All Types</option>
                        <?php while ($pt = $payment_types->fetch_assoc()): ?>
                        <option value="<?= $pt['id'] ?>" <?= $type_filter == $pt['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($pt['type_name']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <div class="flex gap-2">
                <button type="submit"
                    class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition">
                    <i class="fa-solid fa-filter"></i> Filter
                </button>
                <a href="transactions.php"
                    class="flex items-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm font-semibold px-4 py-2.5 rounded-xl transition">
                    <i class="fa-solid fa-xmark"></i> Clear
                </a>
            </div>
        </div>
    </form>
</div>

<!--  TABLE CARD  -->
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm fade-up3">

    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-50">
        <div>
            <h3 class="text-slate-800 font-bold text-base">
                <i class="fa-solid fa-list text-indigo-400 mr-2"></i>Transaction Records
            </h3>
            <p class="text-slate-400 text-xs mt-0.5">
                <?php if ($search || $date_from || $date_to || $type_filter): ?>
                    Filtered — <span class="text-indigo-500 font-semibold"><?= number_format($total_count) ?></span> record<?= $total_count != 1 ? 's' : '' ?>
                <?php else: ?>
                    All payment transactions
                <?php endif; ?>
            </p>
        </div>
        <button onclick="doPrint()"
            class="flex items-center gap-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 text-sm font-semibold px-4 py-2.5 rounded-xl border border-indigo-100 transition">
            <i class="fa-solid fa-print"></i> Print
        </button>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-slate-50 text-left">
                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">#</th>
                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Receipt No.</th>
                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Student</th>
                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Payment Type</th>
                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Amount</th>
                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Date & Time</th>
                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Cashier</th>
                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Notes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50" id="main-tbody">
                <?php $count = 0; while ($tx = $transactions->fetch_assoc()): $count++;
                    $notes_clean = (!empty($tx['notes']) && $tx['notes'] !== '-') ? htmlspecialchars($tx['notes']) : '—';
                ?>
                <tr class="hover:bg-slate-50"
                    data-receipt="<?= htmlspecialchars($tx['receipt_no']) ?>"
                    data-student="<?= htmlspecialchars($tx['student_name']) ?>"
                    data-stud-id="<?= htmlspecialchars($tx['stud_id']) ?>"
                    data-type="<?= htmlspecialchars($tx['type_name']) ?>"
                    data-amount="<?= number_format($tx['amount'],2) ?>"
                    data-date="<?= date('M j, Y', strtotime($tx['payment_date'])) ?>"
                    data-time="<?= date('h:i A', strtotime($tx['payment_time'])) ?>"
                    data-cashier="<?= htmlspecialchars($tx['cashier_name']) ?>">

                    <td class="px-6 py-4 text-slate-400 text-xs font-medium"><?= $count ?></td>
                    <td class="px-6 py-4">
                        <span class="font-mono text-xs font-bold text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-lg">
                            <?= htmlspecialchars($tx['receipt_no']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                           
                            <div>
                                <p class="font-semibold text-slate-800 text-sm leading-none"><?= htmlspecialchars($tx['student_name']) ?></p>
                                <p class="text-slate-400 text-xs mt-1">
                                    ID-<?= htmlspecialchars($tx['stud_id']) ?>
                                </p>
                            </div>
                        </div>
                    </td>
                 <?php $c = getTypeColor($tx['type_name'], $type_colors, $fallback_colors); ?>
<td class="px-6 py-4">
    <span style="background:<?= $c['bg'] ?>; color:<?= $c['text'] ?>;" class="text-xs font-semibold px-2.5 py-1 rounded-full inline-flex items-center gap-1 whitespace-nowrap">
        <i class="fa-solid fa-tag text-[10px]" style="color:<?= $c['icon'] ?>;"></i>
        <?= htmlspecialchars($tx['type_name']) ?>
    </span>
</td>
                    <td class="px-6 py-4">
                        <span class="text-emerald-600 font-extrabold text-base">
                            ₱<?= number_format($tx['amount'],2) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <p class="text-slate-700 text-sm font-medium">
                            <i class="fa-regular fa-calendar text-slate-300 mr-1"></i>
                            <?= date('M j, Y', strtotime($tx['payment_date'])) ?>
                        </p>
                        <p class="text-slate-400 text-xs mt-0.5">
                            <i class="fa-regular fa-clock text-slate-300 mr-1"></i>
                            <?= date('h:i A', strtotime($tx['payment_time'])) ?>
                        </p>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                           
                            <span class="text-slate-600 text-sm"><?= htmlspecialchars($tx['cashier_name']) ?></span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <?php if (!empty($tx['notes']) && $tx['notes'] !== '-'): ?>
                        <span class="text-slate-500 text-sm italic"><?= htmlspecialchars($tx['notes']) ?></span>
                        <?php else: ?>
                        <span class="text-slate-300 text-sm">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>

                <?php if ($count === 0): ?>
                <tr>
                    <td colspan="8" class="px-6 py-16 text-center text-slate-400">
                        <i class="fa-solid fa-receipt text-4xl mb-3 block text-slate-200"></i>
                        <p class="font-semibold text-slate-500 mb-1">No transactions found</p>
                        <p class="text-sm">Try adjusting your search or filters.</p>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>

            <?php if ($count > 0): ?>
            <tfoot>
                <tr class="bg-gradient-to-r from-slate-50 to-indigo-50 border-t-2 border-slate-200">
                    <td colspan="4" class="px-6 py-4 text-right text-xs font-extrabold text-slate-500 uppercase tracking-wider">
                        Grand Total (<?= number_format($count) ?> records)
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-emerald-600 font-extrabold text-lg">₱<?= number_format($total_amount,2) ?></span>
                    </td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<script src="transactions.js"></script>

<?php include 'layout_footer.php'; ?>