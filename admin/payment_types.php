<?php

require_once '../includes/auth.php';
require_once '../includes/db.php';
requireAdmin();

$page_title  = 'Payment Types';
$active_page = 'payment_types';
$msg = ''; $msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name   = trim($_POST['type_name']);
        $desc   = trim($_POST['description']);
        $status = $_POST['status'];
        $stmt   = $conn->prepare("INSERT INTO payment_types (type_name, description, status) VALUES (?,?,?)");
        $stmt->bind_param("sss", $name, $desc, $status);
        if ($stmt->execute()) { $msg = 'Payment type added.'; $msg_type = 'success'; }
        else { $msg = 'Error.'; $msg_type = 'error'; }
    }

    if ($action === 'edit') {
        $id     = intval($_POST['id']);
        $name   = trim($_POST['type_name']);
        $desc   = trim($_POST['description']);
        $status = $_POST['status'];
        $stmt   = $conn->prepare("UPDATE payment_types SET type_name=?, description=?, status=? WHERE id=?");
        $stmt->bind_param("sssi", $name, $desc, $status, $id);
        if ($stmt->execute()) { $msg = 'Updated.'; $msg_type = 'success'; }
    }

    if ($action === 'delete') {
        $id = intval($_POST['id']);
        $conn->query("DELETE FROM payment_types WHERE id=$id");
        $msg = 'Deleted.'; $msg_type = 'success';
    }
}

$types = $conn->query("SELECT pt.*, COUNT(t.id) as usage_count FROM payment_types pt LEFT JOIN transactions t ON t.payment_type_id = pt.id GROUP BY pt.id ORDER BY pt.created_at DESC");


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

<?php if ($msg): ?>
<div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div class="card">
    <div style="padding:20px 24px; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center;">
        <h3 style="font-size:15px; font-weight:700; color:#0f172a; margin:0;">Payment Types</h3>
        <button class="btn-primary" onclick="openModal('addModal')">
            <i class="fa-solid fa-plus"></i> Add Type
        </button>
    </div>
    <div style="overflow-x:auto;">
        <table>
            <thead class="table-header">
                <tr>
                    <th>Type Name</th>
                    <th>Description</th>
                    <th>Usage</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($t = $types->fetch_assoc()):
                    $c = getTypeColor($t['type_name'], $type_colors, $fallback_colors);
                ?>
                <tr>
        
                    <td>
                        <span style="background:<?= $c['bg'] ?>; color:<?= $c['text'] ?>; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600; display:inline-flex; align-items:center; gap:5px; white-space:nowrap;">
                            <i class="fa-solid fa-tag" style="color:<?= $c['icon'] ?>; font-size:10px;"></i>
                            <?= htmlspecialchars($t['type_name']) ?>
                        </span>
                    </td>
                    <td style="color:#64748b;"><?= htmlspecialchars($t['description']) ?></td>
                    <td><span class="badge badge-info"><?= $t['usage_count'] ?> uses</span></td>
                    <td><span class="badge badge-<?= $t['status']==='active' ? 'success' : 'danger' ?>"><?= ucfirst($t['status']) ?></span></td>
                    <td>
                        <button class="btn-edit" onclick="editType(<?= htmlspecialchars(json_encode($t)) ?>)">Edit</button>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this type?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $t['id'] ?>">
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
            <h3 style="font-size:18px; font-weight:700; color:#0f172a; margin:0;">Add Payment Type</h3>
            <button onclick="closeModal('addModal')" style="background:none; border:none; cursor:pointer; color:#94a3b8; font-size:22px;">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label>Type Name *</label>
                <input type="text" name="type_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="3" style="resize:vertical;"></textarea>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <button type="button" class="btn-secondary" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" class="btn-primary">Add</button>
            </div>
        </form>
    </div>
</div>


<div class="modal-overlay" id="editModal">
    <div class="modal">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
            <h3 style="font-size:18px; font-weight:700; color:#0f172a; margin:0;">Edit Payment Type</h3>
            <button onclick="closeModal('editModal')" style="background:none; border:none; cursor:pointer; color:#94a3b8; font-size:22px;">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="form-group">
                <label>Type Name *</label>
                <input type="text" name="type_name" id="edit_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" id="edit_desc" class="form-control" rows="3" style="resize:vertical;"></textarea>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" id="edit_status" class="form-control">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <button type="button" class="btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id)  { document.getElementById(id).classList.add('active'); }
function closeModal(id) { document.getElementById(id).classList.remove('active'); }
function editType(t) {
    document.getElementById('edit_id').value     = t.id;
    document.getElementById('edit_name').value   = t.type_name;
    document.getElementById('edit_desc').value   = t.description;
    document.getElementById('edit_status').value = t.status;
    openModal('editModal');
}
document.querySelectorAll('.modal-overlay').forEach(el => {
    el.addEventListener('click', e => { if (e.target === el) el.classList.remove('active'); });
});
</script>

<?php include 'layout_footer.php'; ?>
