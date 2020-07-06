<?php

/*
	功能：本程序用于将 xn21 转换到 xn4
	步骤：
		假定您的域名为：http://bbs.domain.com/
		假定您的目录为：/data/wwwroot/bbs.domain.com/
		
		1. 备份好 2.1 的数据库
		2. 新建 4.0 的数据库 xiuno4
		3. 把 2.1 的移动到 /data/wwwroot/bbs.domain.com/old 目录，新上传 4.0 到 /data/wwwroot/bbs.domain.com/ 下
		4. 安装 4.0，访问 http://bbs.domain.com/install/
		5. 安装完毕以后，将  xn21_to_xn4.php 上传到 /data/wwwroot/bbs.domain.com/ 下
		6. 命令行执行:
			cd /data/wwwroot/bbs.domain.com/
			php xn21_to_xn4.php
*/

// 需要在命令行下运行。

define('XN2_PATH', '../ceshi.1lou.com/');

define('DEBUG', 1);

$tablepre = 'bbs_';

if(!$oldconf = include XN2_PATH.'conf/conf.php') {
	exit('请将原来的整站移动到 ./old 目录');
}

if(!$conf = include './conf/conf.php') {
	exit('请先安装完 Xiuno BBS 4.0。');
}

include './xiunophp/xiunophp.php';

$oldconf['db']['type'] = 'mysql';
$oldconf['db']['mysql']['master']['tablepre'] = 'bbs_';
$oldconf['db']['mysql']['user'] = 'ceshi2';
$oldconf['db']['mysql']['password'] = 'ceshi2';
$oldconf['db']['mysql']['name'] = 'ceshi2';

$db = db_new($conf['db']);
$olddb = db_new($oldconf['db']);

!db_connect($db) AND exit('连接 4.0 数据库失败:'.$db->errstr);
!db_connect($olddb) AND exit('连接 3.0 数据库失败:'.$olddb->errstr);

if($conf['db']['pdo_mysql']['master']['host'] == $oldconf['db']['pdo_mysql']['master']['host'] && $conf['db']['pdo_mysql']['master']['name'] == $oldconf['db']['pdo_mysql']['master']['name']) {
	exit('不能在同一个数据库里升级，否则数据会被清空！请将新论坛安装到其他数据库。');
}

/*
echo "upgrade group:\r\n";
$grouplist = $olddb->sql_find("SELECT * FROM {$tablepre}group");
$db->exec("TRUNCATE `{$tablepre}group`");
foreach ($grouplist as $group) {
	$group['groupid'] > 10 && $group['groupid'] += 90;
	$arr = array(
		'gid'=>$group['groupid'],
		'name'=>$group['name'],
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
	$sqladd = db_array_to_insert_sqladd($arr);
	$r = $db->exec("INSERT INTO `{$tablepre}group` $sqladd");
	if($r === FALSE) echo($db->errstr);
	echo ".";
}
echo "[ok]\r\n";
unset($grouplist);
*/

echo "upgrade user:\r\n";
$userlist = $olddb->sql_find("SELECT uid FROM {$tablepre}user");
$db->exec("TRUNCATE {$tablepre}user");
foreach ($userlist as $user) {
	$user = $olddb->sql_find_one("SELECT * FROM {$tablepre}user WHERE uid='$user[uid]'");
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
		'credits'=>$user['credits'],
		'create_ip'=>$user['regip'],
		'create_date'=>$user['regdate'],
		'avatar'=>$user['avatar'],
	);
	$sqladd = db_array_to_insert_sqladd($arr);
	$r = $db->exec("INSERT INTO bbs_user $sqladd");
	if($r === FALSE) echo($db->errstr);
	
	// 拷贝头像
	if($user['avatar']) {
		$uid = $user['uid'];
		$dir = xn_set_dir($uid,  './upload/avatar/');
		
		$oldpath = XN2_PATH.'upload/avatar/'.$dir.'/'.$uid."_huge.gif";
		$newpath = './upload/avatar/'.$dir.'/'.$uid.".png";
		
		!is_file($newpath) && copy($oldpath, $newpath);
	}
	echo $user['uid'] % 100 == 0 ? "." : '';
}
echo "[ok]\r\n";
unset($userlist);

exit;

/*
echo "upgrade qq login:\r\n";

$tablepre = $db->tablepre;
$sql = "CREATE TABLE IF NOT EXISTS {$tablepre}user_open_plat (
	uid int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '用户编号',
	platid tinyint(1) NOT NULL DEFAULT '0' COMMENT '平台编号 0:本站 1:QQ 登录 2:微信登陆 3:支付宝登录 ',
	openid char(40) NOT NULL DEFAULT '' COMMENT '第三方唯一标识',
	PRIMARY KEY (uid),
	KEY openid_platid (platid,openid)
) ENGINE=MyISAM AUTO_INCREMENT=8805 DEFAULT CHARSET=utf8
";
$r = db_exec($sql);
$r === FALSE AND message(-1, '创建表结构失败'); // 中断，安装失败。

$userlist = $olddb->sql_find("SELECT * FROM {$tablepre}user_qqlogin");
if($userlist) {
	$db->exec("TRUNCATE `bbs_user_open_plat`");
	foreach ($userlist as $user) {
		$arr = array(
			'uid'=>$user['uid'],
			'platid'=>1,
			'openid'=>$user['openid'],
		);
		$sqladd = db_array_to_insert_sqladd($arr);
		$r = $db->exec("INSERT INTO {$tablepre}user_open_plat $sqladd");
		echo ".";
	}
	echo "[ok]\r\n";
	unset($userlist);
}

echo "upgrade forum:\r\n";
$forumlist = $olddb->sql_find("SELECT * FROM {$tablepre}forum");
$db->exec("TRUNCATE `{$tablepre}forum`");
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
		'icon'=>0,
		'moduids'=>str_replace("\t", ',', $forum['modids']),
		'seo_title'=>$forum['seo_title'],
		'seo_keywords'=>$forum['seo_keywords'],
	);
	$sqladd = db_array_to_insert_sqladd($arr);
	$r = $db->exec("INSERT INTO {$tablepre}forum $sqladd");
	if($r === FALSE) echo($db->errstr);
	
	echo ".";
}
echo "[ok]\r\n";
unset($forumlist);

echo "upgrade forum_access:\r\n";
$accesslist = $olddb->sql_find("SELECT * FROM {$tablepre}forum_access");
$db->exec("TRUNCATE `{$tablepre}forum_access`");
foreach ($accesslist as $access) {
	$access['groupid'] > 10 && $access['groupid'] += 90;
	$arr = array(
		'fid'=>$access['fid'],
		'gid'=>$access['groupid'],
		'allowread'=>$access['allowread'],
		'allowthread'=>$access['allowthread'],
		'allowpost'=>$access['allowpost'],
		'allowattach'=>$access['allowattach'],
		'allowdown'=>$access['allowdown']
	);
	$sqladd = db_array_to_insert_sqladd($arr);
	$r = $db->exec("INSERT INTO bbs_forum_access $sqladd");
	if($r === FALSE) echo($db->errstr);
	echo ".";
}
echo "[ok]\r\n";
unset($accesslist);

$i = 0;

echo "upgrade thread:\r\n";
$threadlist = $olddb->sql_find("SELECT tid FROM {$tablepre}thread");
$db->exec("TRUNCATE `{$tablepre}thread`");
$db->exec("TRUNCATE `{$tablepre}thread_top`");
foreach ($threadlist as $thread) {
	$thread = $olddb->sql_find_one("SELECT * FROM {$tablepre}thread WHERE tid='$thread[tid]'");
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
		'images'=>$thread['imagenum'],
		'files'=>$thread['attachnum'],
		'mods'=>$thread['modnum'],	# 预留
		'closed'=>$thread['closed'],	# 预留
		'firstpid'=>$thread['firstpid'],
		'lastuid'=>$thread['lastuid'],
		'lastpid'=>0,	# 此处应该求最后一个，暂时没用
	);
	$sqladd = db_array_to_insert_sqladd($arr);
	$r = $db->exec("INSERT INTO {$tablepre}thread $sqladd");
	if($r === FALSE) echo($db->errstr);
	
	if($thread['top'] > 0) {
		$db->exec("INSERT INTO {$tablepre}thread_top SET fid='$thread[fid]', tid='$thread[tid]', top='$thread[top]'");
	}
	
	$db->exec("INSERT INTO {$tablepre}mythread SET uid='$thread[uid]',tid='$thread[tid]'");
	
	if($i++ > 10) { echo  "."; $i = 0; }
}
echo "[ok]\r\n";
unset($threadlist);

*/
/*
echo "upgrade post:\r\n";
$postlist = $olddb->sql_find("SELECT pid FROM {$tablepre}post");
$db->exec("TRUNCATE `{$tablepre}post`");
foreach ($postlist as $post) {
	$post = $olddb->sql_find_one("SELECT * FROM {$tablepre}post WHERE pid='$post[pid]'");
	$thread = $olddb->sql_find_one("SELECT * FROM {$tablepre}thread WHERE tid='$post[tid]'");
	if(strlen($post['message']) > 1024000) continue;
	$arr = array(
		'tid'=>$post['tid'],
		'pid'=>$post['pid'],
		'uid'=>$post['uid'],
		'isfirst'=>($thread['firstpid'] == $post['pid'] ? 1 : 0),
		'create_date'=>$post['dateline'],
		'userip'=>$post['userip'],
		'images'=>$post['imagenum'],
		'files'=>$post['attachnum'],
		'message'=>$post['message'],
		'message_fmt'=>$post['message'],
	);
	$sqladd = db_array_to_insert_sqladd($arr);
	$r = $db->exec("INSERT INTO {$tablepre}post $sqladd");
	if($r === FALSE) echo($db->errstr);
	if($i++ > 10) { echo  " . ".memory_get_usage().' '; $i = 0; }
}
echo "[ok]\r\n";
unset($postlist);

echo "upgrade attach:\r\n";
$attachlist = $olddb->sql_find("SELECT aid FROM {$tablepre}attach");
$db->exec("TRUNCATE `{$tablepre}attach`");
foreach ($attachlist as $attach) {
	$attach = $olddb->sql_find_one("SELECT * FROM {$tablepre}attach WHERE aid='$attach[aid]'");
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
	$sqladd = db_array_to_insert_sqladd($arr);
	$r = $db->exec("INSERT INTO {$tablepre}attach $sqladd");
	if($r === FALSE) echo($db->errstr);
	if($i++ > 10) { echo  "."; $i = 0; }
}
echo "[ok]\r\n";
unset($attachlist);

echo "upgrade modlog:\r\n";
$modloglist = $olddb->sql_find("SELECT * FROM {$tablepre}modlog");
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
	$sqladd = db_array_to_insert_sqladd($arr);
	$r = $db->exec("INSERT INTO {$tablepre}modlog $sqladd");
	if($r === FALSE) echo($db->errstr);
	echo ".";
}
echo "[ok]\r\n";
unset($modloglist);


echo "upgrade friendlink:\r\n";

$tablepre = $db->tablepre;
$sql = "CREATE TABLE IF NOT EXISTS {$tablepre}friendlink (
  linkid bigint(11) unsigned NOT NULL AUTO_INCREMENT,
  type smallint(11) NOT NULL DEFAULT '0',
  rank smallint(11) NOT NULL DEFAULT '0',
  create_date int(11) unsigned NOT NULL DEFAULT '0',
  name char(32) NOT NULL DEFAULT '',
  url char(64) NOT NULL DEFAULT '',
  PRIMARY KEY (linkid),
  KEY type (type)
) ENGINE=MyISAM AUTO_INCREMENT=65 DEFAULT CHARSET=utf8
";
$r = db_exec($sql);
$r === FALSE AND message(-1, '创建友情链接表结构失败');

$linklist = $olddb->sql_find("SELECT * FROM {$tablepre}friendlink");
if($linklist) {
	foreach ($linklist as $link) {
		$arr = array(
			'linkid'=>$link['linkid'],
			'type'=>$link['type'],
			'rank'=>$link['rank'],
			'create_date'=>0,
			'name'=>$link['name'],
			'url'=>$link['url'],
		);
		$sqladd = db_array_to_insert_sqladd($arr);
		$r = $db->exec("INSERT INTO bbs_friendlink $sqladd");
		echo ".";
	}
}
echo "[ok]\r\n";
unset($linklist);
*/

// 主题分类

$thread_type_map = array (
	1 => array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40),
	2 => array(41, 82, 123, 164, 205, 246, 287, 328, 369, 410, 451, 492, 533, 574, 615, 656, 697, 738, 779, 820, 861, 902, 943, 984, 1025, 1066, 1107, 1148, 1189, 1230, 1271, 1312, 1353, 1394, 1435, 1476, 1517, 1558, 1599, 1640),
	3 => array(1681, 3362, 5043, 6724, 8405, 10086, 11767, 13448, 15129, 16810, 18491, 20172, 21853, 23534, 25215, 26896, 28577, 30258, 31939, 33620, 35301, 36982, 38663, 40344, 42025, 43706, 45387, 47068, 48749, 50430, 52111, 53792, 55473, 57154, 58835, 60516, 62197, 63878, 65559, 67240),
	4 => array(136161, 205082, 274003, 342924, 411845, 480766, 549687, 618608, 687529, 756450, 825371, 894292, 963213, 1032134, 1101055, 1169976, 1238897, 1307818, 1376739, 1445660, 1514581, 1583502, 1652423, 1721344, 1790265, 1859186, 1928107, 1997028, 2065949, 2134870, 2203791, 2272712, 2341633, 2410554, 2479475, 2548396, 2617317, 2686238, 2755159, 2824080),
);
$thread_type_map2 = array_merge($thread_type_map[1],$thread_type_map[2],$thread_type_map[3],$thread_type_map[4]);
$thread_type_map3 = array();
foreach($thread_type_map as $cateid=>$arr) {
	foreach ($arr as $v) {
		$thread_type_map3[$v] = $cateid;
	}
}

$tablepre = $db->tablepre;
db_exec("TRUNCATE {$tablepre}tag_cate");
db_exec("TRUNCATE {$tablepre}tag");
db_exec("TRUNCATE {$tablepre}tag_thread");
$sql = "CREATE TABLE IF NOT EXISTS {$tablepre}tag_cate (
	cateid int(11) unsigned NOT NULL AUTO_INCREMENT,
	fid int(11) unsigned NOT NULL DEFAULT '0',		# 属于哪个版块
	name char(32) NOT NULL DEFAULT '',
	rank int(11) unsigned NOT NULL DEFAULT '0',
	enable int(11) unsigned NOT NULL DEFAULT '0',
	PRIMARY KEY (cateid),
	KEY (fid)
) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$r = db_exec($sql);
$sql = "CREATE TABLE IF NOT EXISTS {$tablepre}tag (
	tagid int(11) unsigned NOT NULL AUTO_INCREMENT,
	cateid int(11) unsigned NOT NULL DEFAULT '0',
	name char(32) NOT NULL DEFAULT '',
	rank int(11) unsigned NOT NULL DEFAULT '0',
	enable int(11) unsigned NOT NULL DEFAULT '0',
	PRIMARY KEY (tagid),
	KEY (cateid)
) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$r = db_exec($sql);
$sql = "CREATE TABLE IF NOT EXISTS {$tablepre}tag_thread (
	tagid int(11) unsigned NOT NULL DEFAULT '0',
	tid int(11) unsigned NOT NULL DEFAULT '0',
	PRIMARY KEY (tagid, tid),
	KEY (tid)
) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$r = db_exec($sql);

$cateid_map = array();
$maxcateid = 0;
$tagid_map = array();
$maxtagid = 0;
$tagcatelist = $olddb->sql_find("SELECT * FROM {$tablepre}thread_type_cate");
if($tagcatelist) {
	foreach ($tagcatelist as $tagcate) {
		$cateid_map["$tagcate[fid]-$tagcate[cateid]"] = ++$maxcateid;
		$arr = array(
			'fid'=>$tagcate['fid'],
			'cateid'=>$maxcateid,
			'name'=>$tagcate['catename'],
			'rank'=>$tagcate['rank'],
			'enable'=>$tagcate['enable'],
		);
		$sqladd = db_array_to_insert_sqladd($arr);
		$r = $db->exec("INSERT INTO {$tablepre}tag_cate $sqladd");
		echo ".";
	}
}
echo "[ok]\r\n";
unset($tagcatelist);

$taglist = $olddb->sql_find("SELECT * FROM {$tablepre}thread_type ORDER BY fid ASC, typeid ASC");
if($taglist) {
	foreach ($taglist as $tag) {
		$tagid_map["$tag[fid]-$tag[typeid]"] = ++$maxtagid;
		$tagcateid = $thread_type_map3[$tag['typeid']]; // typeid 根据范围自动隐射到 cateid
		$cateid = $cateid_map["$tag[fid]-$tagcateid"];	// 找到隐射的自增的 cateid
		$arr = array(
			'cateid'=>$cateid,
			'tagid'=>$maxtagid,
			'name'=>$tag['typename'],
			'rank'=>$tag['rank'],
			'enable'=>$tag['enable'],
		);
		$sqladd = db_array_to_insert_sqladd($arr);
		$r = $db->exec("INSERT INTO {$tablepre}tag $sqladd");
		echo ".";
	}
}
echo "[ok]\r\n";
unset($taglist);

/*
  fid smallint(6) NOT NULL default '0',			# 版块id
  tid int(11) NOT NULL default '0',			# tid
  typeidsum int(11) unsigned NOT NULL default '0',	# 这个值是一个“和”
  */
$tagdatalist = $olddb->sql_find("SELECT * FROM {$tablepre}thread_type_data");
if($tagdatalist) {
	foreach($tagdatalist as $tagdata) {
		// 过滤掉加和的值，只升级单个的值。
		if(!isset($thread_type_map3[$tagdata['typeidsum']])) continue;
		$tagid = $tagid_map["$tagdata[fid]-$tagdata[typeidsum]"];
		$arr = array(
			'tagid'=>$tagid,
			'tid'=>$tagdata['tid'],
		);
		$sqladd = db_array_to_insert_sqladd($arr);
		$r = $db->exec("INSERT INTO {$tablepre}tag_thread $sqladd");
		echo ".";
	}
}
echo "[ok]\r\n";
unset($tagdatalist);


# 精华主题
db_exec("TRUNCATE {$tablepre}thread_digest");
$sql = "CREATE TABLE IF NOT EXISTS {$tablepre}thread_digest (
  fid smallint(6) NOT NULL default '0',			# 版块id
  tid int(11) unsigned NOT NULL default '0',		# 主题id
  uid int(11) unsigned NOT NULL default '0',		# uid
  digest tinyint(3) unsigned NOT NULL default '0',	# 精华等级
  PRIMARY KEY (tid),					# 
  KEY (uid),					# 
  UNIQUE KEY (fid, tid)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";
db_exec($sql);
$sql = "ALTER TABLE {$tablepre}thread ADD COLUMN digest tinyint(3) unsigned NOT NULL default '0';";
db_exec($sql);
$sql = "ALTER TABLE {$tablepre}user ADD COLUMN digests tinyint(3) unsigned NOT NULL default '0';";
db_exec($sql);
echo "upgrade digest:\r\n";
$digestlist = $olddb->sql_find("SELECT * FROM {$tablepre}thread_digest");
$db->exec("TRUNCATE `{$tablepre}thread_digest`");
foreach ($digestlist as $digest) {
	$thread = $olddb->sql_find_one("SELECT * FROM {$tablepre}thread WHERE tid='$digest[tid]'");
	$arr = array(
		'fid'=>$digest['fid'],
		'tid'=>$digest['tid'],
		'uid'=>$thread['uid'],
		'digest'=>$digest['digest'],
	);
	$sqladd = db_array_to_insert_sqladd($arr);
	$r = $db->exec("INSERT INTO `{$tablepre}thread_digest` $sqladd");
	if($r === FALSE) echo($db->errstr);
	echo ".";
}
echo "[ok]\r\n";
unset($digestlist);





// 站点介绍
/*
$arr = $olddb->sql_find_one("SELECT * FROM kv WHERE k='sitebrief'");
$sitebrief = $arr['v'];
file_replace_var('./conf/conf.php', array('sitebrief'=>$sitebrief));
*/

// 递归拷贝目录
//copy_recusive(XN2_PATH.'upload/avatar', "./upload/avatar");
//copy_recusive(XN2_PATH.'upload/forum', "./upload/forum");
//copy_recusive(XN2_PATH.'upload/attach', "./upload/attach");

mkdir('./upload/tmp', 0777);

echo '<a href="../">升级完成，请手工移动 old/upload/ 目录下所有文件到 upload/ 目录下，点击进入论坛。</a>';

// tag 缓存的时间
include './model/kv.func.php';
setting_set('tag_update_time', $time);

?>