<?php

!defined('DEBUG') AND exit('Access Denied.');

$action = param(1);

// hook admin_user_start.php

if(empty($action) || $action == 'list') {

	$header['title'] = lang('user_admin');
	$header['mobile_title'] = lang('user_admin');
		
	$pagesize = 20;
	$srchtype = param(2);
	$keyword  = trim(xn_urldecode(param(3)));
	$page     = param(4, 1);

	// hook admin_user_list_start.php
	
	$cond = array();
	$allowtype = array('uid', 'username', 'email', 'gid', 'create_ip');
	
	// hook admin_user_list_allow_type_after.php
	
	if($keyword) {
		!in_array($srchtype, $allowtype) AND $srchtype = 'uid';
		$cond[$srchtype] = $srchtype == 'create_ip' ? sprintf('%u', ip2long($keyword)) : $keyword; 
	}

	// hook admin_user_list_cond_after.php
	$n = user_count($cond);
	$userlist = user_find($cond, array('uid'=>-1), $page, $pagesize);
	$pagination = pagination(url("user-list-$srchtype-".urlencode($keyword).'-{page}'), $n, $page, $pagesize);
	$pager = pager(url("user-list-$srchtype-".urlencode($keyword).'-{page}'), $n, $page, $pagesize);

	foreach ($userlist as &$_user) {
		$_user['group'] = array_value($grouplist, $_user['gid'], '');
	}

	// hook admin_user_list_end.php
	
	include _include(ADMIN_PATH."view/htm/user_list.htm");

} elseif($action == 'create') {

	// hook admin_user_create_get_post.php
	
	if($method == 'GET') {

		// hook admin_user_create_get_start.php
		
		$header['title'] = lang('admin_user_create');
		$header['mobile_title'] = lang('admin_user_create');
		
		$input['email'] = form_text('email', '');
		$input['username'] = form_text('username','');
		$input['password'] = form_password('password', '');
		$grouparr = arrlist_key_values($grouplist, 'gid', 'name');
		$input['_gid'] = form_select('_gid', $grouparr, 0);
		
		// hook admin_user_create_get_end.php
		
		include _include(ADMIN_PATH."view/htm/user_create.htm");

	} elseif ($method == 'POST') {

		$email = param('email');
		$username = param('username');
		$password = param('password');
		$_gid = param('_gid');
		
		// hook admin_user_create_post_start.php
		
		empty($email) AND message('email', lang('please_input_email'));
		$email AND !is_email($email, $err) AND message('email', $err);
		$username AND !is_username($username, $err) AND message('username', $err);

		$_user = user_read_by_email($email);
		$_user AND message('email', lang('email_is_in_use'));

		$_user = user_read_by_username($username);
		$_user AND message('username', lang('user_already_exists'));

		$salt = xn_rand(16);
		$r = user_create(array(
			'username'=>$username,
			'password'=>md5(md5($password).$salt),
			'salt'=>$salt,
			'gid'=>$_gid,
			'email'=>$email,
			'create_ip'=>$longip,
			'create_date'=>$time
		));
		$r === FALSE AND message(-1, lang('create_failed'));
		
		// hook admin_user_create_post_end.php
		
		message(0, lang('create_successfully'));

	}

} elseif($action == 'update') {

	$_uid = param(2, 0);
	
	// hook admin_user_update_get_post.php
	
	if($method == 'GET') {

		// hook admin_user_update_get_start.php
		
		$header['title'] = lang('user_edit');
		$header['mobile_title'] = lang('user_edit');
		
		$_user = user_read($_uid);
		
		$input['email'] = form_text('email', $_user['email']);
		$input['username'] = form_text('username', $_user['username']);
		$input['password'] = form_password('password', '');
		$grouparr = arrlist_key_values($grouplist, 'gid', 'name');
		$input['_gid'] = form_select('_gid', $grouparr, $_user['gid']);

		// hook admin_user_update_get_end.php
		
		include _include(ADMIN_PATH."view/htm/user_update.htm");

	} elseif($method == 'POST') {

		$email = param('email');
		$username = param('username');
		$password = param('password');
		$_gid = param('_gid');
		
		// hook admin_user_update_post_start.php
		
		$old = user_read($_uid);
		empty($old) AND message('username', lang('uid_not_exists'));
		
		$email AND !is_email($email, $err) AND message(2, $err);
		if($email AND $old['email'] != $email) {
			$_user = user_read_by_email($email);
			$_user AND $_user['uid'] != $_uid AND message('email', lang('email_already_exists'));
		}
		if($username AND $old['username'] != $username) {
			$_user = user_read_by_username($username);
			$_user AND $_user['uid'] != $_uid AND message('username', lang('user_already_exists'));
		}
		
		$arr = array();
		$arr['email'] = $email;
		$arr['username'] = $username;
		$arr['gid'] = $_gid;
		
		if($password) {
			$salt = xn_rand(16);
			$arr['password'] = md5(md5($password).$salt);
			$arr['salt'] = $salt;
		}
		
		// hook admin_user_update_post_exec_before.php
		
		// 仅仅更新发生变化的部分 / only update changed field
		$update = array_diff_value($arr, $old);
		empty($update) AND message(-1, lang('data_not_changed'));

		$r = user_update($_uid, $update);
		$r === FALSE AND message(-1, lang('update_failed'));
		
		// hook admin_user_update_post_end.php
		
		message(0, lang('update_successfully'));
	}

} elseif($action == 'delete') {

	if($method != 'POST') message(-1, 'Method Error.');

	$_uid = param('uid', 0);
	
	// hook admin_user_delete_start.php
	
	$_user = user_read($_uid);
	empty($_user) AND message(-1, lang('user_not_exists'));
	($_user['gid'] == 1) AND message(-1, 'admin_cant_be_deleted');

	$r = user_delete($_uid);
	$r === FALSE AND message(-1, lang('delete_failed'));
	
	// hook admin_user_delete_end.php
	
	message(0, lang('delete_successfully'));
	
}

// hook admin_user_end.php

?>