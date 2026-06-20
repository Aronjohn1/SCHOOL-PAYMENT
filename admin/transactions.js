function doPrint() {
    const rows = document.querySelectorAll('#main-tbody tr[data-receipt]');
    let html = '';
    rows.forEach((r, i) => {
        const bg = i % 2 === 0 ? '#fff' : '#f9fafb';
        html += `<tr style="background:${bg};">
            <td style="padding:7px 10px; border-bottom:1px solid #e5e7eb;">${i+1}</td>
            <td style="padding:7px 10px; border-bottom:1px solid #e5e7eb; font-family:monospace; font-weight:700; color:#4f46e5;">${r.dataset.receipt}</td>
            <td style="padding:7px 10px; border-bottom:1px solid #e5e7eb; font-weight:600;">${r.dataset.student}</td>
            <td style="padding:7px 10px; border-bottom:1px solid #e5e7eb; color:#6b7280;">${r.dataset.studId}</td>
            <td style="padding:7px 10px; border-bottom:1px solid #e5e7eb;">${r.dataset.type}</td>
            <td style="padding:7px 10px; border-bottom:1px solid #e5e7eb; text-align:right; font-weight:800; color:#059669;">₱${r.dataset.amount}</td>
            <td style="padding:7px 10px; border-bottom:1px solid #e5e7eb;">${r.dataset.date}</td>
            <td style="padding:7px 10px; border-bottom:1px solid #e5e7eb;">${r.dataset.time}</td>
            <td style="padding:7px 10px; border-bottom:1px solid #e5e7eb;">${r.dataset.cashier}</td>
        </tr>`;
    });
    document.getElementById('print-tbody').innerHTML = html || '<tr><td colspan="9" style="text-align:center;padding:20px;color:#9ca3af;">No records.</td></tr>';

    const pa = document.getElementById('print-area');
    pa.style.display = 'block';
    window.print();
    pa.style.display = 'none';
}