<?php

// hook model_forum_access_start.php

// ------------> 最原生的 CURD，无关联其他数据。

function forum_access__create($arr) {
	// hook model_forum_access__create_start.php
	$r = db_create('forum_access', $arr);
	// hook model_forum_access__create_end.php
	return $r;
}

function forum_access__update($fid, $gid, $arr) {
	// hook model_forum_access__update_start.php
	$r = db_update('forum_access', array('fid'=>$fid, 'gid'=>$gid), $arr);
	// hook model_forum_access__update_end.php
	return $r;
}

function forum_access__read($fid, $gid) {
	// hook model_forum_access__read_start.php
	$access = db_find_one('forum_access', array('fid'=>$fid, 'gid'=>$gid));
	// hook model_forum_access__read_end.php
	return $access;
}

function forum_access__delete($fid, $gid) {
	// hook model_forum_access__delete_start.php
	$r = db_delete('forum_access', array('fid'=>$fid, 'gid'=>$gid));
	// hook model_forum_access__delete_end.php
	return $r;
}

function forum_access__find($cond = array(), $orderby = array(), $page = 1, $pagesize = 20) {
	// hook model_forum_access__find_start.php
	$accesslist = db_find('forum_access', $cond, $orderby, $page, $pagesize);
	// hook model_forum_access__find_end.php
	return $accesslist;
}

// ------------> 关联 CURD，主要是强相关的数据，比如缓存。弱相关的大量数据需要另外处理。

function forum_access_create($arr) {
	// hook model_forum_access_create_start.php
	$r = forum_access__create($arr);
	// hook model_forum_access_create_end.php
	return $r;
}

function forum_access_update($fid, $gid, $arr) {
	// hook model_forum_access_update_start.php
	$r = forum_access__update($fid, $gid, $arr);
	// hook model_forum_access_update_end.php
	return $r;
}

// 不存在，则创建一条
function forum_access_replace($fid, $gid, $arr) {
	// hook model_forum_access_replace_start.php
	$access = forum_access__read($fid, $gid);
	if(empty($access)) {
		$arr['fid'] = $fid;
		$arr['gid'] = $gid;
		$r = forum_access__create($arr);
	} else {
		$r = forum_access__update($fid, $gid, $arr);
	}
	// hook model_forum_access_replace_end.php
	return $r;
}

// 根据 gid 补充 forum_access
function forum_access_padding($gid, $fill = FALSE) {
	// hook model_forum_access_padding_start.php
	$forumlist = forum_list_cache();
	foreach($forumlist as $fid=>$forum) {
		if(!$forum['accesson']) continue;
		$fill ? forum_access_create(array('fid'=>$fid, 'gid'=>$gid)) : forum_access_delete($fid, $gid);
	}
	// hook model_forum_access_padding_end.php
}

function forum_access_read($fid, $gid) {
	// hook model_forum_access_read_start.php
	$access = forum_access__read($fid, $gid);
	forum_access_format($access);
	// hook model_forum_access_read_end.php
	return $access;
}

function forum_access_delete($fid, $gid) {
	// hook model_forum_access_delete_start.php
	$r = forum_access__delete($fid, $gid);
	// hook model_forum_access_delete_end.php
	return $r;
}

function forum_access_delete_by_fid($fid) {
	// hook model_forum_access_delete_by_fid_start.php
	$accesslist = forum_access_find_by_fid($fid);
	foreach ($accesslist as $access) {
		forum_access_delete($access['fid'], $access['gid']);
	}
	// hook model_forum_access_delete_by_fid_end.php
}

function forum_access_find($cond = array(), $orderby = array(), $page = 1, $pagesize = 20) {
	// hook model_forum_access_find_start.php
	$accesslist = forum_access__find($cond, $orderby, $page, $pagesize);
	if($accesslist) foreach ($accesslist as &$access) forum_access_format($access);
	// hook model_forum_access_find_end.php
	return $accesslist;
}

function forum_access_find_by_fid($fid) {
	// hook model_forum_access_find_by_fid_start.php
	$cond = array('fid'=>$fid);
	$orderby = array('gid'=>1);
	$accesslist = db_find('forum_access', $cond, $orderby, 1, 100, 'gid');
	// hook model_forum_access_find_by_fid_end.php
	return $accesslist;
}

// 普通用户权限判断: allowread, allowthread, allowpost, allowattach, allowdown
function forum_access_user($fid, $gid, $access) {
	// hook model_forum_access_user_start.php
	global $conf, $grouplist, $forumlist;
	if(empty($forumlist[$fid])) return FALSE;
	$group = $grouplist[$gid];
	$forum = $forumlist[$fid];
	if($forum['accesson']) {
		$r = (!isset($group[$access]) || $group[$access]) && !empty($forum['accesslist'][$gid][$access]);
	} else {
		$r = !empty($group[$access]);
	}
	// hook model_forum_access_user_end.php
	return $r;
}

// 板块斑竹权限判断: allowtop, allowmove, allowupdate, allowdelete, allowbanuser, allowviewip, allowdeleteuser
function forum_access_mod($fid, $gid, $access) {
	// hook model_forum_access_mod_start.php
	global $uid, $conf, $grouplist, $forumlist;
	
	// 结果缓存，加速判断！
	static $result = array();
	$k = "$fid-$gid-$access";
	if(isset($result[$k])) return $result[$k];
	
	if($gid == 1 || $gid == 2) return TRUE; // 管理员有所有权限
	if($gid == 3 || $gid == 4) {
		$group = $grouplist[$gid];
		$forum = $forumlist[$fid];
		$r = !empty($group[$access]) && in_string($uid, $forum['moduids']);
	} else {
		$r = FALSE;
	}
	$result[$k] = $r;
	// hook model_forum_access_mod_end.php
	return $r;
}

function forum_is_mod($fid, $gid, $uid) {
	// hook forum_is_mod_start.php
	global $conf, $grouplist, $forumlist;
	if($gid == 1 || $gid == 2) return TRUE; // 管理员有所有权限
	if($gid == 3 || $gid == 4) {
		if($fid == 0) return TRUE; // 此处不严谨！
		$group = $grouplist[$gid];
		$forum = $forumlist[$fid];
		return in_string($uid, $forum['moduids']);
	}
	// hook forum_is_mod_end.php
	return FALSE;
}

// ------------> 其他方法

function forum_access_format(&$access) {
	// hook model_forum_access_format_start.php
	if(empty($access)) return;
	// hook model_forum_access_format_end.php
}

function forum_access_count($cond = array()) {
	// hook model_forum_access_count_start.php
	$n = db_count('forum_access', $cond);
	// hook model_forum_access_count_end.php
	return $n;
}


// hook model_forum_access_end.php

?>