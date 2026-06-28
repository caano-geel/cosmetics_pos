<?php 
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') 
    $link = "https"; 
else
    $link = "http"; 
$link .= "://"; 
$link .= $_SERVER['HTTP_HOST']; 
$link .= $_SERVER['REQUEST_URI'];
if(!isset($_SESSION['userdata']) && !strpos($link, 'login.php')){
	redirect('admin/login.php');
}
if(isset($_SESSION['userdata']) && strpos($link, 'login.php')){
	$landing = admin_login_landing_path();
	if($landing !== null){
		redirect($landing);
		exit;
	}
	echo "<script>alert('Access Denied!');location.replace('".base_url."admin/login.php');</script>";
	exit;
}
$module = array('','admin','faculty','student');
if(isset($_SESSION['userdata']) && (strpos($link, 'index.php') || strpos($link, 'admin/')) && $_SESSION['userdata']['login_type'] !=  1){
	echo "<script>alert('Access Denied!');location.replace('".base_url.$module[$_SESSION['userdata']['login_type']]."');</script>";
    exit;
}
if(isset($_SESSION['userdata']) && !empty($_SESSION['admin_post_login_redirect'])){
	$in_admin = strpos($link, 'login.php') === false && (strpos($link, 'index.php') !== false || preg_match('#/admin/?(\?.*)?$#', parse_url($link, PHP_URL_PATH) ?: '') || strpos($link, 'admin/') !== false);
	if($in_admin){
		unset($_SESSION['admin_post_login_redirect']);
		$landing = admin_login_landing_path();
		if($landing !== null){
			redirect($landing);
			exit;
		}
	}
}
if(isset($_SESSION['userdata']) && admin_is_cashier()){
	$on_login = strpos($link, 'login.php') !== false;
	$on_index = strpos($link, 'index.php') !== false || preg_match('#/admin/?(\?.*)?$#', parse_url($link, PHP_URL_PATH) ?: '');
	if(!$on_login){
		if($on_index){
			$req_page = (isset($_GET['page']) && $_GET['page'] !== '') ? trim($_GET['page']) : 'home';
			$access = admin_cashier_resolve_page_access($req_page);
			if($access['status'] === 'redirect'){
				echo '<script>location.replace("'.$access['url'].'");</script>';
				exit;
			}
			if($access['status'] === 'deny'){
				echo "<script>alert('Access Denied!');location.replace('".$access['url']."');</script>";
				exit;
			}
		}elseif(strpos($link, 'admin') !== false){
			$landing = admin_cashier_first_allowed_path();
			if($landing !== null){
				redirect($landing);
				exit;
			}
			echo "<script>alert('Access Denied!');location.replace('".base_url."admin/login.php');</script>";
			exit;
		}
	}
}
