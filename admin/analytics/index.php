<?php
require_once __DIR__.'/../inc/module_ui.php';
if(!admin_can_view_profit()){
    echo '<div class="alert alert-danger">Access denied. Profit Analytics is available to administrators only.</div>';
    return;
}
$period = isset($_GET['period']) ? $_GET['period'] : 'month';
$bounds = profit_analytics_period_bounds($period);
$date_start = isset($_GET['date_start']) ? $_GET['date_start'] : $bounds['start'];
$date_end = isset($_GET['date_end']) ? $_GET['date_end'] : $bounds['end'];
$rows = profit_analytics_daily_rows($date_start, $date_end);
$chart_daily = profit_analytics_chart_series($date_start, $date_end, 'day');
$chart_monthly = profit_analytics_chart_series(date('Y-01-01'), date('Y-m-d'), 'month');
$summary_today = dashboard_net_profit(date('Y-m-d'), date('Y-m-d'));
$summary_week = dashboard_net_profit(date('Y-m-d', strtotime('monday this week')), date('Y-m-d'));
$summary_month = dashboard_net_profit(date('Y-m-01'), date('Y-m-d'));
$summary_year = dashboard_net_profit(date('Y-01-01'), date('Y-m-d'));
$totals = array('sales' => 0, 'cost' => 0, 'expenses' => 0, 'profit' => 0, 'net' => 0);
foreach($rows as $r){
    $totals['sales'] += $r['sales'];
    if($r['cost'] !== null) $totals['cost'] += (float)$r['cost'];
    $totals['expenses'] += $r['expenses'];
    if($r['profit'] !== null) $totals['profit'] += (float)$r['profit'];
    if($r['net_profit'] !== null) $totals['net'] += (float)$r['net_profit'];
}
echo module_page_styles();
?>
<?php if($_settings->chk_flashdata('success')): ?>
<script>alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')</script>
<?php endif; ?>
<div class="mod-page" id="profit-analytics-printable">
    <div class="card mod-header mod-header-analytics">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center">
            <div class="mb-2 mb-md-0">
                <h4><i class="fas fa-chart-line mr-2"></i>Profit Analytics</h4>
                <p class="mod-subtitle">Sales profit, expenses, and net profit insights</p>
            </div>
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn mod-btn-export" id="paPrintBtn"><i class="fas fa-print text-secondary"></i> Print</button>
                <button type="button" class="btn mod-btn-export" id="paPdfBtn"><i class="fas fa-file-pdf text-danger"></i> PDF</button>
                <button type="button" class="btn mod-btn-export" id="paExcelBtn"><i class="fas fa-file-excel text-success"></i> Excel</button>
            </div>
        </div>
    </div>

    <div class="mod-section">
        <div class="mod-section-header mod-sh-analytics"><i class="fas fa-coins"></i> Profit Summary</div>
        <div class="mod-section-body">
            <div class="row">
                <?php
                echo module_mini_stat("Today's Profit", dashboard_format_net_profit($summary_today), 'fa-sun', 'bg-success');
                echo module_mini_stat('Weekly Profit', dashboard_format_net_profit($summary_week), 'fa-calendar-week', 'bg-teal');
                echo module_mini_stat('Monthly Profit', dashboard_format_net_profit($summary_month), 'fa-calendar-alt', 'bg-primary');
                echo module_mini_stat('Yearly Profit', dashboard_format_net_profit($summary_year), 'fa-chart-area', 'bg-indigo');
                ?>
            </div>
        </div>
    </div>

    <div class="mod-section">
        <div class="mod-section-header mod-sh-filter"><i class="fas fa-filter"></i> Date Range</div>
        <div class="mod-section-body">
            <form method="get" class="mod-filter-card mb-0">
                <input type="hidden" name="page" value="analytics">
                <div class="row align-items-end">
                    <div class="col-md-4 form-group mb-2 mb-md-0">
                        <label>Date From</label>
                        <input type="date" name="date_start" class="form-control form-control-sm" value="<?php echo htmlspecialchars($date_start) ?>">
                    </div>
                    <div class="col-md-4 form-group mb-2 mb-md-0">
                        <label>Date To</label>
                        <input type="date" name="date_end" class="form-control form-control-sm" value="<?php echo htmlspecialchars($date_end) ?>">
                    </div>
                    <div class="col-md-4 form-group mb-0">
                        <button type="submit" class="btn btn-sm btn-success btn-block" style="border-radius:8px;font-weight:600;">
                            <i class="fas fa-search mr-1"></i> Apply Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="mod-section">
        <div class="mod-section-header mod-sh-analytics"><i class="fas fa-chart-bar"></i> Charts</div>
        <div class="mod-section-body">
            <div class="row">
                <div class="col-lg-6 mb-3 mb-lg-0">
                    <div class="mod-chart-panel">
                        <div class="mod-chart-head"><i class="fas fa-chart-line text-success mr-1"></i> Daily Profit Trend</div>
                        <div class="mod-chart-body"><canvas id="paDailyChart"></canvas></div>
                    </div>
                </div>
                <div class="col-lg-6 mb-3 mb-lg-0">
                    <div class="mod-chart-panel">
                        <div class="mod-chart-head"><i class="fas fa-chart-line text-primary mr-1"></i> Monthly Profit Trend</div>
                        <div class="mod-chart-body"><canvas id="paMonthlyChart"></canvas></div>
                    </div>
                </div>
                <div class="col-lg-6 mt-lg-3">
                    <div class="mod-chart-panel">
                        <div class="mod-chart-head"><i class="fas fa-balance-scale text-warning mr-1"></i> Profit vs Expenses</div>
                        <div class="mod-chart-body"><canvas id="paVsExpChart"></canvas></div>
                    </div>
                </div>
                <div class="col-lg-6 mt-lg-3">
                    <div class="mod-chart-panel">
                        <div class="mod-chart-head"><i class="fas fa-chart-area text-info mr-1"></i> Sales vs Profit</div>
                        <div class="mod-chart-body"><canvas id="paSalesChart"></canvas></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mod-section">
        <div class="mod-section-header mod-sh-analytics"><i class="fas fa-table"></i> Profit Breakdown</div>
        <div class="mod-section-body p-0">
            <div class="mod-table-wrap">
                <table class="table table-hover mod-table" id="profit-analytics-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th class="text-right">Sales</th>
                            <th class="text-right">Cost</th>
                            <th class="text-right">Expenses</th>
                            <th class="text-right">Profit</th>
                            <th class="text-right">Net Profit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($rows) > 0): foreach($rows as $r): ?>
                        <tr>
                            <td class="text-nowrap"><?php echo date('Y-m-d', strtotime($r['date'])) ?></td>
                            <td class="text-right"><?php echo format_price($r['sales']) ?></td>
                            <td class="text-right"><?php echo $r['cost'] === null ? '&mdash;' : format_price($r['cost']) ?></td>
                            <td class="text-right text-danger"><?php echo format_price($r['expenses']) ?></td>
                            <td class="text-right"><?php echo $r['profit'] === null ? '&mdash;' : format_price($r['profit']) ?></td>
                            <td class="text-right font-weight-bold text-success"><?php echo $r['net_profit'] === null ? '&mdash;' : format_price($r['net_profit']) ?></td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No data for selected period.</td></tr>
                        <?php endif; ?>
                    </tbody>
                    <?php if(count($rows) > 0): ?>
                    <tfoot>
                        <tr>
                            <th>Totals</th>
                            <th class="text-right"><?php echo format_price($totals['sales']) ?></th>
                            <th class="text-right"><?php echo format_price($totals['cost']) ?></th>
                            <th class="text-right"><?php echo format_price($totals['expenses']) ?></th>
                            <th class="text-right"><?php echo format_price($totals['profit']) ?></th>
                            <th class="text-right"><?php echo format_price($totals['net']) ?></th>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
            <p class="small text-muted mb-0 px-3 py-2 border-top">Profit = Selling Price &minus; Buying Cost &middot; Net Profit = Profit &minus; Expenses</p>
        </div>
    </div>
</div>
<script>
$(function(){
    if(typeof Chart === 'undefined') return;
    var daily = <?php echo json_encode($chart_daily) ?>;
    var monthly = <?php echo json_encode($chart_monthly) ?>;
    var chartOpts = {
        responsive: true,
        maintainAspectRatio: false,
        legend: { labels: { boxWidth: 10, fontSize: 10, padding: 8 } },
        tooltips: { mode: 'index', intersect: false },
        scales: {
            yAxes: [{ ticks: { beginAtZero: true, fontSize: 9, maxTicksLimit: 6, callback: function(v){ return 'Ksh '+Number(v).toLocaleString(); } }, gridLines: { color: 'rgba(0,0,0,.04)' } }],
            xAxes: [{ ticks: { fontSize: 9, maxRotation: 0, autoSkip: true, maxTicksLimit: 8 }, gridLines: { display: false } }]
        }
    };
    function lineChart(id, labels, datasets){
        new Chart(document.getElementById(id).getContext('2d'), {
            type: 'line',
            data: { labels: labels, datasets: datasets },
            options: chartOpts
        });
    }
    lineChart('paDailyChart', daily.labels, [
        {label:'Net Profit', data:daily.net, borderColor:'#16a34a', borderWidth:2, fill:false, lineTension:.3, pointRadius:2, pointHoverRadius:3}
    ]);
    lineChart('paMonthlyChart', monthly.labels, [
        {label:'Net Profit', data:monthly.net, borderColor:'#2563eb', borderWidth:2, fill:false, lineTension:.3, pointRadius:2, pointHoverRadius:3}
    ]);
    lineChart('paVsExpChart', daily.labels, [
        {label:'Profit', data:daily.profit, borderColor:'#16a34a', borderWidth:2, fill:false, lineTension:.3, pointRadius:2},
        {label:'Expenses', data:daily.expenses, borderColor:'#dc2626', borderWidth:2, fill:false, lineTension:.3, pointRadius:2}
    ]);
    lineChart('paSalesChart', daily.labels, [
        {label:'Sales', data:daily.sales, borderColor:'#0891b2', borderWidth:2, fill:false, lineTension:.3, pointRadius:2},
        {label:'Profit', data:daily.profit, borderColor:'#7c3aed', borderWidth:2, fill:false, lineTension:.3, pointRadius:2}
    ]);
    function exportPrint(){
        var rep = $('#profit-analytics-printable').clone();
        var nw = window.open('', '_blank', 'width=1100,height=800');
        nw.document.write('<html><head><title>Profit Analytics</title><link rel="stylesheet" href="'+_base_url_+'plugins/bootstrap/css/bootstrap.min.css"></head><body>');
        nw.document.write('<h4>Profit Analytics</h4>'+rep.html());
        nw.document.write('</body></html>');
        nw.document.close();
        setTimeout(function(){ nw.print(); setTimeout(function(){ nw.close(); }, 300); }, 400);
    }
    $('#paPrintBtn,#paPdfBtn').click(exportPrint);
    $('#paExcelBtn').click(function(){
        var csv = [];
        $('#profit-analytics-table thead tr, #profit-analytics-table tbody tr, #profit-analytics-table tfoot tr').each(function(){
            var row = [];
            $(this).find('th,td').each(function(){ row.push('"'+$(this).text().trim().replace(/"/g,'""')+'"'); });
            if(row.length) csv.push(row.join(','));
        });
        var a = document.createElement('a');
        a.href = URL.createObjectURL(new Blob([csv.join('\n')], {type:'text/csv'}));
        a.download = 'profit-analytics-<?php echo date('Y-m-d') ?>.csv';
        a.click();
    });
});
</script>
