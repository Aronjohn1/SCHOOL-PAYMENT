<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireAdmin();

$page_title  = 'Reports';
$active_page = 'reports';

$report_type = $_GET['report_type'] ?? 'monthly';
if (!in_array($report_type, ['daily','monthly'])) $report_type = 'monthly';

$today         = date('Y-m-d');
$current_month = date('Y-m');

$date  = (isset($_GET['date'])  && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date']))  ? $_GET['date']  : $today;
$month = (isset($_GET['month']) && preg_match('/^\d{4}-\d{2}$/',        $_GET['month'])) ? $_GET['month'] : $current_month;

if ($report_type === 'daily') {
    $d = $conn->real_escape_string($date);
    $transactions = $conn->query("
        SELECT t.*, s.full_name as student_name, s.student_id as stud_id,
               pt.type_name, u.full_name as cashier_name
        FROM transactions t
        JOIN students s  ON t.student_id      = s.id
        JOIN payment_types pt ON t.payment_type_id = pt.id
        JOIN users u      ON t.cashier_id     = u.id
        WHERE t.payment_date = '$d'
        ORDER BY t.payment_time ASC
    ");
    $total = (float)$conn->query("SELECT COALESCE(SUM(amount),0) as tot FROM transactions WHERE payment_date='$d'")->fetch_assoc()['tot'];
    $count = (int)$conn->query("SELECT COUNT(*) as cnt FROM transactions WHERE payment_date='$d'")->fetch_assoc()['cnt'];
    $pt_breakdown = $conn->query("
        SELECT pt.type_name, SUM(t.amount) as total, COUNT(t.id) as cnt
        FROM transactions t
        JOIN payment_types pt ON t.payment_type_id = pt.id
        WHERE t.payment_date = '$d'
        GROUP BY pt.type_name ORDER BY total DESC
    ");
    $period_label = date('F j, Y', strtotime($date));
} else {
    $ym = $conn->real_escape_string($month);
    $transactions = $conn->query("
        SELECT t.*, s.full_name as student_name, s.student_id as stud_id,
               pt.type_name, u.full_name as cashier_name
        FROM transactions t
        JOIN students s  ON t.student_id      = s.id
        JOIN payment_types pt ON t.payment_type_id = pt.id
        JOIN users u      ON t.cashier_id     = u.id
        WHERE DATE_FORMAT(t.payment_date,'%Y-%m') = '$ym'
        ORDER BY t.payment_date ASC, t.payment_time ASC
    ");
    $total = (float)$conn->query("SELECT COALESCE(SUM(amount),0) as tot FROM transactions WHERE DATE_FORMAT(payment_date,'%Y-%m')='$ym'")->fetch_assoc()['tot'];
    $count = (int)$conn->query("SELECT COUNT(*) as cnt FROM transactions WHERE DATE_FORMAT(payment_date,'%Y-%m')='$ym'")->fetch_assoc()['cnt'];
    $pt_breakdown = $conn->query("
        SELECT pt.type_name, SUM(t.amount) as total, COUNT(t.id) as cnt
        FROM transactions t
        JOIN payment_types pt ON t.payment_type_id = pt.id
        WHERE DATE_FORMAT(t.payment_date,'%Y-%m') = '$ym'
        GROUP BY pt.type_name ORDER BY total DESC
    ");
    $period_label = date('F Y', strtotime($month . '-01'));
}

$avg = $count > 0 ? ($total / $count) : 0;

$available_months = $conn->query("
    SELECT DISTINCT DATE_FORMAT(payment_date,'%Y-%m') as ym,
                    DATE_FORMAT(payment_date,'%M %Y')  as label
    FROM transactions ORDER BY ym DESC LIMIT 12
");

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



<?php include 'reportstyle.php';?>

<div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 mb-5 fade-up no-print">
    <div class="flex items-center gap-2 mb-4">
        <i class="fa-solid fa-chart-bar text-indigo-400"></i>
        <h3 class="text-slate-700 font-bold text-sm">Generate Report</h3>
    </div>
    <form method="GET" id="reportForm">
        <div class="flex gap-3 items-end flex-wrap filter-row">

            <!-- Report Type -->
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Report Type</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-solid fa-file-alt text-sm"></i>
                    </span>
                    <select name="report_type" onchange="switchType(this.value)"
                        class="field-input pl-9 pr-4 py-2.5 border-2 border-slate-200 rounded-xl text-sm text-slate-700 bg-white transition-all appearance-none">
                        <option value="monthly" <?= $report_type==='monthly' ? 'selected':'' ?>>Monthly Report</option>
                        <option value="daily"   <?= $report_type==='daily'   ? 'selected':'' ?>>Daily Report</option>
                    </select>
                </div>
            </div>

  
            <?php if ($report_type === 'daily'): ?>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Date</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-regular fa-calendar text-sm"></i>
                    </span>
                    <input type="date" name="date" value="<?= htmlspecialchars($date) ?>"
                        class="field-input pl-9 pr-4 py-2.5 border-2 border-slate-200 rounded-xl text-sm text-slate-700 transition-all">
                </div>
            </div>
            <?php else: ?>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Month</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-regular fa-calendar text-sm"></i>
                    </span>
                    <input type="month" name="month" value="<?= htmlspecialchars($month) ?>"
                        class="field-input pl-9 pr-4 py-2.5 border-2 border-slate-200 rounded-xl text-sm text-slate-700 transition-all">
                </div>
            </div>
            <?php endif; ?>

    
            <div class="flex gap-2 btn-row">
                <button type="submit"
                    class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition">
                    <i class="fa-solid fa-chart-bar"></i> Generate
                </button>
                <button type="button" onclick="window.print()"
                    class="flex items-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm font-semibold px-4 py-2.5 rounded-xl transition">
                    <i class="fa-solid fa-print"></i> Print
                </button>
            </div>
        </div>
    </form>
</div>


<div class="rounded-2xl p-6 mb-5 fade-up2 print-card w-[300px]  "
     style="background: linear-gradient(135deg, #1e3a5f, #1a2980, #1a2980);">
    <div class="flex items-center justify-between flex-wrap gap-4 banner-inner">
        <div>
            <p class="text-blue-200 text-xs font-semibold uppercase tracking-widest mb-1">
              
                <?= $report_type === 'daily' ? 'Daily' : 'Monthly' ?> Collection Report
            </p>
            <h2 class="text-white text-2xl font-extrabold tracking-tight"><?= $period_label ?></h2>
          
        </div>
    
    </div>
</div>


<?php
$months_list = [];
while ($am = $available_months->fetch_assoc()) $months_list[] = $am;
if ($count == 0 && !empty($months_list)):
?>
<div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 mb-5 fade-up2 flex items-start gap-3 no-print">
    <i class="fa-solid fa-circle-info text-amber-500 mt-0.5 flex-shrink-0"></i>
    <div>
        <p class="text-amber-700 font-semibold text-sm">No transactions found for this period.</p>
        <p class="text-amber-600 text-xs mt-1">Transactions exist in:
            <?php foreach ($months_list as $i => $m): ?>
                <a href="?report_type=monthly&month=<?= $m['ym'] ?>"
                   class="underline font-bold hover:text-amber-800"><?= $m['label'] ?></a><?= $i < count($months_list)-1 ? ', ' : '' ?>
            <?php endforeach; ?>
        </p>
    </div>
</div>
<?php elseif ($count == 0): ?>
<div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 mb-5 fade-up2 flex items-center gap-3 no-print">
    <i class="fa-solid fa-circle-info text-slate-400"></i>
    <p class="text-slate-500 text-sm">No transactions recorded yet in the system.</p>
</div>
<?php endif; ?>


<div class="grid grid-cols-3 gap-4 mb-5 fade-up2 summary-grid">
    <div class="bg-white rounded-2xl border border-emerald-100 shadow-sm p-5 flex  flex-col justify-center items-center text-center print-card">
     
        <p class="text-slate-400 text-xs font-semibold uppercase tracking-wider mb-1">Total Collections</p>
        <p class="text-2xl font-extrabold text-gray-700">₱<?= number_format($total, 2) ?></p>
    </div>
    <div class="bg-white rounded-2xl border border-blue-100 shadow-sm p-5 text-center print-card flex  flex-col justify-center items-center text-center">

        <p class="text-slate-400 text-xs font-semibold uppercase tracking-wider mb-1">Transactions</p>
        <p class="text-2xl font-extrabold text-gray-700 "><?= number_format($count) ?></p>
    </div>
    <div class="bg-white rounded-2xl border border-purple-100 shadow-sm p-5 text-center print-card flex  flex-col justify-center items-center text-center">
    
        <p class="text-slate-400 text-xs font-semibold uppercase tracking-wider mb-1">Average per Transaction</p>
        <p class="text-2xl font-extrabold text-gray-700 ">₱<?= number_format($avg, 2) ?></p>
    </div>
</div>


<div class="bg-white rounded-2xl border border-slate-100 shadow-sm mb-5 fade-up3 print-card">
    <div class="flex items-center gap-2 px-6 py-4 border-b border-slate-50">
   
        <h3 class="text-slate-800 font-bold text-base">Breakdown by Payment Type</h3>
    </div>
    <div class="table-wrap">
        <table class="w-full">
            <thead>
                <tr class="bg-slate-50 text-left">
                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Payment Type</th>
                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Transactions</th>
                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Total Amount</th>
                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">% of Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
            <?php
            $pt_rows = []; $has_pt = false;
            while ($pb = $pt_breakdown->fetch_assoc()) { $pt_rows[] = $pb; $has_pt = true; }
            $colors = ['indigo','blue','emerald','amber','purple','rose','cyan'];
       foreach ($pt_rows as $pb):
    $pct = $total > 0 ? ($pb['total'] / $total * 100) : 0;
    $c   = getTypeColor($pb['type_name'], $type_colors, $fallback_colors);
?>
<tr class="hover:bg-slate-50">
    <td class="px-6 py-4">
        <span style="background:<?= $c['bg'] ?>; color:<?= $c['text'] ?>;" class="text-xs font-semibold px-2.5 py-1 rounded-full inline-flex items-center gap-1">
            <i class="fa-solid fa-tag text-[10px]" style="color:<?= $c['icon'] ?>;"></i>
            <?= htmlspecialchars($pb['type_name']) ?>
        </span>
    </td>
    <td class="px-6 py-4 text-slate-600 text-sm font-semibold">
        <?= number_format($pb['cnt']) ?>
    </td>
    <td class="px-6 py-4 text-gray-700 font-extrabold text-sm">
        ₱<?= number_format($pb['total'], 2) ?>
    </td>
    <td class="px-6 py-4">
        <div class="flex items-center gap-2">
            <div class="flex-1 bg-slate-100 rounded-full h-2 max-w-24">
                <div style="width:<?= min(100, round($pct)) ?>%; background:<?= $c['icon'] ?>;" class="h-2 rounded-full"></div>
            </div>
            <span class="text-slate-500 text-xs font-semibold"><?= number_format($pct, 1) ?>%</span>
        </div>
    </td>
</tr>
<?php endforeach; ?>
            <?php if (!$has_pt): ?>
            <tr>
                <td colspan="4" class="px-6 py-10 text-center text-slate-400">
                    <i class="fa-solid fa-chart-pie text-3xl mb-2 block text-slate-200"></i>
                    No data for this period.
                </td>
            </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>


<div class="bg-white rounded-2xl border border-slate-100 shadow-sm fade-up3 print-card">
    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-50">
        <div>
            <h3 class="text-slate-800 font-bold text-base">
                <i class="fa-solid fa-list text-indigo-400 mr-2"></i>Transaction Details
            </h3>
            <p class="text-slate-400 text-xs mt-0.5">
                <?= number_format($count) ?> record<?= $count != 1 ? 's' : '' ?> for <?= $period_label ?>
            </p>
        </div>
    </div>
    <div class="table-wrap">
        <table class="w-full">
            <thead>
                <tr class="bg-slate-50 text-left">
                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">#</th>
                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Receipt No.</th>
                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Student</th>
                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Payment Type</th>
                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Amount</th>
                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">
                        <?= $report_type === 'monthly' ? 'Date & Time' : 'Time' ?>
                    </th>
                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Cashier</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
            <?php $n = 0; while ($tx = $transactions->fetch_assoc()): $n++; ?>
            <tr class="hover:bg-slate-50">
                <td class="px-6 py-4 text-slate-400 text-xs"><?= $n ?></td>
                <td class="px-6 py-4">
                    <span class="font-mono text-xs font-bold text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-lg">
                        <?= htmlspecialchars($tx['receipt_no']) ?>
                    </span>
                </td>
                <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                    
                        <div>
                            <p class="font-semibold text-slate-800 text-sm leading-none"><?= htmlspecialchars($tx['student_name']) ?></p>
                            <p class="text-slate-400 text-xs mt-0.5">
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
                    <span class="text-emerald-600 font-extrabold text-sm">
                        ₱<?= number_format($tx['amount'], 2) ?>
                    </span>
                </td>
                <td class="px-6 py-4 text-slate-500 text-sm">
                    <?php if ($report_type === 'monthly'): ?>
                    <p class="font-medium text-slate-700">
                        <i class="fa-regular fa-calendar text-slate-300 mr-1"></i>
                        <?= date('M j, Y', strtotime($tx['payment_date'])) ?>
                    </p>
                    <p class="text-xs text-slate-400 mt-0.5">
                        <i class="fa-regular fa-clock text-slate-300 mr-1"></i>
                        <?= date('h:i A', strtotime($tx['payment_time'])) ?>
                    </p>
                    <?php else: ?>
                    <span class="font-medium">
                        <i class="fa-regular fa-clock text-slate-300 mr-1"></i>
                        <?= date('h:i A', strtotime($tx['payment_time'])) ?>
                    </span>
                    <?php endif; ?>
                </td>
                <td class="px-6 py-4">
                    <div class="flex items-center gap-2">
                       
                        <span class="text-slate-600 text-sm"><?= htmlspecialchars($tx['cashier_name']) ?></span>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
            <?php if ($n === 0): ?>
            <tr>
                <td colspan="7" class="px-6 py-16 text-center text-slate-400">
                    <i class="fa-solid fa-calendar-xmark text-3xl mb-3 block text-slate-200"></i>
                    No transactions for this period.
                </td>
            </tr>
            <?php endif; ?>
            </tbody>

            <?php if ($n > 0): ?>
            <tfoot>
                <tr class="bg-gradient-to-r from-slate-50 to-emerald-50 border-t-2 border-slate-200">
                    <td colspan="4" class="px-6 py-4 text-right text-sm font-extrabold text-slate-600 uppercase tracking-wider">
                        Grand Total (<?= number_format($n) ?> records)
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-emerald-600 font-extrabold text-lg">₱<?= number_format($total, 2) ?></span>
                    </td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<script>
function switchType(type) {
    const dateVal  = '<?= htmlspecialchars($date) ?>';
    const monthVal = '<?= htmlspecialchars($month) ?>';
    if (type === 'daily') {
        window.location.href = 'reports.php?report_type=daily&date=' + dateVal;
    } else {
        window.location.href = 'reports.php?report_type=monthly&month=' + monthVal;
    }
}
</script>

<?php include 'layout_footer.php'; ?>
