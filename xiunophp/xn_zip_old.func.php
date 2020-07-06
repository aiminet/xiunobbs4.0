<?php

// 对于没有安装扩展的使用该类。

/*
   该类库收集于互联网，版权未知，由 axiuno@gmail.com 修正了部分64位下的 bug, 如果谁知道请麻烦告知作者。
*/

/*if(!defined('FRAMEWORK_TMP_TMP_PATH')) {
	define('FRAMEWORK_TMP_TMP_PATH', './');
}
date_default_timezone_set('Asia/Shanghai');
*/
class php_zip {
	private $ctrl_dir	= array();
	private $datasec	= array();
	//public $fileList 	= array();
	public $files		= array(); // 用来返回文件名， 需要。

	public function zip($dir, $saveName) {
		if(@!function_exists('gzcompress')){ throw new Exception('gzcompress does not exits.'); }
		if(!is_dir($dir)) {
			throw new Exception($dir.'does not exits.');
		}
		$filelist = $this->visit_file($dir);
		if(count($filelist) == 0){ return; }

		foreach($filelist as $file)
		{
			if(!file_exists($file) || !is_file($file)){ continue; }

			$fd	  = fopen($file, "rb");
			$filesize = filesize($file);
			$content  = $filesize ? fread($fd, $filesize) : '';
			fclose($fd);

			$file = substr($file, strlen($dir));
			if(substr($file, 0, 1) == "\\" || substr($file, 0, 1) == "/"){ $file = substr($file, 1); }

			$this->addFile($content, $file);
		}
		$out = $this->file();

		$fp = fopen($saveName, "wb");
		fwrite($fp, $out, strlen($out));
		fclose($fp);
	}

	public function unzip($zipfile, $zipdir = '') {
		empty($zipdir) && $zipdir = substr($zipfile, 0, strrpos($zipfile, '.'));
		$zipfile   = $zipfile;
		$savepath  = $zipdir;
		$array	 = $this->get_zip_inner_file_info($zipfile);
		$filecount = 0;
		$dircount  = 0;
		$failfiles = array();
		//set_time_limit(0);

		for($i=0; $i<count($array); $i++) {
		 	if($array[$i]['folder'] == 0){
				 if($this->do_unzip($zipfile, $savepath, $i) > 0){
					 $filecount++;
				 } else {
					$failfiles[] = $array[$i]['filename'];
				 }
			 } else {
				 $dircount++;
			 }
		 }
		 return empty($failfiles);
	}

	private function visit_file($path) {
		$fileList = array();
		$path = str_replace("\\", "/", $path);
		$fdir = dir($path);
		while(($file = $fdir->read()) !== false)
		{
			if($file == '.' || $file == '..'){ continue; }

			$pathSub    = preg_replace("*/{2,999}*", "/", $path."/".$file);
			$fileList[] = is_dir($pathSub) ? $pathSub."/" : $pathSub;
			if(is_dir($pathSub)){ $fileList = array_merge($fileList, $this->visit_file($pathSub)); }
		}
		$fdir->close();
		return $fileList;
	}


	private function unix_to_dos_time($unixtime = 0) {
		$timearray = ($unixtime == 0) ? getdate() : getdate($unixtime);

		if($timearray['year'] < 1980)
		{
			$timearray['year']	= 1980;
			$timearray['mon']	 = 1;
			$timearray['mday']	= 1;
			$timearray['hours']   = 0;
			$timearray['minutes'] = 0;
			$timearray['seconds'] = 0;
		}

		return (  ($timearray['year'] - 1980) << 25)
				| ($timearray['mon'] << 21)
				| ($timearray['mday'] << 16)
				| ($timearray['hours'] << 11)
				| ($timearray['minutes'] << 5)
				| ($timearray['seconds'] >> 1);
	}


	private $old_offset = 0;
	private function addFile($data, $filename, $time = 0) {
		$filename = str_replace('\\', '/', $filename);

		$dtime	= dechex($this->unix_to_dos_time($time));
		
		// fixed by axiuno@gmail.com 干掉 eval()
		$hexdtime = pack("C*", hexdec('0x'.$dtime[6].$dtime[7]), hexdec('0x'.$dtime[4].$dtime[5]), hexdec('0x'.$dtime[2].$dtime[3]), hexdec('0x'.$dtime[0].$dtime[1]));

		/*
		$hexdtime = '\x' . $dtime[6] . $dtime[7]
				  . '\x' . $dtime[4] . $dtime[5]
				  . '\x' . $dtime[2] . $dtime[3]
				  . '\x' . $dtime[0] . $dtime[1];
				  
				  echo '$hexdtime = "' . $hexdtime . '";';exit;
		eval('$hexdtime = "' . $hexdtime . '";');
		*/
		
		$fr	   = "\x50\x4b\x03\x04";
		$fr	  .= "\x14\x00";
		$fr	  .= "\x00\x00";
		$fr	  .= "\x08\x00";
		$fr	  .= $hexdtime;
		$unc_len  = strlen($data);
		$crc	  = crc32($data);
		$zdata	= gzcompress($data);
		$c_len	= strlen($zdata);
		$zdata	= substr(substr($zdata, 0, strlen($zdata) - 4), 2);
		$fr	  .= pack('V', $crc);
		$fr	  .= pack('V', $c_len);
		$fr	  .= pack('V', $unc_len);
		$fr	  .= pack('v', strlen($filename));
		$fr	  .= pack('v', 0);
		$fr	  .= $filename;

		$fr	  .= $zdata;

		$fr	  .= pack('V', $crc);
		$fr	  .= pack('V', $c_len);
		$fr	  .= pack('V', $unc_len);

		$this->datasec[] = $fr;
		$new_offset	  = strlen(implode('', $this->datasec));

		$cdrec  = "\x50\x4b\x01\x02";
		$cdrec .= "\x00\x00";
		$cdrec .= "\x14\x00";
		$cdrec .= "\x00\x00";
		$cdrec .= "\x08\x00";
		$cdrec .= $hexdtime;
		$cdrec .= pack('V', $crc);
		$cdrec .= pack('V', $c_len);
		$cdrec .= pack('V', $unc_len);
		$cdrec .= pack('v', strlen($filename) );
		$cdrec .= pack('v', 0 );
		$cdrec .= pack('v', 0 );
		$cdrec .= pack('v', 0 );
		$cdrec .= pack('v', 0 );
		$cdrec .= pack('V', 32 );

		$cdrec .= pack('V', $this->old_offset );
		$this->old_offset = $new_offset;

		$cdrec .= $filename;
		$this->ctrl_dir[] = $cdrec;
	}

	private $eof_ctrl_dir = "\x50\x4b\x05\x06\x00\x00\x00\x00";
	private function file() {
		$data	= implode('', $this->datasec);
		$ctrldir = implode('', $this->ctrl_dir);

		return   $data
			   . $ctrldir
			   . $this->eof_ctrl_dir
			   . pack('v', sizeof($this->ctrl_dir))
			   . pack('v', sizeof($this->ctrl_dir))
			   . pack('V', strlen($ctrldir))
			   . pack('V', strlen($data))
			   . "\x00\x00";
	}

	private function read_central_dir($zip, $zipfile) {
		$size	 = filesize($zipfile);
		$max_size = ($size < 277) ? $size : 277;

		fseek($zip, $size - $max_size);
		$pos   = ftell($zip);
		$bytes = 0;

		while($pos < $size)
		{
			$byte  = fread($zip, 1);

			// fixed 64 bit bug, by axiuno@gmail.com
			// 64 bit
			if(PHP_INT_SIZE === 8) {
				$bytes = ($bytes << 8) | Ord($byte);
				$pos++;
				if($bytes < 0) {
					$bytes = ($bytes << 32 >> 32);
					$bytes = $bytes & 0x00000000FFFFFFFF;
				} else {
					$bytes = ($bytes << 32 >> 32);
				}
				// 考虑负数的位移
				$bytes == 0xFFFFFFFF504B0506 && $bytes = 0x00000000504B0506;
				if($bytes == 0x00000000504B0506) break;
			} else {
				$bytes = ($bytes << 8) | Ord($byte);
				$pos++;
				if($bytes == 0x504B0506) break;
			}
			//printf("bytes: %016x, %d\r\n", $bytes, PHP_INT_SIZE);
		}

		$data = unpack('vdisk/vdisk_start/vdisk_entries/ventries/Vsize/Voffset/vcomment_size', fread($zip, 18));

		$centd['comment']	  = ($data['comment_size'] != 0) ? fread($zip, $data['comment_size']) : '';
		$centd['entries']	  = $data['entries'];
		$centd['disk_entries'] = $data['disk_entries'];
		$centd['offset']	   = $data['offset'];
		$centd['disk_start']   = $data['disk_start'];
		$centd['size']		 = $data['size'];
		$centd['disk']		 = $data['disk'];
		return $centd;
	}


	private function read_central_file_headers($zip) {
		$binary_data = fread($zip, 46);
		$header	  = unpack('vchkid/vid/vversion/vversion_extracted/vflag/vcompression/vmtime/vmdate/Vcrc/Vcompressed_size/Vsize/vfilename_len/vextra_len/vcomment_len/vdisk/vinternal/Vexternal/Voffset', $binary_data);

		$header['filename'] = ($header['filename_len'] != 0) ? fread($zip, $header['filename_len']) : '';
		$header['extra']	= ($header['extra_len']	!= 0) ? fread($zip, $header['extra_len'])	: '';
		$header['comment']  = ($header['comment_len']  != 0) ? fread($zip, $header['comment_len'])  : '';


		if($header['mdate'] && $header['mtime'])
		{
			$hour	= ($header['mtime']  & 0xF800) >> 11;
			$minute  = ($header['mtime']  & 0x07E0) >> 5;
			$seconde = ($header['mtime']  & 0x001F) * 2;
			$year	= (($header['mdate'] & 0xFE00) >> 9) + 1980;
			$month   = ($header['mdate']  & 0x01E0) >> 5;
			$day	 = $header['mdate']   & 0x001F;
			$header['mtime'] = mktime($hour, $minute, $seconde, $month, $day, $year);
		} else {
			$header['mtime'] = time();
		}
		$header['stored_filename'] = $header['filename'];
		$header['status'] = 'ok';
		if(substr($header['filename'], -1) == '/'){ $header['external'] = 0x41FF0010; }
		return $header;
	}


	private function readfileheader($zip) {
		$binary_data = fread($zip, 30);
		$data		= unpack('vchk/vid/vversion/vflag/vcompression/vmtime/vmdate/Vcrc/Vcompressed_size/Vsize/vfilename_len/vextra_len', $binary_data);

		$header = array();
		$header['filename']		= fread($zip, $data['filename_len']);
		$header['extra']		   = ($data['extra_len'] != 0) ? fread($zip, $data['extra_len']) : '';
		$header['compression']	 = $data['compression'];
		$header['size']			= $data['size'];
		$header['compressed_size'] = $data['compressed_size'];
		$header['crc']			 = $data['crc'];
		$header['flag']			= $data['flag'];
		$header['mdate']		   = $data['mdate'];
		$header['mtime']		   = $data['mtime'];

		if($header['mdate'] && $header['mtime']){
			$hour	= ($header['mtime']  & 0xF800) >> 11;
			$minute  = ($header['mtime']  & 0x07E0) >> 5;
			$seconde = ($header['mtime']  & 0x001F) * 2;
			$year	= (($header['mdate'] & 0xFE00) >> 9) + 1980;
			$month   = ($header['mdate']  & 0x01E0) >> 5;
			$day	 = $header['mdate']   & 0x001F;
			$header['mtime'] = mktime($hour, $minute, $seconde, $month, $day, $year);
		}else{
			$header['mtime'] = time();
		}

		$header['stored_filename'] = $header['filename'];
		$header['status']		  = "ok";
		$header['external']		  = 0;	// 重要修正! 无法解压文件 by xiuno
		if(substr($header['filename'], -1) == '/'){ $header['external'] = 0x41FF0010; }
		return $header;
	}


	private function extrace_file($header, $to, $zip) {
		// fixed by axiuno, mac os zip compress
		$header2 = $this->readfileheader($zip);
		$header = array_merge($header2, $header);
		
		if(substr($to, -1) != "/"){ $to .= "/"; }
		if(!@is_dir($to)){ @mkdir($to, 0777); }
		$pth = explode("/", dirname($header['filename']));
		$pthss = '';
		for($i=0; isset($pth[$i]); $i++){
			if(!$pth[$i]){ continue; }
			$pthss .= $pth[$i]."/";
			if(!is_dir($to.$pthss)){ @mkdir($to.$pthss, 0777); }
		}

		if(isset($header['external']) && !($header['external'] == 0x41FF0010) && !($header['external'] == 16))
		{
			if($header['compression'] == 0)
			{
				$fp = fopen($to.$header['filename'], 'wb');
				if(!$fp){ return(-1); }
				$size = $header['compressed_size'];

				while($size != 0)
				{
					$read_size   = ($size < 2048 ? $size : 2048);
					$buffer	  = fread($zip, $read_size);
					$binary_data = pack('a'.$read_size, $buffer);
					@fwrite($fp, $binary_data, $read_size);
					$size	   -= $read_size;
				}
				fclose($fp);
				// 改变文件时间
				touch($to.$header['filename'], $header['mtime']);

			}else{

				$fp = fopen($to.$header['filename'].'.gz', 'wb');
				if(!$fp){ return(-1); }
				$binary_data = pack('va1a1Va1a1', 0x8b1f, chr($header['compression']), chr(0x00), time(), chr(0x00), chr(3));

				fwrite($fp, $binary_data, 10);
				$size = $header['compressed_size'];

				while($size != 0)
				{
					$read_size   = ($size < 1024 ? $size : 1024);
					$buffer	  = fread($zip, $read_size);
					$binary_data = pack('a'.$read_size, $buffer);
					@fwrite($fp, $binary_data, $read_size);
					$size	   -= $read_size;
				}

				$binary_data = pack('VV', $header['crc'], $header['size']);
				fwrite($fp, $binary_data, 8);
				fclose($fp);

				// 此处存在兼容性问题，在php5.3 下有问题，由 xiuno 修正。
				$gzfile = $to.$header['filename'].'.gz';
				$s = file_get_contents($gzfile);
				$unzipdata = $this->compatible_gzinflate($s);
				file_put_contents($to.$header['filename'], $unzipdata);

				/* old code
				$gzp = gzopen($to.$header['filename'].'.gz', 'rb');// or die("gzopen failed: $to$header[filename].gz");
				if(!$gzp){ return(-2); }
				$fp = @fopen($to.$header['filename'], 'wb');
				if(!$fp){ return(-1); }
				$size = $header['size'];
				echo $size;exit;
				while($size != 0)
				{
					$read_size   = ($size < 2048 ? $size : 2048);
					$buffer	  = gzread($gzp, $read_size);
					$binary_data = pack('a'.$read_size, $buffer);
					@fwrite($fp, $binary_data, $read_size);
					$size	   -= $read_size;
				}
				fclose($fp); gzclose($gzp);
				*/

				@unlink($to.$header['filename'].'.gz');
			}
		}
		return true;
	}

	// 参考 wordpress 和 http://bbs.xiuno.com/thread-3483.htm
	private function compatible_gzinflate($gzData) {

		// gzencode 函数
		if (substr($gzData, 0, 3) == "\x1f\x8b\x08") {
			// 1F 8B 08 00 00 00 00 00 00 03
			$s = '';
			if(function_exists('gzdecode')) {
				$s = gzdecode($gzData);
			}
			if(!empty($s)) {
				return $s;
			} else {
				$i = 10;
				$flg = ord( substr($gzData, 3, 1) );
				if ( $flg > 0 ) {
					if ( $flg & 4 ) {
						list($xlen) = unpack('v', substr($gzData, $i, 2) );
						$i = $i + 2 + $xlen;
					}
					if ( $flg & 8 )
						$i = strpos($gzData, "\0", $i) + 1;
					if ( $flg & 16 )
						$i = strpos($gzData, "\0", $i) + 1;
					if ( $flg & 2 )
						$i = $i + 2;
				}
				if(function_exists('gzinflate')) {
					return gzinflate(substr($gzData, $i, -8));
				} else {
					throw new Exception('gzinflate() has been disabled');
					//return gzuncompress($gzData);
				}
			}
		} else {
			return FALSE;
		}
	}


	private function do_unzip($zipfile, $to, $index = Array(-1)) {
		$ok  = 0;
		$zip = @fopen($zipfile, 'rb');
		if(!$zip){ return(-1); }

		$cdir	  = $this->read_central_dir($zip, $zipfile);
		$pos_entry = $cdir['offset'];

		if(!is_array($index)){ $index = array($index); }
		for($i=0; isset($index[$i]); $i++)
		{
			if(intval($index[$i]) != $index[$i] || $index[$i] > $cdir['entries'])
			{
				return(-1);
			}
		}

		for($i=0; $i<$cdir['entries']; $i++)
		{
			@fseek($zip, $pos_entry);
			$header		  = $this->read_central_file_headers($zip);
			$header['index'] = $i;
			$pos_entry	   = ftell($zip);
			@rewind($zip);
			fseek($zip, $header['offset']);

			if(in_array("-1", $index) || in_array($i, $index))
			{
				//echo "$header[filename] $i ".print_r($index, 1)."\r\n\r\n";
				$stat[$header['filename']] = $this->extrace_file($header, $to, $zip);
				$this->files[] = $header['filename'];
			}
		}

		fclose($zip);
		return $stat;
	}

	private function get_zip_inner_file_info($zipfile) {
		$zip = @fopen($zipfile, 'rb');
		if(!$zip){ return(0); }
		$centd = $this->read_central_dir($zip, $zipfile);

		@rewind($zip);
		@fseek($zip, $centd['offset']);
		$ret = array();

		for($i=0; $i<$centd['entries']; $i++)
		{
			$header		  = $this->read_central_file_headers($zip);
			$header['index'] = $i;
			$info = array(
				'filename'	=> $header['filename'],
				'stored_filename' => $header['stored_filename'],
				'size'		=> $header['size'],
				'compressed_size' => $header['compressed_size'],
				'crc'		 => strtoupper(dechex($header['crc'])),
				'mtime'		  => date("Y-m-d H:i:s",$header['mtime']),
				'comment'	 => $header['comment'],
				'folder'	=> ($header['external'] == 0x41FF0010 || $header['external'] == 16) ? 1 : 0,
				'index'		  => $header['index'],
				'status'	=> $header['status']
			);
			$ret[] = $info;
			unset($header);
		}
		fclose($zip);
		return $ret;
	}
}

function xn_mkdir_recusive($path) {
	// 查找最后一个 /
	if(empty($path) || strpos($path, '/') === FALSE || $path == '/') return;
	if(!is_dir($path)) {
		// 开始一级级的检测目录
		$arr = explode('/', $path);
		$t = array_shift($arr);
		while($first = array_shift($arr)) {
			$t .= '/'.$first;
			$isdir = is_dir($t);
			if(!$isdir) {
				mkdir($t, 0777);
			}
		}
	}
}

function xn_mkdir_by_filename($filename) {
	$path = substr($filename, 0, strrpos($filename, '/'));
	xn_mkdir_recusive($path);
}

function xn_unzip_old($zipfile, $destpath) {
	$conf = _SERVER('conf');
	$tmppath = empty($conf['tmp_path']) ? ini_get('upload_tmp_dir').'/' : $conf['tmp_path'];
	$destpath = str_replace('\\', '/', $destpath);
	substr($destpath, -1, 1) != '/' && $destpath .= '/';
	$archive = new php_zip();
	$tmppath = $tmppath.md5($zipfile).'/';
	!is_dir($destpath) && mkdir($destpath, 0777);
	!is_dir($tmppath) && mkdir($tmppath, 0777);
	$archive->unzip($zipfile, $tmppath);
	foreach($archive->files as $file) {
		// 判断目录是否存在,
		if(strpos($file, '..')) continue; // 安全过滤
		xn_mkdir_by_filename($destpath.$file);
		copy($tmppath.$file, $destpath.$file);
		unlink($tmppath.$file);
	}
	clearstatcache();
	rmdir_recusive($tmppath);
	return $archive->files;
}

// 不支持对 storage 打包
function xn_zip_old($destzip, $srcpath) {
	$conf = _SERVER('conf');
	$tmppath = empty($conf['tmp_path']) ? ini_get('upload_tmp_dir').'/' : $conf['tmp_path'];
	$tmppath == '/' AND $tmppath = './';
	$srcpath = str_replace('\\', '/', $srcpath);
	
	$arr = pathinfo($srcpath);
	$srcpathnew1 = $tmppath.$arr['basename'].'/';
	$srcpathnew2 = $tmppath.$arr['basename'].'/'.$arr['basename'].'/';
	xn_mkdir($srcpathnew1, 0777);
	xn_mkdir($srcpathnew2, 0777);
	copy_recusive($srcpath, $srcpathnew2);
	substr($srcpath, -1, 1) != '/' && $srcpath .= '/';
	$tmpzip = $tmppath.rand(1, 10000000000).'.tmp.zip';
	$archive = new php_zip();
	$archive->zip($srcpathnew1, $tmpzip);
	copy($tmpzip, $destzip);
	unlink($tmpzip);
	clearstatcache();
	rmdir_recusive($srcpathnew1);
	return $archive->files;
}

?>