function openModal(id) { document.getElementById(id).classList.add('active'); }
function closeModal(id) { document.getElementById(id).classList.remove('active'); }
function editCashier(c) {
    document.getElementById('edit_id').value = c.id;
    document.getElementById('edit_full_name').value = c.full_name;
    document.getElementById('edit_username').value = c.username;
    document.getElementById('edit_email').value = c.email;
    document.getElementById('edit_status').value = c.status;
    openModal('editModal');
}
document.querySelectorAll('.modal-overlay').forEach(el => {
    el.addEventListener('click', e => { if (e.target === el) el.classList.remove('active'); });
});