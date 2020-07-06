<?php

class db_pdo_mysql {
	
	public $conf = array(); // 配置，可以支持主从
	public $rconf = array(); // 选择的从配置
	public $wlink = NULL;  // 写连接
	public $rlink = NULL;  // 读连接
	public $link = NULL;   // 最后一次使用的连接
	public $errno = 0;
	public $errstr = '';
	public $sqls = array();
	public $tablepre = '';
	public $innodb_first = TRUE;// 优先 InnoDB
	
	public function __construct($conf) {
		$this->conf = $conf;
		$this->tablepre = $conf['master']['tablepre'];
	}
	
	// 根据配置文件连接
	public function connect() {
		$this->wlink = $this->connect_master();
		$this->rlink = $this->connect_slave();
		return $this->wlink && $this->rlink;
	}
	
	// 连接写服务器
	public function connect_master() {
		if($this->wlink) return $this->wlink;
		$conf = $this->conf['master'];
		$this->wlink = $this->real_connect($conf['host'], $conf['user'], $conf['password'], $conf['name'], $conf['charset'], $conf['engine']);
		return $this->wlink;
	}
	
	// 连接从服务器，如果有多台，则随机挑选一台，如果为空，则与主服务器一致。
	public function connect_slave() {
		if($this->rlink) return $this->rlink;
		if(empty($this->conf['slaves'])) {
			if($this->wlink === NULL) $this->wlink = $this->connect_master();
			$this->rlink = $this->wlink;
			$this->rconf = $this->conf['master'];
		} else {
			//$n = array_rand($this->conf['slaves']);
			$arr = array_rand($this->conf['slaves'], 1);
			$conf = $this->conf['slaves'][$arr[0]];
			$this->rconf = $conf;
			$this->rlink = $this->real_connect($conf['host'], $conf['user'], $conf['password'], $conf['name'], $conf['charset'], $conf['engine']);
		}
		return $this->rlink;
	}
	
	public function real_connect($host, $user, $password, $name, $charset = '', $engine = '') {
		if(strpos($host, ':') !== FALSE) {
			list($host, $port) = explode(':', $host);
		} else {
			$port = 3306;
		}
		try {
			$attr = array(
				PDO::ATTR_TIMEOUT => 5,
				//PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
			);
			$link = new PDO("mysql:host=$host;port=$port;dbname=$name", $user, $password, $attr);
			//$link->setAttribute(PDO::ATTR_TIMEOUT, 5);
			//$link->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
		} catch (Exception $e) {
			$this->error($e->getCode(), '连接数据库服务器失败:'.$e->getMessage());
			return FALSE;
	        }
	        //$link->setFetchMode(PDO::FETCH_ASSOC);
		$charset AND $link->query("SET names $charset, sql_mode=''");
		 //$link->query('SET NAMES '.($charset ? $charset.',' : '').', sql_mode=""');  
		return $link;
	}
	
	public function sql_find_one($sql) {
		$query = $this->query($sql);
		if(!$query) return $query;
		$query->setFetchMode(PDO::FETCH_ASSOC);
		$r = $query->fetch();
		if($r === FALSE) {
			// $this->error();
			return NULL;
		}
		return $r;
	}
	
	public function sql_find($sql, $key = NULL) {
		$query = $this->query($sql);
		if(!$query) return $query;
		$query->setFetchMode(PDO::FETCH_ASSOC);
		$arrlist = $query->fetchAll();
		$key AND $arrlist = arrlist_change_key($arrlist, $key);
		return $arrlist;
	}
	
	public function find($table, $cond = array(), $orderby = array(), $page = 1, $pagesize = 10, $key = '', $col = array()) {
		$page = max(1, $page);
		$cond = db_cond_to_sqladd($cond);
		$orderby = db_orderby_to_sqladd($orderby);
		$offset = ($page - 1) * $pagesize;
		$cols = $col ? implode(',', $col) : '*';
		return $this->sql_find("SELECT $cols FROM {$this->tablepre}$table $cond$orderby LIMIT $offset,$pagesize", $key);
		
	}
		
	public function find_one($table, $cond = array(), $orderby = array(), $col = array()) {
		$cond = db_cond_to_sqladd($cond);
		$orderby = db_orderby_to_sqladd($orderby);
		$cols = $col ? implode(',', $col) : '*';
		return $this->sql_find_one("SELECT $cols FROM {$this->tablepre}$table $cond$orderby LIMIT 1");
	}
	
	public function query($sql) {
		if(!$this->rlink && !$this->connect_slave()) return FALSE;
		$link = $this->link = $this->rlink;
		try {
			$t1 = microtime(1);
			$query = $link->query($sql);
			$t2 = microtime(1);
			
			$t3 = substr($t2 - $t1, 0, 6);
			//DEBUG AND $GLOBALS['gid'] == 1 AND xn_log("[$t3]".$sql, 'db_sql');
			
		} catch (Exception $e) {  
			$this->error($e->getCode(), $e->getMessage());
			return FALSE;
	        }
		if($query === FALSE) $this->error();
		if(count($this->sqls) < 1000) $this->sqls[] = substr($t2 - $t1, 0, 6).' '.$sql;
		return $query;
	}
	
	public function exec($sql) {
		if(!$this->wlink && !$this->connect_master()) return FALSE;
		$link = $this->link = $this->wlink;
		$n = $t3 = 0;
		try {
			if(strtoupper(substr($sql, 0, 12) == 'CREATE TABLE')) {
				$fulltext = strpos($sql, 'FULLTEXT(') !== FALSE;
				$highversion = version_compare($this->version(), '5.6') >= 0;
				if(!$fulltext || ($fulltext && $highversion)) {
					$conf = $this->conf['master'];
					if(strtolower($conf['engine']) != 'myisam') {
						$this->innodb_first AND $this->is_support_innodb() AND $sql = str_ireplace('MyISAM', 'InnoDB', $sql);
					}
				}
			}
			$t1 = microtime(1);
			$n = $link->exec($sql); // 返回受到影响的行，插入的 id ?
			$t2 = microtime(1);
			
			$t3 = substr($t2 - $t1, 0, 6);
			//DEBUG AND $GLOBALS['gid'] == 1 AND xn_log("[$t3]".$sql, 'db_sql');
			
		} catch (Exception $e) {  
			$this->error($e->getCode(), $e->getMessage());
			return FALSE;
	        }
		if(count($this->sqls) < 1000) $this->sqls[] = "[$t3]".$sql;
		
		if($n !== FALSE) {
			$pre = strtoupper(substr(trim($sql), 0, 7));
			if($pre == 'INSERT ' || $pre == 'REPLACE') {
				return $this->last_insert_id();
			}
		} else {
			$this->error();
		}
		
		return $n;
	}
	
	// innoDB 通过 information_schema 读取大致的行数
	// SELECT TABLE_ROWS FROM information_schema.tables WHERE TABLE_SCHEMA = '$table' AND TABLE_NAME = '$table';
	// SELECT TABLE_ROWS FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = '$table';
	public function count($table, $cond = array()) {
		$this->connect_slave();
		if(empty($cond) && $this->rconf['engine'] == 'innodb') {
			$dbname = $this->rconf['name'];
			$sql = "SELECT TABLE_ROWS as num FROM information_schema.tables WHERE TABLE_SCHEMA='$dbname' AND TABLE_NAME='$table'";
		} else {
			$cond = db_cond_to_sqladd($cond);
			$sql = "SELECT COUNT(*) AS num FROM `$table` $cond";
		}
		$arr = $this->sql_find_one($sql);
		return !empty($arr) ? intval($arr['num']) : $arr;
	}
	
	public function maxid($table, $field, $cond = array()) {
		$sqladd = db_cond_to_sqladd($cond);
		$sql = "SELECT MAX($field) AS maxid FROM `$table` $sqladd";
		$arr = $this->sql_find_one($sql);
		return !empty($arr) ? intval($arr['maxid']) : $arr;
	}
	
	public function truncate($table) {
		return $this->exec("TRUNCATE $table");
	}
	
	public function last_insert_id() {
		return $this->wlink->lastinsertid();
	}
	
	public function version() {
		$r = $this->sql_find_one("SELECT VERSION() AS v");
		return $r['v'];
	}
	
	// 设置错误。
	public function error($errno = 0, $errstr = '') {
		$error = $this->link ? $this->link->errorInfo() : array(0, $errno, $errstr);
		$this->errno = $errno ? $errno : (isset($error[1]) ? $error[1] : 0);
		$this->errstr = $errstr ? $errstr : (isset($error[2]) ? $error[2] : '');
		//DEBUG AND trigger_error('Database Error:'.$this->errstr);
	}
	
	public function is_support_innodb() {
		$arrlist = $this->sql_find('SHOW ENGINES');
		$arrlist2 = arrlist_key_values($arrlist, 'Engine', 'Support');
		return isset($arrlist2['InnoDB']) AND $arrlist2['InnoDB'] == 'YES';
	}

	
	public function close() {
		$this->wlink = NULL;
		$this->rlink = NULL;
		return TRUE;
	}
	
	public function __destruct() {
		if($this->wlink) $this->wlink = NULL;
		if($this->rlink) $this->rlink = NULL;
	}
}

?>