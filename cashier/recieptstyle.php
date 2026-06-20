<?php

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt – <?= htmlspecialchars($receipt_no) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; box-sizing: border-box; }
        body { background: #f0fdf4; margin: 0; padding: 20px; }

        .action-bar {
            max-width: 520px; margin: 0 auto 20px;
            display: flex; gap: 10px; justify-content: flex-end;
        }
        .btn { padding: 10px 20px; border-radius: 10px; font-weight: 600; font-size: 13px; cursor: pointer; border: none; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; }
        .btn-print  { background: #10b981; color: #fff; }
        .btn-print:hover  { background: #059669; }
        .btn-back   { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
        .btn-back:hover { background: #e2e8f0; }

        .receipt {
            max-width: 520px; margin: 0 auto;
            background: #fff; border-radius: 20px;
            border: 1px solid #d1fae5;
            box-shadow: 0 8px 30px rgba(16,185,129,0.1);
            overflow: hidden;
        }
        .receipt-header {
            background: linear-gradient(135deg, #064e3b, #065f46);
            padding: 28px 32px;
            text-align: center;
        }
        .receipt-header .logo {
            width: 52px; height: 52px;
            background: rgba(255,255,255,0.15);
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 12px;
        }
        .receipt-header h1 { color: #fff; font-size: 18px; font-weight: 800; margin: 0; }
        .receipt-header p  { color: rgba(255,255,255,0.6); font-size: 12px; margin: 4px 0 0; }

        .receipt-badge {
            background: rgba(255,255,255,0.1);
            display: inline-block;
            padding: 5px 14px; border-radius: 20px;
            color: #6ee7b7; font-size: 12px; font-weight: 700;
            margin-top: 12px; letter-spacing: 1px;
        }

        .receipt-body { padding: 28px 32px; }

        .receipt-no {
            text-align: center; margin-bottom: 24px;
            padding-bottom: 20px; border-bottom: 2px dashed #d1fae5;
        }
        .receipt-no p { color: #6b7280; font-size: 12px; margin: 0 0 4px; }
        .receipt-no h2 { color: #064e3b; font-size: 20px; font-weight: 800; margin: 0; font-family: monospace; }

        .section-title { color: #9ca3af; font-size: 10px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; margin: 0 0 10px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px; }
        .info-item { background: #f0fdf4; border-radius: 10px; padding: 12px; }
        .info-item .label { color: #6b7280; font-size: 11px; font-weight: 600; margin: 0 0 3px; }
        .info-item .value { color: #064e3b; font-size: 14px; font-weight: 700; margin: 0; }

        .amount-box {
            background: linear-gradient(135deg, #064e3b, #065f46);
            border-radius: 14px; padding: 20px 24px;
            text-align: center; margin: 20px 0;
        }
        .amount-box p  { color: rgba(255,255,255,0.6); font-size: 12px; margin: 0 0 6px; }
        .amount-box h2 { color: #fff; font-size: 32px; font-weight: 800; margin: 0; }

        .balance-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 14px 16px; border-radius: 12px;
            margin-bottom: 20px;
        }
        .balance-row.has-balance { background: #fef2f2; }
        .balance-row.no-balance  { background: #f0fdf4; }

        .footer-note {
            text-align: center; padding-top: 20px;
            border-top: 2px dashed #d1fae5;
        }
        .footer-note p { color: #9ca3af; font-size: 12px; margin: 4px 0; }

        .sig-line {
            display: flex; justify-content: space-around;
            margin-top: 24px;
        }
        .sig-box { text-align: center; }
        .sig-box .line { width: 140px; border-top: 1.5px solid #d1d5db; margin: 40px auto 6px; }
        .sig-box p { color: #6b7280; font-size: 11px; margin: 0; }

        @media print {
            body { background: white; padding: 0; }
            .action-bar { display: none; }
            .receipt { box-shadow: none; border: none; max-width: 100%; }
        }
    </style>
</head>
<body>