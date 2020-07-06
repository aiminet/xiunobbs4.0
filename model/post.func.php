<?php

// hook model_post_start.php

// ------------> 最原生的 CURD，无关联其他数据。

// 只用传 message, message_fmt 自动生成
function post__create($arr, $gid) {
	// hook model_post__create_start.php
	
	post_message_fmt($arr, $gid);
	
	// hook model_post__create_insert_before.php
	
	$r = db_insert('post', $arr);
	// hook model_post__create_end.php
	return $r;
}

function post__update($pid, $arr) {
	// hook model_post__update_start.php
	$r = db_update('post', array('pid'=>$pid), $arr);
	// hook model_post__update_end.php
	return $r;
}

function post__read($pid) {
	// hook model_post__read_start.php
	$post = db_find_one('post', array('pid'=>$pid));
	// hook model_post__read_end.php
	return $post;
}

function post__delete($pid) {
	// hook model_post__delete_start.php
	$r = db_delete('post', array('pid'=>$pid));
	// hook model_post__delete_end.php
	return $r;
}

function post__find($cond = array(), $orderby = array(), $page = 1, $pagesize = 20) {
	// hook model_post__find_start.php
	$postlist = db_find('post', $cond, $orderby, $page, $pagesize, 'pid');
	// hook model_post__find_end.php
	return $postlist;
}

// ------------> 关联 CURD，主要是强相关的数据，比如缓存。弱相关的大量数据需要另外处理。

// 回帖
function post_create($arr, $fid, $gid) {
	global $conf, $time;
	
	// hook model_post_create_start.php
	
	$pid = post__create($arr, $gid);
	if(!$pid) return $pid;
	
	$tid = $arr['tid'];
	$uid = $arr['uid'];

	// 回帖
	if($tid > 0) {
		
		// todo: 如果是老帖，不更新 lastpid
		thread__update($tid, array('posts+'=>1, 'lastpid'=>$pid, 'lastuid'=>$uid, 'last_date'=>$time));
		$uid AND user__update($uid, array('posts+'=>1));
	
		runtime_set('posts+', 1);
		runtime_set('todayposts+', 1);
		forum__update($fid, array('todayposts+'=>1));
	}
	
	//post_list_cache_delete($tid);
	
	// 更新板块信息。
	forum_list_cache_delete();
	
	// 关联附件
	$message = $arr['message'];
	attach_assoc_post($pid);
	
	// 更新用户的用户组
	user_update_group($uid);
	
	// hook model_post_create_end.php
	
	return $pid;
}

// 编辑回帖
function post_update($pid, $arr, $tid = 0) {
	global $conf, $user, $gid;

	$post = post__read($pid);
	if(empty($post)) return FALSE;
	$tid = $post['tid'];
	$uid = $post['uid'];
	$isfirst = $post['isfirst'];
	
	// hook model_post_update_start.php

	
	post_message_fmt($arr, $gid);
	
	// hook model_post_create_post__create_before.php
	
	$r = post__update($pid, $arr);
	
	attach_assoc_post($pid);
	
	// hook model_post_update_end.php
	return $r;
}

function post_read($pid) {
	// hook model_post_read_start.php
	$post = post__read($pid);
	post_format($post);
	// hook model_post_read_end.php
	return $post;
}

// 从缓存中读取，避免重复从数据库取数据，主要用来前端显示，可能有延迟。重要业务逻辑不要调用此函数，数据可能不准确，因为并没有清理缓存，针对 request 生命周期有效。
function post_read_cache($pid) {
	// hook model_post_read_cache_start.php
	static $cache = array(); // 用静态变量只能在当前 request 生命周期缓存，要跨进程，可以再加一层缓存： memcached/xcache/apc/
	if(isset($cache[$pid])) return $cache[$pid];
	$cache[$pid] = post_read($pid);
	// hook model_post_read_cache_end.php
	return $cache[$pid];
}

// $tid 用来清理缓存
function post_delete($pid) {
	global $conf;
	$post = post_read_cache($pid);
	if(empty($post)) return TRUE; // 已经不存在了。
	
	$tid = $post['tid'];
	$uid = $post['uid'];
	$thread = thread_read_cache($tid);
	$fid = $thread['fid'];
	
	// hook model_post_delete_start.php
	
	if(!$post['isfirst']) {
		thread__update($tid, array('posts-'=>1));
		$uid AND user__update($uid, array('posts-'=>1));
		runtime_set('posts-', 1);
	} else {
		//post_list_cache_delete($tid);
	}
	
	($post['images'] || $post['files']) AND attach_delete_by_pid($pid);
	
	$r = post__delete($pid);

	// 更新最后的 lastpid
	if($r && !$post['isfirst'] && $pid == $thread['lastpid']) {
		thread_update_last($tid);
	}
	
	// hook model_post_delete_end.php
	return $r;
}

// 此处有可能会超时
function post_delete_by_tid($tid) {
	// hook model_post_delete_by_tid_start.php
	$postlist = post_find_by_tid($tid);
	foreach($postlist as $post) {
		post_delete($post['pid']);
	}
	// hook model_post_delete_by_tid_end.php
	return count($postlist);
}

// 此处有可能会超时，并且导致统计不准确，需要重建统计数
function post_delete_by_uid($uid) {
	// hook model_post_delete_by_uid_start.php
	$r = db_delete('post', array('uid'=>$uid));
	// hook model_post_delete_by_uid_end.php
	return $r;
}

function post_find($cond = array(), $orderby = array(), $page = 1, $pagesize = 20) {
	// hook model_post_find_start.php
	$postlist = post__find($cond, $orderby, $page, $pagesize);
	$floor = 1;
	if($postlist) foreach($postlist as &$post) {
		$post['floor'] = $floor++;
		post_format($post);
	}
	// hook model_post_find_end.php
	return $postlist;
}

// 此处有缓存，是否有必要？
function post_find_by_tid($tid, $page = 1, $pagesize = 50) {
	global $conf;
	
	// hook model_post_find_by_tid_start.php
	
	$postlist = post__find(array('tid'=>$tid), array('pid'=>1), $page, $pagesize);
	
	if($postlist) {
		$floor = ($page - 1)* $pagesize + 1;
		foreach($postlist as &$post) {
			$post['floor'] = $floor++;
			post_format($post);
		}
	}
	
	// hook model_post_find_by_tid_end.php
	return $postlist;
}

// <img src="/view/img/face/1.gif"/>
// <blockquote class="blockquote">
function user_post_message_format(&$s) {
	if(xn_strlen($s) < 100) return;
	$s = preg_replace('#<blockquote\s+class="blockquote">.*?</blockquote>#is', '', $s);
	$s = str_ireplace(array('<br>', '<br />', '<br/>', '</p>', '</tr>', '</div>', '</li>', '</dd>'. '</dt>'), "\r\n", $s);
	$s = str_ireplace(array('&nbsp;'), " ", $s);
	$s = strip_tags($s);
	$s = preg_replace('#[\r\n]+#', "\n", $s);
	$s = xn_substr(trim($s), 0, 100);
	$s = str_replace("\n", '<br>', $s);
}


/*
function post_list_cache_delete($tid) {
	// hook model_post_list_cache_delete_start.php
	global $conf;
	$r = cache_delete("postlist_$tid");
	// hook model_post_list_cache_delete_end.php
	return $r;
}*/

// ------------> 其他方法

function post_count($cond = array()) {
	// hook model_post_count_start.php
	$n = db_count('post', $cond);
	// hook model_post_count_end.php
	return $n;
}

function post_maxid() {
	// hook model_post_maxid_start.php
	$n = db_maxid('post', 'pid');
	// hook model_post_maxid_end.php
	return $n;
}

function post_safe_info($post) {
	// hook model_post_safe_info_start.php
	unset($post['userip']);
	if(!empty($post['user'])) {
		$post['user'] = user_safe_info($post['user']);
	}
	// hook model_post_safe_info_end.php
	return $post;
}

function post_find_by_pids($pids, $order = array('pid'=>-1)) {
	// hook model_post_find_by_pids_start.php
	if(!$pids) return array();
	$postlist = db_find('post', array('pid'=>$pids), $order, 1, 1000, 'pid');
	if($postlist) foreach($postlist as &$post) post_format($post);
	// hook model_post_find_by_pids_end.php
	return $postlist;
}


function post_highlight_keyword($str, $k) {
	// hook model_post_highlight_keyword_start.php
	$r = str_ireplace($k, '<span class="red">'.$k.'</span>', $str);
	// hook model_post_highlight_keyword_end.php
	return $r;
}

// 公用的附件模板，采用函数，效率比 include 高。
function post_file_list_html($filelist, $include_delete = FALSE) {
	if(empty($filelist)) return '';
	
	// hook model_post_file_list_html_start.php
	
	$s = '<fieldset class="fieldset">'."\r\n";
	$s .= '<legend>上传的附件：</legend>'."\r\n";
	$s .= '<ul class="attachlist">'."\r\n";
	foreach ($filelist as &$attach) {
		$s .= '<li aid="'.$attach['aid'].'">'."\r\n";
		$s .= '		<a href="'.url("attach-download-$attach[aid]").'" target="_blank">'."\r\n";
		$s .= '			<i class="icon filetype '.$attach['filetype'].'"></i>'."\r\n";
		$s .= '			'.$attach['orgfilename']."\r\n";
		$s .= '		</a>'."\r\n";
		// hook model_post_file_list_html_delete_before.php
		$include_delete AND $s .= '		<a href="javascript:void(0)" class="delete ml-3"><i class="icon-remove"></i> '.lang('delete').'</a>'."\r\n";
		// hook model_post_file_list_html_delete_after.php
		$s .= '</li>'."\r\n";
	};
	$s .= '</ul>'."\r\n";
	$s .= '</fieldset>'."\r\n";
	
	// hook model_post_file_list_html_end.php
	
	return $s;
}

function post_format(&$post) {
	global $conf, $uid, $sid, $gid, $longip;
	if(empty($post)) return;
	$post['create_date_fmt'] = humandate($post['create_date']);
	
	$user = user_read_cache($post['uid']);
	
	// hook model_post_format_start.php
	
	$post['username'] = array_value($user, 'username');
	$post['user_avatar_url'] = array_value($user, 'avatar_url');
	$post['user'] = $user ? $user : user_guest();
	!isset($post['floor']) AND  $post['floor'] = '';
	
	$thread = thread_read_cache($post['tid']);
	
	// 权限判断
	$post['allowupdate'] = ($uid == $post['uid']) || forum_access_mod($thread['fid'], $gid, 'allowupdate');
	$post['allowdelete'] = ($uid == $post['uid']) || forum_access_mod($thread['fid'], $gid, 'allowdelete');
	
	$post['user_url'] = url("user-$post[uid]".($post['uid'] ? '' : "-$post[pid]"));
	
	if($post['files'] > 0) {
		list($attachlist, $imagelist, $filelist) = attach_find_by_pid($post['pid']);
		$post['filelist'] = $filelist;
	} else {
		$post['filelist'] = array();
	}

	$post['classname'] = 'post';
	
	// hook model_post_format_end.php

}

// 写入时格式化
function post_message_fmt(&$arr, $gid) {
	
	// hook post_message_fmt_start.php

	// 超长内容截取
	$arr['message'] = xn_substr($arr['message'], 0, 2028000);
	
	// 格式转换: 类型，0: html, 1: txt; 2: markdown; 3: ubb
	$arr['message_fmt'] = htmlspecialchars($arr['message']);
	
	// 入库的时候进行转换，编辑的时候，自行调取 message, 或者 message_fmt
	$arr['doctype'] == 0 && $arr['message_fmt'] = ($gid == 1 ? $arr['message'] : xn_html_safe($arr['message']));
	$arr['doctype'] == 1 && $arr['message_fmt'] = xn_txt_to_html($arr['message']);
	
	// hook post_message_fmt_end.php
	
	// 对引用进行处理
	!empty($arr['quotepid']) && $arr['quotepid'] > 0 && $arr['message_fmt'] = post_quote($arr['quotepid']).$arr['message_fmt'];
}

// 获取内容的简介 0: html, 1: txt; 2: markdown; 3: ubb
function post_brief($s, $len = 100) {
	// hook post_brief_start.php
	$s = strip_tags($s);
	$s = htmlspecialchars($s);
	$more = xn_strlen($s) > $len ? ' ... ' : '';
	$s = xn_substr($s, 0, $len).$more;
	// hook post_brief_end.php
	return $s;
}

// 对内容进行引用
function post_quote($quotepid) {
	$quotepost = post__read($quotepid);
	if(empty($quotepost)) return '';
	$uid = $quotepost['uid'];
	$s = $quotepost['message'];
	
	// hook post_quote_start.php
	
	$s = post_brief($s, 100);
	$userhref = url("user-$uid");
	$user = user_read_cache($uid);
	$r = '<blockquote class="blockquote">
		<a href="'.$userhref.'" class="text-small text-muted user">
			<img class="avatar-1" src="'.$user['avatar_url'].'">
			'.$user['username'].'
		</a>
		'.$s.'
		</blockquote>';
	// hook post_quote_end.php
	return $r;
}


// 对 $threadlist 权限过滤
function post_list_access_filter(&$postlist, $gid) {
	global $conf, $forumlist;
	if(empty($postlist)) return;
	
	// hook model_post_list_access_filter_start.php
	
	foreach($postlist as $pid=>$post) {
		$thread = thread__read($post['tid']);
		$fid = $thread['fid'];
		if(empty($forumlist[$fid]['accesson'])) continue;
		if($thread['top'] > 0) continue;
		if(!forum_access_user($fid, $gid, 'allowread')) {
			unset($postlist[$pid]);
		}
	}
	// hook model_post_list_access_filter_end.php
}

// hook model_post_end.php

?>