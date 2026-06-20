<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
@keyframes fadeUp {
    from { opacity:0; transform:translateY(18px); }
    to   { opacity:1; transform:translateY(0); }
}
@keyframes pulse-ring {
    0%   { box-shadow: 0 0 0 0 rgba(16,185,129,0.3); }
    70%  { box-shadow: 0 0 0 10px rgba(16,185,129,0); }
    100% { box-shadow: 0 0 0 0 rgba(16,185,129,0); }
}
.fade-up  { animation: fadeUp 0.4s ease forwards; }
.fade-up2 { animation: fadeUp 0.4s ease 0.08s forwards; opacity:0; }
.fade-up3 { animation: fadeUp 0.4s ease 0.16s forwards; opacity:0; }

.field-input {
    width: 100%;
    padding: 11px 14px 11px 40px;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    font-size: 14px;
    color: #1e293b;
    background: #fff;
    transition: all 0.2s;
    box-sizing: border-box;
    font-family: inherit;
}
.field-input:focus {
    outline: none;
    border-color: #10b981;
    box-shadow: 0 0 0 4px rgba(16,185,129,0.1);
}
.field-input::placeholder { color: #94a3b8; }
.field-group { position: relative; }
.field-icon {
    position: absolute; left: 13px; top: 50%;
    transform: translateY(-50%);
    color: #94a3b8; font-size: 13px; pointer-events: none;
}
textarea.field-input { padding-top: 11px; padding-left: 40px; resize: none; }

.section-label {
    font-size: 11px; font-weight: 800; text-transform: uppercase;
    letter-spacing: 1px; color: #94a3b8; margin: 0 0 14px;
    display: flex; align-items: center; gap: 8px;
}
.section-label::after { content: ''; flex: 1; height: 1px; background: #f1f5f9; }

.step-badge {
    width: 26px; height: 26px;
    background: linear-gradient(135deg, #10b981, #059669);
    color: white; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 800; flex-shrink: 0;
}

.avatar-ring { animation: pulse-ring 2s ease infinite; }

.fee-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 11px 0; border-bottom: 1px solid #f8fafc;
}
.fee-row:last-child { border-bottom: none; }

.progress-track { width: 100%; height: 8px; background: #e2e8f0; border-radius: 99px; overflow: hidden; }
.progress-fill  { height: 100%; border-radius: 99px; background: linear-gradient(90deg, #10b981, #059669); transition: width 0.8s ease; }


.pt-item {
    border: 2px solid #e2e8f0;
    border-radius: 14px;
    padding: 14px 16px;
    transition: all 0.2s;
    background: #fff;
    cursor: pointer;
}
.pt-item:hover { border-color: #a7f3d0; background: #f0fdf4; }
.pt-item.selected {
    border-color: #10b981;
    background: #f0fdf4;
    box-shadow: 0 0 0 3px rgba(16,185,129,0.1);
}
.pt-item .amount-wrap { display: none; margin-top: 10px; }
.pt-item.selected .amount-wrap { display: block; }

.pt-checkbox { display: none; }

.btn-confirm {
    display: flex; align-items: center; gap: 10px;
    background: linear-gradient(135deg, #10b981, #059669);
    color: white; border: none;
    padding: 14px 32px; border-radius: 14px;
    font-size: 15px; font-weight: 800; cursor: pointer;
    transition: all 0.25s; font-family: inherit; letter-spacing: 0.3px;
}
.btn-confirm:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(16,185,129,0.4); }
.btn-confirm:disabled { opacity: 0.5; cursor: not-allowed; transform: none; box-shadow: none; }

.btn-cancel {
    display: flex; align-items: center; gap: 8px;
    background: #f1f5f9; color: #64748b; border: none;
    padding: 14px 22px; border-radius: 14px;
    font-size: 14px; font-weight: 600; cursor: pointer;
    transition: all 0.2s; text-decoration: none; font-family: inherit;
}
.btn-cancel:hover { background: #e2e8f0; }

.total-strip {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 18px; background: #f0fdf4; border: 1.5px solid #bbf7d0;
    border-radius: 14px; margin-top: 16px;
}


@media (max-width: 640px) {


    .page-header {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 8px !important;
    }
    .page-header .text-right { text-align: left !important; }


    .search-row {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 10px !important;
    }
    .search-row button,
    .search-row a {
        width: 100% !important;
        justify-content: center !important;
    }


    .student-grid {
        grid-template-columns: 1fr !important;
    }

  
    .student-detail-grid {
        grid-template-columns: 1fr !important;
    }

 
    .pt-grid {
        grid-template-columns: 1fr !important;
    }


    .total-strip {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 8px !important;
    }
    .total-strip #totalAmount { font-size: 20px !important; }

   
    .summary-strip {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 8px !important;
    }


    .action-row {
        flex-direction: column-reverse !important;
        gap: 10px !important;
    }
    .action-row .btn-confirm,
    .action-row .btn-cancel {
        width: 100% !important;
        justify-content: center !important;
    }


    .empty-features {
        flex-direction: column !important;
        align-items: center !important;
        gap: 12px !important;
    }
}
</style>
