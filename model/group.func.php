<?php

// hook model_group_start.php

// ------------> 最原生的 CURD，无关联其他数据。

function group__create($arr) {
	// hook model_group__create_start.php
	$r = db_create('group', $arr);
	// hook model_group__create_end.php
	return $r;
}

function group__update($gid, $arr) {
	// hook model_group__update_start.php
	$r = db_update('group', array('gid'=>$gid), $arr);
	// hook model_group__update_end.php
	return $r;
}

function group__read($gid) {
	// hook model_group__read_start.php
	$group = db_find_one('group', array('gid'=>$gid));
	// hook model_group__read_end.php
	return $group;
}

function group__delete($gid) {
	// hook model_group__delete_start.php
	$r = db_delete('group', array('gid'=>$gid));
	// hook model_group__delete_end.php
	return $r;
}

function group__find($cond = array(), $orderby = array(), $page = 1, $pagesize = 1000) {
	// hook model_group__find_start.php
	$grouplist = db_find('group', $cond, $orderby, $page, $pagesize, 'gid');
	// hook model_group__find_end.php
	return $grouplist;
}

// ------------> 关联 CURD，主要是强相关的数据，比如缓存。弱相关的大量数据需要另外处理。

function group_create($arr) {
	// hook model_group_create_start.php
	$r = group__create($arr);
	group_list_cache_delete();
	forum_access_padding($arr['gid'], TRUE); // 填充
	// hook model_group_create_end.php
	return $r;
}

function group_update($gid, $arr) {
	// hook model_group_update_start.php
	$r = group__update($gid, $arr);
	group_list_cache_delete();
	// hook model_group_update_end.php
	return $r;
}

function group_read($gid) {
	// hook model_group_read_start.php
	$group = group__read($gid);
	group_format($group);
	// hook model_group_read_end.php
	return $group;
}

function group_delete($gid) {
	// hook model_group_delete_start.php
	$r = group__delete($gid);
	group_list_cache_delete();
	forum_access_padding($gid, FALSE); // 删除
	// hook model_group_delete_end.php
	return $r;
}

function group_find($cond = array(), $orderby = array('gid'=>1), $page = 1, $pagesize = 1000) {
	// hook model_group_find_start.php
	$grouplist = group__find($cond, $orderby, $page, $pagesize);
	if($grouplist) foreach ($grouplist as &$group) group_format($group);
	// hook model_group_find_end.php
	return $grouplist;
}

// ------------> 其他方法

function group_format(&$group) {
	// hook model_group_format_start.php
	
}

function group_name($gid) {
	global $grouplist;
	return isset($grouplist[$gid]['name']) ? $grouplist[$gid]['name'] : '';
}


function group_count($cond = array()) {
	$n = db_count('group', $cond);
	// hook model_group_format_end.php
	return $n;
}

function group_maxid() {
	// hook model_group_maxid_start.php
	$n = db_maxid('group', 'gid');
	// hook model_group_maxid_end.php
	return $n;
}

// 从缓存中读取 forum_list 数据
function group_list_cache() {
	$grouplist = cache_get('grouplist');
	// hook model_group_list_cache_start.php
	if($grouplist === NULL || $grouplist === FALSE) {
		$grouplist = group_find();
		cache_set('grouplist', $grouplist);
	}
	// hook model_group_list_cache_end.php
	return $grouplist;
}

// 更新 forumlist 缓存
function group_list_cache_delete() {
	// hook model_group_list_cache_delete_start.php
	$r = cache_delete('grouplist');
	// hook model_group_list_cache_delete_end.php
	return $r;
}


// hook model_group_end.php

?>