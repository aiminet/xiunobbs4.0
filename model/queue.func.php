<?php

/*
	mysql 模拟队列，顺序可能是乱的，不是严格意义上的队列
*/

// 提取整个队列
function queue_find($queueid, $page = 1, $pagesize = 100) {
	$arrlist = db_find('queue', array('queueid'=>$queueid), array(), $page, $pagesize);
	$ids = array();
	if($arrlist) {
		$ids = arrlist_values($arrlist, 'v');
	}
	return $ids;
}

// 添加到队列
function queue_push($queueid, $v, $expiry = 0) {
	global $time;
	$r = db_create('queue', array('queueid'=>$queueid, 'v'=>$v, 'expiry'=>($time + $expiry)));
	return $r;
}

// 弹出某个值
function queue_pop($queueid) {
	$r = db_find_one('queue', array('queueid'=>$queueid));
	if($r) {
		queue_delete($queueid, $r['v']);
	}
	return $r ? $r['v'] : FALSE;
}

// 删除某个值
function queue_delete($queueid, $v) {
	$r = db_delete('queue', array('queueid'=>$queueid, 'v'=>$v));
	return $r;
}

// 销毁某个队列
function queue_destory($queueid) {
	$r = db_delete('queue', array('queueid'=>$queueid));
	return $r;
}

function queue_count($queueid) {
	$n = db_count('queue', array('queueid'=>$queueid));
	return $n;
}

function queue_gc() {
	global $time;
	$r = db_delete('queue', array('expiry'=>array('<'=>$time)));
	return $r;
}

?>