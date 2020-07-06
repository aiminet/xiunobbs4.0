<?php

// hook model_thread_top_start.php

// 置顶主题

function thread_top_change($tid, $top = 0) {
	// hook model_thread_top_change_start.php
	$thread = thread__read($tid);
	if(empty($thread)) return FALSE;
	if($top != $thread['top']) {
		thread__update($tid, array('top'=>$top));
		$fid = $thread['fid'];
		$tid = $thread['tid'];
		thread_top_cache_delete();
		
		$arr = array('fid'=>$fid, 'tid'=>$tid, 'top'=>$top);
		$r = db_replace('thread_top', $arr);
		return $r;
	}
	// hook model_thread_top_change_end.php
	return FALSE;
}

function thread_top_delete($tid) {
	// hook model_thread_top_delete_start.php
	$r = db_delete('thread_top', array('tid'=>$tid));
	// hook model_thread_top_delete_end.php
	return $r;
}

function thread_top_find($fid = 0) {
	// hook model_thread_top_find_start.php
	if($fid == 0) {
		$threadlist = db_find('thread_top', array('top'=>3), array('tid'=>-1), 1, 100, 'tid');
	} else {
		$threadlist = db_find('thread_top', array('fid'=>$fid, 'top'=>1), array('tid'=>-1), 1, 100, 'tid');
	}
	$tids = arrlist_values($threadlist, 'tid');
	$threadlist = thread_find_by_tids($tids);
	// hook model_thread_top_find_end.php
	return $threadlist;
}

function thread_top_find_cache() {
	// hook model_thread_top_find_cache_start.php
	global $conf;
	$threadlist = cache_get('thread_top_list');
	if($threadlist === NULL) {
		$threadlist = thread_top_find();
		cache_set('thread_top_list', $threadlist);
	} else {
		// 重新格式化时间
		foreach($threadlist as &$thread) {
			thread_format_last_date($thread);
		}
	}
	// hook model_thread_top_find_cache_end.php
	return $threadlist;
}

function thread_top_cache_delete() {
	// hook model_thread_top_cache_delete_start.php
	global $conf;
	static $deleted = FALSE;
	if($deleted) return;
	cache_delete('thread_top_list');
	$deleted = TRUE;
	// hook model_thread_top_cache_delete_end.php
}

function thread_top_update_by_tid($tid, $newfid) {
	// hook model_thread_top_update_by_tid_start.php
	$r = db_update('thread_top', array('tid'=>$tid), array('fid'=>$newfid));
	// hook model_thread_top_update_by_tid_end.php
	return $r;
}


// hook model_thread_top_end.php

?>