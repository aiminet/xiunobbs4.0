<?php

class cache_redis {
	
	public $conf = array();
	public $link = NULL;
	public $cachepre = '';
	public $errno = 0;
	public $errstr = '';
	
        public function __construct($conf = array()) {
                if(!extension_loaded('Redis')) {
                        return $this->error(-1, ' Redis 扩展没有加载');
                }
                $this->conf = $conf;
		$this->cachepre = isset($conf['cachepre']) ? $conf['cachepre'] : 'pre_';
        }
        public function connect() {
                if($this->link) return $this->link;
                $redis = new Redis;
                $r = $redis->connect($this->conf['host'], $this->conf['port']);
                if(!$r) {
                        return $this->error(-1, '连接 Redis 服务器失败。');
                }
                //$redis->select('xn');
                $this->link = $redis;
                return $this->link;
        }
        public function set($k, $v, $life = 0) {
                if(!$this->link && !$this->connect()) return FALSE;
                $v = xn_json_encode($v);
                $r = $this->link->set($k, $v);
                $life AND $r AND $this->link->expire($k, $life);
                return $r;
        }
        public function get($k) {
                if(!$this->link && !$this->connect()) return FALSE;
                $r = $this->link->get($k);
                return $r === FALSE ? NULL : xn_json_decode($r);
        }
        public function delete($k) {
                if(!$this->link && !$this->connect()) return FALSE;
                return $this->link->del($k) ? TRUE : FALSE;
        }
        public function truncate() {
                if(!$this->link && !$this->connect()) return FALSE;
                return $this->link->flushdb(); // flushall
        }
        public function error($errno = 0, $errstr = '') {
		$this->errno = $errno;
		$this->errstr = $errstr;
		DEBUG AND trigger_error('Cache Error:'.$this->errstr);
	}
        public function __destruct() {

        }
}

?>