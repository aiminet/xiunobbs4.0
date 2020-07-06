<?php

// 经过测试 xcache3.1 xcache_set() life 参数不管用
class cache_xcache {
	public $conf = array();
	public $link = NULL;
	public $cachepre = '';
	public $errno = 0;
	public $errstr = '';
	
        public function __construct($conf = array()) {
                if(!function_exists('xcache_set')) {
                        return $this->error(1, 'Xcache 扩展没有加载，请检查您的 PHP 版本');
                }
                $this->conf = $conf;
		$this->cachepre = isset($conf['cachepre']) ? $conf['cachepre'] : 'pre_';
        }
        public function connect() {
        }
        public function set($k, $v, $life) {
                return xcache_set($k, $v, $life);
        }
        // 取不到数据的时候返回 NULL，不是 FALSE
        public function get($k) {
                $r = xcache_get($k);
                if($r === FALSE) $r = NULL;
                return $r;
        }
        public function delete($k) {
                return xcache_unset($k);
        }
        public function truncate() {
                xcache_unset_by_prefix($this->cachepre);
                return TRUE;
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