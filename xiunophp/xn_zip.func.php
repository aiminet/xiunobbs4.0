<?php 

function xn_zip($zipfile, $extdir) { 
	if(class_exists('ZipArchive')) {
		$pathinfo = pathinfo($extdir); 
		$parentpath = $pathinfo['dirname']; 
		$dirname = $pathinfo['basename']; 
	
		xn_unlink($zipfile);
		$z = new ZipArchive(); 
		$z->open($zipfile, ZIPARCHIVE::CREATE); 
		$z->addEmptyDir($dirname); 
		xn_dir_to_zip($z, $extdir, strlen("$parentpath/")); 
		$z->close();
	} else {
		
		xn_unlink($zipfile);
		include_once XIUNOPHP_PATH.'xn_zip_old.func.php';
		xn_zip_old($zipfile, $extdir);
	}
}

function xn_unzip($zipfile, $extdir) {
	if(class_exists('ZipArchive')) {
		$z = new ZipArchive;
		if($z->open($zipfile) === TRUE) {
			$z->extractTo($extdir);
			$z->close();
		}
	} else {
		include_once XIUNOPHP_PATH.'xn_zip_old.func.php';
		xn_unzip_old($zipfile, $extdir);
	}
	
	// 如果解压出来多了一层，则去掉一层。
	// /path/dir1/dir1/a/b   ->   /path/dir1/a/b
	
	$extdirlast = substr(strrchr(substr($extdir, 0, -1), '/'), 1); // /path/dir1/ -> /dir1 -> dir1
	$extdirp = substr(substr($extdir, 0, -1), 0, strpos($extdir, '/') + 1); // 上一级目录 /path/
	if(is_dir($extdir.$extdirlast)) { // /path/dir1/dir1
		$extdirtmp = substr($extdir, 0, -1).'__xn__tmp__dir__/'; // path/dir1__xn__tmp__dir__/
		
		rename(substr($extdir, 0, -1), substr($extdirtmp, 0, -1)); // rename('/path/dir1', '/path/dir1__xn__tmp__dir__');
		rename($extdirtmp.$extdirlast, substr($extdir, 0, -1)); // rename('/path/dir1__xn__tmp__dir__/dir1', '/path/dir1');
		
		// 干掉临时目录
		// rmdir($extdirtmp);
	}
}

function xn_dir_to_zip($z, $zippath, $prelen = 0) {
		
	// (PHP 5 >= 5.3.0, PHP 7, PECL zip >= 1.9.0)
	/*
	$zip = new ZipArchive();
	$ret = $zip->open($zipfile, ZipArchive::OVERWRITE);
	if ($ret !== TRUE) {
		printf('Failed with code %d', $ret);
	}else {
		//$options = array('add_path' => 'sources/', 'remove_all_path' => TRUE);
		$options = array('remove_all_path' => TRUE);
		$zip->addGlob($extdir.'/*', GLOB_BRACE, $options);
		$zip->close();
	}
	*/
	substr($zippath, -1) != '/' AND $zippath .= '/';
	$filelist = glob($zippath."*");
	foreach($filelist as $filepath) {
		$localpath = substr($filepath, $prelen); 
		if(is_file($filepath)) { 
			$z->addFile($filepath, $localpath); 
		} elseif(is_dir($filepath)) { 
			$z->addEmptyDir($localpath); 
			xn_dir_to_zip($z, $filepath, $prelen); 
		}
	}
}

// 第一层的目录名称，用来兼容多层打包
function xn_zip_unwrap_path($zippath, $dirname = '') {
	substr($zippath, -1) != '/' AND $zippath .= '/';
	$arr = glob("$zippath*");
	if(empty($arr)) return array($zippath, '');
	$arr[0] = str_replace('\\', '/', $arr[0]);
	$tmparr = explode('/', $arr[0]);
	$wrapdir = array_pop($tmparr);
	$lastpath = $arr[0].'/';
	if(!$dirname) return count($arr) == 1 ? array($lastpath, $wrapdir) : array($zippath, '');
	if($dirname && $dirname == $wrapdir) {
		return array($lastpath, $wrapdir);
	} else {
		return array($zippath, '');
	}
}

//xn_unzip('d:/test/yyy.zip', 'd:/test/yyy/');
//xn_zip('d:/test/yyy.zip', 'd:/test/xxx/xxx');

?>