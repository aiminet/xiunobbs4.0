<?php
/*
 * Copyright (C) xiuno.com
 */

//xhprof_enable();

//$_SERVER['REQUEST_URI'] = '/?user-login.htm';
//$_SERVER['REQUEST_METHOD'] = 'POST';
//$_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
//$_COOKIE['bbs_sid'] = 'e1d8c2790b9dd08267e6ea2595c3bc82';
//$postdata = 'email=admin&password=c4ca4238a0b923820dcc509a6f75849b';
//parse_str($postdata, $_POST);

// 0: Production mode; 1: Developer mode; 2: Plugin developement mode;
// 0: 线上模式; 1: 调试模式; 2: 插件开发模式;
!defined('DEBUG') AND define('DEBUG', 0);
define('APP_PATH', dirname(__FILE__).'/'); // __DIR__
!defined('ADMIN_PATH') AND define('ADMIN_PATH', APP_PATH.'admin/');
!defined('XIUNOPHP_PATH') AND define('XIUNOPHP_PATH', APP_PATH.'xiunophp/');

// !ini_get('zlib.output_compression') AND ob_start('ob_gzhandler');

//ob_start('ob_gzhandler');
$conf = (@include APP_PATH.'conf/conf.php') OR exit('<script>window.location="install/"</script>');

// 兼容 4.0.3 的配置文件	
!isset($conf['user_create_on']) AND $conf['user_create_on'] = 1;
!isset($conf['logo_mobile_url']) AND $conf['logo_mobile_url'] = 'view/img/logo.png';
!isset($conf['logo_pc_url']) AND $conf['logo_pc_url'] = 'view/img/logo.png';
!isset($conf['logo_water_url']) AND $conf['logo_water_url'] = 'view/img/water-small.png';
$conf['version'] = '4.0.4';		// 定义版本号！避免手工修改 conf/conf.php

// 转换为绝对路径，防止被包含时出错。
substr($conf['log_path'], 0, 2) == './' AND $conf['log_path'] = APP_PATH.$conf['log_path']; 
substr($conf['tmp_path'], 0, 2) == './' AND $conf['tmp_path'] = APP_PATH.$conf['tmp_path']; 
substr($conf['upload_path'], 0, 2) == './' AND $conf['upload_path'] = APP_PATH.$conf['upload_path']; 

$_SERVER['conf'] = $conf;

if(DEBUG > 1) {
	include XIUNOPHP_PATH.'xiunophp.php';
} else {
	include XIUNOPHP_PATH.'xiunophp.min.php';
}

// 测试数据库连接 / try to connect database
//db_connect() OR exit($errstr);

include APP_PATH.'model/plugin.func.php';
include _include(APP_PATH.'model.inc.php');
include _include(APP_PATH.'index.inc.php');

//file_put_contents((ini_get('xhprof.output_dir') ? : '/tmp') . '/' . uniqid() . '.xhprof.xhprof', serialize(xhprof_disable()));

?>