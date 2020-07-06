<?php

// hook model_attach_start.php

// ------------> 最原生的 CURD，无关联其他数据。

function attach__create($arr) {
	// hook model_attach__create_start.php
	$r = db_create('attach', $arr);
	// hook model_attach__create_end.php
	return $r;
}

function attach__update($aid, $arr) {
	// hook model_attach__update_start.php
	$r = db_update('attach', array('aid'=>$aid), $arr);
	// hook model_attach__update_end.php
	return $r;
}

function attach__read($aid) {
	// hook model_attach__read_start.php
	$attach = db_find_one('attach', array('aid'=>$aid));
	// hook model_attach__read_end.php
	return $attach;
}

function attach__delete($aid) {
	// hook model_attach__delete_start.php
	$r = db_delete('attach', array('aid'=>$aid));
	// hook model_attach__delete_end.php
	return $r;
}

function attach__find($cond = array(), $orderby = array(), $page = 1, $pagesize = 20) {
	// hook model_attach__find_start.php
	$attachlist = db_find('attach', $cond, $orderby, $page, $pagesize);
	// hook model_attach__find_end.php
	return $attachlist;
}

// ------------> 关联 CURD，主要是强相关的数据，比如缓存。弱相关的大量数据需要另外处理。

function attach_create($arr) {
	// hook model_attach_create_start.php
	$r = attach__create($arr);
	// hook model_attach_create_end.php
	return $r;
}

function attach_update($aid, $arr) {
	// hook model_attach_update_start.php
	$r = attach__update($aid, $arr);
	// hook model_attach_update_end.php
	return $r;
}

function attach_read($aid) {
	// hook model_attach_read_start.php
	$attach = attach__read($aid);
	attach_format($attach);
	// hook model_attach_read_end.php
	return $attach;
}

function attach_delete($aid) {
	// hook model_attach_delete_start.php
	global $conf;
	$attach = attach_read($aid);
	$path = $conf['upload_path'].'attach/'.$attach['filename'];
	file_exists($path) AND unlink($path);
	
	$r = attach__delete($aid);
	// hook model_attach_delete_end.php
	return $r;
}

function attach_delete_by_pid($pid) {
	global $conf;
	list($attachlist, $imagelist, $filelist) = attach_find_by_pid($pid);
	// hook model_attach_delete_by_pid_start.php
	foreach($attachlist as $attach) {
		$path = $conf['upload_path'].'attach/'.$attach['filename'];
		file_exists($path) AND unlink($path);
		attach__delete($attach['aid']);
	}
	// hook model_attach_delete_by_pid_end.php
	return count($attachlist);
}

function attach_delete_by_uid($uid) {
	global $conf;
	// hook model_attach_delete_by_uid_start.php
	$attachlist = db_find('attach', array('uid'=>$uid), array(), 1, 9000);
	foreach ($attachlist as $attach) {
		$path = $conf['upload_path'].'attach/'.$attach['filename'];
		file_exists($path) AND unlink($path);
		attach__delete($attach['aid']);
	}
	// hook model_attach_delete_by_uid_end.php
}

function attach_find($cond = array(), $orderby = array(), $page = 1, $pagesize = 20) {
	// hook model_attach_find_start.php
	$attachlist = attach__find($cond, $orderby, $page, $pagesize);
	if($attachlist) foreach ($attachlist as &$attach) attach_format($attach);
	// hook model_attach_find_end.php
	return $attachlist;
}

// 获取 $filelist $imagelist
function attach_find_by_pid($pid) {
	$attachlist = $imagelist = $filelist = array();
	// hook model_attach_find_by_pid_start.php
	$attachlist = attach__find(array('pid'=>$pid), array(), 1, 1000);
	if($attachlist) {
		foreach ($attachlist as $attach) {
			attach_format($attach);
			$attach['isimage'] ? ($imagelist[] = $attach) : ($filelist[] = $attach);
		}
	}
	// hook model_attach_find_by_pid_end.php
	return array($attachlist, $imagelist, $filelist);
}

// ------------> 其他方法

function attach_format(&$attach) {
	global $conf;
	if(empty($attach)) return;
	// hook model_attach_format_start.php
	$attach['create_date_fmt'] = date('Y-n-j', $attach['create_date']);
	$attach['url'] = $conf['upload_url'].'attach/'.$attach['filename'];
	// hook model_attach_format_end.php
}

function attach_count($cond = array()) {
	// hook model_attach_count_start.php
	$cond = db_cond_to_sqladd($cond);
	$n = db_count('attach', $cond);
	// hook model_attach_count_end.php
	return $n;
}

function attach_type($name, $types) {
	// hook model_attach_type_start.php
	$ext = file_ext($name);
	foreach($types as $type=>$exts) {
		if($type == 'all') continue;
		if(in_array($ext, $exts)) {
			return $type;
		}
	}
	// hook model_attach_type_end.php
	return 'other';
}

// 扫描垃圾的附件，每日清理一次
function attach_gc() {
	global $time, $conf;
	// hook model_attach_gc_start.php
	$tmpfiles = glob($conf['upload_path'].'tmp/*.*');
	if(is_array($tmpfiles)) {
		foreach($tmpfiles as $file) {
			// 清理超过一天还没处理的临时文件
			if($time - filemtime($file) > 86400) {
				unlink($file);
			}
		}
	}
	// hook model_attach_gc_end.php
}

// 关联 session 中的临时文件，并不会重新统计 images, files
function attach_assoc_post($pid) {
	global $uid, $time, $conf;
	$sess_tmp_files = _SESSION('tmp_files');
	//if(empty($tmp_files)) return;

    // fixed by qiukong, https://bbs.xiuno.com/thread-150336.htm
    if(!$sess_tmp_files && preg_match('/tmp\+files\|(a\:1\:\{.*\})/',_SESSION('data'),$arr)) {
        $sess_tmp_files = unserialize(str_replace(array('+','='),array('_','.'),$arr['1']));
    }
	
	$post = post__read($pid);
	if(empty($post)) return;
	
	// hook attach_assoc_post_start.php
	
	$tid = $post['tid'];
	$post['message_old'] = $post['message_fmt'];
	
	// 把临时文件 upload/tmp/xxx.xxx 也处理了
	//preg_match_all('#src="upload/tmp/(\w+\.\w+)"#', $post['message_old'], $m);
	//$use_tmp_files = $m[1]; // 实际使用的临时文件，不用的全部删除？如果是两个帖子一起编辑？
	
	// 将 session 中的数据和 message 中的数据合并。
	//$tmp_files = array_unique(array_merge($sess_tmp_files, $use_tmp_files));
	
	$attach_dir_save_rule = array_value($conf, 'attach_dir_save_rule', 'Ym');
	
	$tmp_files = $sess_tmp_files;
	if($tmp_files) {
		foreach($tmp_files as $file) {
			
			// 将文件移动到 upload/attach 目录
			$filename = file_name($file['url']);
			
			$day = date($attach_dir_save_rule, $time);
			$path = $conf['upload_path'].'attach/'.$day;
			$url = $conf['upload_url'].'attach/'.$day;
			!is_dir($path) AND mkdir($path, 0777, TRUE);
			
			$destfile = $path.'/'.$filename;
			$desturl = $url.'/'.$filename;
			$r = xn_copy($file['path'], $destfile);
			!$r AND xn_log("xn_copy($file[path]), $destfile) failed, pid:$pid, tid:$tid", 'php_error');
			if(is_file($destfile) && filesize($destfile) == filesize($file['path'])) {
				@unlink($file['path']);
			}
			$arr = array(
				'tid'=>$tid,
				'pid'=>$pid,
				'uid'=>$uid,
				'filesize'=>$file['filesize'],
				'width'=>$file['width'],
				'height'=>$file['height'],
				'filename'=>"$day/$filename",
				'orgfilename'=>$file['orgfilename'],
				'filetype'=>$file['filetype'],
				'create_date'=>$time,
				'comment'=>'',
				'downloads'=>0,
				'isimage'=>$file['isimage']
			);
			
			// 插入后，进行关联
			$aid = attach_create($arr);
			$post['message'] = str_replace($file['url'], $desturl, $post['message']);
			$post['message_fmt'] = str_replace($file['url'], $desturl, $post['message_fmt']);
			
		}
	}

	// 清空 session
	$_SESSION['tmp_files'] = array();
	
	$post['message_old'] != $post['message_fmt'] AND post__update($pid, array('message'=>$post['message'], 'message_fmt'=>$post['message_fmt']));
	
	// 处理不在 message 中的图片，删除掉没有插入的图片附件
	/*
	list($attachlist, $imagelist, $filelist) = attach_find_by_pid($pid);
	foreach($imagelist as $k=>$attach) {
		$url = $conf['upload_url'].'attach/'.$attach['filename'];
		if(strpos($post['message_fmt'], $url) === FALSE) {
			unset($imagelist[$k]);
			attach_delete($attach['aid']);
		}
	}
	*/
	
	// 更新 images files
	list($attachlist, $imagelist, $filelist) = attach_find_by_pid($pid);
	$images = count($imagelist);
	$files = count($filelist);
	$post['isfirst'] AND thread__update($tid, array('images'=>$images, 'files'=>$files));
	post__update($pid, array('images'=>$images, 'files'=>$files));
	
	// hook attach_assoc_post_end.php
	
	return TRUE;
}


// hook model_attach_end.php

?>