<?php

// hook model_smtp_start.php

// 用配置文件来保存 smtp 列表数据
$smtplist = array();
function smtp_create($arr) {
	// hook model_smtp_create_start.php
	global $smtplist;
	$smtplist[] = $arr;
	smtp_save();
	// hook model_smtp_create_end.php
	return count($smtplist);
}

function smtp_update($id, $arr) {
	// hook model_smtp_update_start.php
	global $smtplist;
	if(!isset($smtplist[$id])) return FALSE;
	foreach($arr as $k=>$v) {
		$smtplist[$id][$k] = $v;
	}
	smtp_save();
	// hook model_smtp_update_end.php
	return TRUE;
}

function smtp_read($id) {
	// hook model_smtp_read_start.php
	global $smtplist;
	// hook model_smtp_read_end.php
	return isset($smtplist[$id]) ? $smtplist[$id] : array();
}

function smtp_delete($id) {
	// hook model_smtp_delete_start.php
	global $smtplist;
	unset($smtplist[$id]);
	smtp_save();
	// hook model_smtp_delete_end.php
	return TRUE;
}

function smtp_save() {
	// hook model_smtp_save_start.php
	global $smtplist;
	// hook model_smtp_save_end.php
	file_put_contents(APP_PATH.'conf/smtp.conf.php', "<?php\r\nreturn ".var_export($smtplist,true).";\r\n?>");
}

function smtp_init($confile) {
	$list = array(
		array(
		'email'=>'',
		'host'=>'',
		'port'=>'',
		'user'=>'',
		'pass'=>'',
	));
	if(!is_file($confile)) {
		touch($confile);
		return $list;
	} else {
		$arr = include $confile;
		if(!is_array($arr)) {
			return $list;
		}
		return $arr;
	}
}

function smtp_find() {
	// hook model_smtp_find_start.php
	// hook model_smtp_find_end.php
	global $smtplist;
	return $smtplist;
}

function smtp_count() {
	// hook model_smtp_count_start.php
	global $smtplist;
	$n = count($smtplist);
	// hook model_smtp_count_end.php
	return $n;
}

function smtp_maxid() {
	// hook model_smtp_maxid_start.php
	// hook model_smtp_maxid_end.php
	return smtp_count() - 1;
}


// hook model_smtp_end.php

?>