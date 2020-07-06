<?php

// hook model_forum_start.php

// ------------> 最原生的 CURD，无关联其他数据。

function forum__create($arr) {
	// hook model_forum__create_start.php
	$r = db_create('forum', $arr);
	// hook model_forum__create_end.php
	return $r;
}

function forum__update($fid, $arr) {
	// hook model_forum__update_start.php
	$r = db_update('forum', array('fid'=>$fid), $arr);
	// hook model_forum__update_end.php
	return $r;
}

function forum__read($fid) {
	// hook model_forum__read_start.php
	$forum = db_find_one('forum', array('fid'=>$fid));
	// hook model_forum__read_end.php
	return $forum;
}

function forum__delete($fid) {
	// hook model_forum__delete_start.php
	$r = db_delete('forum', array('fid'=>$fid));
	// hook model_forum__delete_end.php
	return $r;
}

function forum__find($cond = array(), $orderby = array(), $page = 1, $pagesize = 1000) {
	// hook model_forum__find_start.php
	$forumlist = db_find('forum', $cond, $orderby, $page, $pagesize, 'fid');
	// hook model_forum__find_end.php
	return $forumlist;
}

// ------------> 关联 CURD，主要是强相关的数据，比如缓存。弱相关的大量数据需要另外处理。

function forum_create($arr) {
	// hook model_forum_create_start.php
	$r = forum__create($arr);
	forum_list_cache_delete();
	// hook model_forum_create_end.php
	return $r;
}

function forum_update($fid, $arr) {
	// hook model_forum_update_start.php
	$r = forum__update($fid, $arr);
	forum_list_cache_delete();
	// hook model_forum_update_end.php
	return $r;
}

function forum_read($fid) {
	// hook model_forum_read_start.php
	global $conf, $forumlist;
	if($conf['cache']['enable']) {
		return empty($forumlist[$fid]) ? array() : $forumlist[$fid];
	} else {
		$forum = forum__read($fid);
		forum_format($forum);
		return $forum;
	}
	// hook model_forum_read_end.php
}

// 关联数据删除
function forum_delete($fid) {
	//  把板块下所有的帖子都查找出来，此处数据量大可能会超时，所以不要删除帖子特别多的板块
	$cond = array('fid'=>$fid);
	$threadlist = db_find('thread', $cond, array(), 1, 1000000, '', array('tid', 'uid'));
	
	// hook model_forum_delete_start.php
	
	foreach ($threadlist as $thread) {
		thread_delete($thread['tid']);
	}
	
	$r = forum__delete($fid);
	
	forum_access_delete_by_fid($fid);
	
	forum_list_cache_delete();
	// hook model_forum_delete_end.php
	return $r;
}

function forum_find($cond = array(), $orderby = array('rank'=>-1), $page = 1, $pagesize = 1000) {
	// hook model_forum_find_start.php
	$forumlist = forum__find($cond, $orderby, $page, $pagesize);
	if($forumlist) foreach ($forumlist as &$forum) forum_format($forum);
	// hook model_forum_find_end.php
	return $forumlist;
}

// ------------> 其他方法

function forum_format(&$forum) {
	global $conf;
	if(empty($forum)) return;
	
	// hook model_forum_format_start.php
	
	$forum['create_date_fmt'] = date('Y-n-j', $forum['create_date']);
	$forum['icon_url'] = $forum['icon'] ? $conf['upload_url']."forum/$forum[fid].png" : 'view/img/forum.png';
	$forum['accesslist'] = $forum['accesson'] ? forum_access_find_by_fid($forum['fid']) : array();
	$forum['modlist'] = array();
	if($forum['moduids']) {
		$modlist = user_find_by_uids($forum['moduids']);
		foreach($modlist as &$mod) $mod = user_safe_info($mod);
		$forum['modlist'] = $modlist;
	}
	// hook model_forum_format_end.php
}

function forum_count($cond = array()) {
	// hook model_forum_count_start.php
	$n = db_count('forum', $cond);
	// hook model_forum_count_end.php
	return $n;
}

function forum_maxid() {
	// hook model_forum_maxid_start.php
	$n = db_maxid('forum', 'fid');
	// hook model_forum_maxid_end.php
	return $n;
}

// 从缓存中读取 forum_list 数据x
function forum_list_cache() {
	global $conf, $forumlist;
	$forumlist = cache_get('forumlist');
	
	// hook model_forum_list_cache_start.php
	
	if($forumlist === NULL) {
		$forumlist = forum_find();
		cache_set('forumlist', $forumlist, 60);
	}
	// hook model_forum_list_cache_end.php
	return $forumlist;
}

// 更新 forumlist 缓存
function forum_list_cache_delete() {
	global $conf;
	static $deleted = FALSE;
	if($deleted) return;
	
	// hook model_forum_list_cache_delete_start.php
	
	cache_delete('forumlist');
	$deleted = TRUE;
	// hook model_forum_list_cache_delete_end.php
}

// 对 $forumlist 权限过滤，查看权限没有，则隐藏
function forum_list_access_filter($forumlist, $gid, $allow = 'allowread') {
	global $conf, $grouplist;
	if(empty($forumlist)) return array();
	if($gid == 1) return $forumlist;
	$forumlist_filter = $forumlist;
	$group = $grouplist[$gid];
	
	// hook model_forum_list_access_filter_start.php
	
	foreach($forumlist_filter as $fid=>$forum) {
		if(empty($forum['accesson']) && empty($group[$allow]) || !empty($forum['accesson']) && empty($forum['accesslist'][$gid][$allow])) {
			unset($forumlist_filter[$fid]);
			unset($forumlist_filter[$fid]['modlist']);
		}
		unset($forumlist_filter[$fid]['accesslist']);
	}
	// hook model_forum_list_access_filter_end.php
	return $forumlist_filter;
}

function forum_filter_moduid($moduids) {
	$moduids = trim($moduids);
	if(empty($moduids)) return '';
	$arr = explode(',', $moduids);
	$r = array();
	foreach($arr as $_uid) {
		$_uid = intval($_uid);
		$_user = user_read($_uid);
		if(empty($_user)) continue;
		if($_user['gid'] > 4) continue;
		$r[] = $_uid;
	}
	return implode(',', $r);
}


function forum_safe_info($forum) {
	// hook model_forum_safe_info_start.php
	//unset($forum['moduids']);
	// hook model_forum_safe_info_end.php
	return $forum;
}

// hook model_forum_end.php

?>