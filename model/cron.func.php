<?php

// hook model_cron_start.php

// 计划任务
function cron_run($force = 0) {
	// hook model_cron_run_start.php
	global $conf, $time, $forumlist, $runtime;
	$cron_1_last_date = runtime_get('cron_1_last_date');
	$cron_2_last_date = runtime_get('cron_2_last_date');
	
	$t = $time - $cron_1_last_date;
	
	// 每隔 5 分钟执行一次的计划任务
	if($t > 300 || $force) {
		$lock = cache_get('cron_lock_1');
		if($lock === NULL) {
			cache_set('cron_lock_1', 1, 10); // 设置 10 秒超时
			
			sess_gc($conf['online_hold_time']);
			
			$runtime['onlines'] = max(1, online_count());
			
			runtime_set('cron_1_last_date', $time);
			
			// hook model_cron_5_minutes_end.php
			
			cache_delete('cron_lock_1');
		}
	}
	
	// 每日 0 点执行一次的计划任务
	$t = $time - $cron_2_last_date;
	if($t > 86400 || $force) {
		
		$lock = cache_get('cron_lock_2'); // 高并发下, mysql 机制实现的锁锁不住，但是没关系
		if($lock === NULL) {
			cache_set('cron_lock_2', 1, 10); // 设置 10 秒超时
			
			// 每日统计清 0
			runtime_set('todayposts', 0);
			runtime_set('todaythreads', 0);
			runtime_set('todayusers', 0);
			
			foreach($forumlist as $fid=>$forum) {
				forum__update($fid, array('todayposts'=>0, 'todaythreads'=>0));
			}
			forum_list_cache_delete();
			
			// 清理临时附件
			attach_gc();
			
			// 清理过期的队列数据
			queue_gc();
			
			list($y, $n, $d) = explode(' ', date('Y n j', $time)); 	// 0 点
			$today = mktime(0, 0, 0, $n, $d, $y);			// -8 hours
			runtime_set('cron_2_last_date', $today, TRUE);		// 加到1天后
			
			// 往前推8个小时，尽量保证在前一天
			// 如果是升级过来和采集的数据，这里会很卡。
			// table_day_cron($time - 8 * 3600);
			
			// hook model_cron_daily_end.php
			
			cache_delete('cron_lock_2');
		}
		
	}
	// hook model_cron_run_end.php
}



// hook model_cron_end.php

?>