<style id="sales-report-page-styles">
    #sales-report-table {
        width: 100%;
        font-size: 0.8125rem;
        margin-bottom: 0;
    }
    #sales-report-table td,
    #sales-report-table th {
        padding: 3px 7px !important;
        vertical-align: middle !important;
        border-color: #dee2e6;
        line-height: 1.25;
    }
    #sales-report-table thead th {
        background-color: #f1f3f5;
        font-weight: 600;
        font-size: 0.72rem;
        color: #495057;
        border-bottom-width: 2px;
    }
    #sales-report-table tbody tr:nth-child(even) {
        background-color: #f8f9fa;
    }
    #sales-report-table tbody tr:hover {
        background-color: #e8f4fc !important;
    }
    #sales-report-table tfoot th {
        background-color: #e9ecef;
        font-weight: 700;
        border-top-width: 2px;
    }
    #sales-report-table .col-idx {
        width: 1%;
        white-space: nowrap;
        text-align: center;
    }
    #sales-report-table .col-date,
    #sales-report-table .col-receipt,
    #sales-report-table .col-payment,
    #sales-report-table .col-num {
        width: 1%;
        white-space: nowrap;
    }
    #sales-report-table .col-date {
        font-variant-numeric: tabular-nums;
    }
    #sales-report-table .col-receipt {
        text-align: center;
    }
    #sales-report-table .receipt-badge {
        display: inline-block;
        padding: 1px 6px;
        font-size: 0.72rem;
        font-weight: 500;
        color: #6c757d;
        background: #f1f3f5;
        border: 1px solid #dee2e6;
        border-radius: 3px;
        white-space: nowrap;
        line-height: 1.3;
    }
    #sales-report-table .col-product {
        white-space: normal;
        word-break: break-word;
        min-width: 140px;
    }
    #sales-report-table .col-customer {
        min-width: 110px;
        max-width: 180px;
        white-space: normal;
        word-break: break-word;
    }
    #sales-report-table .col-payment {
        text-align: center;
    }
    #sales-report-table .col-num {
        text-align: right;
        font-variant-numeric: tabular-nums;
    }
    #sales-report-table .profit-na {
        color: #adb5bd;
    }
    .sales-report-scroll {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .sales-summary-row {
        margin-bottom: 0.75rem !important;
    }
    .sales-summary-card {
        border-radius: .35rem;
        border: 1px solid rgba(0,0,0,.08);
        background: #fff;
        padding: 0.45rem 0.65rem;
        height: 100%;
        box-shadow: none;
    }
    .sales-summary-card .label {
        font-size: .68rem;
        text-transform: uppercase;
        letter-spacing: .03em;
        color: #6c757d;
        margin-bottom: .15rem;
        line-height: 1.1;
    }
    .sales-summary-card .value {
        font-size: 1rem;
        font-weight: 700;
        color: #343a40;
        line-height: 1.15;
    }
    .sales-summary-card .icon {
        display: none;
    }
    .sales-print-only {
        display: none;
    }
    #sales-print-summary {
        margin-bottom: 0.75rem;
        padding: 0.45rem 0.65rem;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        background: #f8f9fa;
        font-size: 0.78rem;
        line-height: 1.4;
    }
    #sales-print-summary .summary-item {
        display: inline-block;
        white-space: nowrap;
        margin-right: 0.15rem;
    }
    #sales-print-summary .summary-item strong {
        font-weight: 600;
        color: #495057;
    }
    #sales-print-summary .summary-sep {
        color: #ced4da;
        margin: 0 0.35rem;
    }
    #sales-print-totals {
        margin-top: 0.75rem;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        overflow: hidden;
    }
    #sales-print-totals table {
        width: 100%;
        margin: 0;
        font-size: 0.78rem;
        border-collapse: collapse;
    }
    #sales-print-totals td {
        padding: 5px 10px;
        border: 1px solid #dee2e6;
        background: #f8f9fa;
    }
    #sales-print-totals td.label {
        font-weight: 600;
        color: #495057;
        width: 25%;
    }
    #sales-print-totals td.value {
        text-align: right;
        font-weight: 700;
        font-variant-numeric: tabular-nums;
    }
    #print_header .system-logo-print {
        width: 45px;
        height: 45px;
        padding: 4px;
    }
    #print_header .system-logo-print img {
        max-width: 100%;
        max-height: 100%;
    }
    @media print {
        .no-print { display: none !important; }
        #print_header { display: flex !important; }
        .sales-print-only { display: block !important; }
        #sales-print-summary { display: block !important; }
        #sales-print-totals { display: block !important; }
        #sales-report-table { font-size: 10px; border-color: #dee2e6; }
        #sales-report-table td,
        #sales-report-table th {
            padding: 2px 5px !important;
            border-color: #dee2e6 !important;
        }
        #sales-report-table thead th {
            background-color: #f1f3f5 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        #sales-report-table tbody tr:nth-child(even) {
            background-color: #f8f9fa !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        #sales-report-table tfoot { display: none; }
        #sales-report-table .col-date,
        #sales-report-table .col-receipt,
        #sales-report-table .col-payment,
        #sales-report-table .col-num { white-space: nowrap; }
    }
</style>
<?php
function sales_report_customer_name($row){
    if(!empty($row['delivery_address']) && preg_match('/Customer:\s*(.+)$/i', $row['delivery_address'], $matches)){
        return trim($matches[1]);
    }
    $name = trim($row['client_name']);
    if($name === '' || stripos($name, 'Walk-in') !== false) return 'Walk-in Customer';
    return $name;
}
function sales_report_payment_label($method){
    if(strcasecmp($method, 'Cash') === 0) return 'Cash';
    if(strcasecmp($method, 'M-Pesa') === 0) return 'M-Pesa';
    return $method;
}
function sales_report_cost_column(){
    global $conn;
    static $column = false;
    if($column !== false) return $column;
    $column = null;
    if(!isset($conn) || !$conn) return $column;
    $check = $conn->query("SHOW COLUMNS FROM `inventory`");
    if($check){
        $allowed = array('cost_price', 'cost', 'buy_price');
        while($col = $check->fetch_assoc()){
            if(in_array(strtolower($col['Field']), $allowed, true)){
                $column = $col['Field'];
                break;
            }
        }
    }
    return $column;
}
function sales_report_line_revenue($row, $order_subtotal){
    $line = (float)$row['quantity'] * (float)$row['price'];
    $discount = isset($row['discount_total']) ? (float)$row['discount_total'] : 0;
    if($discount > 0 && $order_subtotal > 0){
        $line -= ($line / $order_subtotal) * $discount;
    }
    return max(0, $line);
}
function sales_report_line_profit($row, $order_subtotal){
    if(!sales_report_row_profit_calculable($row)){
        return null;
    }
    $qty = (int)$row['quantity'];
    $revenue = sales_report_line_revenue($row, $order_subtotal);
    return $revenue - ((float)$row['cost_price'] * $qty);
}
function sales_report_row_profit_calculable($row){
    if(!admin_can_view_profit() || !sales_report_cost_column()){
        return false;
    }
    if(!array_key_exists('cost_price', $row) || $row['cost_price'] === null || $row['cost_price'] === ''){
        return false;
    }
    if((float)$row['cost_price'] <= 0){
        return false;
    }
    return true;
}
function sales_report_format_profit($amount, $calculable){
    if(!admin_can_view_profit()){
        return '';
    }
    if(!$calculable){
        return '<span class="profit-na">&mdash;</span>';
    }
    return format_price($amount);
}
function sales_report_format_total_profit($amount){
    if(!admin_can_view_profit() || !sales_report_cost_column()){
        return '<span class="profit-na">&mdash;</span>';
    }
    return format_price($amount);
}

$show_profit = admin_can_view_profit();
$report_cols = $show_profit ? 10 : 9;
$date_start = isset($_GET['date_start']) ? $_GET['date_start'] : date('Y-m-d', strtotime(date('Y-m-d').' -7 days'));
$date_end = isset($_GET['date_end']) ? $_GET['date_end'] : date('Y-m-d');
$payment_filter = isset($_GET['payment_method']) ? trim($_GET['payment_method']) : '';

$date_start = date('Y-m-d', strtotime($date_start));
$date_end = date('Y-m-d', strtotime($date_end));

$payment_sql = '';
if($payment_filter !== '' && in_array($payment_filter, array('Cash', 'M-Pesa'), true)){
    $payment_esc = $conn->real_escape_string($payment_filter);
    $payment_sql = " AND o.payment_method = '{$payment_esc}' ";
}

$rows = array();
$summary = array(
    'total_amount' => 0,
    'total_profit' => 0,
    'total_items' => 0,
    'total_orders' => array(),
    'cash_total' => 0,
    'mpesa_total' => 0,
);

$cost_column = $show_profit ? sales_report_cost_column() : null;
if($show_profit && $cost_column && db_table_has_column('order_list', 'cost_price')){
    $cost_select = "COALESCE(NULLIF(ol.cost_price, ''), i.`{$cost_column}`) AS cost_price";
}elseif($show_profit && $cost_column){
    $cost_select = "i.`{$cost_column}` AS cost_price";
}else{
    $cost_select = "NULL AS cost_price";
}
$discount_select = db_table_has_column('orders', 'discount_total') ? 'o.discount_total' : '0 AS discount_total';

$sql = "SELECT
    s.id AS sale_id,
    s.date_created AS sale_date,
    o.id AS order_id,
    o.ref_code,
    o.payment_method,
    o.date_created AS order_date,
    o.delivery_address,
    {$discount_select},
    ol.quantity,
    ol.price,
    p.name AS product_name,
    i.variant,
    {$cost_select},
    CONCAT(c.firstname,' ',c.lastname) AS client_name
FROM sales s
INNER JOIN orders o ON o.id = s.order_id
INNER JOIN order_list ol ON ol.order_id = o.id
INNER JOIN inventory i ON ol.inventory_id = i.id
INNER JOIN products p ON p.id = i.product_id
INNER JOIN clients c ON c.id = o.client_id
WHERE DATE(s.date_created) BETWEEN '{$date_start}' AND '{$date_end}'{$payment_sql}
ORDER BY UNIX_TIMESTAMP(s.date_created) DESC, ol.id ASC";

$qry = $conn->query($sql);
$raw_rows = array();
if($qry){
    while($row = $qry->fetch_assoc()){
        $raw_rows[] = $row;
    }
}
$order_subtotals = array();
foreach($raw_rows as $row){
    $oid = $row['order_id'];
    if(!isset($order_subtotals[$oid])){
        $order_subtotals[$oid] = 0;
    }
    $order_subtotals[$oid] += (float)$row['quantity'] * (float)$row['price'];
}
foreach($raw_rows as $row){
    $line_total = (float)$row['quantity'] * (float)$row['price'];
    $order_subtotal = isset($order_subtotals[$row['order_id']]) ? $order_subtotals[$row['order_id']] : 0;
    $line_profit = null;
    $profit_calculable = false;
    if($show_profit){
        $line_profit = sales_report_line_profit($row, $order_subtotal);
        $profit_calculable = sales_report_row_profit_calculable($row);
    }
    $row['line_total'] = $line_total;
    $row['line_profit'] = $line_profit;
    $row['profit_calculable'] = $profit_calculable;
    $rows[] = $row;
    $summary['total_amount'] += $line_total;
    if($profit_calculable){
        $summary['total_profit'] += $line_profit;
    }
    $summary['total_items'] += (int)$row['quantity'];
    $summary['total_orders'][$row['order_id']] = true;
    if(strcasecmp($row['payment_method'], 'Cash') === 0){
        $summary['cash_total'] += $line_total;
    }elseif(strcasecmp($row['payment_method'], 'M-Pesa') === 0){
        $summary['mpesa_total'] += $line_total;
    }
}
$summary['total_orders'] = count($summary['total_orders']);
?>
<div class="card card-primary card-outline">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="fas fa-chart-line mr-1"></i> Sales Report</h5>
    </div>
    <div class="card-body">
        <form id="filter-form" class="no-print">
            <div class="row align-items-end">
                <div class="form-group col-md-2">
                    <label for="date_start">Date Start</label>
                    <input type="date" class="form-control form-control-sm" name="date_start" id="date_start" value="<?php echo $date_start ?>">
                </div>
                <div class="form-group col-md-2">
                    <label for="date_end">Date End</label>
                    <input type="date" class="form-control form-control-sm" name="date_end" id="date_end" value="<?php echo $date_end ?>">
                </div>
                <div class="form-group col-md-2">
                    <label for="payment_method">Payment Method</label>
                    <select class="form-control form-control-sm" name="payment_method" id="payment_method">
                        <option value="" <?php echo $payment_filter === '' ? 'selected' : '' ?>>All</option>
                        <option value="Cash" <?php echo $payment_filter === 'Cash' ? 'selected' : '' ?>>Cash</option>
                        <option value="M-Pesa" <?php echo $payment_filter === 'M-Pesa' ? 'selected' : '' ?>>M-Pesa</option>
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <button type="submit" class="btn btn-flat btn-block btn-primary btn-sm"><i class="fa fa-filter"></i> Filter</button>
                </div>
                <div class="form-group col-md-2">
                    <button type="button" class="btn btn-flat btn-block btn-success btn-sm" id="printBTN"><i class="fa fa-print"></i> Print</button>
                </div>
                <div class="form-group col-md-1">
                    <button type="button" class="btn btn-flat btn-block btn-info btn-sm" id="excelBTN"><i class="fa fa-file-excel"></i> Excel</button>
                </div>
                <div class="form-group col-md-1">
                    <button type="button" class="btn btn-flat btn-block btn-danger btn-sm" id="pdfBTN"><i class="fa fa-file-pdf"></i> PDF</button>
                </div>
            </div>
        </form>

        <div class="row sales-summary-row no-print">
            <div class="col">
                <div class="sales-summary-card">
                    <div class="label">Total Sales</div>
                    <div class="value"><?php echo format_price($summary['total_amount']) ?></div>
                </div>
            </div>
            <div class="col">
                <div class="sales-summary-card">
                    <div class="label">Orders</div>
                    <div class="value"><?php echo format_num($summary['total_orders']) ?></div>
                </div>
            </div>
            <div class="col">
                <div class="sales-summary-card">
                    <div class="label">Items</div>
                    <div class="value"><?php echo format_num($summary['total_items']) ?></div>
                </div>
            </div>
            <div class="col">
                <div class="sales-summary-card">
                    <div class="label">Cash</div>
                    <div class="value"><?php echo format_price($summary['cash_total']) ?></div>
                </div>
            </div>
            <div class="col">
                <div class="sales-summary-card">
                    <div class="label">M-Pesa</div>
                    <div class="value"><?php echo format_price($summary['mpesa_total']) ?></div>
                </div>
            </div>
            <?php if($show_profit): ?>
            <div class="col">
                <div class="sales-summary-card">
                    <div class="label">Total Profit</div>
                    <div class="value"><?php echo sales_report_format_total_profit($summary['total_profit']) ?></div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div id="printable">
            <div class="row row-cols-2 justify-content-center align-items-center mb-2" id="print_header" style="display:none">
                <div class="col-2 text-center">
                    <span class="system-logo-wrapper system-logo-print">
                    <img src="<?php echo validate_image($_settings->info('logo')) ?>" alt="<?php echo htmlspecialchars($_settings->info('short_name')) ?>">
                    </span>
                </div>
                <div class="col-10">
                    <h4 class="text-center m-0"><?php echo $_settings->info('name') ?></h4>
                    <h3 class="text-center m-0"><b>Sales Report</b></h3>
                    <?php if($date_start != $date_end): ?>
                    <p class="text-center m-0 mb-1">Date Between <?php echo date('M d, Y', strtotime($date_start)) ?> and <?php echo date('M d, Y', strtotime($date_end)) ?></p>
                    <?php else: ?>
                    <p class="text-center m-0 mb-1">As of <?php echo date('M d, Y', strtotime($date_start)) ?></p>
                    <?php endif; ?>
                    <?php if($payment_filter !== ''): ?>
                    <p class="text-center m-0">Payment Method: <?php echo htmlspecialchars($payment_filter) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div id="sales-print-summary" class="sales-print-only">
                <span class="summary-item"><strong>Total Sales:</strong> <?php echo format_price($summary['total_amount']) ?></span>
                <span class="summary-sep">|</span>
                <span class="summary-item"><strong>Orders:</strong> <?php echo format_num($summary['total_orders']) ?></span>
                <span class="summary-sep">|</span>
                <span class="summary-item"><strong>Items:</strong> <?php echo format_num($summary['total_items']) ?></span>
                <span class="summary-sep">|</span>
                <span class="summary-item"><strong>Cash:</strong> <?php echo format_price($summary['cash_total']) ?></span>
                <span class="summary-sep">|</span>
                <span class="summary-item"><strong>M-Pesa:</strong> <?php echo format_price($summary['mpesa_total']) ?></span>
            </div>

            <div class="table-responsive sales-report-scroll">
                <table class="table table-bordered table-sm mb-0" id="sales-report-table">
                    <thead>
                        <tr>
                            <th class="col-idx">#</th>
                            <th class="col-date">Date</th>
                            <th class="col-receipt">ReceiptNo</th>
                            <th class="col-product">Product Name</th>
                            <th class="col-customer">Customer</th>
                            <th class="col-payment">Payment</th>
                            <th class="col-num">Price</th>
                            <th class="col-num">Qty</th>
                            <?php if($show_profit): ?><th class="col-num">Profit</th><?php endif; ?>
                            <th class="col-num">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($rows) > 0): ?>
                        <?php $i = 1; foreach($rows as $row): ?>
                        <?php
                            $product_label = trim(stripslashes($row['product_name']));
                            $customer_name = sales_report_customer_name($row);
                            $payment_label = sales_report_payment_label($row['payment_method']);
                        ?>
                        <tr>
                            <td class="col-idx"><?php echo $i++ ?></td>
                            <td class="col-date"><?php echo date('Y-m-d H:i', strtotime($row['order_date'])) ?></td>
                            <td class="col-receipt"><span class="receipt-badge"><?php echo htmlspecialchars($row['ref_code']) ?></span></td>
                            <td class="col-product"><?php echo htmlspecialchars($product_label) ?></td>
                            <td class="col-customer"><?php echo htmlspecialchars($customer_name) ?></td>
                            <td class="col-payment"><?php echo htmlspecialchars($payment_label) ?></td>
                            <td class="col-num"><?php echo format_price($row['price']) ?></td>
                            <td class="col-num"><?php echo format_num($row['quantity']) ?></td>
                            <?php if($show_profit): ?><td class="col-num"><?php echo sales_report_format_profit($row['line_profit'], $row['profit_calculable']) ?></td><?php endif; ?>
                            <td class="col-num"><?php echo format_price($row['line_total']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td class="text-center text-muted py-4" colspan="<?php echo $report_cols ?>">No sales found for the selected filters.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                    <?php if(count($rows) > 0): ?>
                    <tfoot>
                        <tr>
                            <th colspan="<?php echo $show_profit ? 8 : 8 ?>" class="text-right">Totals</th>
                            <?php if($show_profit): ?><th class="col-num"><?php echo sales_report_format_total_profit($summary['total_profit']) ?></th><?php endif; ?>
                            <th class="col-num"><?php echo format_price($summary['total_amount']) ?></th>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>

            <div id="sales-print-totals" class="sales-print-only">
                <table>
                    <tr>
                        <td class="label">Total Orders</td>
                        <td class="value"><?php echo format_num($summary['total_orders']) ?></td>
                        <td class="label">Total Qty</td>
                        <td class="value"><?php echo format_num($summary['total_items']) ?></td>
                    </tr>
                    <tr>
                        <?php if($show_profit): ?>
                        <td class="label">Total Profit</td>
                        <td class="value"><?php echo sales_report_format_total_profit($summary['total_profit']) ?></td>
                        <?php endif; ?>
                        <td class="label">Grand Total</td>
                        <td class="value"><?php echo format_price($summary['total_amount']) ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
<noscript>
    <style>
        .m-0{margin:0}
        .text-center{text-align:center}
        .text-right{text-align:right}
        .table{border-collapse:collapse;width:100%}
        .table tr,.table td,.table th{border:1px solid gray}
    </style>
</noscript>
<script>
$(function(){
    $('#filter-form').submit(function(e){
        e.preventDefault();
        var qs = 'date_start=' + encodeURIComponent($('[name="date_start"]').val())
            + '&date_end=' + encodeURIComponent($('[name="date_end"]').val())
            + '&payment_method=' + encodeURIComponent($('[name="payment_method"]').val());
        location.href = './?page=sales&' + qs;
    });

    function openPrintWindow(forPdf){
        var head = $('head').clone();
        var rep = $('#printable').clone();
        var styles = $('#sales-report-page-styles').clone();
        var ns = $('noscript').clone().html();
        start_loader();
        rep.prepend(styles);
        rep.prepend(ns);
        rep.prepend(head);
        rep.find('#print_header').show();
        rep.find('.sales-print-only').css('display', 'block');
        rep.find('#sales-report-table tfoot').hide();
        var nw = window.open('', '_blank', 'width=1100,height=800');
        nw.document.write('<!DOCTYPE html><html><head><title>Sales Report</title></head><body>');
        nw.document.write(rep.html());
        nw.document.write('</body></html>');
        nw.document.close();
        setTimeout(function(){
            if(forPdf){
                nw.print();
            }else{
                nw.print();
            }
            setTimeout(function(){
                nw.close();
                end_loader();
            }, 300);
        }, 400);
    }

    $('#printBTN').click(function(){
        openPrintWindow(false);
    });

    $('#pdfBTN').click(function(){
        openPrintWindow(true);
    });

    $('#excelBTN').click(function(){
        var csv = [];
        var headers = [];
        $('#sales-report-table thead th').each(function(){
            headers.push('"' + $(this).text().trim().replace(/"/g, '""') + '"');
        });
        csv.push(headers.join(','));

        $('#sales-report-table tbody tr').each(function(){
            if($(this).find('td').length === 1) return;
            var row = [];
            $(this).find('td').each(function(){
                var text = $(this).text().replace(/\s+/g, ' ').trim().replace(/"/g, '""');
                row.push('"' + text + '"');
            });
            if(row.length) csv.push(row.join(','));
        });

        if($('#sales-report-table tfoot tr').length){
            var foot = [];
            $('#sales-report-table tfoot th').each(function(){
                foot.push('"' + $(this).text().trim().replace(/"/g, '""') + '"');
            });
            csv.push(foot.join(','));
        }

        var blob = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'sales-report-<?php echo $date_start ?>-to-<?php echo $date_end ?>.csv';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
});
</script>
