<?php 

/*
	php 默认的 session 采用文件存储，并且使用 flock() 文件锁避免并发访问不出问题（实际上还是无法解决业务层的并发读后再写入）
	自定义的 session 采用数据表来存储，同样无法解决业务层并发请求问题。
	xiuno.js $.each_sync() 串行化并发请求，可以避免客户端并发访问导致的 session 写入问题。
*/

$sid = '';
$g_session = array();	
$g_session_invalid = FALSE; // 0: 有效， 1：无效

// 可以指定独立的 session 服务器，在系统压力巨大的时候可以考虑优化
//$g_sess_db = $db;

// 如果是管理员, sid, 与 ip 绑定，一旦 IP 发生变化，则需要重新登录。管理员采用 token (绑定IP) 双重验证，避免 sid 被中间窃取。

function sess_open($save_path, $session_name) { 
	//echo "sess_open($save_path,$session_name) \r\n";
	return true;
}

// 关闭句柄，清理资源，这里 $sid 已经为空，
function sess_close() {
	return true;
}

// 如果 cookie 中没有 bbs_sid, php 会自动生成 sid，作为参数
function sess_read($sid) { 
	global $g_session, $longip, $time;
	//echo "sess_read() sid: $sid <br>\r\n";
	if(empty($sid)) {
		// 查找刚才是不是已经插入一条了？  如果相隔时间特别短，并且 data 为空，则删除。
		// 测试是否支持 cookie，如果不支持 cookie，则不生成 sid
		$sid = session_id();
		sess_new($sid);
		return '';
	}
	$arr = db_find_one('session', array('sid'=>$sid));
	if(empty($arr)) {
		sess_new($sid);
		return '';
	}
	if($arr['bigdata'] == 1) {
		$arr2 = db_find_one('session_data', array('sid'=>$sid));
		$arr['data'] = $arr2['data'];
	}
	$g_session = $arr;
	// 在 php 5.6.29 版本，需要返回 session_decode()
	//return $arr ? session_decode($arr['data']) : '';
	return $arr ? $arr['data'] : '';
}

function sess_new($sid) {
	global $time, $longip, $conf, $g_session, $g_session_invalid;
	
	$agent = _SERVER('HTTP_USER_AGENT');
	
	// 干掉同 ip 的 sid，仅仅在遭受攻击的时候
	//db_delete('session', array('ip'=>$longip));
	
	$cookie_test = _COOKIE('cookie_test');
	if($cookie_test) {
		$cookie_test_decode = xn_decrypt($cookie_test, $conf['auth_key']);
		$g_session_invalid = ($cookie_test_decode != md5($agent.$longip));
		setcookie('cookie_test', '', $time - 86400, '');
	} else {
		$cookie_test = xn_encrypt(md5($agent.$longip), $conf['auth_key']);
		setcookie('cookie_test', $cookie_test, $time + 86400, '');
		$g_session_invalid = FALSE;
		return;
	}
	
	// 可能会暴涨
	$url = _SERVER('REQUEST_URI_NO_PATH');
	
	$arr = array(
		'sid'=>$sid,
		'uid'=>0,
		'fid'=>0,
		'url'=>$url,
		'last_date'=>$time,
		'data'=> '',
		'ip'=> $longip,
		'useragent'=> $agent,
		'bigdata'=> 0,
	);
	$g_session = $arr;
	db_insert('session', $arr);
}

// 重新启动 session，降低并发写入数据的问题，这回抛弃前面的 _SESSION 数据
function sess_restart() {
	global $sid;
	$data = sess_read($sid);
	session_decode($data); // 直接存入了 $_SESSION
}

// 将当前的 _SESSION 变量保存
function sess_save() {
	global $sid;
	sess_write($sid, TRUE);
}

// 模拟加锁，如果发现写入的时候数据已经发生改变，则读取后，合并数据，重新写入（合并总比删除安全一点）。
function sess_write($sid, $data) {
	global $g_session, $time, $longip, $g_session_invalid, $conf;
	
	//echo "sess_write($sid, $data)";
	//if($g_session_invalid) return TRUE;
	
	$uid = _SESSION('uid');
	$fid = _SESSION('fid');
	unset($_SESSION['uid']);
	unset($_SESSION['fid']);
	
	if($data) {
		//$arr = session_decode($data);
		//unset($_SESSION['uid']);
		//unset($_SESSION['fid']);
		$data = session_encode();
	}
	
	function_exists('chdir') AND chdir(APP_PATH);
	
	$url = _SERVER('REQUEST_URI_NO_PATH');
	$agent = _SERVER('HTTP_USER_AGENT');
	$arr = array(
		'uid'=>$uid,
		'fid'=>$fid,
		'url'=>$url,
		'last_date'=>$time,
		'data'=> $data,
		'ip'=> $longip,
		'useragent'=> $agent,
		'bigdata'=> 0,
	);
	
	// 开启 session 延迟更新，减轻压力，会导致不重要的数据(useragent,url)显示有些延迟，单位为秒。
	$session_delay_update_on = !empty($conf['session_delay_update']) && $time - $g_session['last_date'] < $conf['session_delay_update'];
	if($session_delay_update_on) {
		unset($arr['fid']);
		unset($arr['url']);
		unset($arr['last_date']);
	}
	
	// 判断数据是否超长
	$len = strlen($data);
	if($len > 255 && $g_session['bigdata'] == 0) {
		db_insert('session_data', array('sid'=>$sid));
	}
	if($len <= 255) {
		$update = array_diff_value($arr, $g_session);
		db_update('session', array('sid'=>$sid), $update);
		if(!empty($g_session) && $g_session['bigdata'] == 1) {
			db_delete('session_data', array('sid'=>$sid));
		}
	} else {
		$arr['data'] = '';
		$arr['bigdata'] = 1;
		$update = array_diff_value($arr, $g_session);
		$update AND db_update('session', array('sid'=>$sid), $update);
		$arr2 = array('data'=>$data, 'last_date'=>$time);
		if($session_delay_update_on) unset($arr2['last_date']);
		$update2 = array_diff_value($arr2, $g_session);
		$update2 AND db_update('session_data', array('sid'=>$sid), $update2);
	}
	return TRUE;
}

function sess_destroy($sid) { 
	//echo "sess_destroy($sid) \r\n";
	db_delete('session', array('sid'=>$sid));
	db_delete('session_data', array('sid'=>$sid));
	return TRUE; 
}

function sess_gc($maxlifetime) {
	global $time;
	// echo "sess_gc($maxlifetime) \r\n";
	$expiry = $time - $maxlifetime;
	db_delete('session', array('last_date'=>array('<'=>$expiry)));
	db_delete('session_data', array('last_date'=>array('<'=>$expiry)));
	return TRUE; 
}

function sess_start() {
	global $conf, $sid, $g_session;
	ini_set('session.name', 'bbs_sid');
	
	ini_set('session.use_cookies', 'On');
	ini_set('session.use_only_cookies', 'On');
	ini_set('session.cookie_domain', '');
	ini_set('session.cookie_path', '');	// 为空则表示当前目录和子目录
	ini_set('session.cookie_secure', 'Off'); // 打开后，只有通过 https 才有效。
	ini_set('session.cookie_lifetime', 8640000);
	ini_set('session.cookie_httponly', 'On'); // 打开后 js 获取不到 HTTP 设置的 cookie, 有效防止 XSS，这个对于安全很重要，除非有 BUG，否则不要关闭。
	
	ini_set('session.gc_maxlifetime', $conf['online_hold_time']);	// 活动时间 $conf['online_hold_time']
	ini_set('session.gc_probability', 1); 	// 垃圾回收概率 = gc_probability/gc_divisor
	ini_set('session.gc_divisor', 500); 	// 垃圾回收时间 5 秒，在线人数 * 10 
	
	session_set_save_handler('sess_open', 'sess_close', 'sess_read', 'sess_write', 'sess_destroy', 'sess_gc'); 
	
	// register_shutdown_function 会丢失当前目录，需要 chdir(APP_PATH)
	
	// 这个比须有，否则 ZEND 会提前释放 $db 资源
	register_shutdown_function('session_write_close');
	
	session_start();
	
	$sid = session_id();
	
	//$_SESSION['uid'] = $g_session['uid'];
	//$_SESSION['fid'] = $g_session['fid'];
	
	//echo "sess_start() sid: $sid <br>\r\n";
	//print_r(db_find('session'));
	return $sid;
}

function online_count() {
	return db_count('session');
}

function online_find_cache() {
	return db_find('session');
}

function online_list_cache() {
	$onlinelist = cache_get('online_list');
	if($onlinelist === NULL) {
		$onlinelist = db_find('session', array('uid'=>array('>'=>0)), array('last_date'=>-1), 1, 500);
		foreach($onlinelist as &$online) {
			$user = user_read_cache($online['uid']);
			$online['username'] = $user['username'];
			$online['gid'] = $user['gid'];
			$online['ip_fmt'] = long2ip($online['ip']);
			$online['last_date_fmt'] = date('Y-n-j H:i', $online['last_date']);
		}
		cache_set('online_list', $onlinelist, 300);
	}
	return $onlinelist;
}

?>