<?php

// hook model_check_start.php

function is_word($s) {
	// hook model_is_word_start.php
	$r = preg_match('#^\\w{1,32}$#', $s, $m);
	return $r;
}

function is_mobile($mobile, &$err) {
	// hook model_is_mobile_start.php
	if(!preg_match('#^\d{11}$#', $mobile)) {
		$err = lang('mobile_format_mismatch');
		return FALSE;
	}
	// hook model_is_mobile_end.php
	return TRUE;
}

function is_email($email, &$err) {
	// hook model_is_email_start.php
	$len = mb_strlen($email, 'UTF-8');
	if(strlen($len) > 32) {
		$err = lang('email_too_long', array('length'=>$len));
		return FALSE;
	} elseif(!preg_match('/^[\w\-\.]+@[\w\-\.]+(\.\w+)+$/i', $email)) {
		$err = lang('email_format_mismatch');
		return FALSE;
	}
	// hook model_is_email_end.php
	return TRUE;
}

function is_username($username, &$err = '') {
	// hook model_is_username_start.php
	$len = mb_strlen($username, 'UTF-8');
	if($len > 16) {
		$err = lang('username_too_long', array('length'=>$len));
		return FALSE;
	} elseif(strpos($username, ' ') !== FALSE || strpos($username, '　') !== FALSE) {
		$err = lang('username_cant_include_cn_space');
		return FALSE;
	} elseif(!preg_match('#^[\w\x{4E00}-\x{9FA5}\x{1100}-\x{11FF}\x{3130}-\x{318F}\x{AC00}-\x{D7AF}]+$#u', $username)) {
		// 4E00-9FA5(中文)  1100-11FF(朝鲜文) 3130-318F(朝鲜文兼容字母) AC00-D7AF(朝鲜文音节)
		// 4E00-9FA5(chinese)  1100-11FF(korea) 3130-318F(korea compatiable word) AC00-D7AF(korea)
		$err = lang('username_format_mismatch');
		return FALSE;
	}
	// hook model_is_username_end.php
	return TRUE;
}

function is_password($password, &$err = '') {
	$len = strlen($password);
	// hook model_is_password_start.php
	if($len == 0) {
		$err = lang('password_is_empty');
		return FALSE;
	} elseif($len != 32) {
		$err = lang('password_length_incorrect');
		return FALSE;
	} elseif($password == 'd41d8cd98f00b204e9800998ecf8427e') {
		$err = lang('password_is_empty');
		return FALSE;
	}
	// hook model_is_password_start.php
	return TRUE;
}

// hook model_check_end.php

?>