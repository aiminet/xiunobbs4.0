<?php

// 脚本执行完，如果过一段时间如果没有问题，则清理掉备份文件
// cd /home/wwwroot/bbs.xiuno.com/  你的 web 目录
// find ./upload/ -name "*.backup_png"|xargs rm -rf 

include '../xiunophp/xiunophp.min.php';

if(!function_exists('imagecreatefrompng')) {
	exit('请安装 GD 库。');
}
if(!function_exists('glob')) {
	exit('不支持 glob() 函数');
}

$files = glob_recursive('../upload/*.png');
foreach ($files as $file) {
	list($width, $height, $type, $attr) = getimagesize($file);
	$size = filesize($file);
	$file2 = str_pad($file, 64, ' ');
	$width2 = str_pad($width, 16, ' ');
	$height2 = str_pad($height, 16, ' ');
	//echo "$file $width $height $size ".IMAGETYPE_PNG." ".$type." <br>\r\n";
	if($type == IMAGETYPE_PNG) {
		echo "$file2 $width2 $height2 $size \r\n";
		xn_copy($file, $file.'.backup_png');
		png2jpg($file, $file);
	} elseif($type == IMAGETYPE_GIF && $size > 12000) {
		echo "$file2 $width2 $height2 $size \r\n";
		xn_copy($file, $file.'.backup_png');
		gif2jpg($file, $file);
	}
}


function png2jpg($originalFile, $outputFile, $quality = 80) {
	$image = imagecreatefrompng($originalFile);
	imagejpeg($image, $outputFile, $quality);
	imagedestroy($image);
}

function gif2jpg($originalFile, $outputFile, $quality = 80) {
	$image = imagecreatefromgif($originalFile);
	imagejpeg($image, $outputFile, $quality);
	imagedestroy($image);
}



?>