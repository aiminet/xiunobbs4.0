<?php

/*
	Xiuno BBS 4.0 本地化图片程序
	
	将文件存放于 tool/save_remote_image.php
	cd tool/
	php save_remote_image.php
	
	该脚本可以一直执行，可以放到后台运行：
	nohup php save_remote_image.php&
*/


// 配置不进行抓取的 URL
$localurlarr = array(
	'http://bbs.xiuno.com/',
	'http://plugin.xiuno.com/',
);

ob_implicit_flush(0);
set_time_limit(0);
define('SKIP_ROUTE', 1);
include '../index.php';

$lastpid = intval(kv_get('save_last_pid'));

xn_mkdir($conf['upload_path']."remote/");

//$types = array(1=>'gif', 2=>'jpg', 3=>'png', 4=>'swf', 5=>'psd', 6=>'bmp');

while(1) {
	
	// 创建目录
	$day = date('Ymd', time());
	$attach_dir = $conf['upload_path']."attach/$day/";
	$attach_url = $conf['upload_url']."attach/$day/";
	xn_mkdir($attach_dir);

	// 获取帖子
	$postlist = db_find('post', array('pid'=>array('>'=>$lastpid)), array('pid'=>1), 1, 10);
	foreach($postlist as $post) {
		$pid = $post['pid'];
		$tid = $post['tid'];
		$uid = $post['uid'];
		$s = $post['message_fmt'];
		preg_match_all('#<img[^>]+src="(http://.*?)"#i', $s, $m);
		if(!empty($m[1])) {
			$n = 0;
			foreach($m[1] as $url) {
				foreach($localurlarr as $localurl) {
					if($localurl == substr($url, 0, strlen($localurl))) continue 2;
				}
				$ext = file_ext($url);
				if(!in_array($ext, array('gif', 'jpg', 'png', 'bmp'))) continue;
				$filename = xn_rand(16).'.'.$ext;
				$destpath = $attach_dir.$filename;
				$desturl = $attach_url.$filename;
				$s2 = str_replace($url, $desturl, $s);
				if($s != $s2) {
					$imgdata = file_get_contents($url);
					$filesize = strlen($imgdata);
					if($filesize < 10) continue;
					file_put_contents_try($destpath, $imgdata);
					list($width, $height) = getimagesize($destpath);
					$attach = array(
						'tid'=>$tid,
						'pid'=>$pid,
						'uid'=>$uid,
						'filesize'=>$filesize,
						'width'=>$width,
						'height'=>$height,
						'filename'=>"$day/$filename",
						'orgfilename'=>$filename,
						'filetype'=>'image',
						'create_date'=>$time,
						'comment'=>'',
						'downloads'=>0,
						'isimage'=>1
					);
					$s = $s2;
					$aid = attach_create($attach);
					$n++;
				}
			}
			post__update($pid, array('message_fmt'=>$s, 'images'=>($post['images'] > 0 ? $post['images'] : $n)));
		}
		$lastpid = $pid;
		kv_set('save_last_pid', $lastpid);
		echo '.';
		flush();
	}
	if(IN_CMD) {
		function_exists('sleep') AND sleep(1);
	} else {
		if(empty($postlist)) {
			echo '<h1>本地化完毕</h1>';
		}
		echo '<script>window.location.reload();</script>';
		flush();
	}
}

?>