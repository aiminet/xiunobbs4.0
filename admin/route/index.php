<?php

!defined('DEBUG') AND exit('Access Denied.');

$action = param(1);

// hook admin_index_start.php

if($action == 'login') {

	// hook admin_index_login_get_post.php
	
	if($method == 'GET') {

		// hook admin_index_login_get_start.php
		
		$header['title'] = lang('admin_login');
		
		include _include(ADMIN_PATH."view/htm/index_login.htm");

	} else if($method == 'POST') {

		// hook admin_index_login_post_start.php
		
		$password = param('password');

		if(md5($password.$user['salt']) != $user['password']) {
			xn_log('password error. uid:'.$user['uid'].' - ******'.substr($password, -6), 'admin_login_error');
			message('password', lang('password_incorrect'));
		}

		admin_token_set();

		xn_log('login successed. uid:'.$user['uid'], 'admin_login');

		// hook admin_index_login_post_end.php
		
		message(0, jump(lang('login_successfully'), '.'));

	}

} elseif ($action == 'logout') {

	// hook admin_index_logout_start.php
	
	admin_token_clean();
	
	message(0, jump(lang('logout_successfully'), './'));

} elseif ($action == 'phpinfo') {
	
	unset($_SERVER['conf']);
	unset($_SERVER['db']);
	unset($_SERVER['cache']);
	phpinfo();
	exit;
	
} else {

	// hook admin_index_empty_start.php
	
	$header['title'] = lang('admin_page');
	
	$info = array();
	$info['disable_functions'] = ini_get('disable_functions');
	$info['allow_url_fopen'] = ini_get('allow_url_fopen') ? lang('yes') : lang('no');
	$info['safe_mode'] = ini_get('safe_mode') ? lang('yes') : lang('no');
	empty($info['disable_functions']) && $info['disable_functions'] = lang('none');
	$info['upload_max_filesize'] = ini_get('upload_max_filesize');
	$info['post_max_size'] = ini_get('post_max_size');
	$info['memory_limit'] = ini_get('memory_limit');
	$info['max_execution_time'] = ini_get('max_execution_time');
	$info['dbversion'] = $db->version();
	$info['SERVER_SOFTWARE'] = _SERVER('SERVER_SOFTWARE');
	$info['HTTP_X_FORWARDED_FOR'] = _SERVER('HTTP_X_FORWARDED_FOR');
	$info['REMOTE_ADDR'] = _SERVER('REMOTE_ADDR');
	
	
	$stat = array();
	$stat['threads'] = thread_count();
	$stat['posts'] = post_count();
	$stat['users'] = user_count();
	$stat['attachs'] = attach_count();
	$stat['disk_free_space'] = function_exists('disk_free_space') ? humansize(disk_free_space(APP_PATH)) : lang('unknown');
	
	$lastversion = get_last_version($stat);
	
	// hook admin_index_empty_end.php
	
	include _include(ADMIN_PATH.'view/htm/index.htm');

}

// hook admin_index_end.php

function get_last_version($stat) {
	global $conf, $time;
	$last_version = kv_get('last_version');
	if($time - $last_version > 86400) {
		kv_set('last_version', $time);
		$sitename = urlencode($conf['sitename']);
		$sitedomain = urlencode(http_url_path());
		$version = urlencode($conf['version']);
		return '<script src="http://custom.xiuno.com/version.htm?sitename='.$sitename.'&sitedomain='.$sitedomain.'&users='.$stat['users'].'&threads='.$stat['threads'].'&posts='.$stat['posts'].'&version='.$version.'"></script>';
	} else {
		return '';
	}
}

?>
