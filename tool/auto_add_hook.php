<?php
exit;
$files = glob('../model/*.func.php');
function my_callback($m) {
	$arr = preg_split('#\r?\n#', $m[0]);
	$funcname = $m[1];
	$len = count($arr);
	if(strpos($arr[$len - 3], 'return ') !== FALSE) {
		$returnline = $len - 3;
	} else {
		$returnline = $len - 2;
	}
	
	$arr = array_insert($arr, "\t// hook {$funcname}_end.php", $returnline);
	$arr = array_insert($arr, "\t// hook {$funcname}_start.php", 1);
	return implode("\r\n", $arr);
}

foreach ($files as $file) {
	//$arr = array('../model/sms.func.php', '../model/sms.func.php', '../model/check.func.php');
	//if(in_array($file, $arr)) continue;
	$s = file_get_contents($file);
	//preg_match_all('#function\s+(\w+)\(.*?\)\s*\{\s*\r\n(.*?)\r\n\}\r\n#is', $s, $m);
	$s2 = preg_replace_callback('#function\s+(\w+)\(.*?\)\s*\{\s*\r?\n(.*?)\r?\n\}\r?\n#is', 'my_callback', $s, 100);
	
	$n = strrpos($file, '/') + 1;
	$filename = str_replace('.', '_', substr($file, $n));
	
	$s2 = trim($s2);
	$s2 = preg_replace('#^\<\?php\r\n(.*?)\?\>$#is', "<?php\r\n\r\n// hook {$filename}_start.php\r\n\\1\r\n// hook {$filename}_end.php\r\n\r\n?>", $s2);
	//echo $s2; break;
	file_put_contents($file, $s2);
}

function array_insert($myarray, $value, $position=0) {
   $fore=($position==0)?array():array_splice($myarray,0,$position);
   $fore[]=$value;
   $ret=array_merge($fore,$myarray);
   return $ret;
}