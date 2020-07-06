<?php

/*
# 持久的 key value 数据存储
DROP TABLE IF EXISTS bbs_kv;
CREATE TABLE bbs_kv (
  k char(32) NOT NULL default '',
  v mediumtext NOT NULL,
  expiry int(11) unsigned NOT NULL default '0',		# 过期时间
  PRIMARY KEY(k)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
*/
class cache_mysql {
	
	public $conf = array();
	public $db = NULL;
	public $link = NULL;
	public $table = 'cache';
	public $cachepre = '';
	public $errno = 0;
	public $errstr = '';
	
        public function __construct($dbconf = array()) {
        	
        	// 可以复用全局的 $db
                if(is_object($dbconf['db'])) {
                        $this->db = $dbconf['db']; // 可以直接传 $db 进来
                } else {
                        $this->conf = $dbconf;
                        $this->db = db_new($dbconf);
                }
		$this->cachepre = isset($dbconf['cachepre']) ? $dbconf['cachepre'] : 'pre_';
        }
        public function connect() {
        	return db_connect($this->db);
        }
        public function set($k, $v, $life = 0) {
                $time = time();
                $expiry = $life ? $time + $life : 0;
                $arr= array(
                	'k'=>$k,
                	'v'=>xn_json_encode($v),
                	'expiry'=>$expiry,
                );
                $r = db_replace($this->table, $arr, $this->db);
                if($r === FALSE) {
                	$this->errno = $this->db->errno;
                	$this->errstr = $this->db->errstr;
                	return FALSE;
                }
                return $r !== FALSE;
        }
        public function get($k) {
                $time = time();
                $arr = db_find_one($this->table, array('k'=>$k), array(), array(), $this->db);
                // 如果表不存在，则建立表 pre_cache
                if($arr === FALSE) {
                	$this->errno = $this->db->errno;
                	$this->errstr = $this->db->errstr;
                	return FALSE;
                }
                if(!$arr) return NULL;
                if($arr['expiry'] && $time > $arr['expiry']) {
                	db_delete($this->table, array('k'=>$k), $this->db);
                        return NULL;
                }
                return xn_json_decode($arr['v'], 1);
        }
        public function delete($k) {
        	$r = db_delete($this->table, array('k'=>$k), $this->db);
        	if($r === FALSE) {
                	$this->errno = $this->db->errno;
                	$this->errstr = $this->db->errstr;
                	return FALSE;
                }
                return empty($r) ? FALSE : TRUE;
        }
        public function truncate() {
        	$r = db_truncate($this->table, $this->db);
        	if($r === FALSE) {
                	$this->errno = $this->db->errno;
                	$this->errstr = $this->db->errstr;
                	return FALSE;
                }
                return TRUE;
        }
        public function error($errno, $errstr) {
        	$this->errno = $errno;
        	$this->errstr = $errstr;
        }
        public function __destruct() {

        }
}
?>