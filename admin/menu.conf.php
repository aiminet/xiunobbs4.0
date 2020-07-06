<?php

return array(
	'setting' => array(
		'url'=>url('setting-base'), 
		'text'=>lang('setting'), 
		'icon'=>'icon-cog', 
		'tab'=> array (
			'base'=>array('url'=>url('setting-base'), 'text'=>lang('admin_setting_base')),
			'smtp'=>array('url'=>url('setting-smtp'), 'text'=>lang('admin_setting_smtp')),
		)
	),
	'forum' => array(
		'url'=>url('forum-list'), 
		'text'=>lang('forum'), 
		'icon'=>'icon-comment',
		'tab'=> array (
		)
	),
	'thread' => array(
		'url'=>url('thread-list'), 
		'text'=>lang('thread'), 
		'icon'=>'icon-comment',
		'tab'=> array (
			'list'=>array('url'=>url('thread-list'), 'text'=>lang('admin_thread_batch')),
		)
	),
	'user' => array(
		'url'=>url('user-list'), 
		'text'=>lang('user'), 
		'icon'=>'icon-user',
		'tab'=> array (
			'list'=>array('url'=>url('user-list'), 'text'=>lang('admin_user_list')),
			'group'=>array('url'=>url('group-list'), 'text'=>lang('admin_user_group')),
			'create'=>array('url'=>url('user-create'), 'text'=>lang('admin_user_create')),
		)
	),
	'other' => array(
		'url'=>url('other'), 
		'text'=>lang('other'), 
		'icon'=>'icon-wrench',
		'tab'=> array (
			'cache'=>array('url'=>url('other-cache'), 'text'=>lang('admin_other_cache')),
		)
	),
	'plugin' => array(
		'url'=>url('plugin'), 
		'text'=>lang('plugin'), 
		'icon'=>'icon-cogs',
		'tab'=> array (
			'local'=>array('url'=>url('plugin-local'), 'text'=>lang('admin_plugin_local_list')),
			'official_free'=>array('url'=>url('plugin-official_free'), 'text'=>lang('admin_plugin_official_free_list')),
			'official_fee'=>array('url'=>url('plugin-official_fee'), 'text'=>lang('admin_plugin_official_fee_list')),
		)
	)
);

?>