<?php

// ------------> 关联的 CURD，无关联其他数据。

// hook model_mythread_start.php

function mythread_create($uid, $tid) {
	// hook model_mythread_create_start.php
	if($uid == 0) return TRUE; // 匿名发帖
	$thread = mythread_read($uid, $tid);
	if(empty($thread)) {
		$r = db_create('mythread', array('uid'=>$uid, 'tid'=>$tid));
		return $r;
	} else {
		return TRUE;
	}
	// hook model_mythread_create_end.php
}

function mythread_read($uid, $tid) {
	// hook model_mythread_read_start.php
	$mythread = db_find_one('mythread', array('uid'=>$uid, 'tid'=>$tid));
	// hook model_mythread_read_end.php
	return $mythread;
}

function mythread_delete($uid, $tid) {
	// hook model_mythread_delete_start.php
	$r = db_delete('mythread', array('uid'=>$uid, 'tid'=>$tid));
	// hook model_mythread_delete_end.php
	return $r;
}

function mythread_delete_by_uid($uid) {
	// hook model_mythread_delete_by_uid_start.php
	$r = db_delete('mythread', array('uid'=>$uid));
	// hook model_mythread_delete_by_uid_end.php
	return $r;
}

function mythread_delete_by_fid($fid) {
	// hook model_mythread_delete_by_fid_start.php
	$r = db_delete('mythread', array('fid'=>$fid));
	// hook model_mythread_delete_by_fid_end.php
	return $r;
}

function mythread_delete_by_tid($tid) {
	// hook model_mythread_delete_by_tid_start.php
	$r = db_delete('mythread', array('tid'=>$tid));
	// hook model_mythread_delete_by_tid_end.php
	return $r;
}

function mythread_find($cond = array(), $orderby = array(), $page = 1, $pagesize = 20) {
	// hook model_mythread_find_start.php
	$mythreadlist = db_find('mythread', $cond, $orderby, $page, $pagesize);
	// hook model_mythread_find_end.php
	return $mythreadlist;
}

function mythread_find_by_uid($uid, $page = 1, $pagesize = 20) {
	// hook model_mythread_find_by_uid_start.php
	$mythreadlist = mythread_find(array('uid'=>$uid), array('tid'=>-1), $page, $pagesize);
	if(empty($mythreadlist)) return array();
	$threadlist = array();
	foreach ($mythreadlist as &$mythread) {
		$threadlist[$mythread['tid']] = thread_read($mythread['tid']);
	}
	// hook model_mythread_find_by_uid_end.php
	return $threadlist;
}

// hook model_mythread_end.php

?>