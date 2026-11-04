<style>
    @page { margin: 26px 30px; }
    body {
        font-family: 'Helvetica', 'Arial', sans-serif;
        color: #3a2e4d;
        font-size: 11px;
    }
    .pdf-header {
        display: table;
        width: 100%;
        background: #7d4fb0;
        color: #fff;
        padding: 14px 18px;
        border-radius: 8px;
        margin-bottom: 16px;
    }
    .pdf-header-cell {
        display: table-cell;
        vertical-align: middle;
    }
    .pdf-header-logo {
        width: 40px;
    }
    .pdf-header-logo-badge {
        width: 34px;
        height: 34px;
        line-height: 34px;
        text-align: center;
        border-radius: 8px;
        background: rgba(255, 255, 255, .18);
        color: #fff;
        font-size: 16px;
        font-weight: bold;
    }
    .pdf-header h1 {
        font-size: 16px;
        color: #fff;
        margin: 0 0 3px;
    }
    .pdf-header p {
        margin: 0;
        color: rgba(255, 255, 255, .85);
        font-size: 10px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 6px;
    }
    thead th {
        background: #ede8f8;
        color: #6b4c9a;
        text-transform: uppercase;
        font-size: 9px;
        letter-spacing: .4px;
        padding: 7px 8px;
        text-align: left;
        border-bottom: 1.5px solid #d8c8ef;
    }
    tbody td {
        padding: 6px 8px;
        border-bottom: 1px solid #ece6f5;
        font-size: 10.5px;
    }
    tbody tr:nth-child(even) {
        background: #faf8fd;
    }
    tfoot td {
        padding: 8px;
        font-weight: bold;
        border-top: 2px solid #b48ad4;
        font-size: 11px;
    }
    .pdf-total-box {
        margin-top: 14px;
        text-align: right;
        font-size: 13px;
        font-weight: bold;
        color: #6b4c9a;
    }
    .pdf-footer {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        font-size: 9px;
        color: #9992a3;
        text-align: right;
    }
    .badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 9.5px;
        font-weight: bold;
    }
    .badge-danger { background: #fde2e2; color: #b03030; }
    .badge-warning { background: #fdf0d5; color: #a5710f; }
    .badge-success { background: #e2f5e2; color: #2f8a2f; }
</style>
