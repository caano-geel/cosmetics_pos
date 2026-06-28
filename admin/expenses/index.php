<?php
require_once __DIR__.'/../inc/module_ui.php';
if($_settings->chk_flashdata('success')): ?>
<script>alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')</script>
<?php endif;
$date_range = expenses_normalize_range(
    isset($_GET['date_start']) ? $_GET['date_start'] : date('Y-m-01'),
    isset($_GET['date_end']) ? $_GET['date_end'] : date('Y-m-d')
);
$date_start = $date_range['start'];
$date_end = $date_range['end'];
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : '';
$expenses_today = dashboard_expenses_today();
$expenses_month = dashboard_expenses_month();
$expenses_total_all = expenses_total($date_start, $date_end, $category_filter);
$can_manage = admin_cashier_can('expenses');
echo module_page_styles();
?>
<div class="mod-page">
    <div class="card mod-header mod-header-expenses">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center">
            <div class="mb-2 mb-md-0">
                <h4><i class="fas fa-file-invoice-dollar mr-2"></i>Expense Management</h4>
                <p class="mod-subtitle">Track, filter, and manage business expenses</p>
            </div>
            <?php if($can_manage): ?>
            <button type="button" class="btn mod-btn-action mod-btn-primary" id="create_new">
                <i class="fas fa-plus mr-1"></i> Add Expense
            </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="mod-section">
        <div class="mod-section-header mod-sh-expenses"><i class="fas fa-chart-pie"></i> Summary</div>
        <div class="mod-section-body">
            <div class="row">
                <?php
                echo module_mini_stat("Today's Expenses", format_price($expenses_today), 'fa-calendar-day', 'bg-danger');
                echo module_mini_stat('Monthly Expenses', format_price($expenses_month), 'fa-calendar-alt', 'bg-warning');
                echo module_mini_stat('Total Expenses', format_price($expenses_total_all), 'fa-coins', 'bg-secondary');
                ?>
            </div>
        </div>
    </div>

    <div class="mod-section">
        <div class="mod-section-header mod-sh-filter"><i class="fas fa-filter"></i> Filter Expenses</div>
        <div class="mod-section-body">
            <form method="get" class="mod-filter-card mb-0">
                <input type="hidden" name="page" value="expenses">
                <div class="row align-items-end">
                    <div class="col-md-3 form-group mb-2 mb-md-0">
                        <label>Date From</label>
                        <input type="date" name="date_start" class="form-control form-control-sm" value="<?php echo htmlspecialchars($date_start) ?>">
                    </div>
                    <div class="col-md-3 form-group mb-2 mb-md-0">
                        <label>Date To</label>
                        <input type="date" name="date_end" class="form-control form-control-sm" value="<?php echo htmlspecialchars($date_end) ?>">
                    </div>
                    <div class="col-md-3 form-group mb-2 mb-md-0">
                        <label>Category</label>
                        <select name="category" class="form-control form-control-sm">
                            <option value="">All Categories</option>
                            <?php foreach(expense_categories() as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat) ?>" <?php echo $category_filter === $cat ? 'selected' : '' ?>><?php echo htmlspecialchars($cat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 form-group mb-0">
                        <button type="submit" class="btn btn-sm btn-primary btn-block" style="border-radius:8px;font-weight:600;">
                            <i class="fas fa-search mr-1"></i> Apply Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="mod-section">
        <div class="mod-section-header mod-sh-expenses"><i class="fas fa-list"></i> Expense History</div>
        <div class="mod-section-body p-0">
            <div class="mod-table-wrap">
                <table class="table table-hover mod-table" id="list">
                    <thead>
                        <tr>
                            <th>Expense ID</th>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Payment</th>
                            <th class="text-right">Amount</th>
                            <th>Created By</th>
                            <th>Created At</th>
                            <?php if($can_manage): ?><th class="text-center">Action</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $where = expenses_where_sql($date_start, $date_end, $category_filter);
                        $qry = expenses_table_enabled() ? $conn->query("SELECT * FROM expenses WHERE {$where} ORDER BY expense_date DESC, id DESC") : false;
                        $expense_rows = ($qry && $qry->num_rows > 0) ? $qry->num_rows : 0;
                        if($qry):
                            while($row = $qry->fetch_assoc()):
                        ?>
                        <tr>
                            <td><span class="mod-badge-id"><?php echo expense_format_id($row['id']) ?></span></td>
                            <td class="text-nowrap"><?php echo date('Y-m-d', strtotime($row['expense_date'])) ?></td>
                            <td><span class="badge badge-light border"><?php echo htmlspecialchars($row['category']) ?></span></td>
                            <td><?php echo htmlspecialchars(stripslashes($row['description'])) ?></td>
                            <td><?php echo htmlspecialchars($row['payment_method']) ?></td>
                            <td class="text-right font-weight-bold text-danger"><?php echo format_price($row['amount']) ?></td>
                            <td><?php echo htmlspecialchars($row['created_by_name'] !== '' ? $row['created_by_name'] : 'System') ?></td>
                            <td class="text-nowrap text-muted small"><?php echo date('Y-m-d H:i', strtotime($row['date_created'])) ?></td>
                            <?php if($can_manage): ?>
                            <td class="text-center text-nowrap">
                                <button type="button" class="btn btn-outline-primary mod-action-btn edit_data mr-1" data-id="<?php echo $row['id'] ?>" title="Edit"><i class="fas fa-edit"></i></button>
                                <?php if(admin_cashier_can('delete_actions')): ?>
                                <button type="button" class="btn btn-outline-danger mod-action-btn delete_data" data-id="<?php echo $row['id'] ?>" title="Delete"><i class="fas fa-trash"></i></button>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endwhile; endif; ?>
                        <?php if($qry && $expense_rows === 0): ?>
                        <tr class="expense-empty-row">
                            <td colspan="<?php echo $can_manage ? 9 : 8 ?>" class="text-center text-muted py-4">No expenses found for the selected filters.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if(!expenses_table_enabled()): ?>
            <p class="text-muted text-center py-4 mb-0">Expenses table not found. Please run <code>database/update_new_modules.sql</code>.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
$(document).ready(function(){
    $('#create_new').click(function(){
        uni_modal("<i class='fa fa-plus'></i> Add Expense","expenses/manage_expense.php",'mid-large')
    })
    $('.delete_data').click(function(){
        _conf("Are you sure to delete this expense?","delete_expense",[$(this).attr('data-id')])
    })
    $('.edit_data').click(function(){
        uni_modal("<i class='fa fa-edit'></i> Edit Expense","expenses/manage_expense.php?id="+$(this).attr('data-id'),'mid-large')
    })
    if($('#list tbody tr').not('.expense-empty-row').length > 0){
        if($.fn.DataTable.isDataTable('#list')){
            $('#list').DataTable().destroy();
        }
        $('#list').DataTable({
            columnDefs: [{ orderable: false, targets: [-1] }],
            order: [[1,'desc']],
            stateSave: false,
            searching: true,
            pageLength: 25
        });
    }
})
function delete_expense($id){
    start_loader();
    $.ajax({
        url:_base_url_+"classes/Master.php?f=delete_expense",
        method:"POST",
        data:{id: $id},
        dataType:"json",
        error:function(){ alert_toast("An error occured.",'error'); end_loader(); },
        success:function(resp){
            if(typeof resp== 'object' && resp.status == 'success') location.reload();
            else { alert_toast(resp.msg || "An error occured.",'error'); end_loader(); }
        }
    })
}
</script>
