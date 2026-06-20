<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireLogin();

$page_title  = 'Process Payment';
$active_page = 'process_payment';
$cashier_id  = $_SESSION['user_id'];

$msg = ''; $msg_type = '';
$student = null;

if (isset($_GET['search_student'])) {
    $q = trim($_GET['q'] ?? '');
    if ($q) {
        $q_esc  = $conn->real_escape_string($q);
        $student = $conn->query("SELECT * FROM students WHERE (student_id LIKE '%$q_esc%' OR full_name LIKE '%$q_esc%') AND status='active' LIMIT 1")->fetch_assoc();
        if (!$student) { $msg = 'No active student found for: ' . htmlspecialchars($q); $msg_type = 'error'; }
    }
}

if (isset($_GET['sid'])) {
    $sid     = intval($_GET['sid']);
    $student = $conn->query("SELECT * FROM students WHERE id=$sid AND status='active'")->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    $student_id_db  = intval($_POST['student_id_db']);
    $pay_date       = date('Y-m-d');
    $pay_time       = date('H:i:s');
    $notes_global   = trim($_POST['notes'] ?? '');
    $payment_items  = $_POST['payment_items'] ?? [];

    $valid_items = [];
    foreach ($payment_items as $item) {
        $type_id = intval($item['type_id'] ?? 0);
        $amount  = floatval($item['amount'] ?? 0);
        if ($type_id > 0 && $amount > 0) {
            $valid_items[] = ['type_id' => $type_id, 'amount' => $amount];
        }
    }

    if (empty($valid_items)) {
        $msg = 'Please select at least one payment type and enter an amount.'; $msg_type = 'error';
    } else {
        $total_paid_now = 0;
        $receipts = [];
        $all_ok = true;

        foreach ($valid_items as $item) {
            $receipt_no = 'RCP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
            $stmt = $conn->prepare("INSERT INTO transactions (receipt_no, student_id, cashier_id, payment_type_id, amount, payment_date, payment_time, notes) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->bind_param("siiidsss", $receipt_no, $student_id_db, $cashier_id, $item['type_id'], $item['amount'], $pay_date, $pay_time, $notes_global);
            if ($stmt->execute()) {
                $total_paid_now += $item['amount'];
                $receipts[] = $receipt_no;
            } else {
                $all_ok = false;
            }
        }

        if ($total_paid_now > 0) {
            $conn->query("UPDATE students SET total_paid = total_paid + $total_paid_now WHERE id = $student_id_db");
        }

        if ($all_ok && !empty($receipts)) {
            header("Location: receipt.php?receipt=" . urlencode(end($receipts)) . "&just_paid=1");
            exit();
        } else {
            $msg = 'Some payments could not be saved. Please try again.'; $msg_type = 'error';
        }
    }

    $student = $conn->query("SELECT * FROM students WHERE id=$student_id_db")->fetch_assoc();
}

$payment_types_res = $conn->query("SELECT * FROM payment_types WHERE status='active' ORDER BY type_name");
$payment_types = [];
while ($pt = $payment_types_res->fetch_assoc()) $payment_types[] = $pt;

include 'layout_header.php';
?>

<?php include 'processstyle.php'; ?>

<!-- PAGE HEADER -->
<div class="fade-up page-header" style="display:flex; align-items:center; justify-content:space-between; margin-bottom:22px;">
    <div>
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:4px;">
            <div style="width:40px; height:40px; background:linear-gradient(135deg,#10b981,#059669); border-radius:12px; display:flex; align-items:center; justify-content:center;">
                <i class="fa-solid fa-cash-register" style="color:white; font-size:17px;"></i>
            </div>
            <h2 style="font-size:20px; font-weight:800; color:#064e3b; margin:0;">Process Payment</h2>
        </div>
        <p style="color:#94a3b8; font-size:13px; margin:0 0 0 52px;">Search for a student, select payment types, and confirm</p>
    </div>
    <div style="text-align:right;">
        <p style="font-size:12px; color:#94a3b8; margin:0;"><i class="fa-regular fa-calendar-days" style="margin-right:5px;"></i><?= date('l, F j, Y') ?></p>
        <p style="font-size:12px; color:#10b981; font-weight:700; margin:4px 0 0;"><i class="fa-regular fa-clock" style="margin-right:5px;"></i><?= date('h:i A') ?></p>
    </div>
</div>


<?php if ($msg): ?>
<div class="fade-up" style="display:flex; align-items:center; gap:12px; padding:14px 18px; border-radius:14px; margin-bottom:18px;
    <?= $msg_type==='error' ? 'background:#fef2f2; border:1.5px solid #fecaca; color:#b91c1c;' : 'background:#f0fdf4; border:1.5px solid #bbf7d0; color:#065f46;' ?>">
    <i class="fa-solid <?= $msg_type==='error' ? 'fa-circle-exclamation' : 'fa-circle-check' ?>" style="font-size:16px; flex-shrink:0;"></i>
    <span style="font-size:14px; font-weight:600;"><?= $msg ?></span>
</div>
<?php endif; ?>


<div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 mb-5 fade-up">
    <div class="section-label">
        <div class="step-badge">1</div> Find Student
    </div>
    <form method="GET">
        <input type="hidden" name="search_student" value="1">
        <div class="search-row" style="display:flex; gap:10px; align-items:center;">
            <div class="field-group" style="flex:1;">
                <i class="fa-solid fa-magnifying-glass field-icon"></i>
                <input type="text" name="q"
                    value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
                    placeholder="Enter Student ID (e.g. 2024-001) or full name..."
                    autofocus class="field-input">
            </div>
            <button type="submit" style="display:flex;align-items:center;gap:8px;background:#059669;color:white;border:none;padding:11px 22px;border-radius:12px;font-size:14px;font-weight:700;cursor:pointer;transition:all 0.2s;font-family:inherit;">
                <i class="fa-solid fa-magnifying-glass"></i> Search
            </button>
            <?php if ($student || isset($_GET['search_student'])): ?>
            <a href="process_payment.php" style="display:flex;align-items:center;gap:8px;background:#f1f5f9;color:#64748b;padding:11px 18px;border-radius:12px;font-size:14px;font-weight:600;text-decoration:none;transition:all 0.2s;">
                <i class="fa-solid fa-rotate-left"></i> Clear
            </a>
            <?php endif; ?>
        </div>
    </form>
    <?php if (isset($_GET['search_student']) && !$student && !empty($_GET['q'])): ?>
    <div style="display:flex;align-items:flex-start;gap:12px;margin-top:14px;padding:14px 16px;background:#fffbeb;border:1.5px solid #fde68a;border-radius:12px;">
        <i class="fa-solid fa-triangle-exclamation" style="color:#d97706;margin-top:1px;flex-shrink:0;"></i>
        <div>
            <p style="color:#92400e;font-size:14px;font-weight:700;margin:0;">No student found</p>
            <p style="color:#b45309;font-size:13px;margin:4px 0 0;">No active student matched "<strong><?= htmlspecialchars($_GET['q']) ?></strong>".</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if ($student):
    $pct = $student['total_fee'] > 0 ? min(100, ($student['total_paid'] / $student['total_fee'] * 100)) : 0;
    $college_levels = ['1st Year','2nd Year','3rd Year','4th Year','5th Year'];
    $is_college = in_array($student['grade_level'], $college_levels);
?>


<div class="student-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:18px; margin-bottom:18px;">


    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 fade-up2">
        <div class="section-label"><div class="step-badge">2</div> Student Information</div>
        <div style="display:flex; align-items:center; gap:16px; margin-bottom:20px;">
            <div class="avatar-ring" style="width:58px;height:58px;border-radius:16px;display:flex;align-items:center;justify-content:center;flex-shrink:0;
                background:<?= $is_college ? 'linear-gradient(135deg,#f59e0b,#d97706)' : 'linear-gradient(135deg,#10b981,#059669)' ?>;">
                <span style="color:white;font-weight:800;font-size:22px;"><?= strtoupper(substr($student['full_name'],0,1)) ?></span>
            </div>
            <div>
                <h3 style="font-size:17px;font-weight:800;color:#0f172a;margin:0 0 4px;"><?= htmlspecialchars($student['full_name']) ?></h3>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <span style="font-family:monospace;font-size:13px;font-weight:700;color:#10b981;background:#f0fdf4;padding:3px 10px;border-radius:8px;border:1px solid #d1fae5;">
                        <i class="fa-solid fa-id-card" style="margin-right:5px;font-size:11px;"></i><?= htmlspecialchars($student['student_id']) ?>
                    </span>
                    <span style="display:inline-flex;align-items:center;gap:5px;background:#f0fdf4;border:1px solid #d1fae5;color:#065f46;font-size:11px;font-weight:700;padding:4px 10px;border-radius:20px;">
                        <i class="fa-solid fa-<?= $is_college ? 'graduation-cap' : 'school' ?>" style="font-size:10px;"></i>
                        <?= $is_college ? 'College' : 'High School' ?>
                    </span>
                </div>
            </div>
        </div>
        <div class="student-detail-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <div style="background:#f8fafc;border-radius:12px;padding:12px 14px;">
                <p style="color:#94a3b8;font-size:10px;font-weight:800;letter-spacing:0.8px;text-transform:uppercase;margin:0 0 5px;">
                    <i class="fa-solid fa-chalkboard" style="margin-right:4px;"></i><?= $is_college ? 'Year / Course' : 'Grade / Section' ?>
                </p>
                <p style="color:#1e293b;font-weight:700;font-size:13px;margin:0;">
                    <?= htmlspecialchars($student['grade_level']) ?><?= $student['section'] ? ' · ' . htmlspecialchars($student['section']) : '' ?>
                </p>
            </div>
            <div style="background:#f8fafc;border-radius:12px;padding:12px 14px;">
                <p style="color:#94a3b8;font-size:10px;font-weight:800;letter-spacing:0.8px;text-transform:uppercase;margin:0 0 5px;">
                    <i class="fa-regular fa-calendar" style="margin-right:4px;"></i>School Year
                </p>
                <p style="color:#1e293b;font-weight:700;font-size:13px;margin:0;"><?= htmlspecialchars($student['school_year']) ?></p>
            </div>
        </div>
    </div>


    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 fade-up2">
        <div class="section-label"><i class="fa-solid fa-peso-sign" style="color:#10b981;"></i> Fee Summary</div>
        <div class="fee-row">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:34px;height:34px;background:#eff6ff;border-radius:10px;display:flex;align-items:center;justify-content:center;">
                    <i class="fa-solid fa-file-invoice-dollar" style="color:#3b82f6;font-size:14px;"></i>
                </div>
                <span style="color:#64748b;font-size:14px;">Total Fee</span>
            </div>
            <span style="font-weight:800;font-size:15px;color:#1e293b;">₱<?= number_format($student['total_fee'],2) ?></span>
        </div>
        <div class="fee-row">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:34px;height:34px;background:#f0fdf4;border-radius:10px;display:flex;align-items:center;justify-content:center;">
                    <i class="fa-solid fa-circle-check" style="color:#10b981;font-size:14px;"></i>
                </div>
                <span style="color:#64748b;font-size:14px;">Total Paid</span>
            </div>
            <span style="font-weight:800;font-size:15px;color:#10b981;">₱<?= number_format($student['total_paid'],2) ?></span>
        </div>
        <div style="padding:14px 0;">
            <div style="display:flex;justify-content:space-between;margin-bottom:7px;">
                <span style="font-size:12px;color:#94a3b8;font-weight:600;"><i class="fa-solid fa-chart-simple" style="margin-right:4px;"></i>Payment Progress</span>
                <span style="font-size:12px;font-weight:800;color:#10b981;"><?= number_format($pct,1) ?>%</span>
            </div>
            <div class="progress-track"><div class="progress-fill" style="width:<?= $pct ?>%;"></div></div>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-radius:14px;
            <?= $student['balance'] > 0 ? 'background:#fef2f2;border:1.5px solid #fecaca;' : 'background:#f0fdf4;border:1.5px solid #bbf7d0;' ?>">
            <div style="display:flex;align-items:center;gap:8px;">
                <i class="fa-solid <?= $student['balance'] > 0 ? 'fa-triangle-exclamation' : 'fa-circle-check' ?>"
                   style="font-size:16px;color:<?= $student['balance'] > 0 ? '#ef4444' : '#10b981' ?>;"></i>
                <span style="font-weight:800;font-size:14px;color:<?= $student['balance'] > 0 ? '#dc2626' : '#059669' ?>;">
                    <?= $student['balance'] > 0 ? 'Balance Due' : 'Fully Paid' ?>
                </span>
            </div>
            <span style="font-weight:900;font-size:20px;color:<?= $student['balance'] > 0 ? '#dc2626' : '#059669' ?>;">
                ₱<?= number_format(abs($student['balance']),2) ?>
            </span>
        </div>
    </div>
</div>


<div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 fade-up3">
    <div class="section-label"><div class="step-badge">3</div> Select Payment Types & Amounts</div>
    <p style="font-size:13px;color:#94a3b8;margin:-8px 0 18px;">
        <i class="fa-solid fa-circle-info" style="margin-right:5px;color:#a7f3d0;"></i>
        You can select <strong style="color:#059669;">multiple payment types</strong> at once. Click a type to expand and enter the amount.
    </p>

    <form method="POST" id="paymentForm">
        <input type="hidden" name="process_payment" value="1">
        <input type="hidden" name="student_id_db" value="<?= $student['id'] ?>">

  
        <div class="pt-grid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap:12px; margin-bottom:20px;">
            <?php foreach ($payment_types as $i => $pt): ?>
            <div class="pt-item" id="pt_item_<?= $pt['id'] ?>" onclick="togglePT(<?= $pt['id'] ?>)">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div id="pt_check_<?= $pt['id'] ?>"
                         style="width:22px;height:22px;border-radius:7px;border:2px solid #cbd5e1;background:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all 0.2s;">
                        <i class="fa-solid fa-check" style="font-size:11px;color:white;display:none;" id="pt_checkmark_<?= $pt['id'] ?>"></i>
                    </div>
                    <div>
                        <p style="font-size:14px;font-weight:700;color:#1e293b;margin:0;"><?= htmlspecialchars($pt['type_name']) ?></p>
                        <?php if (!empty($pt['description'])): ?>
                        <p style="font-size:11px;color:#94a3b8;margin:2px 0 0;"><?= htmlspecialchars($pt['description']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="amount-wrap" id="amt_wrap_<?= $pt['id'] ?>" onclick="event.stopPropagation()">
                    <div style="border-top:1px solid #e2e8f0;margin-top:10px;padding-top:10px;">
                        <label style="display:block;font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:0.8px;margin-bottom:6px;">
                            <i class="fa-solid fa-peso-sign" style="margin-right:4px;color:#10b981;"></i>Amount
                        </label>
                        <div class="field-group">
                            <i class="fa-solid fa-peso-sign field-icon"></i>
                            <input type="number"
                                name="payment_items[<?= $i ?>][amount]"
                                id="pt_amt_<?= $pt['id'] ?>"
                                step="0.01" min="0.01"
                                placeholder="0.00"
                                class="field-input"
                                style="padding:10px 10px 10px 36px;font-size:14px;font-weight:700;"
                                oninput="updateTotal()"
                                onclick="event.stopPropagation()">
                            <input type="hidden" name="payment_items[<?= $i ?>][type_id]" id="pt_tid_<?= $pt['id'] ?>" value="0">
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($payment_types)): ?>
        <div style="text-align:center;padding:32px;background:#f8fafc;border-radius:14px;color:#94a3b8;">
            <i class="fa-solid fa-tags fa-2x" style="margin-bottom:10px;display:block;color:#e2e8f0;"></i>
            No active payment types found. Add payment types in the admin panel.
        </div>
        <?php endif; ?>


        <div class="total-strip" id="totalStrip" style="display:none;">
            <div>
                <p style="font-size:11px;font-weight:800;color:#065f46;text-transform:uppercase;letter-spacing:0.8px;margin:0 0 2px;">
                    <i class="fa-solid fa-calculator" style="margin-right:5px;"></i>Total Payment
                </p>
                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;" id="totalBreakdown"></div>
            </div>
            <span id="totalAmount" style="font-size:24px;font-weight:900;color:#059669;">₱0.00</span>
        </div>

        <!-- Notes -->
        <div style="margin-top:16px;">
            <label style="display:block;font-size:11px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:0.8px;margin-bottom:7px;">
                <i class="fa-regular fa-note-sticky" style="margin-right:5px;color:#10b981;"></i>Notes
                <span style="color:#cbd5e1;font-weight:400;text-transform:none;letter-spacing:0;font-size:11px;">(optional)</span>
            </label>
            <div class="field-group">
                <i class="fa-regular fa-note-sticky" style="position:absolute;left:13px;top:13px;color:#94a3b8;font-size:13px;pointer-events:none;"></i>
                <textarea name="notes" rows="2" placeholder="e.g. Partial payment, 1st quarter installment..."
                    class="field-input" style="padding-top:11px;padding-left:40px;resize:none;"></textarea>
            </div>
        </div>


        <div class="summary-strip" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-top:16px;padding:14px 18px;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:14px;">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:36px;height:36px;background:linear-gradient(135deg,<?= $is_college ? '#f59e0b,#d97706' : '#10b981,#059669' ?>);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                    <span style="color:white;font-weight:800;font-size:14px;"><?= strtoupper(substr($student['full_name'],0,1)) ?></span>
                </div>
                <div>
                    <p style="font-size:14px;font-weight:800;color:#1e293b;margin:0;"><?= htmlspecialchars($student['full_name']) ?></p>
                    <p style="font-size:11px;color:#94a3b8;margin:2px 0 0;font-family:monospace;"><?= htmlspecialchars($student['student_id']) ?></p>
                </div>
            </div>
            <div style="display:flex;gap:18px;font-size:12px;color:#64748b;">
                <span><i class="fa-regular fa-calendar" style="margin-right:5px;color:#94a3b8;"></i><?= date('F j, Y') ?></span>
                <span><i class="fa-regular fa-clock" style="margin-right:5px;color:#94a3b8;"></i><?= date('h:i A') ?></span>
            </div>
        </div>

  
        <div class="action-row" style="display:flex;align-items:center;justify-content:space-between;margin-top:22px;padding-top:20px;border-top:1.5px solid #f1f5f9;">
            <a href="process_payment.php" class="btn-cancel">
                <i class="fa-solid fa-xmark"></i> Cancel
            </a>
            <button type="submit" class="btn-confirm" id="confirmBtn" disabled>
                <i class="fa-solid fa-circle-check" style="font-size:16px;"></i>
                Confirm Payment
            </button>
        </div>
    </form>
</div>

<script>
const ptMap = {
    <?php foreach ($payment_types as $i => $pt): ?>
    <?= $pt['id'] ?>: { name: <?= json_encode($pt['type_name']) ?>, index: <?= $i ?> },
    <?php endforeach; ?>
};
const selectedPTs = new Set();

function togglePT(ptId) {
    const item      = document.getElementById('pt_item_' + ptId);
    const checkBox  = document.getElementById('pt_check_' + ptId);
    const checkmark = document.getElementById('pt_checkmark_' + ptId);
    const amtWrap   = document.getElementById('amt_wrap_' + ptId);
    const amtInput  = document.getElementById('pt_amt_' + ptId);
    const tidInput  = document.getElementById('pt_tid_' + ptId);

    if (selectedPTs.has(ptId)) {
        selectedPTs.delete(ptId);
        item.classList.remove('selected');
        checkBox.style.background  = '#fff';
        checkBox.style.borderColor = '#cbd5e1';
        checkmark.style.display    = 'none';
        amtWrap.style.display      = 'none';
        amtInput.value             = '';
        tidInput.value             = '0';
    } else {
        selectedPTs.add(ptId);
        item.classList.add('selected');
        checkBox.style.background  = '#10b981';
        checkBox.style.borderColor = '#10b981';
        checkmark.style.display    = 'block';
        amtWrap.style.display      = 'block';
        tidInput.value             = ptId;
        setTimeout(() => amtInput.focus(), 50);
    }
    updateTotal();
}

function updateTotal() {
    let total = 0;
    const breakdown = [];

    selectedPTs.forEach(ptId => {
        const amt = parseFloat(document.getElementById('pt_amt_' + ptId).value) || 0;
        if (amt > 0) {
            total += amt;
            breakdown.push({ name: ptMap[ptId].name, amt });
        }
    });

    const strip      = document.getElementById('totalStrip');
    const totalEl    = document.getElementById('totalAmount');
    const breakEl    = document.getElementById('totalBreakdown');
    const confirmBtn = document.getElementById('confirmBtn');

    strip.style.display = selectedPTs.size > 0 ? 'flex' : 'none';
    totalEl.textContent = '₱' + total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');

    breakEl.innerHTML = breakdown.map(b =>
        `<span style="font-size:12px;background:#d1fae5;color:#065f46;padding:3px 10px;border-radius:20px;font-weight:700;">
            ${b.name}: ₱${b.amt.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')}
        </span>`
    ).join('');

    confirmBtn.disabled = (total <= 0 || selectedPTs.size === 0);
}
</script>

<?php else: ?>


<div class="bg-white rounded-2xl border border-slate-100 shadow-sm fade-up2" style="padding:64px 40px;text-align:center;">
    <div style="width:80px;height:80px;background:linear-gradient(135deg,#ecfdf5,#d1fae5);border-radius:24px;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;border:2px solid #a7f3d0;">
        <i class="fa-solid fa-user-graduate" style="font-size:34px;color:#10b981;"></i>
    </div>
    <h3 style="font-size:18px;font-weight:800;color:#1e293b;margin:0 0 8px;">Search for a Student</h3>
    <p style="color:#94a3b8;font-size:14px;max-width:360px;margin:0 auto 28px;line-height:1.6;">
        Enter a Student ID or full name above to load their profile and process payment.
    </p>
    <div class="empty-features" style="display:flex;justify-content:center;gap:28px;flex-wrap:wrap;">
        <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:#64748b;">
            <div style="width:28px;height:28px;background:#f0fdf4;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                <i class="fa-solid fa-layer-group" style="color:#10b981;font-size:12px;"></i>
            </div>
            Multiple payment types
        </div>
        <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:#64748b;">
            <div style="width:28px;height:28px;background:#f0fdf4;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                <i class="fa-solid fa-receipt" style="color:#10b981;font-size:12px;"></i>
            </div>
            Auto receipt generation
        </div>
        <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:#64748b;">
            <div style="width:28px;height:28px;background:#f0fdf4;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                <i class="fa-solid fa-bolt" style="color:#10b981;font-size:12px;"></i>
            </div>
            Real-time balance check
        </div>
    </div>
</div>

<?php endif; ?>

<?php include 'layout_footer.php'; ?>
