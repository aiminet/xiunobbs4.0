<?php

!defined('DEBUG') AND exit('Access Denied.');

$action = param(1);

// 不允许删除的版块 / system keeped forum
$system_forum = array(1);

// hook admin_forum_start.php

if(empty($action) || $action == 'list') {
	
	// hook admin_forum_list_get_post.php
	
	if($method == 'GET') {
		
		// hook admin_forum_list_get_start.php
		
		$header['title']        = lang('forum_admin');
		$header['mobile_title'] = lang('forum_admin');
	
		$maxfid = forum_maxid();
		
		// hook admin_forum_list_get_end.php
		
		include _include(ADMIN_PATH."view/htm/forum_list.htm");
	
	} elseif($method == 'POST') {
		
		$fidarr = param('fid', array(0));
		$namearr = param('name', array(''));
		$rankarr = param('rank', array(0));
		$iconarr = param('icon', array(''));
		
		// hook admin_forum_list_post_start.php
		
		$arrlist = array();
		foreach($fidarr as $k=>$v) {
			$arr = array(
				'fid'=>$k,
				'name'=>array_value($namearr, $k),
				'rank'=>array_value($rankarr, $k)
			);
			
			if(!isset($forumlist[$k])) {
				// hook admin_forum_list_add_before.php
				forum_create($arr);
			} else {
				// hook admin_forum_list_update_before.php
				forum_update($k, $arr);
			}
			// icon
			if(!empty($iconarr[$k])) {
				
				$s = array_value($iconarr, $k);
				$data = substr($s, strpos($s, ',') + 1);
				$data = base64_decode($data);
				
				$iconfile = "../upload/forum/$k.png";
				file_put_contents($iconfile, $data);
				
				forum_update($k, array('icon'=>$time));
			}
			
			// hook admin_forum_list_post_loop_end.php
		}
		
		// 删除 / delete
		$deletearr = array_diff_key($forumlist, $fidarr);
		foreach($deletearr as $k=>$v) {
			if(in_array($k, $system_forum)) continue;
			// hook admin_forum_list_delete_before.php
			forum_delete($k);
			// hook admin_forum_list_delete_end.php
		}
		
		forum_list_cache_delete();
		
		// hook admin_forum_list_post_end.php
		
		
		
		message(0, lang('save_successfully'));
	}

} elseif($action == 'update') {
	
	$_fid = param(2, 0);
	$_forum = forum_read($_fid);
	empty($_forum) AND message(-1, lang('forum_not_exists'));
	
	// hook admin_forum_update_get_post.php
	
	if($method == 'GET') {
		
		$header['title']        = lang('forum_edit');
		$header['mobile_title'] = lang('forum_edit');
	
		// hook admin_forum_update_get_start.php
		
		$accesslist = forum_access_find_by_fid($_fid);
		
		if(empty($accesslist)) {
			foreach($grouplist as $group) {
				$accesslist[$group['gid']] = $group; // 字段名相同，直接覆盖。 / same field, directly overwrite
			}
		} else {
			foreach($accesslist as &$access) {
				$access['name'] = $grouplist[$access['gid']]['name']; // 字段名相同，直接覆盖。 / same field, directly overwrite
			}
		}
		array_htmlspecialchars($_forum);
		
		$input = array();
		$input['name'] = form_text('name', $_forum['name']);
		$input['rank'] = form_text('rank', $_forum['rank']);
		$input['brief'] = form_textarea('brief', $_forum['brief'], '100%', 80);
		$input['announcement'] = form_textarea('announcement', $_forum['announcement'], '100%', 80);
		$input['accesson'] = form_checkbox('accesson', $_forum['accesson']);
		$input['modnames'] = form_text('modnames', user_ids_to_names($_forum['moduids']));
		
		// hook admin_forum_update_get_end.php
		
		
		include _include(ADMIN_PATH."view/htm/forum_update.htm");
	
	} elseif($method == 'POST') {	
		
		$name = param('name');
		$rank = param('rank', 0);
		$brief = param('brief', '', FALSE);
		$announcement = param('announcement', '', FALSE);
		$modnames = param('modnames');
		$accesson = param('accesson', 0);
		$moduids = user_names_to_ids($modnames);
		
		// hook admin_forum_update_post_start.php
		
		$arr = array (
			'name' => $name,
			'rank' => $rank,
			'brief' => $brief,
			'announcement' => $announcement,
			'moduids' => $moduids,
			'accesson' => $accesson,
		);

		// hook admin_forum_update_post_before.php
		
		forum_update($_fid, $arr);
		
		if($accesson) {
			$allowread = param('allowread', array(0));
			$allowthread = param('allowthread', array(0));
			$allowpost = param('allowpost', array(0));
			$allowattach = param('allowattach', array(0));
			$allowdown = param('allowdown', array(0));
			foreach($grouplist as $_gid=>$v) {
				$access = array (
					'allowread'=>array_value($allowread, $_gid, 0),
					'allowthread'=>array_value($allowthread, $_gid, 0),
					'allowpost'=>array_value($allowpost, $_gid, 0),
					'allowattach'=>array_value($allowattach, $_gid, 0),
					'allowdown'=>array_value($allowdown, $_gid, 0),
				);
				forum_access_replace($_fid, $_gid, $access);
			}
		} else {
			forum_access_delete_by_fid($_fid);
		}
		
		
		
		// hook admin_forum_update_post_end.php
		
		forum_list_cache_delete();
		
		message(0, lang('edit_sucessfully'));	
	}

// 废弃
} elseif($action == 'getname') {
	
	$uids = xn_urldecode(param(2));
	$arr = explode(',', $uids);
	$names = array();
	$err = '';
	
	// hook admin_forum_getname_start.php
	
	foreach($arr as $_uid) {
		$_uid = intval($_uid);
		if(empty($_uid)) continue;
		$_user = user_read($_uid);
		if(empty($_user)) { $err .= lang('item_not_exists', array('item'=>$_uid)); continue; }
		if($_user['gid'] > 4) { $err .= lang('item_not_moderator', array('item'=>$_uid));  continue; }
		$names[] = $_user['username'];
	}
	$s = implode(',', $names);
	$err AND message(-1, $err);
	
	// hook admin_forum_getname_end.php
	
	message(0, $s);
	
} elseif($action == 'delete') {
	
	$_fid = param(2, 0);
	$_forum = forum_read($_fid);
	empty($_forum) AND message(-1, lang('forum_not_exists'));
	
	in_array($_fid, $system_forum) AND message(-1, 'Not allowed');;
	
	// hook admin_forum_delete_start.php
	
	$threadlist = thread_find_by_fid($_fid, 1, 20);
	if(!empty($threadlist)) {
		message(-1, lang('forum_delete_thread_before_delete_forum'));
	}
	
	$sublist = forum_find_son_list($forumlist, $_fid);
	if(!empty($sublist)) {
		message(-1, lang('forum_please_delete_sub_forum'));
	}
	
	forum_delete($_fid);
	
	forum_list_cache_delete();
	
	// hook admin_forum_delete_end.php
	
	message(0, lang('forum_delete_successfully'));
	
}

function user_names_to_ids($names, $sep = ',') {
	$namearr = explode($sep, $names);
	$r = array();
	foreach($namearr as $name) {
		$user = user_read_by_username($name);
		if(empty($user)) continue;
		$r[] = $user ? $user['uid'] : 0;
	}
	return implode($sep, $r);
}

function user_ids_to_names($ids, $sep = ',') {
	$idarr = explode($sep, $ids);
	$r = array();
	foreach($idarr as $id) {
		$user = user_read($id);
		if(empty($user)) continue;
		$r[] = $user ? $user['username'] : '';
	}
	return implode($sep, $r);
}

// hook admin_forum_end.php

?>