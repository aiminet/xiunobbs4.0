<?php
// 合并 XiunoPHP

function_exists('set_magic_quotes_runtime') AND set_magic_quotes_runtime(0);
$dir = '../xiunophp/';


$s = php_strip_whitespace($dir.'db_mysql.class.php');
$s .= php_strip_whitespace($dir.'db_pdo_mysql.class.php');
$s .= php_strip_whitespace($dir.'db_pdo_sqlite.class.php');
$s .= php_strip_whitespace($dir.'cache_apc.class.php');
$s .= php_strip_whitespace($dir.'cache_memcached.class.php');
$s .= php_strip_whitespace($dir.'cache_mysql.class.php');
$s .= php_strip_whitespace($dir.'cache_redis.class.php');
$s .= php_strip_whitespace($dir.'cache_xcache.class.php');
$s .= php_strip_whitespace($dir.'cache_yac.class.php');

$s .= php_strip_whitespace($dir.'db.func.php');
$s .= php_strip_whitespace($dir.'cache.func.php');
$s .= php_strip_whitespace($dir.'image.func.php');
$s .= php_strip_whitespace($dir.'array.func.php');
$s .= php_strip_whitespace($dir.'xn_encrypt.func.php');
$s .= php_strip_whitespace($dir.'misc.func.php');

$s = substr($s, 8, -2);

$xiunophp = file_get_contents($dir.'xiunophp.php');
$before = '// hook xiunophp_include_before.php';
$after = '// hook xiunophp_include_after.php';
$pre = substr($xiunophp, 0, strpos($xiunophp, $before) + 1 + strlen($before));
$suffix = substr($xiunophp, strpos($xiunophp, $after));
$xiunophp_min = trim($pre)."\r\n\r\n".trim($s)."\r\n\r\n".trim($suffix);

//echo $xiunophp_min;exit;
/*
$p = '#//\shook\sxiunophp_include_before\.php(.*?)//\shook\sxiunophp_include_after\.php#ism';
$xiunophp_min = preg_replace($p, $s, $xiunophp);
*/

/*
$xiunophp_min = preg_replace(
'#//\shook\sxiunophp_include_before\.php(.*)//\shook\sxiunophp_include_after\.php#ism', 
'//\shook\sxiunophp_include_before.php'.$s.'//\shook\sxiunophp_include_after.php', 
$xiunophp);*/

file_put_contents($dir.'xiunophp.min.php', $xiunophp_min);

echo 'ok';