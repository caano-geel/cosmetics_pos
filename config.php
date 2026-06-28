<?php
ob_start();
ini_set('date.timezone','Asia/Manila');
date_default_timezone_set('Asia/Manila');
session_start();

require_once('initialize.php');
require_once('classes/DBConnection.php');
require_once('classes/SystemSettings.php');
$db = new DBConnection;
$conn = $db->conn;

function redirect($url=''){
	if(!empty($url))
	echo '<script>location.href="'.base_url .$url.'"</script>';
}
function validate_image($file){
	if(!empty($file)){
			// exit;
        $ex = explode("?",$file);
        $file = $ex[0];
        $ts = isset($ex[1]) ? "?".$ex[1] : '';
		if(is_file(base_app.$file)){
			return base_url.$file.$ts;
		}else{
			return base_url.'dist/img/no-image-available.png';
		}
	}else{
		return base_url.'dist/img/no-image-available.png';
	}
}
function format_num($number = '' , $decimal = ''){
    if(is_numeric($number)){
        $ex = explode(".",$number);
        $decLen = isset($ex[1]) ? strlen($ex[1]) : 0;
        if(is_numeric($decimal)){
            return number_format($number,$decimal);
        }else{
            return number_format($number,$decLen);
        }
    }else{
        return "Invalid Input";
    }
}
function format_price($number = '' , $decimal = ''){
    if($number === '' || $number === null || !is_numeric($number)){
        $number = 0;
    }else{
        $number = $number + 0;
    }
    $ex = explode(".",(string)$number);
    $decLen = isset($ex[1]) ? strlen($ex[1]) : 0;
    if(is_numeric($decimal)){
        return 'Ksh '.number_format($number,$decimal);
    }
    return 'Ksh '.number_format($number,$decLen);
}
function isMobileDevice(){
    $aMobileUA = array(
        '/iphone/i' => 'iPhone', 
        '/ipod/i' => 'iPod', 
        '/ipad/i' => 'iPad', 
        '/android/i' => 'Android', 
        '/blackberry/i' => 'BlackBerry', 
        '/webos/i' => 'Mobile'
    );

    //Return true if Mobile User Agent is detected
    foreach($aMobileUA as $sMobileKey => $sMobileOS){
        if(preg_match($sMobileKey, $_SERVER['HTTP_USER_AGENT'])){
            return true;
        }
    }
    //Otherwise return false..  
    return false;
}
function admin_user_type(){
    if(!isset($_SESSION['userdata']['type']))
        return 1;
    return (int)$_SESSION['userdata']['type'];
}
/**
 * Role definitions (users.type in database).
 * 1 = Admin/Owner, 2 = Cashier/Shop Keeper
 */
function admin_role_definitions(){
    return array(
        1 => array('key' => 'admin', 'label' => 'Admin / Owner'),
        2 => array('key' => 'cashier', 'label' => 'Cashier / Shop Keeper'),
        // Future roles:
        // 3 => array('key' => 'manager', 'label' => 'Manager'),
        // 4 => array('key' => 'inventory', 'label' => 'Inventory Staff'),
    );
}
/**
 * Post-login landing page per role (users.type).
 * Value: admin path relative to site root (no leading slash).
 */
function admin_role_landing_pages(){
    return array(
        1 => 'admin/',                  // Admin -> Dashboard
        2 => 'admin/?page=pos',         // Cashier / Shop Keeper -> POS
        // 3 => 'admin/?page=sales',    // Manager -> Reports
        // 4 => 'admin/?page=inventory', // Inventory -> Inventory
    );
}
function admin_is_cashier(){
    return isset($_SESSION['userdata']['login_type'])
        && $_SESSION['userdata']['login_type'] == 1
        && admin_user_type() === 2;
}
function admin_can_view_profit(){
    return !admin_is_cashier();
}
function db_table_has_column($table, $column){
    global $conn;
    static $cache = array();
    $key = strtolower($table).'.'.strtolower($column);
    if(array_key_exists($key, $cache))
        return $cache[$key];
    $cache[$key] = false;
    if(!isset($conn) || !$conn)
        return false;
    $table = preg_replace('/[^a-z0-9_]/i', '', $table);
    $column = preg_replace('/[^a-z0-9_]/i', '', $column);
    if($table === '' || $column === '')
        return false;
    $q = $conn->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
    if($q && $q->num_rows > 0)
        $cache[$key] = true;
    return $cache[$key];
}
function inventory_low_stock_threshold(){
    global $_settings;
    $val = '';
    if(isset($_settings)){
        $val = $_settings->info('low_stock_threshold');
    }
    if($val === '' || $val === null || !is_numeric($val)){
        return 5;
    }
    $val = (int)$val;
    return $val < 0 ? 0 : $val;
}
function inventory_sold_subquery_sql(){
    return "(
        SELECT ol.inventory_id, SUM(ol.quantity) AS qty
        FROM order_list ol
        INNER JOIN orders o ON o.id = ol.order_id
        WHERE o.status != 4
        GROUP BY ol.inventory_id
    )";
}
function inventory_available_stock_sql($inventory_alias = 'i'){
    return "({$inventory_alias}.quantity - IFNULL(sold.qty, 0))";
}
function inventory_stock_status($available, $threshold = null){
    if($threshold === null){
        $threshold = inventory_low_stock_threshold();
    }
    $available = (float)$available;
    if($available <= 0){
        return 'out';
    }
    if($available <= (float)$threshold){
        return 'low';
    }
    return 'in';
}
function inventory_stock_status_label($status){
    switch($status){
        case 'out':
            return 'Out of Stock';
        case 'low':
            return 'Low Stock';
        default:
            return 'In Stock';
    }
}
function inventory_stock_status_badge($status){
    switch($status){
        case 'out':
            return '<span class="badge badge-danger">Out of Stock</span>';
        case 'low':
            return '<span class="badge badge-warning text-dark">Low Stock</span>';
        default:
            return '<span class="badge badge-success">In Stock</span>';
    }
}
function inventory_stock_counts($threshold = null){
    global $conn;
    if($threshold === null){
        $threshold = inventory_low_stock_threshold();
    }
    if(!isset($conn) || !$conn){
        return array('low' => 0, 'out' => 0);
    }
    $threshold = (int)$threshold;
    $avail_sql = inventory_available_stock_sql('i');
    $sold_sub = inventory_sold_subquery_sql();
    $sql = "SELECT
        SUM(CASE WHEN {$avail_sql} <= 0 THEN 1 ELSE 0 END) AS out_count,
        SUM(CASE WHEN {$avail_sql} > 0 AND {$avail_sql} <= {$threshold} THEN 1 ELSE 0 END) AS low_count
        FROM inventory i
        INNER JOIN products p ON p.id = i.product_id
        LEFT JOIN {$sold_sub} sold ON sold.inventory_id = i.id
        WHERE p.delete_flag = 0 AND p.status = 1";
    $qry = $conn->query($sql);
    if($qry && ($row = $qry->fetch_assoc())){
        return array(
            'low' => (int)$row['low_count'],
            'out' => (int)$row['out_count'],
        );
    }
    return array('low' => 0, 'out' => 0);
}
function app_cost_price_column(){
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
function dashboard_order_customer_name($delivery_address, $client_name){
    if(!empty($delivery_address) && preg_match('/Customer:\s*(.+)$/i', $delivery_address, $matches)){
        return trim($matches[1]);
    }
    $name = trim($client_name);
    if($name === '' || stripos($name, 'Walk-in') !== false) return 'Walk-in Customer';
    return $name;
}
function dashboard_payment_label($method){
    if(strcasecmp($method, 'Cash') === 0) return 'Cash';
    if(strcasecmp($method, 'M-Pesa') === 0) return 'M-Pesa';
    return $method;
}
function dashboard_sales_trend_days($days = 7){
    $chart = dashboard_chart_analytics($days === 7 ? '7d' : '30d');
    return array(
        'labels' => $chart['labels'],
        'values' => $chart['sales'],
    );
}
function dashboard_chart_range_bounds($range){
    $today = date('Y-m-d');
    switch($range){
        case '30d':
            return array(
                'start' => date('Y-m-d', strtotime('-29 days')),
                'end' => $today,
                'mode' => 'day',
                'title' => 'Last 30 days',
            );
        case 'month':
            return array(
                'start' => date('Y-m-01'),
                'end' => $today,
                'mode' => 'day',
                'title' => 'This Month',
            );
        case 'year':
            return array(
                'start' => date('Y-01-01'),
                'end' => $today,
                'mode' => 'month',
                'title' => 'This Year',
            );
        default:
            return array(
                'start' => date('Y-m-d', strtotime('-6 days')),
                'end' => $today,
                'mode' => 'day',
                'title' => 'Last 7 days',
            );
    }
}
function dashboard_chart_init_buckets($start, $end, $mode){
    $buckets = array();
    if($mode === 'month'){
        $cur = strtotime(date('Y-m-01', strtotime($start)));
        $end_ts = strtotime(date('Y-m-01', strtotime($end)));
        while($cur <= $end_ts){
            $key = date('Y-m', $cur);
            $buckets[$key] = array(
                'label' => date('M Y', $cur),
                'sales' => 0,
                'orders' => 0,
                'profit' => 0,
            );
            $cur = strtotime('+1 month', $cur);
        }
        return $buckets;
    }
    $cur = strtotime($start);
    $end_ts = strtotime($end);
    while($cur <= $end_ts){
        $key = date('Y-m-d', $cur);
        $buckets[$key] = array(
            'label' => date('M d', $cur),
            'sales' => 0,
            'orders' => 0,
            'profit' => 0,
        );
        $cur = strtotime('+1 day', $cur);
    }
    return $buckets;
}
function dashboard_profit_by_buckets($date_start, $date_end, $mode = 'day'){
    if(!admin_can_view_profit()) return array();
    global $conn;
    if(!isset($conn) || !$conn) return array();
    $cost_column = app_cost_price_column();
    if(!$cost_column) return array();
    $date_start = date('Y-m-d', strtotime($date_start));
    $date_end = date('Y-m-d', strtotime($date_end));
    $bucket_expr = $mode === 'month'
        ? "DATE_FORMAT(s.date_created, '%Y-%m')"
        : 'DATE(s.date_created)';
    if(db_table_has_column('order_list', 'cost_price')){
        $cost_select = "COALESCE(NULLIF(ol.cost_price, ''), i.`{$cost_column}`) AS cost_price";
    }else{
        $cost_select = "i.`{$cost_column}` AS cost_price";
    }
    $discount_select = db_table_has_column('orders', 'discount_total') ? 'o.discount_total' : '0 AS discount_total';
    $sql = "SELECT {$bucket_expr} AS bucket, o.id AS order_id, ol.quantity, ol.price, {$discount_select}, {$cost_select}
        FROM sales s
        INNER JOIN orders o ON o.id = s.order_id
        INNER JOIN order_list ol ON ol.order_id = o.id
        INNER JOIN inventory i ON ol.inventory_id = i.id
        WHERE DATE(s.date_created) BETWEEN '{$date_start}' AND '{$date_end}'";
    $qry = $conn->query($sql);
    if(!$qry) return array();
    $rows = array();
    while($row = $qry->fetch_assoc()){
        $rows[] = $row;
    }
    $order_subtotals = array();
    foreach($rows as $row){
        $oid = $row['order_id'];
        if(!isset($order_subtotals[$oid])) $order_subtotals[$oid] = 0;
        $order_subtotals[$oid] += (float)$row['quantity'] * (float)$row['price'];
    }
    $bucket_profits = array();
    foreach($rows as $row){
        if(!isset($row['cost_price']) || $row['cost_price'] === null || $row['cost_price'] === '' || (float)$row['cost_price'] <= 0){
            continue;
        }
        $order_subtotal = isset($order_subtotals[$row['order_id']]) ? $order_subtotals[$row['order_id']] : 0;
        $line = (float)$row['quantity'] * (float)$row['price'];
        $discount = isset($row['discount_total']) ? (float)$row['discount_total'] : 0;
        if($discount > 0 && $order_subtotal > 0){
            $line -= ($line / $order_subtotal) * $discount;
        }
        $line = max(0, $line);
        $bucket = $row['bucket'];
        if(!isset($bucket_profits[$bucket])) $bucket_profits[$bucket] = 0;
        $bucket_profits[$bucket] += $line - ((float)$row['cost_price'] * (int)$row['quantity']);
    }
    return $bucket_profits;
}
function dashboard_chart_analytics($range = '7d'){
    global $conn;
    $empty = array(
        'labels' => array(),
        'sales' => array(),
        'profit' => array(),
        'orders' => array(),
        'range_label' => '',
    );
    if(!isset($conn) || !$conn) return $empty;
    $allowed = array('7d', '30d', 'month', 'year');
    if(!in_array($range, $allowed, true)) $range = '7d';
    $bounds = dashboard_chart_range_bounds($range);
    $start = $bounds['start'];
    $end = $bounds['end'];
    $mode = $bounds['mode'];
    $buckets = dashboard_chart_init_buckets($start, $end, $mode);
    if(empty($buckets)) return $empty;
    $sales_group = $mode === 'month'
        ? "DATE_FORMAT(s.date_created, '%Y-%m')"
        : 'DATE(s.date_created)';
    $sales_qry = $conn->query("SELECT {$sales_group} AS bucket, SUM(s.total_amount) AS total
        FROM sales s
        WHERE DATE(s.date_created) BETWEEN '{$start}' AND '{$end}'
        GROUP BY {$sales_group}");
    if($sales_qry){
        while($row = $sales_qry->fetch_assoc()){
            if(isset($buckets[$row['bucket']])){
                $buckets[$row['bucket']]['sales'] = (float)$row['total'];
            }
        }
    }
    $orders_group = $mode === 'month'
        ? "DATE_FORMAT(date_created, '%Y-%m')"
        : 'DATE(date_created)';
    $orders_qry = $conn->query("SELECT {$orders_group} AS bucket, COUNT(*) AS total
        FROM orders
        WHERE DATE(date_created) BETWEEN '{$start}' AND '{$end}' AND status != 4
        GROUP BY {$orders_group}");
    if($orders_qry){
        while($row = $orders_qry->fetch_assoc()){
            if(isset($buckets[$row['bucket']])){
                $buckets[$row['bucket']]['orders'] = (int)$row['total'];
            }
        }
    }
    $profit_map = dashboard_profit_by_buckets($start, $end, $mode);
    foreach($profit_map as $bucket => $profit){
        if(isset($buckets[$bucket])){
            $buckets[$bucket]['profit'] = round((float)$profit, 2);
        }
    }
    $labels = array();
    $sales = array();
    $profit = array();
    $orders = array();
    foreach($buckets as $item){
        $labels[] = $item['label'];
        $sales[] = round($item['sales'], 2);
        $profit[] = round($item['profit'], 2);
        $orders[] = (int)$item['orders'];
    }
    return array(
        'labels' => $labels,
        'sales' => $sales,
        'profit' => $profit,
        'orders' => $orders,
        'range_label' => $bounds['title'],
    );
}
function dashboard_top_products($limit = 5){
    global $conn;
    $limit = max(1, (int)$limit);
    $items = array();
    if(!isset($conn) || !$conn) return $items;
    $sql = "SELECT p.name AS product_name, SUM(ol.quantity) AS qty_sold, SUM(ol.total) AS revenue
        FROM order_list ol
        INNER JOIN orders o ON o.id = ol.order_id
        INNER JOIN inventory i ON i.id = ol.inventory_id
        INNER JOIN products p ON p.id = i.product_id
        WHERE o.status != 4
        GROUP BY p.id, p.name
        ORDER BY qty_sold DESC
        LIMIT {$limit}";
    $qry = $conn->query($sql);
    if($qry){
        while($row = $qry->fetch_assoc()){
            $items[] = array(
                'product_name' => trim(stripslashes($row['product_name'])),
                'qty_sold' => (int)$row['qty_sold'],
                'revenue' => (float)$row['revenue'],
            );
        }
    }
    return $items;
}
function dashboard_recent_sale_lines($limit = 10){
    global $conn;
    $limit = max(1, (int)$limit);
    $items = array();
    if(!isset($conn) || !$conn) return $items;
    $sql = "SELECT o.date_created, o.ref_code, o.delivery_address, o.payment_method,
        p.name AS product_name, ol.quantity, ol.price,
        CONCAT(c.firstname,' ',c.lastname) AS client_name
        FROM sales s
        INNER JOIN orders o ON o.id = s.order_id
        INNER JOIN order_list ol ON ol.order_id = o.id
        INNER JOIN inventory i ON i.id = ol.inventory_id
        INNER JOIN products p ON p.id = i.product_id
        INNER JOIN clients c ON c.id = o.client_id
        WHERE o.status != 4
        ORDER BY o.date_created DESC, ol.id DESC
        LIMIT {$limit}";
    $qry = $conn->query($sql);
    if($qry){
        while($row = $qry->fetch_assoc()){
            $items[] = array(
                'date_created' => $row['date_created'],
                'ref_code' => $row['ref_code'],
                'product_name' => trim(stripslashes($row['product_name'])),
                'customer' => dashboard_order_customer_name($row['delivery_address'], $row['client_name']),
                'payment' => dashboard_payment_label($row['payment_method']),
                'total' => (float)$row['quantity'] * (float)$row['price'],
            );
        }
    }
    return $items;
}
function dashboard_profit_total($date_start, $date_end){
    if(!admin_can_view_profit()) return null;
    global $conn;
    if(!isset($conn) || !$conn) return 0;
    $cost_column = app_cost_price_column();
    if(!$cost_column) return null;
    $date_start = date('Y-m-d', strtotime($date_start));
    $date_end = date('Y-m-d', strtotime($date_end));
    if(db_table_has_column('order_list', 'cost_price')){
        $cost_select = "COALESCE(NULLIF(ol.cost_price, ''), i.`{$cost_column}`) AS cost_price";
    }else{
        $cost_select = "i.`{$cost_column}` AS cost_price";
    }
    $discount_select = db_table_has_column('orders', 'discount_total') ? 'o.discount_total' : '0 AS discount_total';
    $sql = "SELECT o.id AS order_id, ol.quantity, ol.price, {$discount_select}, {$cost_select}
        FROM sales s
        INNER JOIN orders o ON o.id = s.order_id
        INNER JOIN order_list ol ON ol.order_id = o.id
        INNER JOIN inventory i ON ol.inventory_id = i.id
        WHERE DATE(s.date_created) BETWEEN '{$date_start}' AND '{$date_end}'";
    $qry = $conn->query($sql);
    if(!$qry) return 0;
    $rows = array();
    while($row = $qry->fetch_assoc()){
        $rows[] = $row;
    }
    $order_subtotals = array();
    foreach($rows as $row){
        $oid = $row['order_id'];
        if(!isset($order_subtotals[$oid])) $order_subtotals[$oid] = 0;
        $order_subtotals[$oid] += (float)$row['quantity'] * (float)$row['price'];
    }
    $total_profit = 0;
    foreach($rows as $row){
        if(!isset($row['cost_price']) || $row['cost_price'] === null || $row['cost_price'] === '' || (float)$row['cost_price'] <= 0){
            continue;
        }
        $order_subtotal = isset($order_subtotals[$row['order_id']]) ? $order_subtotals[$row['order_id']] : 0;
        $line = (float)$row['quantity'] * (float)$row['price'];
        $discount = isset($row['discount_total']) ? (float)$row['discount_total'] : 0;
        if($discount > 0 && $order_subtotal > 0){
            $line -= ($line / $order_subtotal) * $discount;
        }
        $line = max(0, $line);
        $total_profit += $line - ((float)$row['cost_price'] * (int)$row['quantity']);
    }
    return $total_profit;
}
function dashboard_format_profit($amount){
    if(!admin_can_view_profit()) return '';
    if($amount === null) return '&mdash;';
    return format_price($amount);
}
function dashboard_payment_sales_today($method){
    global $conn;
    if(!isset($conn) || !$conn) return 0;
    $today = date('Y-m-d');
    $method_esc = $conn->real_escape_string($method);
    $qry = $conn->query("SELECT COALESCE(SUM(amount), 0) AS total FROM orders
        WHERE DATE(date_created) = '{$today}' AND payment_method = '{$method_esc}' AND status != 4");
    if($qry && ($row = $qry->fetch_assoc())){
        return (float)$row['total'];
    }
    return 0;
}
function admin_activity_log_enabled(){
    global $conn;
    static $enabled = null;
    if($enabled !== null) return $enabled;
    $enabled = false;
    if(isset($conn) && $conn){
        $q = $conn->query("SHOW TABLES LIKE 'admin_activity_log'");
        if($q && $q->num_rows > 0) $enabled = true;
    }
    return $enabled;
}
function admin_activity_log($action, $details = '', $user_id = null, $username = null){
    global $conn;
    if(!admin_activity_log_enabled() || !isset($conn) || !$conn) return false;
    $action = preg_replace('/[^a-z0-9_]/i', '', strtolower(trim($action)));
    if($action === '') return false;
    if($user_id === null && isset($_SESSION['userdata']['id'])) $user_id = (int)$_SESSION['userdata']['id'];
    if($username === null && isset($_SESSION['userdata']['username'])) $username = $_SESSION['userdata']['username'];
    $user_id = (int)$user_id;
    $username = $conn->real_escape_string(trim((string)$username));
    $details = $conn->real_escape_string(trim((string)$details));
    return $conn->query("INSERT INTO admin_activity_log SET user_id = '{$user_id}', username = '{$username}', action = '{$action}', details = '{$details}'");
}
function admin_activity_action_label($action){
    $map = array(
        'login' => 'Login',
        'logout' => 'Logout',
        'product_created' => 'Product Created',
        'product_updated' => 'Product Updated',
        'product_deleted' => 'Product Deleted',
        'inventory_created' => 'Inventory Created',
        'inventory_updated' => 'Inventory Updated',
        'inventory_deleted' => 'Inventory Deleted',
        'pos_sale_completed' => 'POS Sale Completed',
        'order_updated' => 'Order Updated',
        'settings_updated' => 'Settings Updated',
        'permissions_updated' => 'Permissions Updated',
        'expense_created' => 'Expense Created',
        'expense_updated' => 'Expense Updated',
        'expense_deleted' => 'Expense Deleted',
        'backup_created' => 'Backup Created',
        'backup_restored' => 'Backup Restored',
        'backup_deleted' => 'Backup Deleted',
    );
    return isset($map[$action]) ? $map[$action] : ucwords(str_replace('_', ' ', $action));
}
function dashboard_recent_activities($limit = 10){
    global $conn;
    $limit = max(1, (int)$limit);
    $items = array();
    if(!admin_activity_log_enabled() || !isset($conn) || !$conn) return $items;
    $qry = $conn->query("SELECT user_id, username, action, details, date_created
        FROM admin_activity_log
        ORDER BY date_created DESC, id DESC
        LIMIT {$limit}");
    if($qry){
        while($row = $qry->fetch_assoc()){
            $items[] = array(
                'date_created' => $row['date_created'],
                'username' => trim($row['username']),
                'action' => $row['action'],
                'action_label' => admin_activity_action_label($row['action']),
                'details' => trim(stripslashes($row['details'])),
            );
        }
    }
    return $items;
}
function dashboard_sales_report_url($date_start, $date_end, $payment = ''){
    $url = base_url.'admin/?page=sales&date_start='.urlencode($date_start).'&date_end='.urlencode($date_end);
    if($payment !== '') $url .= '&payment_method='.urlencode($payment);
    return $url;
}
function dashboard_inventory_value(){
    global $conn;
    if(!isset($conn) || !$conn) return 0;
    $avail_sql = inventory_available_stock_sql('i');
    $sold_sub = inventory_sold_subquery_sql();
    $sql = "SELECT COALESCE(SUM(GREATEST({$avail_sql}, 0) * i.price), 0) AS total
        FROM inventory i
        INNER JOIN products p ON p.id = i.product_id
        LEFT JOIN {$sold_sub} sold ON sold.inventory_id = i.id
        WHERE p.delete_flag = 0 AND p.status = 1";
    $qry = $conn->query($sql);
    if($qry && ($row = $qry->fetch_assoc())){
        return (float)$row['total'];
    }
    return 0;
}
function dashboard_users_count(){
    global $conn;
    if(!isset($conn) || !$conn) return 0;
    $qry = $conn->query("SELECT COUNT(*) AS total FROM users");
    if($qry && ($row = $qry->fetch_assoc())){
        return (int)$row['total'];
    }
    return 0;
}
function dashboard_orders_today_count(){
    global $conn;
    if(!isset($conn) || !$conn) return 0;
    $today = date('Y-m-d');
    $qry = $conn->query("SELECT COUNT(*) AS total FROM orders WHERE DATE(date_created) = '{$today}' AND status != 4");
    if($qry && ($row = $qry->fetch_assoc())){
        return (int)$row['total'];
    }
    return 0;
}
function dashboard_greeting_text(){
    $hour = (int)date('G');
    if($hour < 12) return 'Good Morning';
    if($hour < 17) return 'Good Afternoon';
    return 'Good Evening';
}
function dashboard_user_display_name(){
    $parts = array();
    if(isset($_SESSION['userdata']['firstname'])) $parts[] = trim($_SESSION['userdata']['firstname']);
    if(isset($_SESSION['userdata']['lastname'])) $parts[] = trim($_SESSION['userdata']['lastname']);
    $name = trim(implode(' ', $parts));
    if($name === '' && isset($_SESSION['userdata']['username'])){
        $name = trim($_SESSION['userdata']['username']);
    }
    if($name === '') return 'Administrator';
    if(!admin_is_cashier()) return $name;
    return $name;
}
function admin_permission_catalog(){
    return array(
        'dashboard_full' => array('label' => 'Dashboard (full view)', 'admin_only' => true, 'cashier_default' => 0),
        'dashboard_limited' => array('label' => 'Dashboard (limited view)', 'admin_only' => false, 'cashier_default' => 1),
        'pos' => array('label' => 'POS / Cashier', 'admin_only' => false, 'cashier_default' => 1),
        'products' => array('label' => 'Product List (create/edit/delete)', 'admin_only' => false, 'cashier_default' => 0),
        'inventory_view' => array('label' => 'Inventory List (view only)', 'admin_only' => false, 'cashier_default' => 1),
        'inventory_manage' => array('label' => 'Inventory List (create/edit/delete)', 'admin_only' => false, 'cashier_default' => 0),
        'orders_view' => array('label' => 'Order List (view only)', 'admin_only' => false, 'cashier_default' => 1),
        'orders_manage' => array('label' => 'Order List (update/pay/delete)', 'admin_only' => false, 'cashier_default' => 0),
        'clients' => array('label' => 'Client List', 'admin_only' => false, 'cashier_default' => 0),
        'sales_report' => array('label' => 'Sales Report', 'admin_only' => false, 'cashier_default' => 0),
        'expenses' => array('label' => 'Expenses', 'admin_only' => false, 'cashier_default' => 0),
        'profit_analytics' => array('label' => 'Profit Analytics', 'admin_only' => true, 'cashier_default' => 0),
        'backup_restore' => array('label' => 'Backup & Restore', 'admin_only' => true, 'cashier_default' => 0),
        'brands' => array('label' => 'Brand List', 'admin_only' => false, 'cashier_default' => 0),
        'categories' => array('label' => 'Category List', 'admin_only' => false, 'cashier_default' => 0),
        'settings' => array('label' => 'Settings', 'admin_only' => false, 'cashier_default' => 0),
        'permissions' => array('label' => 'Permissions', 'admin_only' => true, 'cashier_default' => 0),
        'my_account' => array('label' => 'My Account', 'admin_only' => false, 'cashier_default' => 1),
        'delete_actions' => array('label' => 'Delete actions', 'admin_only' => false, 'cashier_default' => 0),
    );
}
function admin_default_cashier_permissions(){
    $perms = array();
    foreach(admin_permission_catalog() as $key => $meta){
        if(!empty($meta['admin_only'])){
            $perms[$key] = 0;
        }else{
            $perms[$key] = !empty($meta['cashier_default']) ? 1 : 0;
        }
    }
    return $perms;
}
function admin_load_cashier_permissions(){
    global $conn;
    static $cache = null;
    if($cache !== null)
        return $cache;
    $cache = admin_default_cashier_permissions();
    if(isset($conn) && $conn){
        $qry = $conn->query("SELECT meta_value FROM system_info WHERE meta_field = 'cashier_permissions' LIMIT 1");
        if($qry && $qry->num_rows > 0){
            $decoded = json_decode($qry->fetch_assoc()['meta_value'], true);
            if(is_array($decoded)){
                foreach($cache as $key => $val){
                    if(array_key_exists($key, $decoded))
                        $cache[$key] = !empty($decoded[$key]) ? 1 : 0;
                }
            }
        }
    }
    foreach(admin_permission_catalog() as $key => $meta){
        if(!empty($meta['admin_only']))
            $cache[$key] = 0;
    }
    return $cache;
}
function admin_save_cashier_permissions($data){
    global $conn;
    if(!isset($conn) || !$conn)
        return false;
    $save = admin_default_cashier_permissions();
    if(is_string($data))
        $data = json_decode($data, true);
    if(!is_array($data))
        $data = array();
    foreach($save as $key => $val){
        if(!empty(admin_permission_catalog()[$key]['admin_only']))
            continue;
        $save[$key] = !empty($data[$key]) ? 1 : 0;
    }
    $save['permissions'] = 0;
    $save['dashboard_full'] = 0;
    $json = $conn->real_escape_string(json_encode($save));
    $check = $conn->query("SELECT meta_field FROM system_info WHERE meta_field = 'cashier_permissions' LIMIT 1");
    if($check && $check->num_rows > 0){
        return $conn->query("UPDATE system_info SET meta_value = '{$json}' WHERE meta_field = 'cashier_permissions'");
    }
    return $conn->query("INSERT INTO system_info SET meta_field = 'cashier_permissions', meta_value = '{$json}'");
}
function admin_cashier_has_permission($key){
    if(!admin_is_cashier())
        return true;
    $perms = admin_load_cashier_permissions();
    return !empty($perms[$key]);
}
function admin_cashier_can($key){
    if(!admin_is_cashier())
        return true;
    return admin_cashier_has_permission($key);
}
function admin_cashier_page_permission($page){
    $map = array(
        'home' => 'dashboard_limited',
        '' => 'dashboard_limited',
        'pos' => 'pos',
        'product' => 'products',
        'product/manage_product' => 'products',
        'inventory' => 'inventory_view',
        'inventory/manage_inventory' => 'inventory_manage',
        'orders' => 'orders_view',
        'orders/view_order' => 'orders_view',
        'clients' => 'clients',
        'clients/manage_client' => 'clients',
        'sales' => 'sales_report',
        'expenses' => 'expenses',
        'expenses/manage_expense' => 'expenses',
        'analytics' => 'profit_analytics',
        'backup' => 'backup_restore',
        'notifications' => 'dashboard_limited',
        'maintenance/brand' => 'brands',
        'maintenance/manage_brand' => 'brands',
        'maintenance/view_brand' => 'brands',
        'maintenance/category' => 'categories',
        'maintenance/manage_category' => 'categories',
        'maintenance/view_category' => 'categories',
        'maintenance/permissions' => 'permissions',
        'system_info' => 'settings',
        'user' => 'my_account',
    );
    if(!isset($map[$page]))
        return null;
    return $map[$page];
}
function admin_cashier_allowed_page($page){
    if(!admin_is_cashier())
        return true;
    $perm = admin_cashier_page_permission($page);
    if($perm === null)
        return false;
    if($perm === 'permissions')
        return false;
    if($perm === 'inventory_view')
        return admin_cashier_has_permission('inventory_view') || admin_cashier_has_permission('inventory_manage');
    if($perm === 'orders_view')
        return admin_cashier_has_permission('orders_view') || admin_cashier_has_permission('orders_manage');
    return admin_cashier_has_permission($perm);
}
function admin_deny_cashier_access($page){
    return admin_is_cashier() && !admin_cashier_allowed_page($page);
}
function admin_cashier_landing_candidates(){
    return array(
        array('pos', 'admin/?page=pos'),
        array('dashboard_limited', 'admin/'),
        array('my_account', 'admin/?page=user'),
        array('inventory_view', 'admin/?page=inventory'),
        array('inventory_manage', 'admin/?page=inventory'),
        array('orders_view', 'admin/?page=orders'),
        array('orders_manage', 'admin/?page=orders'),
        array('products', 'admin/?page=product'),
        array('clients', 'admin/?page=clients'),
        array('sales_report', 'admin/?page=sales'),
        array('expenses', 'admin/?page=expenses'),
        array('brands', 'admin/?page=maintenance/brand'),
        array('categories', 'admin/?page=maintenance/category'),
        array('settings', 'admin/?page=system_info'),
    );
}
function admin_cashier_first_allowed_path(){
    if(!admin_is_cashier())
        return null;
    foreach(admin_cashier_landing_candidates() as $item){
        if(admin_cashier_has_permission($item[0]))
            return $item[1];
    }
    return null;
}
function admin_login_landing_path(){
    if(!isset($_SESSION['userdata']['login_type']) || (int)$_SESSION['userdata']['login_type'] !== 1)
        return null;

    $role = admin_user_type();
    $landings = admin_role_landing_pages();

    if(isset($landings[$role])){
        $path = $landings[$role];
        if(admin_is_cashier()){
            if(admin_cashier_has_permission('pos'))
                return 'admin/?page=pos';
            $fallback = admin_cashier_first_allowed_path();
            return $fallback !== null ? $fallback : 'admin/';
        }
        return $path;
    }

    return 'admin/';
}
function admin_cashier_has_any_access(){
    return admin_cashier_first_allowed_path() !== null;
}
function admin_cashier_denied_redirect_url(){
    $path = admin_cashier_first_allowed_path();
    if($path !== null)
        return base_url.$path;
    return base_url.'admin/login.php';
}
function admin_cashier_resolve_page_access($page){
    if(!admin_is_cashier())
        return array('status' => 'allow');
    if(admin_cashier_allowed_page($page))
        return array('status' => 'allow');
    $landing = admin_cashier_first_allowed_path();
    if($landing !== null)
        return array('status' => 'redirect', 'url' => base_url.$landing);
    return array('status' => 'deny', 'url' => base_url.'admin/login.php');
}
function admin_cashier_api_denied($action){
    if(!admin_is_cashier())
        return false;
    $rules = array(
        'save_brand' => 'brands',
        'delete_brand' => array('perm' => 'brands', 'delete' => true),
        'save_category' => 'categories',
        'delete_category' => array('perm' => 'categories', 'delete' => true),
        'save_sub_category' => 'categories',
        'delete_sub_category' => array('perm' => 'categories', 'delete' => true),
        'save_product' => 'products',
        'delete_product' => array('perm' => 'products', 'delete' => true),
        'save_inventory' => 'inventory_manage',
        'delete_inventory' => array('perm' => 'inventory_manage', 'delete' => true),
        'delete_img' => array('any' => array('settings', 'products', 'brands', 'categories'), 'delete' => true),
        'pay_order' => 'orders_manage',
        'update_order_status' => 'orders_manage',
        'delete_order' => array('perm' => 'orders_manage', 'delete' => true),
        'update_client' => 'clients',
        'delete_client' => array('perm' => 'clients', 'delete' => true),
        'save_cashier_permissions' => 'permissions',
        'save_expense' => 'expenses',
        'delete_expense' => array('perm' => 'expenses', 'delete' => true),
        'get_notifications' => null,
        'mark_notification_read' => null,
        'mark_all_notifications_read' => null,
        'delete_notification' => null,
        'create_backup' => 'backup_restore',
        'delete_backup' => array('perm' => 'backup_restore', 'delete' => true),
        'restore_backup' => 'backup_restore',
        'download_backup' => 'backup_restore',
        'profit_analytics_data' => 'profit_analytics',
    );
    if(in_array($action, array('get_notifications', 'mark_notification_read', 'mark_all_notifications_read', 'delete_notification'), true))
        return false;
    if($action === 'save_cashier_permissions')
        return admin_is_cashier();
    if(!isset($rules[$action]))
        return false;
    $rule = $rules[$action];
    if(is_string($rule))
        return !admin_cashier_has_permission($rule);
    if(!empty($rule['any'])){
        $allowed = false;
        foreach($rule['any'] as $perm){
            if(admin_cashier_has_permission($perm)){
                $allowed = true;
                break;
            }
        }
        if(!$allowed)
            return true;
    }elseif(!empty($rule['perm']) && !admin_cashier_has_permission($rule['perm'])){
        return true;
    }
    if(!empty($rule['delete']) && !admin_cashier_has_permission('delete_actions'))
        return true;
    return false;
}
function expenses_table_enabled(){
    global $conn;
    static $enabled = null;
    if($enabled !== null) return $enabled;
    $enabled = false;
    if(isset($conn) && $conn){
        $q = $conn->query("SHOW TABLES LIKE 'expenses'");
        if($q && $q->num_rows > 0) $enabled = true;
    }
    return $enabled;
}
function expense_categories(){
    return array(
        'Rent', 'Salaries', 'Electricity', 'Water', 'Transport',
        'Internet', 'Marketing', 'Packaging', 'Maintenance', 'Miscellaneous'
    );
}
function expense_payment_methods(){
    return array('Cash', 'M-Pesa', 'Bank Transfer', 'Other');
}
function expenses_normalize_date($date, $fallback = null){
    if($fallback === null) $fallback = date('Y-m-d');
    $date = trim((string)$date);
    if($date === '') return $fallback;
    if(preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m)){
        if(checkdate((int)$m[2], (int)$m[3], (int)$m[1])) return $date;
    }
    $ts = strtotime($date);
    if($ts !== false) return date('Y-m-d', $ts);
    return $fallback;
}
function expenses_normalize_range($date_start, $date_end){
    $start = expenses_normalize_date($date_start, date('Y-m-01'));
    $end = expenses_normalize_date($date_end, date('Y-m-d'));
    if($start > $end){
        $tmp = $start;
        $start = $end;
        $end = $tmp;
    }
    return array('start' => $start, 'end' => $end);
}
function expenses_where_sql($date_start, $date_end, $category = ''){
    global $conn;
    $range = expenses_normalize_range($date_start, $date_end);
    $where = "delete_flag = 0 AND DATE(expense_date) BETWEEN '{$range['start']}' AND '{$range['end']}'";
    $category = trim((string)$category);
    if($category !== ''){
        $cat = $conn->real_escape_string($category);
        $where .= " AND TRIM(category) = '{$cat}'";
    }
    return $where;
}
function expenses_total($date_start, $date_end, $category = ''){
    global $conn;
    if(!expenses_table_enabled() || !isset($conn) || !$conn) return 0;
    $where = expenses_where_sql($date_start, $date_end, $category);
    $qry = $conn->query("SELECT COALESCE(SUM(amount), 0) AS total FROM expenses WHERE {$where}");
    if($qry && ($row = $qry->fetch_assoc())) return (float)$row['total'];
    return 0;
}
function dashboard_expenses_today(){
    return expenses_total(date('Y-m-d'), date('Y-m-d'));
}
function dashboard_expenses_month(){
    return expenses_total(date('Y-m-01'), date('Y-m-d'));
}
function dashboard_net_profit($date_start, $date_end){
    if(!admin_can_view_profit()) return null;
    $profit = dashboard_profit_total($date_start, $date_end);
    if($profit === null) return null;
    return (float)$profit - expenses_total($date_start, $date_end);
}
function dashboard_format_net_profit($amount){
    if(!admin_can_view_profit()) return '';
    if($amount === null) return '&mdash;';
    return format_price($amount);
}
function profit_analytics_period_bounds($period){
    $today = date('Y-m-d');
    switch($period){
        case 'week':
            return array('start' => date('Y-m-d', strtotime('monday this week')), 'end' => $today, 'label' => 'This Week');
        case 'month':
            return array('start' => date('Y-m-01'), 'end' => $today, 'label' => 'This Month');
        case 'year':
            return array('start' => date('Y-01-01'), 'end' => $today, 'label' => 'This Year');
        default:
            return array('start' => $today, 'end' => $today, 'label' => 'Today');
    }
}
function profit_analytics_sales_total($date_start, $date_end){
    global $conn;
    if(!isset($conn) || !$conn) return 0;
    $date_start = date('Y-m-d', strtotime($date_start));
    $date_end = date('Y-m-d', strtotime($date_end));
    $qry = $conn->query("SELECT COALESCE(SUM(s.total_amount), 0) AS total FROM sales s
        WHERE DATE(s.date_created) BETWEEN '{$date_start}' AND '{$date_end}'");
    if($qry && ($row = $qry->fetch_assoc())) return (float)$row['total'];
    return 0;
}
function profit_analytics_cost_total($date_start, $date_end){
    if(!admin_can_view_profit()) return null;
    global $conn;
    if(!isset($conn) || !$conn) return 0;
    $cost_column = app_cost_price_column();
    if(!$cost_column) return null;
    $date_start = date('Y-m-d', strtotime($date_start));
    $date_end = date('Y-m-d', strtotime($date_end));
    if(db_table_has_column('order_list', 'cost_price')){
        $cost_select = "COALESCE(NULLIF(ol.cost_price, ''), i.`{$cost_column}`) AS cost_price";
    }else{
        $cost_select = "i.`{$cost_column}` AS cost_price";
    }
    $sql = "SELECT ol.quantity, {$cost_select}
        FROM sales s
        INNER JOIN orders o ON o.id = s.order_id
        INNER JOIN order_list ol ON ol.order_id = o.id
        INNER JOIN inventory i ON ol.inventory_id = i.id
        WHERE DATE(s.date_created) BETWEEN '{$date_start}' AND '{$date_end}'";
    $qry = $conn->query($sql);
    if(!$qry) return 0;
    $total = 0;
    while($row = $qry->fetch_assoc()){
        if(isset($row['cost_price']) && $row['cost_price'] !== null && $row['cost_price'] !== '' && (float)$row['cost_price'] > 0){
            $total += (float)$row['cost_price'] * (int)$row['quantity'];
        }
    }
    return $total;
}
function profit_analytics_daily_rows($date_start, $date_end){
    global $conn;
    $rows = array();
    if(!isset($conn) || !$conn) return $rows;
    $date_start = date('Y-m-d', strtotime($date_start));
    $date_end = date('Y-m-d', strtotime($date_end));
    $cur = strtotime($date_start);
    $end_ts = strtotime($date_end);
    while($cur <= $end_ts){
        $d = date('Y-m-d', $cur);
        $sales = profit_analytics_sales_total($d, $d);
        $cost = profit_analytics_cost_total($d, $d);
        $expenses = expenses_total($d, $d);
        $profit = admin_can_view_profit() ? dashboard_profit_total($d, $d) : null;
        $net = ($profit !== null) ? ((float)$profit - $expenses) : null;
        $rows[] = array(
            'date' => $d,
            'sales' => $sales,
            'cost' => $cost,
            'expenses' => $expenses,
            'profit' => $profit,
            'net_profit' => $net,
        );
        $cur = strtotime('+1 day', $cur);
    }
    return $rows;
}
function profit_analytics_chart_series($date_start, $date_end, $mode = 'day'){
    $daily = profit_analytics_daily_rows($date_start, $date_end);
    if($mode === 'month'){
        $buckets = array();
        foreach($daily as $row){
            $key = date('Y-m', strtotime($row['date']));
            if(!isset($buckets[$key])){
                $buckets[$key] = array('label' => date('M Y', strtotime($row['date'].'-01')), 'sales' => 0, 'profit' => 0, 'expenses' => 0, 'net' => 0);
            }
            $buckets[$key]['sales'] += $row['sales'];
            $buckets[$key]['expenses'] += $row['expenses'];
            if($row['profit'] !== null) $buckets[$key]['profit'] += (float)$row['profit'];
            if($row['net_profit'] !== null) $buckets[$key]['net'] += (float)$row['net_profit'];
        }
        $labels = array(); $sales = array(); $profit = array(); $expenses = array(); $net = array();
        foreach($buckets as $b){
            $labels[] = $b['label'];
            $sales[] = round($b['sales'], 2);
            $profit[] = round($b['profit'], 2);
            $expenses[] = round($b['expenses'], 2);
            $net[] = round($b['net'], 2);
        }
        return compact('labels', 'sales', 'profit', 'expenses', 'net');
    }
    $labels = array(); $sales = array(); $profit = array(); $expenses = array(); $net = array();
    foreach($daily as $row){
        $labels[] = date('M d', strtotime($row['date']));
        $sales[] = round($row['sales'], 2);
        $profit[] = $row['profit'] !== null ? round((float)$row['profit'], 2) : 0;
        $expenses[] = round($row['expenses'], 2);
        $net[] = $row['net_profit'] !== null ? round((float)$row['net_profit'], 2) : 0;
    }
    return compact('labels', 'sales', 'profit', 'expenses', 'net');
}
function notifications_table_enabled(){
    global $conn;
    static $enabled = null;
    if($enabled !== null) return $enabled;
    $enabled = false;
    if(isset($conn) && $conn){
        $q = $conn->query("SHOW TABLES LIKE 'notifications'");
        if($q && $q->num_rows > 0) $enabled = true;
    }
    return $enabled;
}
function notification_type_allowed($type){
    $allowed = array('success', 'warning', 'danger', 'info');
    return in_array($type, $allowed, true) ? $type : 'info';
}
function admin_notify($type, $title, $message, $link = '', $ref_key = ''){
    global $conn;
    if(!notifications_table_enabled() || !isset($conn) || !$conn) return false;
    $type = notification_type_allowed($type);
    $title = $conn->real_escape_string(trim((string)$title));
    $message = $conn->real_escape_string(trim((string)$message));
    $link = $conn->real_escape_string(trim((string)$link));
    $ref_key = $conn->real_escape_string(trim((string)$ref_key));
    if($ref_key !== ''){
        $since = date('Y-m-d H:i:s', strtotime('-24 hours'));
        $dup = $conn->query("SELECT id FROM notifications WHERE ref_key = '{$ref_key}' AND date_created >= '{$since}' LIMIT 1");
        if($dup && $dup->num_rows > 0) return false;
    }
    $ref_sql = $ref_key !== '' ? "'{$ref_key}'" : 'NULL';
    $link_sql = $link !== '' ? "'{$link}'" : 'NULL';
    return $conn->query("INSERT INTO notifications SET user_id = NULL, type = '{$type}', title = '{$title}', message = '{$message}', link = {$link_sql}, ref_key = {$ref_sql}");
}
function notifications_unread_count(){
    global $conn;
    if(!notifications_table_enabled() || !isset($conn) || !$conn) return 0;
    $qry = $conn->query("SELECT COUNT(*) AS total FROM notifications WHERE is_read = 0");
    if($qry && ($row = $qry->fetch_assoc())) return (int)$row['total'];
    return 0;
}
function notifications_list($limit = 10, $unread_only = false){
    global $conn;
    $items = array();
    if(!notifications_table_enabled() || !isset($conn) || !$conn) return $items;
    $limit = max(1, (int)$limit);
    $where = $unread_only ? 'WHERE is_read = 0' : '';
    $qry = $conn->query("SELECT * FROM notifications {$where} ORDER BY date_created DESC, id DESC LIMIT {$limit}");
    if($qry){
        while($row = $qry->fetch_assoc()){
            $items[] = $row;
        }
    }
    return $items;
}
function notifications_sync_system(){
    if(!notifications_table_enabled()) return;
    global $conn;
    if(!isset($conn) || !$conn) return;
    $counts = inventory_stock_counts();
    if($counts['low'] > 0){
        admin_notify('warning', 'Low Stock Alert', format_num($counts['low']).' product variant(s) are low on stock.', base_url.'admin/?page=inventory&stock_filter=low', 'stock_low_'.$counts['low']);
    }
    if($counts['out'] > 0){
        admin_notify('danger', 'Out of Stock', format_num($counts['out']).' product variant(s) are out of stock.', base_url.'admin/?page=inventory&stock_filter=out', 'stock_out_'.$counts['out']);
    }
    if(db_table_has_column('inventory', 'expiry_date')){
        $soon = date('Y-m-d', strtotime('+30 days'));
        $today = date('Y-m-d');
        $qry = $conn->query("SELECT COUNT(*) AS total FROM inventory i
            INNER JOIN products p ON p.id = i.product_id
            WHERE p.delete_flag = 0 AND i.expiry_date IS NOT NULL
            AND i.expiry_date BETWEEN '{$today}' AND '{$soon}'");
        if($qry && ($row = $qry->fetch_assoc()) && (int)$row['total'] > 0){
            admin_notify('warning', 'Expiring Products', format_num($row['total']).' item(s) expire within 30 days.', base_url.'admin/?page=inventory', 'expiry_'.$row['total']);
        }
    }
    $pending = (int)$conn->query("SELECT COUNT(*) AS total FROM orders WHERE status = '0'")->fetch_assoc()['total'];
    if($pending > 0){
        admin_notify('info', 'Pending Orders', format_num($pending).' order(s) awaiting processing.', base_url.'admin/?page=orders&status=0', 'pending_orders_'.$pending);
    }
}
function backup_logs_table_enabled(){
    global $conn;
    static $enabled = null;
    if($enabled !== null) return $enabled;
    $enabled = false;
    if(isset($conn) && $conn){
        $q = $conn->query("SHOW TABLES LIKE 'backup_logs'");
        if($q && $q->num_rows > 0) $enabled = true;
    }
    return $enabled;
}
function backup_dir_path(){
    $dir = base_app.'uploads/backups/';
    if(!is_dir($dir)) @mkdir($dir, 0755, true);
    return $dir;
}
function app_ensure_upload_dirs(){
    $dirs = array(
        'uploads',
        'uploads/avatars',
        'uploads/brands',
        'uploads/backups',
        'uploads/system',
    );
    foreach($dirs as $rel){
        $path = base_app.$rel;
        if(!is_dir($path)) @mkdir($path, 0755, true);
    }
}
app_ensure_upload_dirs();
function backup_last_info(){
    global $conn;
    if(!backup_logs_table_enabled() || !isset($conn) || !$conn) return null;
    $qry = $conn->query("SELECT * FROM backup_logs WHERE status = 'success' ORDER BY date_created DESC, id DESC LIMIT 1");
    if($qry && $qry->num_rows > 0) return $qry->fetch_assoc();
    return null;
}
function format_file_size($bytes){
    $bytes = (float)$bytes;
    if($bytes >= 1073741824) return round($bytes / 1073741824, 2).' GB';
    if($bytes >= 1048576) return round($bytes / 1048576, 2).' MB';
    if($bytes >= 1024) return round($bytes / 1024, 2).' KB';
    return round($bytes).' B';
}
function expense_format_id($id){
    return 'EXP-'.str_pad((int)$id, 5, '0', STR_PAD_LEFT);
}
ob_end_flush();
?>