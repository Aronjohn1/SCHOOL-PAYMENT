<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
    @keyframes fadeUp {
        from { opacity:0; transform:translateY(14px); }
        to   { opacity:1; transform:translateY(0); }
    }
    .fade-up  { animation: fadeUp 0.35s ease forwards; }
    .fade-up2 { animation: fadeUp 0.35s ease 0.07s forwards; opacity:0; }
    .fade-up3 { animation: fadeUp 0.35s ease 0.14s forwards; opacity:0; }

    .field-input:focus {
        outline: none;
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99,102,241,0.12);
    }
    tbody tr { transition: background 0.15s; }

    @media print {
        .no-print, .sidebar, .topbar { display: none !important; }
        .main-content { margin-left: 0 !important; }
        .page-area { padding: 20px !important; }
        body { background: white !important; }
        .print-card { box-shadow: none !important; border: 1px solid #e2e8f0 !important; }
    }


    @media (max-width: 640px) {
 
        .filter-row {
            flex-direction: column !important;
            align-items: stretch !important;
            gap: 12px !important;
        }
        .filter-row > div { width: 100% !important; }
        .filter-row select,
        .filter-row input[type="date"],
        .filter-row input[type="month"] {
            width: 100% !important;
            box-sizing: border-box;
        }
        .filter-row .btn-row {
            display: flex !important;
            width: 100% !important;
        }
        .filter-row .btn-row button { flex: 1; justify-content: center; }

     
        .banner-inner {
            flex-direction: column !important;
            gap: 8px !important;
        }
        .banner-inner .text-right { text-align: left !important; }

   
        .summary-grid {
            grid-template-columns: 1fr !important;
        }


        .table-wrap {
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch;
        }
    }

    @media (min-width: 641px) and (max-width: 900px) {
    
        .summary-grid {
            grid-template-columns: repeat(2, 1fr) !important;
        }
    }
</style>