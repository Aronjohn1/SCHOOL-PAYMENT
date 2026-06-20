


function openModal(id)  { document.getElementById(id).classList.add('active'); }
function closeModal(id) { document.getElementById(id).classList.remove('active'); }

// When grade changes, toggle g12 vs g7-11 section fields
function onGradeChange(prefix) {
    const grade   = document.getElementById(prefix + '_grade_select').value;
    const isSHS   = (grade === 'Grade 11' || grade === 'Grade 12'); // Senior High School
    const g711    = document.getElementById(prefix + '_g7_11_fields');
    const g12     = document.getElementById(prefix + '_g12_fields');
    const secHS   = document.getElementById(prefix + '_section_hs');
    const strand  = document.getElementById(prefix + '_strand');
    const g12sec  = document.getElementById(prefix + '_g12_section');

    g711.style.display = isSHS ? 'none' : 'block';
    g12.style.display  = isSHS ? 'grid' : 'none';
    secHS.disabled     = isSHS;
    strand.disabled    = !isSHS;
    g12sec.disabled    = !isSHS;
}

function setLevel(prefix, level) {
    const isCollege = level === 'college';

    // Level button styles
    const hsBtn  = document.getElementById(prefix + '_btn_highschool');
    const colBtn = document.getElementById(prefix + '_btn_college');
    [hsBtn, colBtn].forEach(b => {
        b.classList.remove('active-level','border-indigo-300','bg-indigo-50');
        b.classList.add('border-slate-200','bg-slate-50');
    });
    const active = isCollege ? colBtn : hsBtn;
    active.classList.remove('border-slate-200','bg-slate-50');
    active.classList.add('active-level','border-indigo-300','bg-indigo-50');

    // Show/hide HS vs College field groups
    document.getElementById(prefix + '_hs_fields').style.display  = isCollege ? 'none'  : 'grid';
    document.getElementById(prefix + '_col_fields').style.display = isCollege ? 'grid'  : 'none';

    // Enable/disable selects/inputs accordingly
    document.getElementById(prefix + '_grade_select').disabled = isCollege;
    document.getElementById(prefix + '_year_select').disabled  = !isCollege;
    document.getElementById(prefix + '_course').disabled       = !isCollege;
    document.getElementById(prefix + '_major').disabled        = !isCollege;

    if (!isCollege) {
        // Re-trigger grade change to show correct HS sub-fields
        onGradeChange(prefix);
    } else {
        // Hide both HS sub-sections
        document.getElementById(prefix + '_g7_11_fields').style.display = 'none';
        document.getElementById(prefix + '_g12_fields').style.display   = 'none';
        document.getElementById(prefix + '_section_hs').disabled = true;
        document.getElementById(prefix + '_strand').disabled     = true;
        document.getElementById(prefix + '_g12_section').disabled= true;
    }

    document.getElementById(prefix + '_level_hidden').value = level;
}

function editStudent(s) {
    const prefix = 'edit';
    document.getElementById('edit_id').value           = s.id;
    document.getElementById('edit_student_id').value   = s.student_id;
    document.getElementById('edit_full_name').value    = s.full_name;
    document.getElementById('edit_sy').value           = s.school_year;
    document.getElementById('edit_status').value       = s.status;
    document.getElementById('edit_fee').value          = s.total_fee;
    document.getElementById('edit_paid').value         = s.total_paid;

    const collegeYears = ['1st Year','2nd Year','3rd Year','4th Year','5th Year'];
    const isCollege    = collegeYears.includes(s.grade_level);
    setLevel(prefix, isCollege ? 'college' : 'highschool');

    if (isCollege) {
        document.getElementById('edit_year_select').value = s.grade_level;
        document.getElementById('edit_course').value      = s.section || '';
        document.getElementById('edit_major').value       = s.major   || '';
    } else {
        document.getElementById('edit_grade_select').value = s.grade_level;
        // Trigger grade change to show right sub-fields
        onGradeChange(prefix);

        if (s.grade_level === 'Grade 11' || s.grade_level === 'Grade 12') {
            // Parse stored "STRAND – Section"
            const parts = (s.section || '').split(' – ');
            document.getElementById('edit_strand').value      = parts[0] || '';
            document.getElementById('edit_g12_section').value = parts[1] || '';
        } else {
            document.getElementById('edit_section_hs').value = s.section || '';
        }
    }

    openModal('editModal');
}

function switchTab(tab) {
    const isHS = tab === 'hs';
    document.getElementById('panel_hs').style.display  = isHS ? 'block' : 'none';
    document.getElementById('panel_col').style.display = isHS ? 'none'  : 'block';

    // Keep search form in sync so tab is preserved on search
    const tabInput = document.getElementById('searchTabInput');
    if (tabInput) tabInput.value = tab;

    const tabHS  = document.getElementById('tab_hs');
    const tabCol = document.getElementById('tab_col');
    [tabHS, tabCol].forEach(t => {
        t.className = 'tab-btn flex items-center gap-2 px-5 py-2.5 text-sm font-semibold rounded-t-xl border-b-2 border-transparent text-slate-400 hover:text-slate-600 transition-all';
    });
    if (isHS) {
        tabHS.className = 'tab-btn flex items-center gap-2 px-5 py-2.5 text-sm font-semibold rounded-t-xl border-b-2 border-indigo-500 text-indigo-600 bg-indigo-50 transition-all';
    } else {
        tabCol.className = 'tab-btn flex items-center gap-2 px-5 py-2.5 text-sm font-semibold rounded-t-xl border-b-2 border-amber-500 text-amber-600 bg-amber-50 transition-all';
    }
    tabHS.querySelector('span').className  = isHS  ? 'bg-indigo-100 text-indigo-600 text-xs font-bold px-2 py-0.5 rounded-full' : 'bg-slate-100 text-slate-500 text-xs font-bold px-2 py-0.5 rounded-full';
    tabCol.querySelector('span').className = !isHS ? 'bg-amber-100 text-amber-700 text-xs font-bold px-2 py-0.5 rounded-full'  : 'bg-slate-100 text-slate-500 text-xs font-bold px-2 py-0.5 rounded-full';
}

// Close on backdrop
document.querySelectorAll('.modal-overlay').forEach(el => {
    el.addEventListener('click', e => { if (e.target === el) el.classList.remove('active'); });
});

// Restore active tab from URL on page load (preserves tab after search)
const urlParams = new URLSearchParams(window.location.search);
const activeTab = urlParams.get('tab') || 'hs';
switchTab(activeTab);
setLevel('add', 'highschool');
setLevel('edit', 'highschool');

//  BULK DELETE
function getChecked() {
    return [...document.querySelectorAll('.chk-hs:checked, .chk-col:checked')];
}
function updateBulk() {
    const checked = getChecked();
    const toolbar = document.getElementById('bulkToolbar');
    const countEl = document.getElementById('bulkCount');
    const inputs  = document.getElementById('bulkInputs');
    if (checked.length > 0) {
        toolbar.classList.remove('hidden');
        toolbar.classList.add('flex');
        countEl.textContent = checked.length + ' selected';
        inputs.innerHTML = checked.map(c => `<input type="hidden" name="selected_ids[]" value="${c.value}">`).join('');
    } else {
        toolbar.classList.add('hidden');
        toolbar.classList.remove('flex');
    }
}
function toggleAll(group, checked) {
    document.querySelectorAll('.chk-' + group).forEach(c => c.checked = checked);
    updateBulk();
}
function clearSelection() {
    document.querySelectorAll('.chk-hs, .chk-col').forEach(c => c.checked = false);
    document.getElementById('hs_check_all').checked  = false;
    document.getElementById('col_check_all').checked = false;
    updateBulk();
}
function confirmBulk() {
    const n = getChecked().length;
    return confirm(`Delete ${n} selected student(s)?\n\nNote: Active students with transactions will be skipped.`);
}
