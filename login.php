<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

if (isLoggedIn()) {
    header('Location: ' . ($_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'cashier/dashboard.php'));
    exit();
}
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $stmt = $conn->prepare("SELECT id, username, password, full_name, role, status FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();

        $password_valid = ($user && $password === $user['password']);

        if ($password_valid && $user['status'] === 'active') {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];
            header('Location: ' . ($user['role'] === 'admin' ? 'admin/dashboard.php' : 'cashier/dashboard.php'));
            exit();
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<?php include 'loginstyle.php'; ?>

<div class="right-panel" style="flex:1; background:#f1f5f9; position:relative; display:flex; align-items:center; justify-content:center; padding:40px; overflow:hidden;">

    <div id="orb1" class="absolute -top-24 -right-24 w-72 h-72 bg-indigo-100/70 rounded-full blur-3xl pointer-events-none transition-all duration-700"></div>
    <div id="orb2" class="absolute -bottom-24 -left-24 w-64 h-64 bg-cyan-100/60 rounded-full blur-3xl pointer-events-none transition-all duration-700"></div>

    <div class="relative z-10 w-full max-w-md">
        <div id="loginCard" class="bg-white rounded-3xl shadow-2xl shadow-slate-200/80 border border-slate-100 p-9 anim-1" style="transition: border-color 0.4s;">

            <div class="text-center mb-7">
                <div class="flex items-center justify-center gap-2 mb-4">
                    <span class="text-blue-500 text-xs font-bold tracking-widest uppercase">
                        School Payment System
                    </span>
                </div>
                <h2 class="text-3xl font-extrabold text-slate-800 leading-tight tracking-tight">
                    Sign in to your<br>
                    <span class="">Account</span>
                </h2>
                <p class="text-slate-400 text-sm mt-2">Enter your credentials to continue.</p>
            </div>

            <div id="roleIndicator" style="display:none;" class="items-center gap-3 px-4 py-2.5 rounded-xl border text-sm font-semibold mb-4 transition-all duration-300">
                <i id="roleIcon" class="fa-solid fa-user-shield text-sm"></i>
                <span id="roleText">Admin Access</span>
            </div>

            <?php if ($error): ?>
            <div class="flex items-center gap-3 bg-red-50 border border-red-200 text-red-600 text-sm font-medium px-4 py-3 rounded-xl mb-5">
                <i class="fa-solid fa-circle-exclamation flex-shrink-0"></i>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">

                <div class="anim-2">
                    <label class="block text-xs font-bold text-slate-500 tracking-widest uppercase mb-2">Username</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-user text-sm"></i>
                        </span>
                        <input type="text" name="username" id="usernameInput"
                            placeholder="Enter your username"
                            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                            autocomplete="username" required
                            oninput="detectRole(this.value)"
                            class="input-glow w-full pl-10 pr-4 py-3 bg-slate-50 border-2 border-slate-200 rounded-xl text-slate-800 text-sm font-medium placeholder-slate-300 transition-all duration-200">
                    </div>
                </div>

                <div class="anim-3">
                    <label class="block text-xs font-bold text-slate-500 tracking-widest uppercase mb-2">Password</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-lock text-sm"></i>
                        </span>
                        <input type="password" name="password" id="passwordInput"
                            placeholder="Enter your password"
                            autocomplete="current-password" required
                            class="input-glow w-full pl-10 pr-11 py-3 bg-slate-50 border-2 border-slate-200 rounded-xl text-slate-800 text-sm font-medium placeholder-slate-300 transition-all duration-200">
                        <button type="button" onclick="togglePassword()"
                            class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-indigo-500 transition-colors">
                            <i id="eyeIcon" class="fa-solid fa-eye text-sm"></i>
                        </button>
                    </div>
                </div>

                <div class="anim-4">
                    <button type="submit"
                        class="btn-submit btn-shine w-full py-3.5 bg-gradient-to-r from-blue-600 to-blue-500 text-white font-bold text-base rounded-xl cursor-pointer border-0 mt-1 transition-all duration-500">
                        <i class="fa-solid fa-right-to-bracket mr-2"></i>Sign In
                    </button>
                </div>

            </form>

            <div class="text-center mt-5 anim-5">
                <button onclick="openContact()"
                    class="inline-flex items-center gap-2 text-slate-400 hover:text-indigo-500 text-xs font-medium transition-colors group">
                    <i class="fa-solid fa-circle-info text-slate-300 group-hover:text-indigo-400 transition-colors"></i>
                    Need help? Contact the developer
                    <i class="fa-solid fa-chevron-up text-[10px] text-slate-300 group-hover:text-indigo-400 transition-colors"></i>
                </button>
            </div>

        </div>
    </div>
</div>

<div id="contactPopup" onclick="closeContact(event)">
    <div id="contactCard">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 style="font-size:18px;font-weight:800;color:#0f172a;margin:0 0 2px;">Contact Developer</h3>
                <p style="font-size:13px;color:#94a3b8;margin:0;">Reach out for support or inquiries</p>
            </div>
            <button onclick="document.getElementById('contactPopup').classList.remove('open')"
                style="width:34px;height:34px;border-radius:10px;background:#f1f5f9;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#64748b;font-size:14px;"
                onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div style="display:flex;align-items:center;gap:14px;padding:16px;background:#f8fafc;border-radius:16px;margin-bottom:18px;border:1.5px solid #e2e8f0;">
            <div style="width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,#6366f1,#4f46e5);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <span style="color:white;font-weight:800;font-size:20px;">J</span>
            </div>
            <div>
                <p style="font-size:15px;font-weight:800;color:#1e293b;margin:0 0 2px;">Jan Han Aaron</p>
                <p style="font-size:12px;color:#94a3b8;margin:0;">System Developer</p>
            </div>
            <div style="margin-left:auto;">
                <span style="font-size:10px;font-weight:700;background:#f0fdf4;color:#059669;border:1px solid #a7f3d0;padding:4px 10px;border-radius:20px;">
                    <i class="fa-solid fa-circle" style="font-size:7px;margin-right:4px;"></i>Available
                </span>
            </div>
        </div>

        <a href="mailto:janhanaron@gmail.com" class="contact-link" style="border-color:#e0e7ff;"
           onmouseover="this.style.background='#eef2ff';this.style.borderColor='#c7d2fe';"
           onmouseout="this.style.background='#fff';this.style.borderColor='#e0e7ff';">
            <div class="icon-box" style="background:linear-gradient(135deg,#6366f1,#4f46e5);">
                <i class="fa-solid fa-envelope" style="color:white;font-size:17px;"></i>
            </div>
            <div style="flex:1;">
                <p style="font-size:13px;font-weight:800;color:#1e293b;margin:0 0 2px;">Email</p>
                <p style="font-size:12px;color:#6366f1;margin:0;font-family:monospace;">janhanaron@gmail.com</p>
            </div>
            <i class="fa-solid fa-arrow-up-right-from-square" style="color:#a5b4fc;font-size:12px;"></i>
        </a>

        <a href="https://www.facebook.com/DEevLopPer" target="_blank" rel="noopener" class="contact-link" style="border-color:#dbeafe;margin-bottom:0;"
           onmouseover="this.style.background='#eff6ff';this.style.borderColor='#bfdbfe';"
           onmouseout="this.style.background='#fff';this.style.borderColor='#dbeafe';">
            <div class="icon-box" style="background:linear-gradient(135deg,#1877f2,#0a5ed8);">
                <i class="fa-brands fa-facebook-f" style="color:white;font-size:17px;"></i>
            </div>
            <div style="flex:1;">
                <p style="font-size:13px;font-weight:800;color:#1e293b;margin:0 0 2px;">Facebook</p>
                <p style="font-size:12px;color:#1877f2;margin:0;">facebook.com/DEevLopPer</p>
            </div>
            <i class="fa-solid fa-arrow-up-right-from-square" style="color:#93c5fd;font-size:12px;"></i>
        </a>
    </div>
</div>

<script>
function openContact()  { document.getElementById('contactPopup').classList.add('open'); }
function closeContact(e){ if (e.target === document.getElementById('contactPopup')) document.getElementById('contactPopup').classList.remove('open'); }

function togglePassword() {
    const input = document.getElementById('passwordInput');
    const icon  = document.getElementById('eyeIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fa-solid fa-eye-slash text-sm';
    } else {
        input.type = 'password';
        icon.className = 'fa-solid fa-eye text-sm';
    }
}

const ADMIN_USERNAMES   = ['admin'];
const CASHIER_USERNAMES = ['cashier1', 'cashier'];

function detectRole(val) {
    const v    = val.trim().toLowerCase();
    const body = document.body;
    const ind  = document.getElementById('roleIndicator');
    const icon = document.getElementById('roleIcon');
    const text = document.getElementById('roleText');

    body.classList.remove('theme-admin', 'theme-cashier');

    if (ADMIN_USERNAMES.some(u => v === u || v.startsWith(u))) {
        body.classList.add('theme-admin');
        icon.className = 'fa-solid fa-user-shield text-sm';
        text.textContent = 'Admin Access';
        ind.style.display = 'flex';
    } else if (CASHIER_USERNAMES.some(u => v === u || v.startsWith(u))) {
        body.classList.add('theme-cashier');
        icon.className = 'fa-solid fa-cash-register text-sm';
        text.textContent = 'Cashier Access';
        ind.style.display = 'flex';
    } else {
        ind.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const val = document.getElementById('usernameInput')?.value || '';
    if (val) detectRole(val);
});
</script>
</body>
</html>