<?php
/**
 * Shared UI styles and helpers for admin module pages (matches dashboard).
 */
function module_mini_stat($label, $value, $icon, $icon_bg){
    return '<div class="col-6 col-lg-3 mb-2">'
        .'<div class="mod-mini-stat h-100">'
        .'<div class="mod-mini-icon '.$icon_bg.'"><i class="fas '.$icon.'"></i></div>'
        .'<div class="mod-mini-body"><div class="mod-mini-label">'.htmlspecialchars($label).'</div>'
        .'<div class="mod-mini-value">'.$value.'</div></div></div></div>';
}
function module_page_styles(){
    static $printed = false;
    if($printed) return '';
    $printed = true;
    return '<style>
    .mod-page { margin: -.25rem 0 0; }
    .mod-header {
        border: none; border-radius: 10px;
        background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
        color: #fff; box-shadow: 0 2px 10px rgba(37,99,235,.22);
        margin-bottom: 20px; overflow: hidden;
    }
    .mod-header.mod-header-expenses { background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%); box-shadow: 0 2px 10px rgba(220,38,38,.2); }
    .mod-header.mod-header-analytics { background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%); box-shadow: 0 2px 10px rgba(22,163,74,.2); }
    .mod-header.mod-header-backup { background: linear-gradient(135deg, #0891b2 0%, #06b6d4 100%); box-shadow: 0 2px 10px rgba(8,145,178,.2); }
    .mod-header .card-body { padding: 1rem 1.25rem; }
    .mod-header h4 { font-weight: 700; margin: 0 0 .2rem; font-size: 1.15rem; }
    .mod-header .mod-subtitle { opacity: .9; font-size: .82rem; margin: 0; }
    .mod-btn-action {
        border: none; border-radius: 8px; font-weight: 600; font-size: .82rem;
        padding: .45rem 1rem; box-shadow: 0 2px 6px rgba(0,0,0,.12);
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .mod-btn-action:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,.18); }
    .mod-btn-primary { background: #fff; color: #1e3a5f; }
    .mod-btn-success { background: #fff; color: #16a34a; }
    .mod-btn-export {
        border-radius: 8px; font-size: .78rem; font-weight: 600; padding: .35rem .75rem;
        border: 1px solid rgba(0,0,0,.1); background: #fff;
        transition: background .15s ease, transform .15s ease;
    }
    .mod-btn-export:hover { background: #f8f9fa; transform: translateY(-1px); }
    .mod-section {
        border: 1px solid rgba(0,0,0,.07); border-radius: 10px;
        box-shadow: 0 1px 4px rgba(0,0,0,.05); margin-bottom: 20px;
        overflow: hidden; background: #fff;
    }
    .mod-section-header {
        padding: 10px 18px; font-size: .72rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: .05em; color: #fff;
        background: linear-gradient(135deg, #495057 0%, #6c757d 100%);
    }
    .mod-section-header.mod-sh-expenses { background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%); }
    .mod-section-header.mod-sh-analytics { background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%); }
    .mod-section-header.mod-sh-backup { background: linear-gradient(135deg, #0891b2 0%, #06b6d4 100%); }
    .mod-section-header.mod-sh-filter { background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%); }
    .mod-section-header i { margin-right: .4rem; font-size: .75rem; }
    .mod-section-body { padding: .85rem; background: #fff; }
    .mod-mini-stat {
        background: #fff; border: 1px solid rgba(0,0,0,.06); border-radius: 8px;
        padding: .7rem .8rem; display: flex; align-items: center; min-height: 76px;
        box-shadow: 0 1px 3px rgba(0,0,0,.04);
        transition: box-shadow .15s ease, transform .15s ease;
    }
    .mod-mini-stat:hover { box-shadow: 0 4px 12px rgba(0,0,0,.08); transform: translateY(-2px); }
    .mod-mini-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-size: .85rem; color: #fff; flex-shrink: 0; margin-right: .65rem;
    }
    .mod-mini-label {
        font-size: .68rem; text-transform: uppercase; letter-spacing: .03em;
        color: #6c757d; line-height: 1.1; margin-bottom: .2rem;
    }
    .mod-mini-value { font-size: 1.02rem; font-weight: 700; color: #212529; line-height: 1.2; }
    .mod-filter-card {
        background: #fafbfc; border: 1px solid rgba(0,0,0,.06);
        border-radius: 8px; padding: .85rem;
    }
    .mod-filter-card label { font-size: .72rem; text-transform: uppercase; letter-spacing: .03em; color: #6c757d; font-weight: 600; margin-bottom: .25rem; }
    .mod-table-wrap { border-radius: 8px; overflow: hidden; border: 1px solid rgba(0,0,0,.06); }
    .mod-table { margin-bottom: 0; font-size: .82rem; }
    .mod-table thead th {
        background: #f8f9fa; font-size: .7rem; text-transform: uppercase;
        letter-spacing: .03em; color: #6c757d; border-bottom-width: 2px;
        padding: .5rem .65rem; white-space: nowrap;
    }
    .mod-table tbody td { padding: .45rem .65rem; vertical-align: middle; border-color: #f1f3f5; }
    .mod-table tbody tr { transition: background .12s ease; }
    .mod-table tbody tr:hover { background: #f0f7ff !important; }
    .mod-table tfoot th { background: #e9ecef; font-size: .82rem; padding: .5rem .65rem; }
    .mod-action-btn {
        width: 30px; height: 30px; padding: 0; border-radius: 6px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: .78rem; transition: transform .12s ease, box-shadow .12s ease;
    }
    .mod-action-btn:hover { transform: translateY(-1px); box-shadow: 0 2px 6px rgba(0,0,0,.12); }
    .mod-chart-panel {
        border: 1px solid rgba(0,0,0,.07); border-radius: 10px;
        box-shadow: 0 1px 4px rgba(0,0,0,.05); height: 100%;
        display: flex; flex-direction: column; overflow: hidden;
    }
    .mod-chart-panel .mod-chart-head {
        padding: .55rem .85rem; font-weight: 600; font-size: .85rem;
        border-bottom: 1px solid rgba(0,0,0,.06); background: #fff;
    }
    .mod-chart-panel .mod-chart-body { padding: .5rem .65rem .65rem; flex: 1; min-height: 200px; max-height: 220px; position: relative; }
    .mod-chart-panel canvas { max-height: 200px !important; }
    .mod-warning-card {
        border: 1px solid rgba(245,158,11,.35); border-radius: 10px;
        background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
        padding: .85rem 1rem; margin-bottom: 20px;
        box-shadow: 0 1px 4px rgba(245,158,11,.15);
    }
    .mod-warning-card .mod-warning-title { font-weight: 700; color: #92400e; font-size: .9rem; margin-bottom: .25rem; }
    .mod-warning-card p { margin: 0; font-size: .82rem; color: #78350f; }
    .mod-badge-id { font-size: .72rem; padding: .2rem .5rem; border-radius: 4px; background: #f1f3f5; border: 1px solid #dee2e6; color: #495057; font-weight: 600; }
    .mod-badge-status { font-size: .72rem; padding: .25rem .55rem; border-radius: 20px; font-weight: 600; }
    </style>';
}
