<?php

/*
* Copyright (C) 2015 xiuno.com
*/

// 安全缩略，按照ID存储
/*
	$arr = image_safe_thumb('abc.jpg', 123, '.jpg', './upload/', 100, 100);
	array(
		'filesize'=>1234,
		'width'=>100,
		'height'=>100,
		'fileurl' => '001/0123/1233.jpg'
	);
*/

// 不包含 .
function image_ext($filename) {
	return strtolower(substr(strrchr($filename, '.'), 1));
}

// 获取安全的文件名，如果文件存在，则加时间戳和随机数，避免重复
function image_safe_name($filename, $dir) {
	$time = $_SERVER['time'];
	// 最后一个 . 保留，其他的 . 替换
	$s1 = substr($filename, 0, strrpos($filename, '.'));
	$s2 = substr(strrchr($filename, '.'), 1);
	$s1 = preg_replace('#\W#', '_', $s1);
	$s2 = preg_replace('#\W#', '_', $s2);
	if(is_file($dir."$s1.$s2")) {
		$newname = $s1.$time.rand(1, 1000).'.'.$s2;
	} else {
		$newname = "$s1.$s2";
	}
	return $newname;
}

// 缩略图的名字
function image_thumb_name($filename) {
	return substr($filename, 0, strrpos($filename, '.')).'_thumb'.strrchr($filename, '.');
}

// 随即文件名
function image_rand_name($k) {
	$time = $_SERVER['time'];
	return $time.'_'.rand(1000000000, 9999999999).'_'.$k;
}

/*
	实例：
	image_set_dir(123, './upload');

	000/000/1.jpg
	000/000/100.jpg
	000/000/100.jpg
	000/000/999.jpg
	000/001/1000.jpg
	000/001/001.jpg
	000/002/001.jpg
*/
function image_set_dir($id, $dir) {

	$id = sprintf("%09d", $id);
	$s1 = substr($id, 0, 3);
	$s2 = substr($id, 3, 3);
	$dir = $dir."$s1/$s2";
	!is_dir($dir) && mkdir($dir, 0777, TRUE);

	return "$s1/$s2";
}

// 取得 user home 路径
function image_get_dir($id) {
	$id = sprintf("%09d", $id);
	$s1 = substr($id, 0, 3);
	$s2 = substr($id, 3, 3);
	return "$s1/$s2";
}

/*
	实例：
 	image_thumb('xxx.jpg', 'xxx_thumb.jpg', 200, 200);

 	返回：
 	array('filesize'=>0, 'width'=>0, 'height'=>0)
 */
function image_thumb($sourcefile, $destfile, $forcedwidth = 80, $forcedheight = 80) {
	$return = array('filesize'=>0, 'width'=>0, 'height'=>0);
	$destext = image_ext($destfile);
	if(!in_array($destext, array('gif', 'jpg', 'bmp', 'png'))) {
		return $return;
	}

	$imginfo = getimagesize($sourcefile);
	$src_width = $imginfo[0];
	$src_height = $imginfo[1];
	if($src_width == 0 || $src_height == 0) {
		return $return;
	}

	if(!function_exists('imagecreatefromjpeg')) {
		copy($sourcefile, $destfile);
		$return = array('filesize'=>filesize($destfile), 'width'=>$src_width, 'height'=>$src_height);
		return $return;
	}

	// 按规定比例缩略
	$src_scale = $src_width / $src_height;
	$des_scale = $forcedwidth / $forcedheight;
	if($src_width <= $forcedwidth && $src_height <= $forcedheight) {
		$des_width = $src_width;
		$des_height = $src_height;
	} elseif($src_scale >= $des_scale) {
		$des_width = ($src_width >= $forcedwidth) ? $forcedwidth : $src_width;
		$des_height = $des_width / $src_scale;
		$des_height = ($des_height >= $forcedheight) ? $forcedheight : $des_height;
	} else {
		$des_height = ($src_height >= $forcedheight) ? $forcedheight : $src_height;
		$des_width = $des_height * $src_scale;
		$des_width = ($des_width >= $forcedwidth) ? $forcedwidth : $des_width;
	}

	switch ($imginfo['mime']) {
		case 'image/jpeg':
			$img_src = imagecreatefromjpeg($sourcefile);
			!$img_src && $img_src = imagecreatefromgif($sourcefile);
			break;
		case 'image/gif':
			$img_src = imagecreatefromgif($sourcefile);
			!$img_src && $img_src = imagecreatefromjpeg($sourcefile);
			break;
		case 'image/png':
			$img_src = imagecreatefrompng($sourcefile);
			break;
		case 'image/wbmp':
			$img_src = imagecreatefromwbmp($sourcefile);
			break;
		default :
			return $return;
	}

	if(!$img_src) return $return;

	$img_dst = imagecreatetruecolor($des_width, $des_height);
	imagefill($img_dst, 0, 0 , 0xFFFFFF);
	imagecopyresampled($img_dst, $img_src, 0, 0, 0, 0, $des_width, $des_height, $src_width, $src_height);

	$conf = _SERVER('conf');
	$tmppath = isset($conf['tmp_path']) ? $conf['tmp_path'] : ini_get('upload_tmp_dir').'/';
	$tmppath == '/' AND $tmppath = './tmp/';

	$tmpfile = $tmppath.md5($destfile).'.tmp';
	switch($destext) {
		case 'jpg': imagejpeg($img_dst, $tmpfile, 90); break;
		case 'gif': imagegif($img_dst, $tmpfile); break;
		case 'png': imagepng($img_dst, $tmpfile); break;
	}
	$r = array('filesize'=>filesize($tmpfile), 'width'=>$des_width, 'height'=>$des_height);;
	copy($tmpfile, $destfile);
	is_file($tmpfile) && unlink($tmpfile);
	imagedestroy($img_dst);
	return $r;
}


/**
 * 图片裁切
 *
 * @param string $sourcefile	原图片路径(绝对路径/abc.jpg)
 * @param string $destfile 		裁切后生成新名称(绝对路径/rename.jpg)
 * @param int $clipx 			被裁切图片的X坐标
 * @param int $clipy 			被裁切图片的Y坐标
 * @param int $clipwidth 		被裁区域的宽度
 * @param int $clipheight 		被裁区域的高度
 * image_clip('xxx/x.jpg', 'xxx/newx.jpg', 10, 40, 150, 150)
 */
function image_clip($sourcefile, $destfile, $clipx, $clipy, $clipwidth, $clipheight) {
	$getimgsize = getimagesize($sourcefile);
	if(empty($getimgsize)) {
		return 0;
	} else {
		$imgwidth = $getimgsize[0];
		$imgheight = $getimgsize[1];
		if($imgwidth == 0 || $imgheight == 0) {
			return 0;
		}
	}

	if(!function_exists('imagecreatefromjpeg')) {
		copy($sourcefile, $destfile);
		return filesize($destfile);
	}
	switch($getimgsize[2]) {
		case 1 :
			$imgcolor = imagecreatefromgif($sourcefile);
			break;
		case 2 :
			$imgcolor = imagecreatefromjpeg($sourcefile);
			break;
		case 3 :
			$imgcolor = imagecreatefrompng($sourcefile);
			break;
	}

	if(!$imgcolor) return 0;

	$img_dst = imagecreatetruecolor($clipwidth, $clipheight);
	imagefill($img_dst, 0, 0 , 0xFFFFFF);
	imagecopyresampled($img_dst, $imgcolor, 0, 0, $clipx, $clipy, $imgwidth, $imgheight, $imgwidth, $imgheight);

	$conf = _SERVER('conf');
	$tmppath = isset($conf['tmp_path']) ? $conf['tmp_path'] : ini_get('upload_tmp_dir').'/';
	$tmppath == '/' AND $tmppath = './tmp/';

	$tmpfile = $tmppath.md5($destfile).'.tmp';
	imagejpeg($img_dst, $tmpfile, 100);
	$n = filesize($tmpfile);
	copy($tmpfile, $destfile);
	is_file($tmpfile) && @unlink($tmpfile);
	return $n;
}

// 先裁切后缩略，因为确定了，width, height, 不需要返回宽高。
function image_clip_thumb($sourcefile, $destfile, $forcedwidth = 80, $forcedheight = 80) {
	// 获取原图片宽高
	$getimgsize = getimagesize($sourcefile);
	if(empty($getimgsize)) {
		return 0;
	} else {
		$src_width = $getimgsize[0];
		$src_height = $getimgsize[1];
		if($src_width == 0 || $src_height == 0) {
			return 0;
		}
	}

	$src_scale = $src_width / $src_height;
	$des_scale = $forcedwidth / $forcedheight;

	if($src_width <= $forcedwidth && $src_height <= $forcedheight) {
		$des_width = $src_width;
		$des_height = $src_height;
		$n = image_clip($sourcefile, $destfile, 0, 0, $des_width, $des_height);
		return filesize($destfile);
	// 原图为横着的矩形
	} elseif($src_scale >= $des_scale) {
		// 以原图的高度作为标准，进行缩略
		$des_height = $src_height;
		$des_width = $src_height / $des_scale;
		$n = image_clip($sourcefile, $destfile, 0, 0, $des_width, $des_height);
		if($n <= 0) return 0;
		$r = image_thumb($destfile, $destfile, $forcedwidth, $forcedheight);
		return $r['filesize'];
	// 原图为竖着的矩形
	} else {
		// 以原图的宽度作为标准，进行缩略
		$des_width = $src_width;
		$des_height = $src_width / $des_scale;

		// echo "src_scale: $src_scale, src_width: $src_width, src_height: $src_height \n";
		// echo "des_scale: $des_scale, forcedwidth: $forcedwidth, forcedheight: $forcedheight \n";
		// echo "des_width: $des_width, des_height: $des_height \n";
		// exit;

		$n = image_clip($sourcefile, $destfile, 0, 0, $des_width, $des_height);
		if($n <= 0) return 0;
		$r = image_thumb($destfile, $destfile, $forcedwidth, $forcedheight);
		return $r['filesize'];
	}
}

function image_safe_thumb($sourcefile, $id, $ext, $dir1, $forcedwidth, $forcedheight, $randomname = 0) {
	$time = $_SERVER['time'];
	$ip = $_SERVER['ip'];
	$dir2 = image_set_dir($id, $dir1);
	$filename = $randomname ? md5(rand(0, 1000000000).$time.$ip).$ext : $id.$ext;
	$filepath = "$dir1$dir2/$filename";
	$arr = image_thumb($sourcefile, $filepath, $forcedwidth, $forcedheight);
	$arr['fileurl'] = "$dir2/$filename";
	return $arr;
}

// image_thumb('D:/image/IMG_0433.JPG', 'd:/image/xxx.gif');
// echo image_clip_thumb('d:/image/editor_bg.gif', 'd:/image/editor_bg_2.gif', 200, 200);

?>