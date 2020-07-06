<?php

/*
	功能：本程序用于将 xn2 转换到 xn3
	步骤：
		假定您的域名为：http://bbs.domain.com/
		假定您的目录为：/data/wwwroot/bbs.domain.com/
		
		1. 备份好 2.1 的数据库
		2. 新建 3.0 的数据库
		3. 把 2.1 的移动到 /data/wwwroot/bbs.domain.com/old 目录，新上传 3.0 到 /data/wwwroot/bbs.domain.com/ 下
		4. 安装 3.0，访问 http://bbs.domain.com/install/
		5. 安装完毕以后，解压 xn2_to_xn3.zip ，将  xn2_to_xn3.php 放到 /data/wwwroot/bbs.domain.com/ 下
		6. 修改 xn2_to_xn3.php，注意红色部分
*/

// 需要在命令行下运行。

define('DEBUG', 1);
define('XIUNO_BBS_2_PATH', '/data/wwwroot/bbs.xiuno.com/');
define('XIUNO_BBS_3_PATH', './');
define('BBS_PATH', XIUNO_BBS_2_PATH);
$tablepre = 'bbs_';

define('APP_NAME', 'convert');

!is_dir(XIUNO_BBS_2_PATH) AND exit('请修改源代码，设置 XiunoBBS 2.1 的目录。');
!is_dir(XIUNO_BBS_3_PATH) AND exit('请修改源代码，设置 XiunoBBS 3.0 的目录。');

$conf = include XIUNO_BBS_3_PATH.'conf/conf.php';
$oldconf = include XIUNO_BBS_2_PATH.'conf/conf.php';

include './xiunophp/xiunophp.php';
!empty($_SERVER['REMOTE_ADDR']) AND exit('本升级程序需要在命令行下执行，不支持 WEB 访问。');

$dbconf = $conf['db'][$conf['db']['type']]['master'];
$olddbconf = $oldconf['db'][$oldconf['db']['type']]['master'];
if($dbconf['host'] == $olddbconf['host'] && $dbconf['name'] == $olddbconf['name']) {
	exit('不能再同一个数据库里升级，否则数据会被清空！请将新论坛安装到其他数据库。');
}

empty($oldconf) AND exit('配置文件读取失败');
$olddb = db_new($oldconf['db']);

$db->connect() OR exit('连接数据库服务器失败：'.$db->errstr);
$olddb->connect() OR exit('连接数据库服务器失败：'.$olddb->errstr);

echo "upgrade group:\r\n";
$grouplist = $olddb->find("SELECT * FROM {$tablepre}group");
$db->exec("TRUNCATE `bbs_group`");
foreach ($grouplist as $group) {
	$group['groupid'] > 10 && $group['groupid'] += 90;
	$arr = array(
		'gid'=>$group['groupid'],
		'name'=>$group['name'],
		'agreesfrom'=>$group['creditsfrom'],
		'agreesto'=>$group['creditsto'],
		'maxagrees'=>$group['maxcredits'],
		'allowread'=>$group['allowread'],
		'allowthread'=>$group['allowthread'],
		'allowpost'=>$group['allowpost'],
		'allowattach'=>$group['allowattach'],
		'allowdown'=>$group['allowdown'],
		'allowtop'=>$group['allowtop'],
		'allowupdate'=>$group['allowupdate'],
		'allowdelete'=>$group['allowdelete'],
		'allowmove'=>$group['allowmove'],
		'allowbanuser'=>$group['allowbanuser'],
		'allowdeleteuser'=>$group['allowdeleteuser'],
		'allowviewip'=>$group['allowviewip'],
		
	);
	$sqladd = array_to_sqladd($arr);
	$r = $db->exec("INSERT INTO `bbs_group` SET $sqladd");
	echo ".";
}
echo "[ok]\r\n";
unset($grouplist);


echo "upgrade user:\r\n";
$userlist = $olddb->find("SELECT * FROM {$tablepre}user");
$db->exec("TRUNCATE `bbs_user`");
foreach ($userlist as $user) {
	$user['groupid'] > 10 && $user['groupid'] += 90;
	$arr = array(
		'uid'=>$user['uid'],
		'gid'=>$user['groupid'],
		'email'=>$user['email'],
		'username'=>$user['username'],
		'password'=>$user['password'],
		'salt'=>$user['salt'],
		'threads'=>$user['threads'],
		'posts'=>$user['posts'],
		'myagrees'=>0,
		'agrees'=>0,
		'last_agree_date'=>0,
		'today_agrees'=>0,
		'credits'=>$user['credits'],
		'create_ip'=>$user['regip'],
		'create_date'=>$user['regdate'],
		'login_ip'=>0,
		'login_date'=>0,
		'logins'=>0,
		'avatar'=>$user['avatar'],
	);
	$sqladd = array_to_sqladd($arr);
	$r = $db->exec("INSERT INTO bbs_user SET $sqladd");
	
	// 拷贝用户头像
	$uid = $user['uid'];
	$olddir = get_old_dir($uid);
	$newdir = set_new_dir($uid, XIUNO_BBS_3_PATH.'upload/avatar/');
	$oldpath = XIUNO_BBS_2_PATH.'upload/avatar/'.$olddir.$uid.'_big.gif';
	$newpath = XIUNO_BBS_3_PATH.'upload/avatar/'.$newdir.$uid.'.png';
	is_file($oldpath) AND !is_file($newpath) AND copy($oldpath, $newpath);
	echo ".";
}
echo "[ok]\r\n";
unset($userlist);

echo "upgrade qq login:\r\n";
$userlist = $olddb->find("SELECT * FROM {$tablepre}user_qqlogin");
if($userlist) {
	$db->exec("TRUNCATE `bbs_user_open_plat`");
	$db->exec("CREATE TABLE IF NOT EXISTS bbs_user_open_plat (
		  uid int(11) unsigned NOT NULL DEFAULT '0',
		  platid tinyint(1) NOT NULL DEFAULT '0' COMMENT '平台编号', # 0:本站 1:QQ 登录 2:微信登陆 3:支付宝登录 
		  openid char(32) NOT NULL DEFAULT '',
		  PRIMARY KEY(uid),
		  UNIQUE KEY(openid)
	);");
	foreach ($userlist as $user) {
		$arr = array(
			'uid'=>$user['uid'],
			'platid'=>1,
			'openid'=>$user['openid'],
		);
		$sqladd = array_to_sqladd($arr);
		$r = $db->exec("INSERT INTO bbs_user_open_plat SET $sqladd");
		echo ".";
	}
	echo "[ok]\r\n";
	unset($userlist);
}

echo "upgrade forum:\r\n";
$forumlist = $olddb->find("SELECT * FROM {$tablepre}forum");
$db->exec("TRUNCATE `bbs_forum`");
foreach ($forumlist as $forum) {
	$arr = array(
		'fid'=>$forum['fid'],
		'name'=>$forum['name'],
		'rank'=>$forum['rank'],
		'threads'=>$forum['threads'],
		'todayposts'=>$forum['todayposts'],
		'todaythreads'=>$forum['todayposts'],
		'brief'=>$forum['brief'],
		'accesson'=>$forum['accesson'],
		'orderby'=>$forum['orderby'],
		'create_date'=>'',
		'icon'=>0,
		'moduids'=>str_replace("\t", ',', $forum['modids']),
		'seo_title'=>$forum['seo_title'],
		'seo_keywords'=>$forum['seo_keywords'],
	);
	$sqladd = array_to_sqladd($arr);
	$r = $db->exec("INSERT INTO bbs_forum SET $sqladd");
	
	// 拷贝文件
	// $oldpath = XIUNO_BBS_2_PATH.'upload/forum/'.$fid.'.png';
	// $newpath = XIUNO_BBS_3_PATH.'upload/forum/'.$fid.'.png';
	// is_file($oldpath) AND !is_file($newpath) && copy($oldpath, $newpath);
	echo ".";
}
echo "[ok]\r\n";
unset($forumlist);

echo "upgrade forum_access:\r\n";
$accesslist = $olddb->find("SELECT * FROM {$tablepre}forum_access");
$db->exec("TRUNCATE `bbs_forum_access`");
foreach ($accesslist as $access) {
	$access['groupid'] > 10 && $access['groupid'] += 90;
	$arr = array(
		'fid'=>$access['fid'],
		'gid'=>$access['groupid'],
		'allowread'=>$access['allowread'],
		'allowagree'=>$access['allowpost'],
		'allowthread'=>$access['allowthread'],
		'allowpost'=>$access['allowpost'],
		'allowattach'=>$access['allowattach'],
		'allowdown'=>$access['allowdown']
	);
	$sqladd = array_to_sqladd($arr);
	$r = $db->exec("INSERT INTO bbs_forum_access SET $sqladd");
	echo ".";
}
echo "[ok]\r\n";
unset($accesslist);

echo "upgrade thread:\r\n";
$threadlist = $olddb->find("SELECT * FROM {$tablepre}thread");
$db->exec("TRUNCATE `bbs_thread`");
$db->exec("TRUNCATE `bbs_thread_top`");
foreach ($threadlist as $thread) {
	$arr = array(
		'fid'=>$thread['fid'],
		'tid'=>$thread['tid'],
		'top'=>$thread['top'],
		'uid'=>$thread['uid'],
		'subject'=>$thread['subject'],
		'create_date'=>$thread['dateline'],
		'last_date'=>$thread['lastpost'] ? $thread['lastpost'] : $thread['dateline'],
		'views'=>$thread['views'],
		'posts'=>max(0, $thread['posts'] - 1),
		'agrees'=>$thread['posts'],
		'images'=>$thread['imagenum'],
		'files'=>$thread['attachnum'],
		'mods'=>$thread['modnum'],	# 预留
		'closed'=>$thread['closed'],	# 预留
		'firstpid'=>$thread['firstpid'],
		'lastuid'=>$thread['lastuid'],
		'lastpid'=>0,	# 此处应该求最后一个，暂时没用
	);
	$sqladd = array_to_sqladd($arr);
	$r = $db->exec("INSERT INTO bbs_thread SET $sqladd");
	
	if($thread['top'] > 0) {
		$db->exec("INSERT INTO bbs_thread_top SET fid='$thread[fid]', tid='$thread[tid]', top='$thread[top]'");
	}
	
	$db->exec("INSERT INTO bbs_mythread SET uid='$thread[uid]',tid='$thread[tid]'");
	
	echo ".";
}
echo "[ok]\r\n";
unset($threadlist);

echo "upgrade post:\r\n";
$postlist = $olddb->find("SELECT * FROM {$tablepre}post");
$db->exec("TRUNCATE `bbs_post`");
$i = 0;
foreach ($postlist as $post) {
	$thread = $olddb->find_one("SELECT firstpid FROM {$tablepre}thread WHERE tid='$post[tid]'");
	$arr = array(
		'tid'=>$post['tid'],
		'pid'=>$post['pid'],
		'uid'=>$post['uid'],
		'isfirst'=>($thread['firstpid'] == $post['pid'] ? 1 : 0),
		'create_date'=>$post['dateline'],
		'userip'=>$post['userip'],
		'sid'=>'',
		'images'=>$post['imagenum'],
		'files'=>$post['attachnum'],
		'agrees'=>0,
		'message'=>$post['message'],
	);
	$sqladd = array_to_sqladd($arr);
	$r = $db->exec("INSERT INTO bbs_post SET $sqladd");
	if($i++ > 10) { echo  "."; $i = 0; }
}
echo "[ok]\r\n";
unset($postlist);

echo "upgrade attach:\r\n";
$attachlist = $olddb->find("SELECT * FROM {$tablepre}attach");
$db->exec("TRUNCATE `bbs_attach`");
foreach ($attachlist as $attach) {
	$arr = array(
		'aid'=>$attach['aid'],
		'tid'=>$attach['tid'],
		'pid'=>$attach['pid'],
		'uid'=>$attach['uid'],
		'filesize'=>$attach['filesize'],
		'width'=>$attach['width'],
		'height'=>$attach['height'],
		'filename'=>$attach['filename'],
		'orgfilename'=>$attach['orgfilename'],
		'filetype'=>$attach['filetype'],
		'create_date'=>$attach['dateline'],
		'comment'=>$attach['comment'],
		'downloads'=>$attach['downloads'],
		'credits'=>0,
		'golds'=>0,
		'rmbs'=>0,
	);
	$sqladd = array_to_sqladd($arr);
	$r = $db->exec("INSERT INTO bbs_attach SET $sqladd");
	
	$oldpath = XIUNO_BBS_2_PATH.'upload/'.$attach['filename'];
	$newpath = XIUNO_BBS_3_PATH.'upload/'.$attach['filename'];
	
	$newdir = substr($newpath, 0, strrpos($newpath, '/'));
	!is_dir($newdir) AND mkdir($newdir, 0777, TRUE);
	
	is_file($oldpath) AND !is_file($newpath) AND copy($oldpath, $newpath);
	
	// 直接拷贝附件目录
	/*
	$aid = $attach['aid'];
	$olddir = get_old_dir($aid);
	$newdir = set_new_dir($aid, XIUNO_BBS_3_PATH.'upload/attach/');
	$oldurl = 'upload/attach/'.$attach['filename'];
	$newurl = 'upload/attach/'.$newdir.substr($attach['filename'], strrpos($attach['filename'], '/') + 1);
	$oldpath = XIUNO_BBS_2_PATH.$oldurl;
	$newpath = XIUNO_BBS_3_PATH.$newurl;
	
	is_file($oldpath) AND !is_file($newpath) && copy($oldpath, $newpath);
	if(is_file($oldpath)) {
		$post = $olddb->find_one("SELECT * FROM {$tablepre}post WHERE fid='$attach[fid]' AND pid='$attach[pid]'");
		$post['message'] = str_replace($oldurl, $newurl, $post['message']);
		$post['message'] = addslashes($post['message']);
		$db->exec("UPDATE bbs_post SET message='$post[message]'");
	}
	*/
	
	echo ".";
}
echo "[ok]\r\n";
unset($attachlist);

echo "upgrade modlog:\r\n";
$modloglist = $olddb->find("SELECT * FROM {$tablepre}modlog");
foreach ($modloglist as $modlog) {
	$arr = array(
		'logid'=>$modlog['logid'],
		'uid'=>$modlog['uid'],
		'tid'=>$modlog['tid'],
		'pid'=>$modlog['pid'],
		'subject'=>$modlog['subject'],
		'comment'=>$modlog['comment'],
		'create_date'=>$modlog['dateline'],
		'action'=>$modlog['action'],
	);
	$sqladd = array_to_sqladd($arr);
	$r = $db->exec("INSERT INTO bbs_modlog SET $sqladd");
	echo ".";
}
echo "[ok]\r\n";
unset($modloglist);

echo "upgrade friendlink:\r\n";
$linklist = $olddb->find("SELECT * FROM {$tablepre}friendlink");
if($linklist) {
	foreach ($linklist as $link) {
		$arr = array(
			'linkid'=>$link['linkid'],
			'type'=>$link['type'],
			'rank'=>$link['rank'],
			'create_date'=>0,
			'name'=>$link['sitename'],
			'url'=>$link['url'],
		);
		$sqladd = array_to_sqladd($arr);
		$r = $db->exec("INSERT INTO bbs_friendlink SET $sqladd");
		echo ".";
	}
}
echo "[ok]\r\n";
unset($linklist);


echo '<a href="../">升级完成，点击进入论坛。</a>';


function set_new_dir($id, $dirpre) {
	$filename = "$id.png";
	$dir = substr(sprintf("%09d", $id), 0, 3);
	!is_dir($dirpre.$dir) AND mkdir($dirpre.$dir, 0777);
	return $dir.'/';
}

function get_old_dir($id) {
	$id = sprintf("%09d", $id);
	$s1 = substr($id, 0, 3);
	$s2 = substr($id, 3, 3);
	return "$s1/$s2/";
}
?>