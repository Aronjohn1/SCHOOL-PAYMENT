<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireLogin();

$receipt_no = trim($_GET['receipt'] ?? '');
$just_paid  = isset($_GET['just_paid']);

if (!$receipt_no) { header('Location: process_payment.php'); exit(); }

$r_esc = $conn->real_escape_string($receipt_no);
$tx = $conn->query("
    SELECT t.*, s.full_name as student_name, s.student_id as stud_id,
           s.grade_level, s.section, s.school_year, s.total_fee, s.total_paid, s.balance,
           pt.type_name, u.full_name as cashier_name
    FROM transactions t
    JOIN students s ON t.student_id = s.id
    JOIN payment_types pt ON t.payment_type_id = pt.id
    JOIN users u ON t.cashier_id = u.id
    WHERE t.receipt_no = '$r_esc'
")->fetch_assoc();

if (!$tx) { header('Location: process_payment.php'); exit(); }


?>

<?php include 'recieptstyle.php'; ?>

<?php if ($just_paid): ?>
<div style="max-width:520px; margin:0 auto 16px; background:#dcfce7; border:1px solid #bbf7d0; border-radius:12px; padding:14px 18px; color:#166534; font-weight:600; font-size:14px;">
    <i class="fa-solid fa-circle-check" style="margin-right:8px;"></i> Payment recorded successfully! Receipt is ready to print.
</div>
<?php endif; ?>

<div class="action-bar">
    <a href="process_payment.php" class="btn btn-back">← New Payment</a>
    <button onclick="window.print()" class="btn btn-print">🖨 Print Receipt</button>
</div>

<div class="receipt">
    <!-- HEADER -->
    <div class="receipt-header">
        <div class="logo">
            <svg width="26" height="26" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
            </svg>
        </div>
        <h1>School Payment System</h1>
        <p>Official Payment Receipt</p>
        <span class="receipt-badge">OFFICIAL RECEIPT</span>
    </div>

    <!-- BODY -->
    <div class="receipt-body">
        <!-- Receipt No & Date -->
        <div class="receipt-no">
            <p>Receipt Number</p>
            <h2><?= htmlspecialchars($tx['receipt_no']) ?></h2>
            <p style="margin-top:8px; font-size:13px; color:#6b7280;">
                <?= date('F j, Y', strtotime($tx['payment_date'])) ?> &nbsp;|&nbsp;
                <?= date('h:i A', strtotime($tx['payment_time'])) ?>
            </p>
        </div>

        <!-- Student Info -->
        <p class="section-title">Student Information</p>
        <div class="info-grid">
            <div class="info-item" style="grid-column:1/-1;">
                <p class="label">Full Name</p>
                <p class="value" style="font-size:16px;"><?= htmlspecialchars($tx['student_name']) ?></p>
            </div>
            <div class="info-item">
                <p class="label">Student ID</p>
                <p class="value"><?= htmlspecialchars($tx['stud_id']) ?></p>
            </div>
            <div class="info-item">
                <p class="label">Grade / Section</p>
                <p class="value"><?= htmlspecialchars($tx['grade_level']) ?> – <?= htmlspecialchars($tx['section']) ?></p>
            </div>
            <div class="info-item">
                <p class="label">School Year</p>
                <p class="value"><?= htmlspecialchars($tx['school_year']) ?></p>
            </div>
            <div class="info-item">
                <p class="label">Payment Type</p>
                <p class="value"><?= htmlspecialchars($tx['type_name']) ?></p>
            </div>
        </div>

        <?php if ($tx['notes']): ?>
        <div style="background:#fefce8; border:1px solid #fde68a; border-radius:10px; padding:12px 14px; margin-bottom:16px;">
            <p style="color:#92400e; font-size:13px; margin:0;"><strong>Note:</strong> <?= htmlspecialchars($tx['notes']) ?></p>
        </div>
        <?php endif; ?>

        <!-- Amount -->
        <div class="amount-box">
            <p>Amount Paid</p>
            <h2>₱<?= number_format($tx['amount'], 2) ?></h2>
        </div>

        <!-- Balance -->
        <div class="balance-row <?= $tx['balance'] > 0 ? 'has-balance' : 'no-balance' ?>">
            <div>
                <p style="font-size:12px; color:#6b7280; margin:0;">Remaining Balance</p>
                <p style="font-size:14px; font-weight:700; color:<?= $tx['balance'] > 0 ? '#dc2626' : '#16a34a' ?>; margin:2px 0 0;">
                    <?= $tx['balance'] > 0 ? '⚠ Has outstanding balance' : '✅ Fully Paid' ?>
                </p>
            </div>
            <p style="font-size:20px; font-weight:800; color:<?= $tx['balance'] > 0 ? '#dc2626' : '#16a34a' ?>; margin:0;">
                ₱<?= number_format($tx['balance'], 2) ?>
            </p>
        </div>

        <!-- Cashier Info -->
        <p class="section-title">Transaction Details</p>
        <div class="info-grid">
            <div class="info-item">
                <p class="label">Processed By</p>
                <p class="value"><?= htmlspecialchars($tx['cashier_name']) ?></p>
            </div>
            <div class="info-item">
                <p class="label">Date & Time</p>
                <p class="value" style="font-size:12px;"><?= date('M j, Y h:i A', strtotime($tx['payment_date'].' '.$tx['payment_time'])) ?></p>
            </div>
        </div>

        <!-- Signature Lines -->
        <div class="sig-line">
            <div class="sig-box">
                <div class="line"></div>
                <p>Cashier's Signature</p>
            </div>
            <div class="sig-box">
                <div class="line"></div>
                <p>Received By</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer-note">
            <p>This is an official receipt. Please keep for your records.</p>
            <p>Generated: <?= date('F j, Y h:i A') ?></p>
        </div>
    </div>
</div>

</body>
</html>