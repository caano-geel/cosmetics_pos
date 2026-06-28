<?php if($_settings->chk_flashdata('success')): ?>
<script>
	alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>
<?php
$stock_filter = isset($_GET['stock_filter']) ? trim($_GET['stock_filter']) : '';
if(!in_array($stock_filter, array('', 'low', 'out'), true)){
    $stock_filter = '';
}
$low_threshold = inventory_low_stock_threshold();
$avail_expr = inventory_available_stock_sql('i');
$sold_sub = inventory_sold_subquery_sql();
$having_sql = '';
if($stock_filter === 'out'){
    $having_sql = " HAVING avail <= 0 ";
}elseif($stock_filter === 'low'){
    $having_sql = " HAVING avail > 0 AND avail <= {$low_threshold} ";
}
$inventory_action_col = 7 + (admin_can_view_profit() ? 1 : 0);
?>
<div class="card card-outline card-primary">
	<div class="card-header">
		<h3 class="card-title">List of Inventory</h3>
		<div class="card-tools d-flex align-items-center flex-wrap">
			<form method="get" action="" class="form-inline mr-2 mb-1 mb-md-0">
				<input type="hidden" name="page" value="inventory">
				<label for="stock_filter" class="mr-2 mb-0 small text-muted">Stock Status</label>
				<select name="stock_filter" id="stock_filter" class="form-control form-control-sm" onchange="this.form.submit()">
					<option value="" <?php echo $stock_filter === '' ? 'selected' : '' ?>>All</option>
					<option value="low" <?php echo $stock_filter === 'low' ? 'selected' : '' ?>>Low Stock</option>
					<option value="out" <?php echo $stock_filter === 'out' ? 'selected' : '' ?>>Out of Stock</option>
				</select>
			</form>
		<?php if(admin_cashier_can('inventory_manage')): ?>
			<a href="?page=inventory/manage_inventory" class="btn btn-flat btn-primary mb-1 mb-md-0"><span class="fas fa-plus"></span>  Create New</a>
		<?php endif; ?>
		</div>
	</div>
	<div class="card-body">
		<div class="container-fluid">
        <div class="container-fluid">
			<div id="list-barcode-scanner-wrap" class="mb-2" style="display:none;">
				<div id="list-barcode-camera-select-wrap" class="mb-2" style="display:none; max-width:420px;">
					<label for="list-barcode-camera-select" class="small mb-1 d-block">Select Camera</label>
					<select id="list-barcode-camera-select" class="form-control form-control-sm"></select>
				</div>
				<p class="small text-muted mb-2">Hold barcode straight, close, and well-lit.</p>
				<div id="list-barcode-scanner-reader" style="max-width:420px;"></div>
				<button type="button" class="btn btn-sm btn-secondary mt-2" id="list-stop-barcode-scan">Stop Scan</button>
			</div>
			<table class="table table-hover table-striped table-bordered">
				<colgroup>
					<col width="5%">
					<col width="20%">
					<col width="12%">
					<col width="15%">
					<col width="8%">
					<col width="10%">
					<?php if(admin_can_view_profit()): ?><col width="10%"><?php endif; ?>
					<col width="8%">
					<col width="10%">
					<?php if(admin_cashier_can('inventory_manage')): ?><col width="10%"><?php endif; ?>
				</colgroup>
				<thead>
					<tr>
						<th>#</th>
						<th>Product</th>
						<th>Barcode</th>
						<th>Variant</th>
						<th>Selling Price</th>
						<?php if(admin_can_view_profit()): ?><th>Cost Price</th><?php endif; ?>
						<th>Stock</th>
						<th>Status</th>
						<?php if(admin_cashier_can('inventory_manage')): ?><th>Action</th><?php endif; ?>
					</tr>
				</thead>
				<tbody>
					<?php 
					$i = 1;
					$inventory_sql = "SELECT i.*, p.name AS product, p.barcode, b.name AS bname, {$avail_expr} AS avail
						FROM inventory i
						INNER JOIN products p ON p.id = i.product_id
						INNER JOIN brands b ON p.brand_id = b.id
						LEFT JOIN {$sold_sub} sold ON sold.inventory_id = i.id
						WHERE p.delete_flag = 0 AND p.status = 1
						{$having_sql}
						ORDER BY avail ASC, unix_timestamp(i.date_created) DESC";
					$qry = $conn->query($inventory_sql);
					if($qry):
						while($row = $qry->fetch_assoc()):
						$avail = (float)$row['avail'];
						$stock_status = inventory_stock_status($avail, $low_threshold);
						foreach($row as $k=> $v){
							$row[$k] = trim(stripslashes($v));
						}
					?>
						<tr>
							<td class="text-center"><?php echo $i++ ?></td>
							<td>
								<b><?php echo $row['product'] ?></b> <br>
								<small><b>Brand:</b> <?php echo $row['bname'] ?></small>
							</td>
							<td><?php echo !empty($row['barcode']) ? $row['barcode'] : '-' ?></td>
							<td class="text-center"><?php echo ($row['variant']) ?></td>
							<td class="text-center"><?php echo format_price($row['price']) ?></td>
							<?php if(admin_can_view_profit()): ?>
							<td class="text-center"><?php echo (isset($row['cost_price']) && $row['cost_price'] !== '' && (float)$row['cost_price'] > 0) ? format_price($row['cost_price']) : '—' ?></td>
							<?php endif; ?>
							<td class="text-center"><?php echo format_num($avail) ?></td>
							<td class="text-center"><?php echo inventory_stock_status_badge($stock_status) ?></td>
							<?php if(admin_cashier_can('inventory_manage')): ?>
							<td align="center">
								 <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
				                  		Action
				                    <span class="sr-only">Toggle Dropdown</span>
				                  </button>
				                  <div class="dropdown-menu" role="menu">
				                    <a class="dropdown-item" href="?page=inventory/manage_inventory&id=<?php echo $row['id'] ?>"><span class="fa fa-edit text-primary"></span> Edit</a>
				                    <?php if(admin_cashier_can('delete_actions')): ?>
				                    <div class="dropdown-divider"></div>
				                    <a class="dropdown-item delete_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-trash text-danger"></span> Delete</a>
				                    <?php endif; ?>
				                  </div>
							</td>
							<?php endif; ?>
						</tr>
					<?php endwhile; endif; ?>
				</tbody>
			</table>
		</div>
		</div>
	</div>
</div>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
	$(document).ready(function(){
		$('.delete_data').click(function(){
			_conf("Are you sure to delete this inventory permanently?","delete_inventory",[$(this).attr('data-id')])
		})
		$('table th, table td').addClass('align-middle px-2 py1')
		var listTable = $('.table').DataTable({
			columnDefs: [
					<?php if(admin_cashier_can('inventory_manage')): ?>{ orderable: false, targets: [<?php echo $inventory_action_col ?>] }<?php endif; ?>
			],
			order:[[<?php echo admin_can_view_profit() ? 6 : 5 ?>, 'asc'], [0, 'asc']]
		});
		$('.dataTables_filter label').addClass('mb-0 d-flex align-items-center flex-wrap');
		$('.dataTables_filter input').addClass('mr-2');
		$('.dataTables_filter label').append('<button type="button" class="btn btn-sm btn-info mb-1" id="list-start-barcode-scan"><i class="fa fa-barcode"></i> Scan Barcode</button>');

		var listBarcodeScanner = null;
		var listBarcodeScanning = false;
		var listAvailableCameras = [];

		function resetListBarcodeCameraSelect(){
			$('#list-barcode-camera-select-wrap').hide();
			$('#list-barcode-camera-select').empty();
			listAvailableCameras = [];
		}
		function getPreferredCameraId(cameras){
			for(var i = 0; i < cameras.length; i++){
				var label = (cameras[i].label || '').toLowerCase();
				if(label.indexOf('irium') !== -1 || label.indexOf('iriun') !== -1 || label.indexOf('external') !== -1 || label.indexOf('usb') !== -1){
					return cameras[i].id;
				}
			}
			return cameras[cameras.length - 1].id;
		}
		function populateListCameraSelect(cameras){
			var $select = $('#list-barcode-camera-select');
			$select.empty();
			cameras.forEach(function(camera, index){
				var label = camera.label && camera.label.trim() ? camera.label : ('Camera ' + (index + 1));
				$select.append($('<option>', { value: camera.id, text: label }));
			});
			if(cameras.length > 1){
				$('#list-barcode-camera-select-wrap').show();
				$select.val(getPreferredCameraId(cameras));
			}else{
				$('#list-barcode-camera-select-wrap').hide();
			}
		}
		function stopListBarcodeScan(showMsg){
			var finish = function(){
				listBarcodeScanning = false;
				listBarcodeScanner = null;
				$('#list-barcode-scanner-wrap').hide();
				resetListBarcodeCameraSelect();
				if(showMsg) alert_toast('Barcode scanner stopped','info');
			};
			if(listBarcodeScanner && listBarcodeScanning){
				listBarcodeScanner.stop().then(function(){ listBarcodeScanner.clear(); }).catch(function(){}).finally(finish);
			}else{
				finish();
			}
		}
		function onListBarcodeScanned(decodedText){
			playScannerSound();
			listTable.search(decodedText).draw();
			stopListBarcodeScan(false);
		}
		function startListBarcodeScan(cameraId){
			var formatsToSupport = [
				Html5QrcodeSupportedFormats.CODE_128,
				Html5QrcodeSupportedFormats.EAN_13,
				Html5QrcodeSupportedFormats.EAN_8,
				Html5QrcodeSupportedFormats.UPC_A,
				Html5QrcodeSupportedFormats.UPC_E
			];
			listBarcodeScanner = new Html5Qrcode('list-barcode-scanner-reader', { formatsToSupport: formatsToSupport, verbose: false });
			return listBarcodeScanner.start(cameraId, { fps: 30, qrbox: { width: 420, height: 160 } }, onListBarcodeScanned, function(){});
		}

		$('#list-start-barcode-scan').click(function(){
			if(typeof Html5Qrcode === 'undefined'){
				alert_toast('Barcode scanner library not loaded','error');
				return;
			}
			$('#list-barcode-scanner-wrap').show();
			Html5Qrcode.getCameras().then(function(cameras){
				if(!cameras || cameras.length === 0){
					alert_toast('No camera found','error');
					return;
				}
				listAvailableCameras = cameras;
				populateListCameraSelect(cameras);
				var cameraId = cameras.length > 1 ? getPreferredCameraId(cameras) : cameras[0].id;
				return startListBarcodeScan(cameraId);
			}).then(function(){
				listBarcodeScanning = true;
			}).catch(function(err){
				alert_toast('Could not start scanner','error');
				console.log(err);
			});
		});
		$('#list-stop-barcode-scan').click(function(){ stopListBarcodeScan(true); });
		$('#list-barcode-camera-select').change(function(){
			if(!$('#list-barcode-scanner-wrap').is(':visible')) return;
			var cameraId = $(this).val();
			if(listBarcodeScanner && listBarcodeScanning){
				listBarcodeScanner.stop().then(function(){
					listBarcodeScanner.clear();
					listBarcodeScanning = false;
					return startListBarcodeScan(cameraId);
				}).then(function(){ listBarcodeScanning = true; });
			}
		});

		$('.dataTable td,.dataTable th').addClass('py-1 px-2 align-middle')
	})
	function delete_inventory($id){
		start_loader();
		$.ajax({
			url:_base_url_+"classes/Master.php?f=delete_inventory",
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