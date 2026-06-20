<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
@keyframes fadeUp {
    from { opacity:0; transform:translateY(14px); }
    to   { opacity:1; transform:translateY(0); }
}
.fade-up  { animation: fadeUp 0.35s ease forwards; }
.fade-up2 { animation: fadeUp 0.35s ease 0.05s forwards; opacity:0; }
.fade-up3 { animation: fadeUp 0.35s ease 0.10s forwards; opacity:0; }

.modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:999; align-items:center; justify-content:center; }
.modal-overlay.active { display:flex; }

.field-input:focus {
    outline: none;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99,102,241,0.12);
}
tbody tr { transition: background 0.15s; }

.active-level {
    border-color: #6366f1 !important;
    background-color: #eef2ff !important;
}
.active-level .icon-wrap  { background: #e0e7ff !important; }
.active-level .level-icon { color: #6366f1 !important; }
.active-level .level-title { color: #4338ca !important; }
.active-level .level-sub   { color: #818cf8 !important; }


@media (max-width: 640px) {


    .summary-cards-grid {
        grid-template-columns: 1fr !important;
    }


    .card-header-row {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 10px !important;
    }
    .card-header-row .flex.items-center.gap-3 {
        flex-direction: column !important;
        align-items: stretch !important;
    }
    .card-header-row input[type="text"] {
        width: 100% !important;
        box-sizing: border-box;
    }
    .card-header-row form { order: 1 !important; }
    .card-header-row button[onclick] {
        order: 2 !important;
        width: 100% !important;
        justify-content: center !important;
        white-space: nowrap !important;
    }


    #bulkToolbar { flex-wrap: wrap; gap: 8px; }
    #bulkToolbar p { display: none; }


    #panel_hs,
    #panel_col { overflow-x: auto; -webkit-overflow-scrolling: touch; }


    .tab-btn { padding: 8px 12px !important; font-size: 12px !important; }


    .modal-overlay > div {
        max-width: 100% !important;
        margin: 0 !important;
        border-radius: 20px 20px 0 0 !important;
        position: fixed !important;
        bottom: 0 !important;
        left: 0 !important;
        right: 0 !important;
        max-height: 92vh !important;
    }
}

@media (min-width: 641px) and (max-width: 900px) {
 
    .summary-cards-grid {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}
</style>
