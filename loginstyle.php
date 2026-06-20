<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – School Payment System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        html, body { height: 100%; overflow: hidden; }

        @keyframes float1 {
            0%, 100% { transform: translateY(0px); }
            50%       { transform: translateY(-16px); }
        }
        @keyframes float2 {
            0%, 100% { transform: translateY(0px); }
            50%       { transform: translateY(-10px); }
        }
        @keyframes gradientShift {
            0%   { background-position: 0% 50%; }
            50%  { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes pulse-ring {
            0%   { transform: scale(1); opacity: 0.5; }
            100% { transform: scale(1.7); opacity: 0; }
        }
        @keyframes spin-slow {
            from { transform: rotate(0deg); }
            to   { transform: rotate(360deg); }
        }

        .float-1 { animation: float1 6s ease-in-out infinite; }
        .float-2 { animation: float2 9s ease-in-out infinite 1.5s; }
        .float-3 { animation: float1 7s ease-in-out infinite 3s; }

        .anim-1 { animation: fadeSlideUp 0.5s ease forwards; }
        .anim-2 { animation: fadeSlideUp 0.5s ease 0.08s forwards; opacity: 0; }
        .anim-3 { animation: fadeSlideUp 0.5s ease 0.16s forwards; opacity: 0; }
        .anim-4 { animation: fadeSlideUp 0.5s ease 0.24s forwards; opacity: 0; }
        .anim-5 { animation: fadeSlideUp 0.5s ease 0.32s forwards; opacity: 0; }

        .gradient-bg {
            background: linear-gradient(135deg, #1e3a5f, #1a2980, #267472);
            background-size: 300% 300%;
            animation: gradientShift 8s ease infinite;
        }

        .pulse-dot { position: relative; }
        .pulse-dot::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 50%;
            background: #4ade80;
            animation: pulse-ring 1.8s ease-out infinite;
        }

        .input-glow:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99,102,241,0.15);
        }

        .btn-shine {
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .btn-shine::after {
            content: '';
            position: absolute;
            top: -50%; left: -60%;
            width: 40%; height: 200%;
            background: rgba(255,255,255,0.18);
            transform: skewX(-20deg);
            transition: left 0.5s ease;
        }
        .btn-shine:hover::after { left: 120%; }
        .btn-shine:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(99,102,241,0.45);
        }

        .quick-btn {
            display: flex; align-items: center; gap: 10px;
            padding: 11px 16px;
            border-radius: 14px;
            border: 1.5px solid rgba(255,255,255,0.12);
            background: rgba(255,255,255,0.07);
            cursor: pointer;
            transition: all 0.2s;
            width: 100%;
            text-align: left;
        }
        .quick-btn:hover {
            background: rgba(255,255,255,0.14);
            border-color: rgba(255,255,255,0.25);
            transform: translateX(3px);
        }

        /* THEME: Admin = Blue, Cashier = Green  */
        body.theme-admin  .input-glow:focus { border-color:#6366f1 !important; box-shadow:0 0 0 4px rgba(99,102,241,0.15) !important; }
        body.theme-cashier .input-glow:focus { border-color:#10b981 !important; box-shadow:0 0 0 4px rgba(16,185,129,0.15) !important; }
        body.theme-admin  #loginCard { border-color:#c7d2fe !important; }
        body.theme-cashier #loginCard { border-color:#a7f3d0 !important; }
        body.theme-admin  #roleIndicator { background:#eef2ff; color:#4338ca; border-color:#c7d2fe; display:flex !important; }
        body.theme-cashier #roleIndicator { background:#f0fdf4; color:#065f46; border-color:#a7f3d0; display:flex !important; }
        body.theme-admin  .btn-submit { background:linear-gradient(135deg,#6366f1,#4f46e5) !important; }
        body.theme-cashier .btn-submit { background:linear-gradient(135deg,#10b981,#059669) !important; }
        body.theme-admin  #orb1 { background:rgba(199,210,254,0.8) !important; }
        body.theme-cashier #orb1 { background:rgba(167,243,208,0.8) !important; }
        body.theme-admin  #orb2 { background:rgba(221,214,254,0.7) !important; }
        body.theme-cashier #orb2 { background:rgba(110,231,183,0.5) !important; }

        /* CONTACT POPUP  */
        #contactPopup {
            display: none; position: fixed; inset: 0; z-index: 9999;
            background: rgba(15,23,42,0.5);
            align-items: center; justify-content: center;
            backdrop-filter: blur(4px);
            padding: 20px;
            width: 100vw; height: 100vh;
            box-sizing: border-box;
        }
        #contactPopup.open { display: flex; }
        #contactCard {
            background: white; border-radius: 28px;
            padding: 32px 28px 36px; width: 100%; max-width: 420px;
            animation: popIn 0.35s cubic-bezier(0.34,1.56,0.64,1) forwards;
        }
        @keyframes popIn {
            from { transform: scale(0.88) translateY(16px); opacity: 0; }
            to   { transform: scale(1)    translateY(0);    opacity: 1; }
        }
        .contact-link {
            display: flex; align-items: center; gap: 14px;
            padding: 14px 16px; border-radius: 16px;
            border: 1.5px solid #e2e8f0; text-decoration: none;
            transition: all 0.2s; margin-bottom: 10px;
        }
        .contact-link:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.08); }
        .contact-link .icon-box {
            width: 44px; height: 44px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; font-size: 18px;
        }

        .feature-item {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 16px;
            border-radius: 14px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.08);
            transition: all 0.2s;
        }
        .feature-item:hover {
            background: rgba(255,255,255,0.1);
            transform: translateX(3px);
        }

        .spin-ring {
            animation: spin-slow 18s linear infinite;
        }

        @media (max-width: 600px) {
            .left-panel { display: none !important; }
            .right-panel { grid-column: 1 / -1 !important; }
        }
    </style>

    
</head>
<body class="h-screen" style="display:flex; overflow:hidden; background: linear-gradient(135deg, #e0e7ff 0%, #f0fdf4 50%, #e0f2fe 100%);">
