<?php if($_settings->chk_flashdata('success')): ?>
<script>
	alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>
<?php if($_settings->chk_flashdata('error')): ?>
<script>
	alert_toast("<?php echo $_settings->flashdata('error') ?>",'error')
</script>
<?php endif;?>
<?php
$order_status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
if($order_status_filter !== '' && !in_array($order_status_filter, array('0','1','2','3','4'), true)){
    $order_status_filter = '';
}
$order_status_sql = $order_status_filter !== '' ? " WHERE o.status = '".(int)$order_status_filter."' " : '';
?>
<div class="card card-outline card-primary">
	<div class="card-header">
		<h3 class="card-title">List of Orders</h3>
		<div class="card-tools">
			<form method="get" action="" class="form-inline">
				<input type="hidden" name="page" value="orders">
				<label for="order_status_filter" class="mr-2 mb-0 small text-muted">Status</label>
				<select name="status" id="order_status_filter" class="form-control form-control-sm" onchange="this.form.submit()">
					<option value="" <?php echo $order_status_filter === '' ? 'selected' : '' ?>>All</option>
					<option value="0" <?php echo $order_status_filter === '0' ? 'selected' : '' ?>>Pending</option>
					<option value="1" <?php echo $order_status_filter === '1' ? 'selected' : '' ?>>Packed</option>
					<option value="2" <?php echo $order_status_filter === '2' ? 'selected' : '' ?>>Out for Delivery</option>
					<option value="3" <?php echo $order_status_filter === '3' ? 'selected' : '' ?>>Delivered</option>
					<option value="4" <?php echo $order_status_filter === '4' ? 'selected' : '' ?>>Cancelled</option>
				</select>
			</form>
		</div>
	</div>
	<div class="card-body">
		<div class="container-fluid">
        <div class="container-fluid">
			<table class="table table-bordered table-stripped">
				<colgroup>
					<col width="5%">
					<col width="15%">
					<col width="25%">
					<col width="20%">
					<col width="10%">
					<col width="10%">
					<col width="15%">
				</colgroup>
				<thead>
					<tr>
						<th>#</th>
						<th>Date Order</th>
						<th>Client</th>
						<th>Total Amount</th>
						<th>Paid</th>
						<th>Status</th>
						<th>Action</th>
					</tr>
				</thead>
				<tbody>
					<?php 
					$i = 1;
						$qry = $conn->query("SELECT o.*,concat(c.firstname,' ',c.lastname) as client from `orders` o inner join clients c on c.id = o.client_id {$order_status_sql} order by unix_timestamp(o.date_created) desc ");
						while($row = $qry->fetch_assoc()):
					?>
						<tr>
							<td class="text-center"><?php echo $i++; ?></td>
							<td><?php echo date("Y-m-d H:i",strtotime($row['date_created'])) ?></td>
							<td><?php echo $row['client'] ?></td>
							<td class="text-right"><?php echo format_price($row['amount']) ?></td>
							<td class="text-center">
                                <?php if($row['paid'] == 0): ?>
                                    <span class="badge badge-light border px-2 rounded-pill">No</span>
                                <?php else: ?>
                                    <span class="badge badge-success px-2 rounded-pill">Yes</span>
                                <?php endif; ?>
                            </td>
							<td class="text-center">
                                <?php if($row['status'] == 0): ?>
                                    <span class="badge badge-light border px-3 rounded-pill">Pending</span>
                                <?php elseif($row['status'] == 1): ?>
                                    <span class="badge badge-primary px-3 rounded-pill">Packed</span>
								<?php elseif($row['status'] == 2): ?>
                                    <span class="badge badge-warning px-3 rounded-pill">Out for Delivery</span>
								<?php elseif($row['status'] == 3): ?>
                                    <span class="badge badge-success px-3 rounded-pill">Delivered</span>
                                <?php else: ?>
                                    <span class="badge badge-danger px-3 rounded-pill">Cancelled</span>
                                <?php endif; ?>
                            </td>
							<td align="center">
								 <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
				                  		Action
				                    <span class="sr-only">Toggle Dropdown</span>
				                  </button>
				                  <div class="dropdown-menu" role="menu">
				                    <a class="dropdown-item" href="?page=orders/view_order&id=<?php echo $row['id'] ?>">View Order</a>
									<?php if(admin_cashier_can('orders_manage')): ?>
									<?php if($row['paid'] == 0 && $row['status'] != 4): ?>
				                    <a class="dropdown-item pay_order" href="javascript:void(0)"  data-id="<?php echo $row['id'] ?>">Mark as Paid</a>
									<?php endif; ?>
				                    <?php if(admin_cashier_can('delete_actions')): ?>
				                    <div class="dropdown-divider"></div>
				                    <a class="dropdown-item delete_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-trash text-danger"></span> Delete</a>
				                    <?php endif; ?>
									<?php endif; ?>
				                  </div>
							</td>
						</tr>
					<?php endwhile; ?>
				</tbody>
			</table>
		</div>
		</div>
	</div>
</div>
<script>
	$(document).ready(function(){
		$('.delete_data').click(function(){
			_conf("Are you sure to delete this order permanently?","delete_order",[$(this).attr('data-id')])
		})
		$('.pay_order').click(function(){
			_conf("Are you sure to mark this order as paid?","pay_order",[$(this).attr('data-id')])
		})
		$('.table').dataTable();
	})
	function pay_order($id){
		start_loader();
		$.ajax({
			url:_base_url_+"classes/Master.php?f=pay_order",
			method:"POST",
			data:{id: $id},
			dataType:"json",
			error:err=>{
				console.log(err)
				alert_toast("An error occured.",'error');
				end_loader();
			},
			success:function(resp){
				if(typeof resp== 'object' && resp.status == 'success'){
					location.reload();
				}else{
					alert_toast("An error occured.",'error');
					end_loader();
				}
			}
		})
	}
	function delete_order($id){
		start_loader();
		$.ajax({
			url:_base_url_+"classes/Master.php?f=delete_order",
			method:"POST",
			data:{id: $id},
			dataType:"json",
			error:err=>{
				console.log(err)
				alert_toast("An error occured.",'error');
				end_loader();
			},
			success:function(resp){
				if(typeof resp== 'object' && resp.status == 'success'){
					location.reload();
				}else{
					alert_toast("An error occured.",'error');
					end_loader();
				}
			}
		})
	}
</script>