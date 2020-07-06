<?php

// ------------> 最原生的 CURD，无关联其他数据。

// hook model_modlog_start.php

function modlog__create($arr) {
	// hook model_modlog__create_start.php
	$r = db_create('modlog', $arr);
	// hook model_modlog__create_end.php
	return $r;
}

function modlog__update($logid, $arr) {
	// hook model_modlog__update_start.php
	$r = db_update('modlog', array('logid'=>$logid), $arr);
	// hook model_modlog__update_end.php
	return $r;
}

function modlog__read($logid) {
	// hook model_modlog__read_start.php
	$modlog = db_find_one('modlog', array('logid'=>$logid));
	// hook model_modlog__read_end.php
	return $modlog;
}

function modlog__delete($logid) {
	// hook model_modlog__delete_start.php
	$r = db_delete('modlog', array('logid'=>$logid));
	// hook model_modlog__delete_end.php
	return $r;
}

function modlog__find($cond = array(), $orderby = array(), $page = 1, $pagesize = 20) {
	// hook model_modlog__find_start.php
	$modloglist = db_find('modlog', $cond, $orderby, $page, $pagesize);
	// hook model_modlog__find_end.php
	return $modloglist;
}

// ------------> 关联 CURD，主要是强相关的数据，比如缓存。弱相关的大量数据需要另外处理。

function modlog_create($arr) {
	// hook model_modlog_create_start.php
	$r = modlog__create($arr);
	// hook model_modlog_create_end.php
	return $r;
}

function modlog_update($logid, $arr) {
	// hook model_modlog_update_start.php
	$r = modlog__update($logid, $arr);
	// hook model_modlog_update_end.php
	return $r;
}

function modlog_read($logid) {
	// hook model_modlog_read_start.php
	$modlog = modlog__read($logid);
	$modlog AND modlog_format($modlog);
	// hook model_modlog_read_end.php
	return $modlog;
}

function modlog_delete($logid) {
	// hook model_modlog_delete_start.php
	$r = modlog__delete($logid);
	// hook model_modlog_delete_end.php
	return $r;
}

function modlog_find($cond = array(), $orderby = array(), $page = 1, $pagesize = 20) {
	// hook model_modlog_find_start.php
	$modloglist = modlog__find($cond, $orderby, $page, $pagesize);
	if($modloglist) foreach ($modloglist as &$modlog) modlog_format($modlog);
	// hook model_modlog_find_end.php
	return $modloglist;
}

// ----------------> 其他方法

function modlog_format(&$modlog) {
	// hook model_modlog_format_start.php
	global $conf;
	$modlog['create_date_fmt'] = date('Y-n-j', $modlog['create_date']);
	// hook model_modlog_format_end.php
}

function modlog_count($cond = array()) {
	// hook model_modlog_count_start.php
	$n = db_count('modlog', $cond);
	// hook model_modlog_count_end.php
	return $n;
}

function modlog_maxid() {
	// hook model_modlog_maxid_start.php
	$n = db_maxid('modlog', 'logid');
	// hook model_modlog_maxid_end.php
	return $n;
}

// hook model_modlog_end.php

?>