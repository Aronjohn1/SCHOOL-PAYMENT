<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireAdmin();

$page_title  = 'Cashier Accounts';
$active_page = 'cashiers';
$msg = ''; $msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $username  = trim($_POST['username']);
        $full_name = trim($_POST['full_name']);
        $email     = trim($_POST['email']);
        $password  = $_POST['password'];  
        $status    = $_POST['status'];

        if (empty($password)) {
            $msg = 'Password is required.'; $msg_type = 'error';
        } else {
            $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, email, role, status) VALUES (?,?,?,?,'cashier',?)");
            $stmt->bind_param("sssss", $username, $password, $full_name, $email, $status);
            if ($stmt->execute()) { $msg = 'Cashier account created.'; $msg_type = 'success'; }
            else { $msg = 'Error: Username may already exist.'; $msg_type = 'error'; }
        }
    }

    if ($action === 'edit') {
        $id        = intval($_POST['id']);
        $username  = trim($_POST['username']);
        $full_name = trim($_POST['full_name']);
        $email     = trim($_POST['email']);
        $status    = $_POST['status'];
        $new_pass  = trim($_POST['new_password'] ?? '');

        if ($new_pass) {
  
            $stmt = $conn->prepare("UPDATE users SET username=?, full_name=?, email=?, status=?, password=? WHERE id=? AND role='cashier'");
            $stmt->bind_param("sssssi", $username, $full_name, $email, $status, $new_pass, $id);
        } else {

            $stmt = $conn->prepare("UPDATE users SET username=?, full_name=?, email=?, status=? WHERE id=? AND role='cashier'");
            $stmt->bind_param("ssssi", $username, $full_name, $email, $status, $id);
        }
        if ($stmt->execute()) { $msg = 'Cashier updated.'; $msg_type = 'success'; }
        else { $msg = 'Error updating cashier.'; $msg_type = 'error'; }
    }

    if ($action === 'delete') {
        $id = intval($_POST['id']);
        $conn->query("DELETE FROM users WHERE id=$id AND role='cashier'");
        $msg = 'Cashier deleted.'; $msg_type = 'success';
    }
}

$cashiers = $conn->query("
    SELECT u.*, COUNT(t.id) as tx_count
    FROM users u
    LEFT JOIN transactions t ON t.cashier_id = u.id
    WHERE u.role = 'cashier'
    GROUP BY u.id
    ORDER BY u.created_at DESC
");

include 'layout_header.php';
?>

<?php if ($msg): ?>
<div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div class="card">
    <div style="padding:20px 24px; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center;">
        <h3 style="font-size:15px; font-weight:700; color:#0f172a; margin:0;">Cashier Accounts</h3>
        <button class="btn-primary" onclick="openModal('addModal')">
            <i class="fa-solid fa-plus"></i> Add Cashier
        </button>
    </div>
    <div style="overflow-x:auto;">
        <table>
            <thead class="table-header">
                <tr>
                    <th>Full Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Transactions</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($c = $cashiers->fetch_assoc()): ?>
                <tr>
                    <td>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <div style="width:36px; height:36px; background:linear-gradient(135deg,#3b82f6,#06b6d4); border-radius:10px; display:flex; align-items:center; justify-content:center; color:white; font-weight:700; font-size:13px; flex-shrink:0;">
                                <?= strtoupper(substr($c['full_name'], 0, 1)) ?>
                            </div>
                            <span style="font-weight:600; color:#1e293b;"><?= htmlspecialchars($c['full_name']) ?></span>
                        </div>
                    </td>
                    <td style="font-family:monospace; color:#3b82f6; font-weight:600;">@<?= htmlspecialchars($c['username']) ?></td>
                    <td style="color:#64748b;"><?= htmlspecialchars($c['email']) ?></td>
                    <td><span class="badge badge-info"><?= $c['tx_count'] ?> transactions</span></td>
                    <td><span class="badge badge-<?= $c['status']==='active' ? 'success' : 'danger' ?>"><?= ucfirst($c['status']) ?></span></td>
                    <td style="color:#94a3b8;"><?= date('M j, Y', strtotime($c['created_at'])) ?></td>
                    <td>
                        <button class="btn-edit" onclick='editCashier(<?= htmlspecialchars(json_encode($c), ENT_QUOTES) ?>)'>Edit</button>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this cashier? This cannot be undone.')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                            <button type="submit" class="btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>


<div class="modal-overlay" id="addModal">
    <div class="modal">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
            <h3 style="font-size:18px; font-weight:700; color:#0f172a; margin:0;">Add Cashier Account</h3>
            <button onclick="closeModal('addModal')" style="background:none; border:none; cursor:pointer; color:#94a3b8; font-size:22px;">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="full_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Username *</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control">
            </div>
            <div class="form-group">
                <label>Password *</label>
                <div style="position:relative;">
                    <input type="password" name="password" id="add_password" class="form-control" required style="padding-right:40px;">
                    <button type="button" onclick="toggleVis('add_password','add_eye')"
                        style="position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:#94a3b8;">
                        <i id="add_eye" class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:8px;">
                <button type="button" class="btn-secondary" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" class="btn-primary">Create Account</button>
            </div>
        </form>
    </div>
</div>


<div class="modal-overlay" id="editModal">
    <div class="modal">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
            <h3 style="font-size:18px; font-weight:700; color:#0f172a; margin:0;">Edit Cashier</h3>
            <button onclick="closeModal('editModal')" style="background:none; border:none; cursor:pointer; color:#94a3b8; font-size:22px;">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Username *</label>
                <input type="text" name="username" id="edit_username" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" id="edit_email" class="form-control">
            </div>
            <div class="form-group">
                <label>New Password <small style="color:#94a3b8;">(leave blank to keep current)</small></label>
                <div style="position:relative;">
                    <input type="password" name="new_password" id="edit_password" class="form-control" style="padding-right:40px;">
                    <button type="button" onclick="toggleVis('edit_password','edit_eye')"
                        style="position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:#94a3b8;">
                        <i id="edit_eye" class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" id="edit_status" class="form-control">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:8px;">
                <button type="button" class="btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id)  { document.getElementById(id).classList.add('active'); }
function closeModal(id) { document.getElementById(id).classList.remove('active'); }

function editCashier(c) {
    document.getElementById('edit_id').value        = c.id;
    document.getElementById('edit_full_name').value = c.full_name;
    document.getElementById('edit_username').value  = c.username;
    document.getElementById('edit_email').value     = c.email || '';
    document.getElementById('edit_status').value    = c.status;
    document.getElementById('edit_password').value  = '';
    openModal('editModal');
}

function toggleVis(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fa-solid fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fa-solid fa-eye';
    }
}

document.querySelectorAll('.modal-overlay').forEach(el => {
    el.addEventListener('click', e => { if (e.target === el) el.classList.remove('active'); });
});
</script>

<?php include 'layout_footer.php'; ?>
