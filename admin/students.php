<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireAdmin();

$page_title = 'Students';
$active_page = 'students';
$msg = ''; $msg_type = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $student_id_val = trim($_POST['student_id_val']);
        $full_name      = trim($_POST['full_name']);
        $school_year    = trim($_POST['school_year']);
        $total_fee      = floatval($_POST['total_fee']);
        $total_paid     = floatval($_POST['total_paid']);
        $status         = $_POST['status'];
        $student_level  = $_POST['student_level'] ?? 'highschool';
        $grade_level    = trim($_POST['grade_level'] ?? '');
        $section        = trim($_POST['section'] ?? '');
   
        if (in_array($grade_level, ['Grade 11', 'Grade 12'])) {
            $strand    = trim($_POST['strand'] ?? '');
            $g12sec    = trim($_POST['g12_section'] ?? '');
            $section   = $strand . ($g12sec ? ' – ' . $g12sec : '');
        }

        if ($action === 'add') {
         
            $check = $conn->prepare("SELECT id FROM students WHERE student_id = ?");
            $check->bind_param("s", $student_id_val);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $msg = "Student ID \"$student_id_val\" already exists. Please use a different ID.";
                $msg_type = 'error';
            } else {
                $stmt = $conn->prepare("INSERT INTO students (student_id, full_name, grade_level, section, school_year, total_fee, total_paid, status) VALUES (?,?,?,?,?,?,?,?)");
                $stmt->bind_param("sssssdds", $student_id_val, $full_name, $grade_level, $section, $school_year, $total_fee, $total_paid, $status);
                if ($stmt->execute()) { $msg = 'Student added successfully.'; $msg_type = 'success'; }
                else { $msg = 'Error: ' . $conn->error; $msg_type = 'error'; }
            }
        } else {
            $id = intval($_POST['id']);
       
            $check = $conn->prepare("SELECT id FROM students WHERE student_id = ? AND id != ?");
            $check->bind_param("si", $student_id_val, $id);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $msg = "Student ID \"$student_id_val\" is already used by another student.";
                $msg_type = 'error';
            } else {
                $stmt = $conn->prepare("UPDATE students SET student_id=?, full_name=?, grade_level=?, section=?, school_year=?, total_fee=?, total_paid=?, status=? WHERE id=?");
                $stmt->bind_param("sssssddsi", $student_id_val, $full_name, $grade_level, $section, $school_year, $total_fee, $total_paid, $status, $id);
                if ($stmt->execute()) { $msg = 'Student updated successfully.'; $msg_type = 'success'; }
                else { $msg = 'Error: ' . $conn->error; $msg_type = 'error'; }
            }
        }
    }

    if ($action === 'delete') {
        $id = intval($_POST['id']);
        $student = $conn->query("SELECT status FROM students WHERE id=$id")->fetch_assoc();
        if (!$student) {
            $msg = 'Student not found.'; $msg_type = 'error';
        } elseif ($student['status'] === 'active') {
            $tx_count = (int)$conn->query("SELECT COUNT(*) as cnt FROM transactions WHERE student_id=$id")->fetch_assoc()['cnt'];
            if ($tx_count > 0) {
                $msg = "Cannot delete — this student has {$tx_count} transaction(s). Set them as Inactive first.";
                $msg_type = 'error';
            } else {
                $conn->query("DELETE FROM students WHERE id=$id");
                $msg = 'Student deleted successfully.'; $msg_type = 'success';
            }
        } else {
            $conn->query("DELETE FROM transactions WHERE student_id=$id");
            $conn->query("DELETE FROM students WHERE id=$id");
            $msg = 'Inactive student and their records deleted.'; $msg_type = 'success';
        }
    }

    if ($action === 'bulk_delete') {
        $ids = $_POST['selected_ids'] ?? [];
        $deleted = 0; $blocked = 0;
        foreach ($ids as $raw_id) {
            $id = intval($raw_id);
            $student = $conn->query("SELECT status FROM students WHERE id=$id")->fetch_assoc();
            if (!$student) continue;
            if ($student['status'] === 'active') {
                $tx_count = (int)$conn->query("SELECT COUNT(*) as cnt FROM transactions WHERE student_id=$id")->fetch_assoc()['cnt'];
                if ($tx_count > 0) { $blocked++; continue; }
            } else {
                $conn->query("DELETE FROM transactions WHERE student_id=$id");
            }
            $conn->query("DELETE FROM students WHERE id=$id");
            $deleted++;
        }
        if ($deleted > 0 && $blocked === 0)
            { $msg = "{$deleted} student(s) deleted successfully."; $msg_type = 'success'; }
        elseif ($deleted > 0 && $blocked > 0)
            { $msg = "{$deleted} deleted. {$blocked} skipped (active with transactions)."; $msg_type = 'error'; }
        else
            { $msg = "No students deleted. {$blocked} are active with transactions — set Inactive first."; $msg_type = 'error'; }
    }
}

$search = trim($_GET['search'] ?? '');
$where  = "WHERE 1";
if ($search) $where .= " AND (full_name LIKE '%".($conn->real_escape_string($search))."%' OR student_id LIKE '%".($conn->real_escape_string($search))."%')";

$students      = $conn->query("SELECT * FROM students $where ORDER BY created_at DESC");
$total_count   = $conn->query("SELECT COUNT(*) as cnt FROM students WHERE status='active'")->fetch_assoc()['cnt'];
$total_balance = $conn->query("SELECT COALESCE(SUM(balance),0) as t FROM students WHERE status='active'")->fetch_assoc()['t'];


$college_levels = ['1st Year','2nd Year','3rd Year','4th Year','5th Year'];
$hs_students = []; $col_students = [];
while ($s = $students->fetch_assoc()) {
    if (in_array($s['grade_level'], $college_levels)) $col_students[] = $s;
    else $hs_students[] = $s;
}

$strands = ['ABM','STEM','HUMSS','GAS','TVL – ICT','TVL – HE','TVL – IA','TVL – AFA','SPORTS','ARTS & DESIGN'];

include 'layout_header.php';
?>
<?php include 'studentstyle.php';?>


<?php if ($msg): ?>
<div class="flex items-center gap-3 mb-5 px-4 py-3 rounded-xl text-sm font-medium fade-up
    <?= $msg_type === 'success' ? 'bg-emerald-50 border border-emerald-200 text-emerald-700' : 'bg-red-50 border border-red-200 text-red-700' ?>">
    <i class="fa-solid <?= $msg_type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
    <?= htmlspecialchars($msg) ?>
</div>
<?php endif; ?>


<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-5 fade-up summary-cards-grid">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 flex items-center gap-4">
        <div class="w-11 h-11 rounded-xl bg-blue-50 flex items-center justify-center flex-shrink-0">
            <i class="fa-solid fa-users text-blue-500 text-lg"></i>
        </div>
        <div>
            <p class="text-slate-400 text-xs font-semibold uppercase tracking-wider">Total Students</p>
            <p class="text-slate-800 text-xl font-extrabold"><?= number_format($total_count) ?></p>
        </div>
    </div>
    <div class="bg-white rounded-2xl border border-indigo-100 shadow-sm p-4 flex items-center gap-4">
        <div class="w-11 h-11 rounded-xl bg-indigo-50 flex items-center justify-center flex-shrink-0">
            <i class="fa-solid fa-school text-indigo-500 text-lg"></i>
        </div>
        <div>
            <p class="text-slate-400 text-xs font-semibold uppercase tracking-wider">High School</p>
            <p class="text-indigo-600 text-xl font-extrabold"><?= count($hs_students) ?></p>
        </div>
    </div>
    <div class="bg-white rounded-2xl border border-amber-100 shadow-sm p-4 flex items-center gap-4">
        <div class="w-11 h-11 rounded-xl bg-amber-50 flex items-center justify-center flex-shrink-0">
            <i class="fa-solid fa-graduation-cap text-amber-500 text-lg"></i>
        </div>
        <div>
            <p class="text-slate-400 text-xs font-semibold uppercase tracking-wider">College</p>
            <p class="text-amber-600 text-xl font-extrabold"><?= count($col_students) ?></p>
        </div>
    </div>

</div>


<div class="bg-white rounded-2xl border border-slate-100 shadow-sm fade-up2">


    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 flex-wrap gap-3 card-header-row">
        <div>
            <h3 class="text-slate-800 font-bold text-base">
                <i class="fa-solid fa-users text-indigo-400 mr-2"></i>Student Records
            </h3>
            <p class="text-slate-400 text-xs mt-0.5">Manage all enrolled students</p>
        </div>
        <div class="flex items-center gap-3">
            <form method="GET" class="relative">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-sm"></i>
                </span>
                <!-- Preserve active tab across search -->
                <input type="hidden" name="tab" id="searchTabInput" value="<?= htmlspecialchars($_GET['tab'] ?? 'hs') ?>">
                <input type="text" name="search"
                    placeholder="Search student..."
                    value="<?= htmlspecialchars($search) ?>"
                    class="field-input pl-9 pr-4 py-2.5 border-2 border-slate-200 rounded-xl text-sm text-slate-700 placeholder-slate-300 w-56 transition-all duration-200">
            </form>
            <button onclick="openModal('addModal')"
                class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition">
                <i class="fa-solid fa-plus"></i> Add Student
            </button>
        </div>
    </div>


    <div id="bulkToolbar" class="hidden items-center gap-3 px-6 py-3 bg-red-50 border-b border-red-100 flex-wrap">
        <i class="fa-solid fa-triangle-exclamation text-red-400"></i>
        <span class="text-red-700 text-sm font-semibold" id="bulkCount">0 selected</span>
        <form method="POST" id="bulkDeleteForm" onsubmit="return confirmBulk()" class="flex items-center gap-2">
            <input type="hidden" name="action" value="bulk_delete">
            <div id="bulkInputs"></div>
            <button type="submit"
                class="flex items-center gap-2 bg-red-500 hover:bg-red-600 text-white text-sm font-semibold px-4 py-2 rounded-xl transition">
                <i class="fa-solid fa-trash"></i> Delete Selected
            </button>
        </form>
        <button onclick="clearSelection()"
            class="text-sm font-semibold text-slate-500 hover:text-slate-700 bg-white px-4 py-2 rounded-xl border border-slate-200 transition">
            Cancel
        </button>
        <p class="text-red-400 text-xs ml-2">
            <i class="fa-solid fa-circle-info mr-1"></i>Active students with transactions cannot be deleted — set Inactive first.
        </p>
    </div>


    <div class="flex gap-1 px-6 pt-4 border-b border-slate-100">
        <button onclick="switchTab('hs')" id="tab_hs"
            class="tab-btn flex items-center gap-2 px-5 py-2.5 text-sm font-semibold rounded-t-xl border-b-2 border-indigo-500 text-indigo-600 bg-indigo-50 transition-all">
            <i class="fa-solid fa-school"></i> High School
            <span class="bg-indigo-100 text-indigo-600 text-xs font-bold px-2 py-0.5 rounded-full"><?= count($hs_students) ?></span>
        </button>
        <button onclick="switchTab('col')" id="tab_col"
            class="tab-btn flex items-center gap-2 px-5 py-2.5 text-sm font-semibold rounded-t-xl border-b-2 border-transparent text-slate-400 hover:text-slate-600 transition-all">
            <i class="fa-solid fa-graduation-cap"></i> College
            <span class="bg-slate-100 text-slate-500 text-xs font-bold px-2 py-0.5 rounded-full"><?= count($col_students) ?></span>
        </button>
    </div>

  
    <div id="panel_hs" class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-slate-50 text-left">
                    <th class="px-4 py-3 w-10">
                        <input type="checkbox" id="hs_check_all" onchange="toggleAll('hs', this.checked)"
                            class="w-4 h-4 rounded border-slate-300 text-indigo-600 cursor-pointer">
                    </th>
                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Student ID</th>
                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Full Name</th>
                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Grade</th>
                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Strand / Section</th>
                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">School Year</th>
                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Total Fee</th>
                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Total Paid</th>
                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Balance</th>
                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php foreach ($hs_students as $s): ?>
                <tr class="hover:bg-slate-50 row-hs" data-id="<?= $s['id'] ?>">
                    <td class="px-4 py-4">
                        <input type="checkbox" class="chk-hs w-4 h-4 rounded border-slate-300 text-indigo-600 cursor-pointer"
                            value="<?= $s['id'] ?>" onchange="updateBulk()">
                    </td>
                    <td class="px-6 py-4">
                        <span class="font-mono text-xs font-bold text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-lg">
                            <?= htmlspecialchars($s['student_id']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                      
                            <span class="font-semibold text-slate-800 text-sm"><?= htmlspecialchars($s['full_name']) ?></span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="<?= in_array($s['grade_level'], ['Grade 11','Grade 12']) ? 'bg-purple-50 text-purple-700' : 'bg-indigo-50 text-indigo-600' ?> text-xs font-bold px-2.5 py-1 rounded-lg">
                            <?= htmlspecialchars($s['grade_level']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-slate-600 text-sm">
                        <?php if (in_array($s['grade_level'], ['Grade 11','Grade 12'])): ?>
                            <?php
             
                            $parts = explode(' – ', $s['section'], 2);
                            $strand_disp = $parts[0] ?? $s['section'];
                            $sec_disp    = $parts[1] ?? '';
                            ?>
                            <span class="bg-purple-100 text-purple-700 text-xs font-bold px-2 py-0.5 rounded-md">
                                <i class="fa-solid fa-layer-group text-[10px] mr-1"></i><?= htmlspecialchars($strand_disp) ?>
                            </span>
                            <?php if ($sec_disp): ?>
                            <span class="text-slate-400 mx-1 text-[10px]">·</span>
                            <span class="text-slate-500 text-xs"><?= htmlspecialchars($sec_disp) ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                            <i class="fa-solid fa-door-open text-slate-300 mr-1 text-[10px]"></i>
                            <?= htmlspecialchars($s['section']) ?>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-slate-500 text-sm">
                        <i class="fa-regular fa-calendar text-slate-300 mr-1"></i><?= htmlspecialchars($s['school_year']) ?>
                    </td>
                    <td class="px-6 py-4 text-slate-700 text-sm font-semibold">₱<?= number_format($s['total_fee'],2) ?></td>
                    <td class="px-6 py-4 text-emerald-600 text-sm font-bold">₱<?= number_format($s['total_paid'],2) ?></td>
                    <td class="px-6 py-4">
                        <span class="text-sm font-extrabold <?= $s['balance'] > 0 ? 'text-red-500' : 'text-emerald-500' ?>">
                            <?= $s['balance'] > 0 ? ' ' : '✓ ' ?>₱<?= number_format($s['balance'],2) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full <?= $s['status']==='active' ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-500' ?>">
                            <i class="fa-solid fa-circle text-[8px] mr-1"></i><?= ucfirst($s['status']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <button onclick="editStudent(<?= htmlspecialchars(json_encode($s)) ?>)"
                                class="flex items-center gap-1.5 text-xs font-semibold bg-blue-50 hover:bg-blue-100 text-blue-600 px-3 py-1.5 rounded-lg transition">
                                <i class="fa-solid fa-pen-to-square"></i> Edit
                            </button>
                            <form method="POST" class="inline" onsubmit="return confirm('Delete this student?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                <button type="submit" class="flex items-center gap-1.5 text-xs font-semibold bg-red-50 hover:bg-red-100 text-red-500 px-3 py-1.5 rounded-lg transition">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($hs_students)): ?>
                <tr><td colspan="11" class="px-6 py-16 text-center text-slate-400">
                    <i class="fa-solid fa-school text-3xl mb-3 block text-slate-200"></i>
                    No high school students found<?= $search ? ' for "'.htmlspecialchars($search).'"' : '' ?>.
                </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>


    <div id="panel_col" class="overflow-x-auto" style="display:none;">
        <table class="w-full">
            <thead>
                <tr class="bg-slate-50 text-left">
                    <th class="px-4 py-3 w-10">
                        <input type="checkbox" id="col_check_all" onchange="toggleAll('col', this.checked)"
                            class="w-4 h-4 rounded border-slate-300 text-amber-500 cursor-pointer">
                    </th>
                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Student ID</th>
                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Full Name</th>
                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Year / Course</th>
                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">School Year</th>
                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Total Fee</th>
                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Total Paid</th>
                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Balance</th>
                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php foreach ($col_students as $s): ?>
                <tr class="hover:bg-amber-50 row-col" data-id="<?= $s['id'] ?>">
                    <td class="px-4 py-4">
                        <input type="checkbox" class="chk-col w-4 h-4 rounded border-slate-300 text-amber-500 cursor-pointer"
                            value="<?= $s['id'] ?>" onchange="updateBulk()">
                    </td>
                    <td class="px-6 py-4">
                        <span class="font-mono text-xs font-bold text-amber-700 bg-amber-50 px-2.5 py-1 rounded-lg">
                            <?= htmlspecialchars($s['student_id']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                          
                            <span class="font-semibold text-slate-800 text-sm"><?= htmlspecialchars($s['full_name']) ?></span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-slate-500 text-sm">
                        <span class="bg-amber-50 text-amber-700 text-xs font-semibold px-2 py-0.5 rounded-md mr-1">
                            <?= htmlspecialchars($s['grade_level']) ?>
                        </span>
                        <i class="fa-solid fa-minus text-slate-300 text-[10px] mr-1"></i>
                        <?= htmlspecialchars($s['section']) ?>
                    </td>
                    <td class="px-6 py-4 text-slate-500 text-sm">
                        <i class="fa-regular fa-calendar text-slate-300 mr-1"></i><?= htmlspecialchars($s['school_year']) ?>
                    </td>
                    <td class="px-6 py-4 text-slate-700 text-sm font-semibold">₱<?= number_format($s['total_fee'],2) ?></td>
                    <td class="px-6 py-4 text-emerald-600 text-sm font-bold">₱<?= number_format($s['total_paid'],2) ?></td>
                    <td class="px-6 py-4">
                        <span class="text-sm font-extrabold <?= $s['balance'] > 0 ? 'text-red-500' : 'text-emerald-500' ?>">
                            <?= $s['balance'] > 0 ? ' ' : '✓ ' ?>₱<?= number_format($s['balance'],2) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full <?= $s['status']==='active' ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-500' ?>">
                            <i class="fa-solid fa-circle text-[8px] mr-1"></i><?= ucfirst($s['status']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <button onclick="editStudent(<?= htmlspecialchars(json_encode($s)) ?>)"
                                class="flex items-center gap-1.5 text-xs font-semibold bg-blue-50 hover:bg-blue-100 text-blue-600 px-3 py-1.5 rounded-lg transition">
                                <i class="fa-solid fa-pen-to-square"></i> Edit
                            </button>
                            <form method="POST" class="inline" onsubmit="return confirm('Delete this student?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                <button type="submit" class="flex items-center gap-1.5 text-xs font-semibold bg-red-50 hover:bg-red-100 text-red-500 px-3 py-1.5 rounded-lg transition">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($col_students)): ?>
                <tr><td colspan="10" class="px-6 py-16 text-center text-slate-400">
                    <i class="fa-solid fa-graduation-cap text-3xl mb-3 block text-slate-200"></i>
                    No college students found<?= $search ? ' for "'.htmlspecialchars($search).'"' : '' ?>.
                </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>



<?php
$strands_list = ['ABM','STEM','HUMSS','GAS','TVL – ICT','TVL – HE','TVL – IA','TVL – AFA','SPORTS','ARTS & DESIGN'];

function buildModal($prefix, $title, $icon, $btnColor, $submitLabel, $strands_list) { ?>

<div class="modal-overlay" id="<?= $prefix ?>Modal">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg mx-4 p-8 max-h-[90vh] overflow-y-auto">

 
        <div class="flex items-center justify-between mb-5">
            <div>
                <h3 class="text-xl font-extrabold text-slate-800">
                    <i class="fa-solid <?= $icon ?> <?= $btnColor ?> mr-2"></i><?= $title ?>
                </h3>
                <p class="text-slate-400 text-sm mt-0.5">Fill in the student details below.</p>
            </div>
            <button onclick="closeModal('<?= $prefix ?>Modal')"
                class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-500 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>


        <div class="mb-5">
            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Student Level *</label>
            <div class="grid grid-cols-2 gap-3">
                <div onclick="setLevel('<?= $prefix ?>','highschool')" id="<?= $prefix ?>_btn_highschool"
                     class="level-btn active-level cursor-pointer rounded-2xl border-2 border-indigo-300 bg-indigo-50 p-4 text-center transition-all">
                    <div class="icon-wrap w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center mx-auto mb-2">
                        <i class="level-icon fa-solid fa-school text-indigo-500 text-lg"></i>
                    </div>
                    <p class="level-title text-indigo-700 font-bold text-sm">High School</p>
                    <p class="level-sub text-indigo-400 text-xs mt-0.5">Grade 7 – 12</p>
                </div>
                <div onclick="setLevel('<?= $prefix ?>','college')" id="<?= $prefix ?>_btn_college"
                     class="level-btn cursor-pointer rounded-2xl border-2 border-slate-200 bg-slate-50 p-4 text-center transition-all">
                    <div class="icon-wrap w-10 h-10 bg-slate-100 rounded-xl flex items-center justify-center mx-auto mb-2">
                        <i class="level-icon fa-solid fa-graduation-cap text-slate-400 text-lg"></i>
                    </div>
                    <p class="level-title text-slate-600 font-bold text-sm">College</p>
                    <p class="level-sub text-slate-400 text-xs mt-0.5">1st – 5th Year</p>
                </div>
            </div>
        </div>

        <form method="POST" id="<?= $prefix ?>Form">
            <input type="hidden" name="action" value="<?= $prefix ?>">
            <?php if ($prefix === 'edit'): ?><input type="hidden" name="id" id="edit_id"><?php endif; ?>
            <input type="hidden" name="student_level" id="<?= $prefix ?>_level_hidden" value="highschool">

            <div class="grid grid-cols-2 gap-4">
      
                <div class="col-span-2">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Student ID *</label>
                    <input type="text" name="student_id_val" id="<?= $prefix ?>_student_id"
                        class="field-input w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl text-sm text-slate-800 placeholder-slate-300 transition-all"
                        placeholder="e.g. 2024-001" required>
                </div>
    
                <div class="col-span-2">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Full Name *</label>
                    <input type="text" name="full_name" id="<?= $prefix ?>_full_name"
                        class="field-input w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl text-sm text-slate-800 placeholder-slate-300 transition-all"
                        placeholder="Complete full name" required>
                </div>

             
                <div id="<?= $prefix ?>_hs_fields" class="col-span-2 grid grid-cols-2 gap-4">

               
                    <div class="col-span-2">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">
                            <i class="fa-solid fa-list-ol mr-1 text-indigo-400"></i>Grade Level
                        </label>
                        <select name="grade_level" id="<?= $prefix ?>_grade_select"
                            onchange="onGradeChange('<?= $prefix ?>')"
                            class="field-input w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl text-sm text-slate-800 transition-all">
                            <option value="Grade 7">Grade 7</option>
                            <option value="Grade 8">Grade 8</option>
                            <option value="Grade 9">Grade 9</option>
                            <option value="Grade 10">Grade 10</option>
                            <option value="Grade 11">Grade 11</option>
                            <option value="Grade 12">Grade 12</option>
                        </select>
                    </div>

           
                    <div id="<?= $prefix ?>_g7_11_fields" class="col-span-2">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">
                            <i class="fa-solid fa-door-open mr-1 text-indigo-400"></i>Section
                        </label>
                        <input type="text" name="section" id="<?= $prefix ?>_section_hs"
                            class="field-input w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl text-sm text-slate-800 placeholder-slate-300 transition-all"
                            placeholder="e.g. Rizal">
                    </div>

  
                    <div id="<?= $prefix ?>_g12_fields" class="col-span-2 grid grid-cols-2 gap-4" style="display:none;">
                        <div class="col-span-2">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">
                                <i class="fa-solid fa-layer-group mr-1 text-purple-500"></i>Strand *
                            </label>
                            <select name="strand" id="<?= $prefix ?>_strand"
                                class="field-input w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl text-sm text-slate-800 transition-all" disabled>
                                <option value="">— Select Strand —</option>
                                <?php foreach ($strands_list as $st): ?>
                                <option value="<?= $st ?>"><?= $st ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">
                                <i class="fa-solid fa-door-open mr-1 text-purple-400"></i>Section
                                <span class="text-slate-300 font-normal normal-case">(optional)</span>
                            </label>
                            <input type="text" name="g12_section" id="<?= $prefix ?>_g12_section"
                                class="field-input w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl text-sm text-slate-800 placeholder-slate-300 transition-all"
                                placeholder="e.g. Section A" disabled>
                        </div>
                    </div>
                </div>

         
                <div id="<?= $prefix ?>_col_fields" class="col-span-2 grid grid-cols-2 gap-4" style="display:none;">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Year Level</label>
                        <select name="grade_level" id="<?= $prefix ?>_year_select"
                            class="field-input w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl text-sm text-slate-800 transition-all" disabled>
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                            <option value="5th Year">5th Year</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Course / Program</label>
                        <input type="text" name="section" id="<?= $prefix ?>_course"
                            class="field-input w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl text-sm text-slate-800 placeholder-slate-300 transition-all"
                            placeholder="e.g. BSIT" disabled>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Major / Specialization</label>
                        <input type="text" name="major" id="<?= $prefix ?>_major"
                            class="field-input w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl text-sm text-slate-800 placeholder-slate-300 transition-all"
                            placeholder="e.g. Web Development (optional)" disabled>
                    </div>
                </div>

          
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">School Year</label>
                    <input type="text" name="school_year" id="<?= $prefix ?>_sy"
                        class="field-input w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl text-sm text-slate-800 placeholder-slate-300 transition-all"
                        placeholder="e.g. 2024-2025">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Status</label>
                    <select name="status" id="<?= $prefix ?>_status"
                        class="field-input w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl text-sm text-slate-800 transition-all">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Total Fee (₱)</label>
                    <input type="number" name="total_fee" id="<?= $prefix ?>_fee"
                        class="field-input w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl text-sm text-slate-800 transition-all"
                        placeholder="0.00" step="0.01" min="0" value="0">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Total Paid (₱)</label>
                    <input type="number" name="total_paid" id="<?= $prefix ?>_paid"
                        class="field-input w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl text-sm text-slate-800 transition-all"
                        placeholder="0.00" step="0.01" min="0" value="0">
                </div>
            </div>

            <div class="flex gap-3 justify-end mt-6 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('<?= $prefix ?>Modal')"
                    class="px-5 py-2.5 text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition">
                    Cancel
                </button>
                <button type="submit"
                    class="flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white <?= $btnColor === 'text-indigo-500' ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-blue-600 hover:bg-blue-700' ?> rounded-xl transition">
                    <i class="fa-solid <?= $icon ?>"></i> <?= $submitLabel ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php } 

buildModal('add',  'Add Student',  'fa-user-plus',       'text-indigo-500', 'Add Student',   $strands_list);
buildModal('edit', 'Edit Student', 'fa-pen-to-square',   'text-blue-500',   'Save Changes',  $strands_list);
?>


<script src="students.js"></script>

<?php include 'layout_footer.php'; ?>
