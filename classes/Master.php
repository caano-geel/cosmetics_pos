<?php
require_once('../config.php');
Class Master extends DBConnection {
	private $settings;
	public function __construct(){
		global $_settings;
		$this->settings = $_settings;
		parent::__construct();
	}
	public function __destruct(){
		parent::__destruct();
	}
	function capture_err(){
		if(!$this->conn->error)
			return false;
		else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
			return json_encode($resp);
			exit;
		}
	}
	function save_brand(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k =>$v){
			if(!in_array($k,array('id'))){
				if(!empty($data)) $data .=",";
				$v = addslashes(trim($v));
				$data .= " `{$k}`='{$v}' ";
			}
		}
		$check = $this->conn->query("SELECT * FROM `brands` where `name` = '{$name}' ".(!empty($id) ? " and id != {$id} " : "")." ")->num_rows;
		if($this->capture_err())
			return $this->capture_err();
		if($check > 0){
			$resp['status'] = 'failed';
			$resp['msg'] = "Brand Name already exist.";
			return json_encode($resp);
			exit;
		}
		if(empty($id)){
			$sql = "INSERT INTO `brands` set {$data} ";
		}else{
			$sql = "UPDATE `brands` set {$data} where id = '{$id}' ";
		}
			$save = $this->conn->query($sql);
		if($save){
			$bid = !empty($id) ? $id : $this->conn->insert_id;
			$resp['status'] = 'success';
			if(empty($id))
				$resp['msg'] = "New Brand successfully saved.";
			else
				$resp['msg'] = "Brand successfully updated.";
			if(!empty($_FILES['img']['tmp_name'])){
				if(!is_dir(base_app."uploads/brands"))
				mkdir(base_app."uploads/brands");
				$ext = pathinfo($_FILES['img']['name'], PATHINFO_EXTENSION);
				$fname = "uploads/brands/$bid.$ext";
				$accept = array('image/jpeg','image/png');
				if(!in_array($_FILES['img']['type'],$accept)){
					$resp['msg'] .= " Image file type is invalid";
				}
				if($_FILES['img']['type'] == 'image/jpeg')
					$uploadfile = imagecreatefromjpeg($_FILES['img']['tmp_name']);
				elseif($_FILES['img']['type'] == 'image/png')
					$uploadfile = imagecreatefrompng($_FILES['img']['tmp_name']);
				if(!$uploadfile){
					$resp['msg'] .= " Image is invalid";
				}
				$temp = imagescale($uploadfile,200,200);
				if(is_file(base_app.$fname))
				unlink(base_app.$fname);
				if($_FILES['img']['type'] == 'image/jpeg')
				$upload =imagejpeg($temp,base_app.$fname);
				elseif($_FILES['img']['type'] == 'image/png')
				$upload =imagepng($temp,base_app.$fname);
				else
				$upload = false;
				if($upload){
					$qry = $this->conn->query("UPDATE brands set `image_path` = CONCAT('{$fname}', '?v=',unix_timestamp(CURRENT_TIMESTAMP)) where id = '{$bid}' ");
				}
				imagedestroy($temp);
			}
			
		}else{
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		if($resp['status'] == 'success')
			$this->settings->set_flashdata('success',$resp['msg']);
			return json_encode($resp);
	}
	function delete_brand(){
		extract($_POST);
		$del = $this->conn->query("UPDATE `brands` set `delete_flag` = 1 where id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success'," Brand successfully deleted.");
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);

	}
	function save_category(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k =>$v){
			if(!in_array($k,array('id','description'))){
				if(!empty($data)) $data .=",";
				$data .= " `{$k}`='{$v}' ";
			}
		}
		if(isset($_POST['description'])){
			if(!empty($data)) $data .=",";
				$data .= " `description`='".addslashes(htmlentities($description))."' ";
		}
		$check = $this->conn->query("SELECT * FROM `categories` where `category` = '{$category}' ".(!empty($id) ? " and id != {$id} " : "")." ")->num_rows;
		if($this->capture_err())
			return $this->capture_err();
		if($check > 0){
			$resp['status'] = 'failed';
			$resp['msg'] = "Category already exist.";
			return json_encode($resp);
			exit;
		}
		if(empty($id)){
			$sql = "INSERT INTO `categories` set {$data} ";
			$save = $this->conn->query($sql);
		}else{
			$sql = "UPDATE `categories` set {$data} where id = '{$id}' ";
			$save = $this->conn->query($sql);
		}
		if($save){
			$resp['status'] = 'success';
			if(empty($id))
				$this->settings->set_flashdata('success',"New Category successfully saved.");
			else
				$this->settings->set_flashdata('success',"Category successfully updated.");
		}else{
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		return json_encode($resp);
	}
	function delete_category(){
		extract($_POST);
		$del = $this->conn->query("UPDATE `categories` set delete_flag = 1 where id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success'," Category successfully deleted.");
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);

	}
	function save_product(){
		$_POST['specs'] = htmlentities($_POST['specs']);
		if(isset($_POST['barcode'])){
			$_POST['barcode'] = trim($_POST['barcode']);
		}
		foreach($_POST as $k =>$v){
			$_POST[$k] = addslashes($v);
		}
		extract($_POST);
		$data = "";
		foreach($_POST as $k =>$v){
			if(!in_array($k,array('id'))){
				if(!empty($data)) $data .=",";
				if($k == 'barcode' && $v === ''){
					$data .= " `barcode`=NULL ";
				}else{
					$v = addslashes($v);
					$data .= " `{$k}`='{$this->conn->real_escape_string($v)}' ";
				}
			}
		}
		$check = $this->conn->query("SELECT * FROM `products` where `name` = '{$name}' ".(!empty($id) ? " and id != {$id} " : "")." ")->num_rows;
		if($this->capture_err())
			return $this->capture_err();
		if($check > 0){
			$resp['status'] = 'failed';
			$resp['msg'] = "Product already exist.";
			return json_encode($resp);
			exit;
		}
		if(!empty($barcode)){
			$check_barcode = $this->conn->query("SELECT * FROM `products` where `barcode` = '{$barcode}' ".(!empty($id) ? " and id != {$id} " : "")." ")->num_rows;
			if($check_barcode > 0){
				$resp['status'] = 'failed';
				$resp['msg'] = "Barcode already exists.";
				return json_encode($resp);
				exit;
			}
		}
		if(empty($id)){
			$sql = "INSERT INTO `products` set {$data} ";
		}else{
			$sql = "UPDATE `products` set {$data} where id = '{$id}' ";
		}
		$save = $this->conn->query($sql);
		if($save){
			$pid = empty($id) ? $this->conn->insert_id : $id;
			$upload_path = "uploads/product_".$pid;
			if(!is_dir(base_app.$upload_path))
				mkdir(base_app.$upload_path);
			if(isset($_FILES['img']) && count($_FILES['img']['tmp_name']) > 0){
				$err = "";
				foreach($_FILES['img']['tmp_name'] as $k => $v){
					if(!empty($_FILES['img']['tmp_name'][$k])){
						$ext = strtolower(pathinfo($_FILES['img']['name'][$k], PATHINFO_EXTENSION));
						$allowed_ext = array('jpg','jpeg','png','webp');
						if(!in_array($ext, $allowed_ext)){
							$err = "Image file type is invalid. Allowed formats: JPG, JPEG, PNG, WEBP";
							break;
						}
						if(in_array($ext, array('jpg','jpeg')))
							$uploadfile = imagecreatefromjpeg($_FILES['img']['tmp_name'][$k]);
						elseif($ext == 'png')
							$uploadfile = imagecreatefrompng($_FILES['img']['tmp_name'][$k]);
						elseif($ext == 'webp')
							$uploadfile = function_exists('imagecreatefromwebp') ? imagecreatefromwebp($_FILES['img']['tmp_name'][$k]) : false;
						if(!$uploadfile){
							$err = "Image is invalid";
							break;
						}
						$temp = imagescale($uploadfile,400,400);
						$spath = base_app.$upload_path.'/'.$_FILES['img']['name'][$k];
						$i = 0;
						while(true){
							if(is_file($spath)){
								$spath = base_app.$upload_path.'/'.$i."_".$_FILES['img']['name'][$k];
							}else{
								break;
							}
							$i++;
						}
						if(in_array($ext, array('jpg','jpeg')))
						imagejpeg($temp, $spath);
						elseif($ext == 'png')
						imagepng($temp, $spath);
						elseif($ext == 'webp' && function_exists('imagewebp'))
						imagewebp($temp, $spath);

						imagedestroy($temp);
					}
				}
				if(!empty($err)){
					$resp['status'] = 'failed';
					$resp['msg'] = 'Product successfully saved but '.$err;
					$resp['id'] = $pid;
				}
			}
			if(!isset($resp)){
				$resp['status'] = 'success';
				if(empty($id))
					$this->settings->set_flashdata('success',"New Product successfully saved.");
				else
					$this->settings->set_flashdata('success',"Product successfully updated.");
				admin_activity_log(empty($id) ? 'product_created' : 'product_updated', stripslashes($name));
			}
		}else{
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		return json_encode($resp);
	}
	function delete_product(){
		extract($_POST);
		$product = $this->conn->query("SELECT name FROM products WHERE id = '{$id}' LIMIT 1")->fetch_assoc();
		$del = $this->conn->query("UPDATE `products` set delete_flag = 1 where id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success'," Product successfully deleted.");
			admin_activity_log('product_deleted', $product ? stripslashes($product['name']) : 'Product #'.$id);
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);

	}
	function delete_img(){
		extract($_POST);
		if(is_file($path)){
			if(unlink($path)){
				$resp['status'] = 'success';
			}else{
				$resp['status'] = 'failed';
				$resp['error'] = 'failed to delete '.$path;
			}
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = 'Unkown '.$path.' path';
		}
		return json_encode($resp);
	}
	function save_inventory(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k =>$v){
			if(!in_array($k,array('id','description'))){
				if(admin_is_cashier() && $k === 'cost_price')
					continue;
				if(!empty($data)) $data .=",";
				$data .= " `{$k}`='{$v}' ";
			}
		}
		$check = $this->conn->query("SELECT * FROM `inventory` where `product_id` = '{$product_id}' and variant = '{$variant}' ".(!empty($id) ? " and id != {$id} " : "")." ")->num_rows;
		if($this->capture_err())
			return $this->capture_err();
		if($check > 0){
			$resp['status'] = 'failed';
			$resp['msg'] = "Inventory already exist.";
			return json_encode($resp);
			exit;
		}
		if(empty($id)){
			$sql = "INSERT INTO `inventory` set {$data} ";
			$save = $this->conn->query($sql);
		}else{
			$sql = "UPDATE `inventory` set {$data} where id = '{$id}' ";
			$save = $this->conn->query($sql);
		}
		if($save){
			$resp['status'] = 'success';
			if(empty($id))
				$this->settings->set_flashdata('success',"New Inventory successfully saved.");
			else
				$this->settings->set_flashdata('success',"Inventory successfully updated.");
			$inv_id = empty($id) ? $this->conn->insert_id : $id;
			$info = $this->conn->query("SELECT p.name, i.variant FROM inventory i INNER JOIN products p ON p.id = i.product_id WHERE i.id = '{$inv_id}' LIMIT 1")->fetch_assoc();
			$detail = $info ? stripslashes($info['name']).' ('.stripslashes($info['variant']).')' : 'Inventory #'.$inv_id;
			admin_activity_log(empty($id) ? 'inventory_created' : 'inventory_updated', $detail);
			notifications_sync_system();
		}else{
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		return json_encode($resp);
	}
	function delete_inventory(){
		extract($_POST);
		$info = $this->conn->query("SELECT p.name, i.variant FROM inventory i INNER JOIN products p ON p.id = i.product_id WHERE i.id = '{$id}' LIMIT 1")->fetch_assoc();
		$del = $this->conn->query("DELETE FROM `inventory` where id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success',"Invenory successfully deleted.");
			$detail = $info ? stripslashes($info['name']).' ('.stripslashes($info['variant']).')' : 'Inventory #'.$id;
			admin_activity_log('inventory_deleted', $detail);
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);

	}
	function register(){
		extract($_POST);
		$data = "";
		$_POST['password'] = md5($_POST['password']);
		foreach($_POST as $k =>$v){
			if(!in_array($k,array('id'))){
				if(!empty($data)) $data .=",";
				$data .= " `{$k}`='{$v}' ";
			}
		}
		$check = $this->conn->query("SELECT * FROM `clients` where `email` = '{$email}' ".(!empty($id) ? " and id != {$id} " : "")." ")->num_rows;
		if($this->capture_err())
			return $this->capture_err();
		if($check > 0){
			$resp['status'] = 'failed';
			$resp['msg'] = "Email already taken.";
			return json_encode($resp);
			exit;
		}
		if(empty($id)){
			$sql = "INSERT INTO `clients` set {$data} ";
		}else{
			$sql = "UPDATE `clients` set {$data} where id = '{$id}' ";
		}
			$save = $this->conn->query($sql);
		if($save){
			$cid = !empty($id) ? $id : $this->conn->insert_id;
			$resp['status'] = 'success';
			if(empty($id))
				$this->settings->set_flashdata('success',"Account successfully created.");
			else
				$this->settings->set_flashdata('success',"Account successfully updated.");
			$this->settings->set_userdata('login_type',2);
			foreach($_POST as $k =>$v){
				$this->settings->set_userdata($k,$v);
			}
			$this->settings->set_userdata('id',$cid);

		}else{
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		return json_encode($resp);
	}
	function add_to_cart(){
		extract($_POST);
		$data = " client_id = '".$this->settings->userdata('id')."' ";
		$_POST['price'] = str_replace(",","",$_POST['price']); 
		foreach($_POST as $k =>$v){
			if(!in_array($k,array('id'))){
				if(!empty($data)) $data .=",";
				$data .= " `{$k}`='{$v}' ";
			}
		}
		$check = $this->conn->query("SELECT * FROM `cart` where `inventory_id` = '{$inventory_id}' and client_id = ".$this->settings->userdata('id'))->num_rows;
		if($this->capture_err())
			return $this->capture_err();
		if($check > 0){
			$sql = "UPDATE `cart` set quantity = quantity + {$quantity} where `inventory_id` = '{$inventory_id}' and client_id = ".$this->settings->userdata('id');
		}else{
			$sql = "INSERT INTO `cart` set {$data} ";
		}
		
		$save = $this->conn->query($sql);
		if($this->capture_err())
			return $this->capture_err();
			if($save){
				$resp['status'] = 'success';
				$resp['cart_count'] = $this->conn->query("SELECT SUM(quantity) as items from `cart` where client_id =".$this->settings->userdata('id'))->fetch_assoc()['items'];
			}else{
				$resp['status'] = 'failed';
				$resp['err'] = $this->conn->error."[{$sql}]";
			}
			return json_encode($resp);
	}
	function update_cart_qty(){
		extract($_POST);
		
		$save = $this->conn->query("UPDATE `cart` set quantity = '{$quantity}' where id = '{$id}'");
		if($this->capture_err())
			return $this->capture_err();
		if($save){
			$resp['status'] = 'success';
		}else{
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		return json_encode($resp);
		
	}
	function empty_cart(){
		$delete = $this->conn->query("DELETE FROM `cart` where client_id = ".$this->settings->userdata('id'));
		if($this->capture_err())
			return $this->capture_err();
		if($delete){
			$resp['status'] = 'success';
		}else{
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		return json_encode($resp);
	}
	function delete_cart(){
		extract($_POST);
		$delete = $this->conn->query("DELETE FROM `cart` where id = '{$id}'");
		if($this->capture_err())
			return $this->capture_err();
		if($delete){
			$resp['status'] = 'success';
		}else{
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		return json_encode($resp);
	}
	function delete_order(){
		extract($_POST);
		$delete = $this->conn->query("DELETE FROM `orders` where id = '{$id}'");
		$delete2 = $this->conn->query("DELETE FROM `order_list` where order_id = '{$id}'");
		$delete3 = $this->conn->query("DELETE FROM `sales` where order_id = '{$id}'");
		if($this->capture_err())
			return $this->capture_err();
		if($delete){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success',"Order successfully deleted");
		}else{
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		return json_encode($resp);
	}
	function place_order(){
		if(empty($id)){
			$prefix = date("Ym");
			$code = sprintf("%'.05d",1);
			while(true){
				$check = $this->conn->query("SELECT * FROM `orders` where ref_code = '{$prefix}{$code}' ")->num_rows;
				if($check > 0){
					$code = sprintf("%'.05d",ceil($code) + 1);
				}else{
					break;
				}
			}
			$_POST['ref_code'] = $prefix.$code;
		}
		extract($_POST);
		$client_id = $this->settings->userdata('id');
		
		$data = " client_id = '{$client_id}' ";
		if(isset($ref_code))
		$data .= " ,ref_code = '{$ref_code}' ";
		$data .= " ,payment_method = '{$payment_method}' ";
		$data .= " ,amount = '{$amount}' ";
		$data .= " ,paid = '{$paid}' ";
		$data .= " ,delivery_address = '{$delivery_address}' ";
		$order_sql = "INSERT INTO `orders` set $data";
		$save_order = $this->conn->query($order_sql);
		if($this->capture_err())
			return $this->capture_err();
		if($save_order){
			$order_id = $this->conn->insert_id;
			$data = '';
			$cart = $this->conn->query("SELECT c.*,p.name,i.price,p.id as pid from `cart` c inner join `inventory` i on i.id=c.inventory_id inner join products p on p.id = i.product_id where c.client_id ='{$client_id}' ");
			while($row= $cart->fetch_assoc()):
				if(!empty($data)) $data .= ", ";
				$total = $row['price'] * $row['quantity'];
				$data .= "('{$order_id}','{$row['pid']}','{$row['quantity']}','{$row['price']}', $total)";
			endwhile;
			$list_sql = "INSERT INTO `order_list` (order_id,inventory_id,quantity,price,total) VALUES {$data} ";
			$save_olist = $this->conn->query($list_sql);
			if($this->capture_err())
				return $this->capture_err();
			if($save_olist){
				$empty_cart = $this->conn->query("DELETE FROM `cart` where client_id = '{$client_id}'");
				$data = " order_id = '{$order_id}'";
				$data .= " ,total_amount = '{$amount}'";
				$save_sales = $this->conn->query("INSERT INTO `sales` set $data");
				if($this->capture_err())
					return $this->capture_err();
				$resp['status'] ='success';
				$this->settings->set_flashdata('success'," Order has been placed successfully.");
			}else{
				$resp['status'] ='failed';
				$resp['err_sql'] =$save_olist;
			}

		}else{
			$resp['status'] ='failed';
			$resp['err_sql'] =$save_order;
		}
		return json_encode($resp);
	}
	function update_order_status(){
		extract($_POST);
		$order = $this->conn->query("SELECT ref_code FROM orders WHERE id = '{$id}' LIMIT 1")->fetch_assoc();
		$update = $this->conn->query("UPDATE `orders` set `status` = '$status' where id = '{$id}' ");
		if($update){
			$resp['status'] ='success';
			$this->settings->set_flashdata("success"," Order status successfully updated.");
			$status_labels = array(0 => 'Pending', 1 => 'Packed', 2 => 'Out for Delivery', 3 => 'Delivered', 4 => 'Cancelled');
			$label = isset($status_labels[(int)$status]) ? $status_labels[(int)$status] : 'Status '.$status;
			$ref = $order ? $order['ref_code'] : 'Order #'.$id;
			admin_activity_log('order_updated', $ref.' updated to '.$label);
		}else{
			$resp['status'] ='failed';
			$resp['err'] =$this->conn->error;
		}
		return json_encode($resp);
	}
	function pay_order(){
		extract($_POST);
		$update = $this->conn->query("UPDATE `orders` set `paid` = '1' where id = '{$id}' ");
		if($update){
			$resp['status'] ='success';
			$this->settings->set_flashdata("success"," Order payment status successfully updated.");
		}else{
			$resp['status'] ='failed';
			$resp['err'] =$this->conn->error;
		}
		return json_encode($resp);
	}
	function update_account(){
		if(!empty($_POST['password']))
			$_POST['password'] = md5($password);
		else
			unset($_POST['password']);
		extract($_POST);
		$data = "";
		if(md5($cpassword) != $this->settings->userdata('password')){
			$resp['status'] = 'failed';
			$resp['msg'] = "Current Password is Incorrect";
			return json_encode($resp);
			exit;
		}
		$check = $this->conn->query("SELECT * FROM `clients`  where `email`='{$email}' and `id` != $id ")->num_rows;
		if($check > 0){
			$resp['status'] = 'failed';
			$resp['msg'] = "Email already taken.";
			return json_encode($resp);
			exit;
		}
		foreach($_POST as $k =>$v){
			if($k == 'cpassword' || ($k == 'password' && empty($v)))
				continue;
				if(!empty($data)) $data .=",";
					$data .= " `{$k}`='{$v}' ";
		}
		$save = $this->conn->query("UPDATE `clients` set $data where id = $id ");
		if($save){
			foreach($_POST as $k =>$v){
				if($k != 'cpassword')
				$this->settings->set_userdata($k,$v);
			}
			
			$this->settings->set_userdata('id',$this->conn->insert_id);
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success',' Your Account Details has been updated successfully.');
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);

	}
	function update_client(){
		if(!empty($_POST['password']))
			$_POST['password'] = md5($password);
		else
			unset($_POST['password']);
		extract($_POST);
		$data = "";
		
		$check = $this->conn->query("SELECT * FROM `clients`  where `email`='{$email}' and `id` != $id ")->num_rows;
		if($check > 0){
			$resp['status'] = 'failed';
			$resp['msg'] = "Email already taken.";
			return json_encode($resp);
			exit;
		}
		foreach($_POST as $k =>$v){
			if(in_array($k,['id']))
				continue;
				if(!empty($data)) $data .=",";
					$data .= " `{$k}`='{$this->conn->real_escape_string($v)}' ";
		}
		$save = $this->conn->query("UPDATE `clients` set $data where id = $id ");
		if($save){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success',' Client Details Successfully Updated.');
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);

	}
	function delete_client(){
		extract($_POST);
		$delete = $this->conn->query("UPDATE `clients` set delete_flag = 1 where id = '{$id}'");
		if($delete){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success'," Client successfully deleted");
		}else{
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	private function pos_require_admin(){
		if($this->settings->userdata('login_type') != 1){
			return json_encode(['status'=>'failed','msg'=>'Access denied.']);
		}
		return false;
	}
	private function get_pos_client_id(){
		$email = 'pos.walkin@local';
		$check = $this->conn->query("SELECT id FROM `clients` WHERE email = '{$email}' AND delete_flag = 0 LIMIT 1");
		if($check && $check->num_rows > 0){
			return (int)$check->fetch_assoc()['id'];
		}
		$pwd = md5('pos_walkin');
		$sql = "INSERT INTO `clients` SET firstname='Walk-in', lastname='Customer', gender='N/A', contact='0000000000', email='{$email}', password='{$pwd}', default_delivery_address='In-Store POS', status=1, delete_flag=0";
		if($this->conn->query($sql)){
			return (int)$this->conn->insert_id;
		}
		return 0;
	}
	private function get_inventory_stock($inventory_id){
		$inventory_id = (int)$inventory_id;
		$cost_select = db_table_has_column('inventory', 'cost_price') ? ', i.cost_price' : '';
		$row = $this->conn->query("SELECT i.id, i.price{$cost_select}, i.quantity, p.name, p.barcode, i.variant, b.name as bname,
			(i.quantity - IFNULL(sold.qty, 0)) AS stock
			FROM inventory i
			INNER JOIN products p ON p.id = i.product_id
			INNER JOIN brands b ON p.brand_id = b.id
			LEFT JOIN (
				SELECT ol.inventory_id, SUM(ol.quantity) AS qty
				FROM order_list ol
				INNER JOIN orders o ON o.id = ol.order_id
				WHERE o.status != 4
				GROUP BY ol.inventory_id
			) sold ON sold.inventory_id = i.id
			WHERE i.id = '{$inventory_id}' AND p.delete_flag = 0 AND p.status = 1
			LIMIT 1")->fetch_assoc();
		return $row ?: null;
	}
	private function snapshot_cost_price($inv){
		if(!is_array($inv) || !array_key_exists('cost_price', $inv))
			return null;
		if($inv['cost_price'] === null || $inv['cost_price'] === '')
			return null;
		$cost = (float)$inv['cost_price'];
		if($cost <= 0)
			return null;
		return $cost;
	}
	function pos_search_product(){
		if($denied = $this->pos_require_admin()) return $denied;
		$q = isset($_POST['q']) ? trim($_POST['q']) : '';
		if($q === ''){
			return json_encode(['status'=>'failed','msg'=>'Enter a barcode or product name.']);
		}
		$q_esc = $this->conn->real_escape_string($q);
		$like = '%'.$q_esc.'%';
		$sql = "SELECT i.id AS inventory_id, i.variant, i.price, p.name, p.barcode, b.name AS bname,
			(i.quantity - IFNULL(sold.qty, 0)) AS stock
			FROM inventory i
			INNER JOIN products p ON p.id = i.product_id
			INNER JOIN brands b ON p.brand_id = b.id
			LEFT JOIN (
				SELECT ol.inventory_id, SUM(ol.quantity) AS qty
				FROM order_list ol
				INNER JOIN orders o ON o.id = ol.order_id
				WHERE o.status != 4
				GROUP BY ol.inventory_id
			) sold ON sold.inventory_id = i.id
			WHERE p.delete_flag = 0 AND p.status = 1
			AND (p.barcode = '{$q_esc}' OR p.name LIKE '{$like}' OR p.barcode LIKE '{$like}')
			ORDER BY (p.barcode = '{$q_esc}') DESC, p.name ASC, i.variant ASC
			LIMIT 25";
		$qry = $this->conn->query($sql);
		if($this->capture_err()) return $this->capture_err();
		$items = [];
		while($row = $qry->fetch_assoc()){
			$row['price'] = (float)$row['price'];
			$row['stock'] = (float)$row['stock'];
			$row['inventory_id'] = (int)$row['inventory_id'];
			$items[] = $row;
		}
		return json_encode(['status'=>'success','items'=>$items]);
	}
	function pos_complete_sale(){
		if($denied = $this->pos_require_admin()) return $denied;
		$items_raw = isset($_POST['items']) ? $_POST['items'] : '';
		$payment_method = isset($_POST['payment_method']) ? trim($_POST['payment_method']) : '';
		$amount = isset($_POST['amount']) ? (float)str_replace(',','',$_POST['amount']) : 0;
		$allowed_payments = ['Cash','M-Pesa'];
		if(!in_array($payment_method, $allowed_payments)){
			return json_encode(['status'=>'failed','msg'=>'Invalid payment method.']);
		}
		$items = is_string($items_raw) ? json_decode($items_raw, true) : $items_raw;
		if(!is_array($items) || count($items) === 0){
			return json_encode(['status'=>'failed','msg'=>'Cart is empty.']);
		}
		$client_id = $this->get_pos_client_id();
		if($client_id <= 0){
			return json_encode(['status'=>'failed','msg'=>'Unable to resolve walk-in customer.']);
		}
		$validated = [];
		$computed_total = 0;
		foreach($items as $item){
			$inventory_id = isset($item['inventory_id']) ? (int)$item['inventory_id'] : 0;
			$quantity = isset($item['quantity']) ? (int)$item['quantity'] : 0;
			if($inventory_id <= 0 || $quantity <= 0){
				return json_encode(['status'=>'failed','msg'=>'Invalid cart item.']);
			}
			$inv = $this->get_inventory_stock($inventory_id);
			if(!$inv){
				return json_encode(['status'=>'failed','msg'=>'Product not found or unavailable.']);
			}
			if($quantity > $inv['stock']){
				return json_encode(['status'=>'failed','msg'=>$inv['name'].' ('.$inv['variant'].') has only '.format_num($inv['stock']).' in stock.']);
			}
			$sale_price = isset($item['price']) ? (float)str_replace(',', '', $item['price']) : (float)$inv['price'];
			if($sale_price < 0){
				return json_encode(['status'=>'failed','msg'=>'Invalid price for '.$inv['name'].'.']);
			}
			$line_total = $sale_price * $quantity;
			$computed_total += $line_total;
			$validated[] = [
				'inventory_id' => $inventory_id,
				'quantity' => $quantity,
				'price' => $sale_price,
				'total' => $line_total,
				'cost_price' => $this->snapshot_cost_price($inv),
				'name' => $inv['name'],
				'variant' => $inv['variant'],
				'bname' => $inv['bname']
			];
		}
		$subtotal = $computed_total;
		$discount_percent = isset($_POST['discount_percent']) ? (float)$_POST['discount_percent'] : 0;
		$discount_ksh = isset($_POST['discount_ksh']) ? (float)str_replace(',', '', $_POST['discount_ksh']) : 0;
		if($discount_percent < 0) $discount_percent = 0;
		if($discount_percent > 100) $discount_percent = 100;
		if($discount_ksh < 0) $discount_ksh = 0;
		$discount_total = min($subtotal, ($subtotal * $discount_percent / 100) + $discount_ksh);
		$expected_amount = max(0, $subtotal - $discount_total);
		if(abs($expected_amount - $amount) > 0.01){
			return json_encode(['status'=>'failed','msg'=>'Total mismatch. Please refresh and try again.']);
		}
		$amount = $expected_amount;
		$customer_name = isset($_POST['customer_name']) ? trim($_POST['customer_name']) : '';
		$display_customer = $customer_name !== '' ? $customer_name : 'Walk-in Customer';
		$delivery_address = 'In-Store POS';
		if($customer_name !== ''){
			$delivery_address .= ' - Customer: '.$this->conn->real_escape_string($customer_name);
		}
		$prefix = date("Ym");
		$code = sprintf("%'.05d",1);
		while(true){
			$check = $this->conn->query("SELECT id FROM `orders` WHERE ref_code = '{$prefix}{$code}' LIMIT 1")->num_rows;
			if($check > 0){
				$code = sprintf("%'.05d",ceil($code) + 1);
			}else{
				break;
			}
		}
		$ref_code = $prefix.$code;
		$payment_esc = $this->conn->real_escape_string($payment_method);
		$this->conn->begin_transaction();
		$discount_sql = db_table_has_column('orders', 'discount_total') ? ", discount_total = '{$discount_total}'" : '';
		$order_sql = "INSERT INTO `orders` SET
			client_id = '{$client_id}',
			ref_code = '{$ref_code}',
			delivery_address = '{$delivery_address}',
			payment_method = '{$payment_esc}',
			order_type = 1,
			amount = '{$amount}',
			status = 3,
			paid = 1{$discount_sql}";
		if(!$this->conn->query($order_sql)){
			$this->conn->rollback();
			return json_encode(['status'=>'failed','msg'=>'Failed to create order.','err'=>$this->conn->error]);
		}
		$order_id = $this->conn->insert_id;
		$list_values = [];
		$has_line_cost = db_table_has_column('order_list', 'cost_price');
		foreach($validated as $row){
			$cost_sql = 'NULL';
			if($has_line_cost && $row['cost_price'] !== null){
				$cost_sql = "'".$row['cost_price']."'";
			}
			if($has_line_cost){
				$list_values[] = "('{$order_id}','{$row['inventory_id']}','{$row['quantity']}','{$row['price']}','{$row['total']}',{$cost_sql})";
			}else{
				$list_values[] = "('{$order_id}','{$row['inventory_id']}','{$row['quantity']}','{$row['price']}','{$row['total']}')";
			}
		}
		if($has_line_cost){
			$list_sql = "INSERT INTO `order_list` (order_id,inventory_id,quantity,price,total,cost_price) VALUES ".implode(', ', $list_values);
		}else{
			$list_sql = "INSERT INTO `order_list` (order_id,inventory_id,quantity,price,total) VALUES ".implode(', ', $list_values);
		}
		if(!$this->conn->query($list_sql)){
			$this->conn->rollback();
			return json_encode(['status'=>'failed','msg'=>'Failed to save order items.','err'=>$this->conn->error]);
		}
		$sales_sql = "INSERT INTO `sales` SET order_id = '{$order_id}', total_amount = '{$amount}'";
		if(!$this->conn->query($sales_sql)){
			$this->conn->rollback();
			return json_encode(['status'=>'failed','msg'=>'Failed to record sale.','err'=>$this->conn->error]);
		}
		$this->conn->commit();
		admin_activity_log('pos_sale_completed', $ref_code.' | '.format_price($amount).' | '.$payment_method);
		admin_notify('success', 'Sale Completed', 'Receipt '.$ref_code.' — '.format_price($amount).' via '.$payment_method, base_url.'admin/?page=sales', 'sale_'.$order_id);
		notifications_sync_system();
		$receipt_items = array();
		foreach($validated as $row){
			$item = $row;
			unset($item['cost_price']);
			$receipt_items[] = $item;
		}
		return json_encode([
			'status' => 'success',
			'order_id' => $order_id,
			'ref_code' => $ref_code,
			'amount' => $amount,
			'subtotal' => $subtotal,
			'discount_percent' => $discount_percent,
			'discount_ksh' => $discount_ksh,
			'discount_total' => $discount_total,
			'customer_name' => $display_customer,
			'payment_method' => $payment_method,
			'items' => $receipt_items,
			'date_created' => date('Y-m-d H:i:s')
		]);
	}
	function save_cashier_permissions(){
		$resp = array('status' => 'failed', 'msg' => 'Unable to save permissions.');
		if(admin_is_cashier()){
			$resp['msg'] = 'Access denied.';
			return json_encode($resp);
		}
		$data = isset($_POST['permissions']) ? $_POST['permissions'] : array();
		if(admin_save_cashier_permissions($data)){
			$resp['status'] = 'success';
			$resp['msg'] = 'Cashier permissions saved successfully.';
			$this->settings->set_flashdata('success', $resp['msg']);
			admin_activity_log('permissions_updated', 'Cashier permissions updated');
		}
		return json_encode($resp);
	}
	function save_expense(){
		$resp = array('status' => 'failed', 'msg' => 'Unable to save expense.');
		if(!expenses_table_enabled()){
			$resp['msg'] = 'Expenses module is not installed.';
			return json_encode($resp);
		}
		$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
		$expense_date = expenses_normalize_date(isset($_POST['expense_date']) ? $_POST['expense_date'] : '', '');
		$category = isset($_POST['category']) ? trim($_POST['category']) : '';
		$description = isset($_POST['description']) ? trim($_POST['description']) : '';
		$amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
		$payment_method = isset($_POST['payment_method']) ? trim($_POST['payment_method']) : 'Cash';
		if($expense_date === '' || $category === '' || $description === '' || $amount <= 0){
			$resp['msg'] = 'Please fill all required fields with a valid amount.';
			return json_encode($resp);
		}
		if(!in_array($category, expense_categories(), true)){
			$resp['msg'] = 'Invalid expense category.';
			return json_encode($resp);
		}
		if(!in_array($payment_method, expense_payment_methods(), true)){
			$resp['msg'] = 'Invalid payment method.';
			return json_encode($resp);
		}
		$user_id = isset($_SESSION['userdata']['id']) ? (int)$_SESSION['userdata']['id'] : 0;
		$user_name = '';
		if(isset($_SESSION['userdata']['firstname'])) $user_name .= trim($_SESSION['userdata']['firstname']);
		if(isset($_SESSION['userdata']['lastname'])) $user_name .= ' '.trim($_SESSION['userdata']['lastname']);
		$user_name = trim($user_name);
		if($user_name === '' && isset($_SESSION['userdata']['username'])) $user_name = $_SESSION['userdata']['username'];
		$cat_esc = $this->conn->real_escape_string($category);
		$desc_esc = $this->conn->real_escape_string($description);
		$pay_esc = $this->conn->real_escape_string($payment_method);
		$name_esc = $this->conn->real_escape_string($user_name);
		if($id > 0){
			$sql = "UPDATE expenses SET expense_date = '{$expense_date}', category = '{$cat_esc}', description = '{$desc_esc}',
				amount = '{$amount}', payment_method = '{$pay_esc}' WHERE id = '{$id}' AND delete_flag = 0";
		}else{
			$sql = "INSERT INTO expenses SET expense_date = '{$expense_date}', category = '{$cat_esc}', description = '{$desc_esc}',
				amount = '{$amount}', payment_method = '{$pay_esc}', created_by = '{$user_id}', created_by_name = '{$name_esc}'";
		}
		if($this->conn->query($sql)){
			$eid = $id > 0 ? $id : $this->conn->insert_id;
			$resp['status'] = 'success';
			$resp['msg'] = $id > 0 ? 'Expense updated successfully.' : 'Expense added successfully.';
			$this->settings->set_flashdata('success', $resp['msg']);
			admin_activity_log($id > 0 ? 'expense_updated' : 'expense_created', expense_format_id($eid).' | '.format_price($amount).' | '.$category);
			admin_notify('info', 'Expense Recorded', expense_format_id($eid).' — '.format_price($amount).' ('.$category.')', base_url.'admin/?page=expenses', 'expense_'.$eid);
		}else{
			$resp['msg'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	function delete_expense(){
		$resp = array('status' => 'failed', 'msg' => 'Unable to delete expense.');
		$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
		if($id <= 0) return json_encode($resp);
		$row = $this->conn->query("SELECT * FROM expenses WHERE id = '{$id}' AND delete_flag = 0")->fetch_assoc();
		if($this->conn->query("UPDATE expenses SET delete_flag = 1 WHERE id = '{$id}'")){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success', 'Expense deleted successfully.');
			$detail = $row ? expense_format_id($id).' | '.format_price($row['amount']) : 'Expense #'.$id;
			admin_activity_log('expense_deleted', $detail);
		}
		return json_encode($resp);
	}
	function get_notifications(){
		$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 15;
		$items = notifications_list($limit);
		$out = array();
		foreach($items as $row){
			$out[] = array(
				'id' => (int)$row['id'],
				'type' => $row['type'],
				'title' => stripslashes($row['title']),
				'message' => stripslashes($row['message']),
				'link' => $row['link'],
				'is_read' => (int)$row['is_read'],
				'date_created' => $row['date_created'],
				'time_ago' => date('M d, H:i', strtotime($row['date_created'])),
			);
		}
		return json_encode(array('status' => 'success', 'items' => $out, 'unread' => notifications_unread_count()));
	}
	function mark_notification_read(){
		$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
		if($id > 0 && notifications_table_enabled()){
			$this->conn->query("UPDATE notifications SET is_read = 1 WHERE id = '{$id}'");
		}
		return json_encode(array('status' => 'success', 'unread' => notifications_unread_count()));
	}
	function mark_all_notifications_read(){
		if(notifications_table_enabled()){
			$this->conn->query("UPDATE notifications SET is_read = 1 WHERE is_read = 0");
		}
		return json_encode(array('status' => 'success', 'unread' => 0));
	}
	function delete_notification(){
		$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
		if($id > 0 && notifications_table_enabled()){
			$this->conn->query("DELETE FROM notifications WHERE id = '{$id}'");
		}
		return json_encode(array('status' => 'success', 'unread' => notifications_unread_count()));
	}
	function profit_analytics_data(){
		if(!admin_can_view_profit()){
			return json_encode(array('status' => 'failed', 'msg' => 'Access denied.'));
		}
		$range = isset($_GET['range']) ? $_GET['range'] : 'month';
		$bounds = profit_analytics_period_bounds($range);
		$daily = profit_analytics_chart_series($bounds['start'], $bounds['end'], 'day');
		$monthly = profit_analytics_chart_series(date('Y-01-01'), date('Y-m-d'), 'month');
		return json_encode(array(
			'status' => 'success',
			'daily' => $daily,
			'monthly' => $monthly,
			'summary' => array(
				'today' => dashboard_net_profit(date('Y-m-d'), date('Y-m-d')),
				'week' => dashboard_net_profit(date('Y-m-d', strtotime('monday this week')), date('Y-m-d')),
				'month' => dashboard_net_profit(date('Y-m-01'), date('Y-m-d')),
				'year' => dashboard_net_profit(date('Y-01-01'), date('Y-m-d')),
			),
		));
	}
	function create_backup(){
		$resp = array('status' => 'failed', 'msg' => 'Unable to create backup.');
		if(admin_is_cashier()){
			$resp['msg'] = 'Access denied.';
			return json_encode($resp);
		}
		if(!backup_logs_table_enabled()){
			$resp['msg'] = 'Backup module is not installed.';
			return json_encode($resp);
		}
		$dir = backup_dir_path();
		$filename = 'backup_'.date('Y-m-d_His').'.sql';
		$filepath = $dir.$filename;
		$dump = $this->generate_sql_dump();
		if($dump === false){
			$resp['msg'] = 'Failed to generate backup.';
			return json_encode($resp);
		}
		if(file_put_contents($filepath, $dump) === false){
			$resp['msg'] = 'Failed to write backup file.';
			return json_encode($resp);
		}
		$size = filesize($filepath);
		$user_id = isset($_SESSION['userdata']['id']) ? (int)$_SESSION['userdata']['id'] : 0;
		$user_name = dashboard_user_display_name();
		$name_esc = $this->conn->real_escape_string($user_name);
		$file_esc = $this->conn->real_escape_string($filename);
		$this->conn->query("INSERT INTO backup_logs SET filename = '{$file_esc}', file_size = '{$size}',
			created_by = '{$user_id}', created_by_name = '{$name_esc}', status = 'success', message = 'Backup created successfully'");
		admin_activity_log('backup_created', $filename.' ('.format_file_size($size).')');
		admin_notify('success', 'Backup Completed', 'Database backup '.$filename.' created successfully.', base_url.'admin/?page=backup', 'backup_'.$filename);
		$resp['status'] = 'success';
		$resp['msg'] = 'Backup created successfully.';
		$this->settings->set_flashdata('success', $resp['msg']);
		return json_encode($resp);
	}
	function generate_sql_dump(){
		$out = "-- CBPOS Database Backup\n-- Date: ".date('Y-m-d H:i:s')."\n\nSET FOREIGN_KEY_CHECKS=0;\n\n";
		$tables = $this->conn->query("SHOW TABLES");
		if(!$tables) return false;
		while($trow = $tables->fetch_array()){
			$table = $trow[0];
			$create = $this->conn->query("SHOW CREATE TABLE `{$table}`");
			if(!$create) continue;
			$crow = $create->fetch_array();
			$out .= "DROP TABLE IF EXISTS `{$table}`;\n".$crow[1].";\n\n";
			$rows = $this->conn->query("SELECT * FROM `{$table}`");
			if($rows && $rows->num_rows > 0){
				while($row = $rows->fetch_assoc()){
					$cols = array_keys($row);
					$vals = array();
					foreach($row as $v){
						if($v === null) $vals[] = 'NULL';
						else $vals[] = "'".$this->conn->real_escape_string($v)."'";
					}
					$out .= "INSERT INTO `{$table}` (`".implode('`,`', $cols)."`) VALUES (".implode(', ', $vals).");\n";
				}
				$out .= "\n";
			}
		}
		$out .= "SET FOREIGN_KEY_CHECKS=1;\n";
		return $out;
	}
	function delete_backup(){
		$resp = array('status' => 'failed', 'msg' => 'Unable to delete backup.');
		if(admin_is_cashier()) return json_encode(array('status' => 'failed', 'msg' => 'Access denied.'));
		$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
		$row = $this->conn->query("SELECT * FROM backup_logs WHERE id = '{$id}'")->fetch_assoc();
		if(!$row) return json_encode($resp);
		$path = backup_dir_path().$row['filename'];
		if(is_file($path)) @unlink($path);
		$this->conn->query("DELETE FROM backup_logs WHERE id = '{$id}'");
		admin_activity_log('backup_deleted', $row['filename']);
		$resp['status'] = 'success';
		$this->settings->set_flashdata('success', 'Backup deleted.');
		return json_encode($resp);
	}
	function restore_backup(){
		$resp = array('status' => 'failed', 'msg' => 'Unable to restore backup.');
		if(admin_is_cashier()){
			$resp['msg'] = 'Only administrators can restore backups.';
			return json_encode($resp);
		}
		$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
		$row = $this->conn->query("SELECT * FROM backup_logs WHERE id = '{$id}'")->fetch_assoc();
		if(!$row){
			$resp['msg'] = 'Backup not found.';
			return json_encode($resp);
		}
		$path = backup_dir_path().$row['filename'];
		if(!is_file($path)){
			$resp['msg'] = 'Backup file missing.';
			return json_encode($resp);
		}
		$sql = file_get_contents($path);
		if($sql === false || trim($sql) === ''){
			$resp['msg'] = 'Backup file is empty.';
			return json_encode($resp);
		}
		$this->conn->query('SET FOREIGN_KEY_CHECKS=0');
		$statements = preg_split('/;\s*[\r\n]+/', $sql);
		foreach($statements as $statement){
			$statement = trim($statement);
			if($statement === '' || strpos($statement, '--') === 0) continue;
			if(!$this->conn->query($statement)){
				$this->conn->query('SET FOREIGN_KEY_CHECKS=1');
				$resp['msg'] = 'Restore failed: '.$this->conn->error;
				return json_encode($resp);
			}
		}
		$this->conn->query('SET FOREIGN_KEY_CHECKS=1');
		admin_activity_log('backup_restored', $row['filename']);
		$resp['status'] = 'success';
		$resp['msg'] = 'Database restored successfully from '.$row['filename'].'.';
		$this->settings->set_flashdata('success', $resp['msg']);
		return json_encode($resp);
	}
	function download_backup(){
		if(admin_is_cashier()){
			header('HTTP/1.1 403 Forbidden');
			exit;
		}
		$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
		$row = $this->conn->query("SELECT * FROM backup_logs WHERE id = '{$id}'")->fetch_assoc();
		if(!$row){
			header('HTTP/1.1 404 Not Found');
			exit;
		}
		$path = backup_dir_path().$row['filename'];
		if(!is_file($path)){
			header('HTTP/1.1 404 Not Found');
			exit;
		}
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="'.basename($row['filename']).'"');
		header('Content-Length: '.filesize($path));
		readfile($path);
		exit;
	}
	function dashboard_chart_data(){
		$range = isset($_GET['range']) ? strtolower(trim($_GET['range'])) : '7d';
		$allowed = array('7d', '30d', 'month', 'year');
		if(!in_array($range, $allowed, true)) $range = '7d';
		$data = dashboard_chart_analytics($range);
		$show_profit = admin_can_view_profit();
		if(!$show_profit){
			$data['profit'] = array_fill(0, count($data['labels']), 0);
		}
		return json_encode(array(
			'status' => 'success',
			'data' => $data,
			'show_profit' => $show_profit,
		));
	}
}

$Master = new Master();
$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
$sysset = new SystemSettings();
$admin_only_actions = array(
	'save_brand','delete_brand','save_category','delete_category','save_sub_category','delete_sub_category',
	'save_product','delete_product','save_inventory','delete_inventory','delete_img',
	'pay_order','update_order_status','delete_order','update_client','delete_client',	'save_cashier_permissions',
	'create_backup','delete_backup','restore_backup','download_backup','profit_analytics_data'
);
if(in_array($action, $admin_only_actions, true) && admin_cashier_api_denied($action)){
	echo json_encode(['status'=>'failed','msg'=>'Access denied.']);
	exit;
}
switch ($action) {
	case 'save_brand':
		echo $Master->save_brand();
	break;
	case 'delete_brand':
		echo $Master->delete_brand();
	break;
	case 'save_category':
		echo $Master->save_category();
	break;
	case 'delete_category':
		echo $Master->delete_category();
	break;
	case 'save_sub_category':
		echo $Master->save_sub_category();
	break;
	case 'delete_sub_category':
		echo $Master->delete_sub_category();
	break;
	case 'save_product':
		echo $Master->save_product();
	break;
	case 'delete_product':
		echo $Master->delete_product();
	break;
	
	case 'save_inventory':
		echo $Master->save_inventory();
	break;
	case 'delete_inventory':
		echo $Master->delete_inventory();
	break;
	case 'register':
		echo $Master->register();
	break;
	case 'add_to_cart':
		echo $Master->add_to_cart();
	break;
	case 'update_cart_qty':
		echo $Master->update_cart_qty();
	break;
	case 'delete_cart':
		echo $Master->delete_cart();
	break;
	case 'empty_cart':
		echo $Master->empty_cart();
	break;
	case 'delete_img':
		echo $Master->delete_img();
	break;
	case 'place_order':
		echo $Master->place_order();
	break;
	case 'update_order_status':
		echo $Master->update_order_status();
	break;
	case 'pay_order':
		echo $Master->pay_order();
	break;
	case 'update_account':
		echo $Master->update_account();
	break;
	case 'update_client':
		echo $Master->update_client();
	break;
	case 'delete_order':
		echo $Master->delete_order();
	break;
	case 'delete_client':
		echo $Master->delete_client();
	break;
	case 'pos_search_product':
		echo $Master->pos_search_product();
	break;
	case 'pos_complete_sale':
		echo $Master->pos_complete_sale();
	break;
	case 'save_cashier_permissions':
		echo $Master->save_cashier_permissions();
	break;
	case 'dashboard_chart_data':
		echo $Master->dashboard_chart_data();
	break;
	case 'save_expense':
		echo $Master->save_expense();
	break;
	case 'delete_expense':
		echo $Master->delete_expense();
	break;
	case 'get_notifications':
		echo $Master->get_notifications();
	break;
	case 'mark_notification_read':
		echo $Master->mark_notification_read();
	break;
	case 'mark_all_notifications_read':
		echo $Master->mark_all_notifications_read();
	break;
	case 'delete_notification':
		echo $Master->delete_notification();
	break;
	case 'profit_analytics_data':
		echo $Master->profit_analytics_data();
	break;
	case 'create_backup':
		echo $Master->create_backup();
	break;
	case 'delete_backup':
		echo $Master->delete_backup();
	break;
	case 'restore_backup':
		echo $Master->restore_backup();
	break;
	case 'download_backup':
		$Master->download_backup();
	break;
	default:
		// echo $sysset->index();
		break;
}