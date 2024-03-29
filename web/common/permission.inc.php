<?php
/**
 * [WeEngine System] Copyright (c) 2014 WE7.CC
 * WeEngine is NOT a free software, it under the license terms, visited http://www.w7.cc/ for more details.
 */
defined('IN_IA') or exit('Access Denied');

/**
 * 说明（以$we7_file_permission数组下第一个元素account为例）：
 * account  代表  设定/web/source/account文件夹下的权限（即代码中的 $controller 或 $_GPC['c']）
 * account数组下的元素：
 *	'default'	   代表  进入此controller后在没有指定$action（即$_GPC['a']）的情况下，默认进入的文件
 *	'direct'		代表  无需任何权限，可以直接进入的权限
 *	'vice_founder'  代表  副创始人拥有的权限
 *	'owner'		 代表  主管理员拥有的权限
 *	'manager'	   代表  管理员拥有的权限
 *	'operator'	  代表  操作员拥有的权限
 *	'clerk'		 代表  店员拥有的权限
 * 权限中带星号'*'指拥有该文件夹下所有权限
 */
$we7_file_permission = array();
$we7_file_permission = array(
	'account' => array(
		'default' => '',
		'direct' => array(
			'auth',
			'welcome',
		),
		'vice_founder' => array('account*'),
		'owner' => array('account*'),
		'manager' => array(
			'display',
			'manage',
			'post-step',
			'post-user',
			'post',
			'create',
		),
		'operator' => array(
			'display',
			'manage',
			'post-step',
			'create',
		),
		'clerk' => array(
			'display',
		),
		'unbind_user' => array(),
	),
	'advertisement' => array(
		'default' => '',
		'direct' => array(),
		'vice_founder' => array(),
		'owner' => array(),
		'manager' => array(),
		'operator' => array(),
		'clerk' => array(),
		'unbind_user' => array(),
	),
	'article' => array(
		'default' => '',
		'direct' => array(
			'notice-show',
			'news-show',
		),
		'vice_founder' => array(),
		'owner' => array(),
		'manager' => array(),
		'operator' => array(),
		'clerk' => array(),
		'unbind_user' => array(),
	),
	'message' => array(
		'default' => '',
		'direct' => array(
			'notice',
		),
		'vice_founder' => array(),
		'owner' => array(),
		'manager' => array(),
		'operator' => array(),
		'clerk' => array(),
		'unbind_user' => array(),
	),
	'cloud' => array(
		'default' => 'touch',
		'direct' => array(
			'touch',
			'dock',
		),
		'vice_founder' => array(),
		'owner' => array(),
		'manager' => array(),
		'operator' => array(),
		'clerk' => array(),
		'unbind_user' => array(),
	),
	'cron' => array(
		'default' => '',
		'direct' => array(
			'entry',
		),
		'vice_founder' => array(),
		'owner' => array(),
		'manager' => array(),
		'operator' => array(),
		'clerk' => array(),
		'unbind_user' => array(),
	),
	'founder' => array(
		'default' => '',
		'direct' => array(),
		'vice_founder' => array(),
		'owner' => array(),
		'manager' => array(),
		'operator' => array(),
		'clerk' => array(),
		'unbind_user' => array(),
	),
	'help' => array(
		'default' => '',
		'direct' => array(),
		'vice_founder' => array('help*'),
		'owner' => array('help*'),
		'manager' => array('help*'),
		'operator' => array('help*'),
		'clerk' => array(),
		'unbind_user' => array(),
	),
	'home' => array(
		'default' => 'welcome',
		'direct' => array(),
		'vice_founder' => array('home*'),
		'owner' => array('home*'),
		'manager' => array('home*'),
		'operator' => array('home*'),
		'clerk' => array('welcome'),
		'unbind_user' => array(),
		'expired' => array('welcome'),
	),
	'mc' => array(
		'default' => '',
		'direct' => array(),
		'vice_founder' => array('mc*'),
		'owner' => array('mc*'),
		'manager' => array(
			'chats',
			'fields',
			'group',
			'trade',
			'fans',
			'member',
			'message',
		),
		'operator' => array(
			'chats',
			'fields',
			'group',
			'trade',
		),
		'clerk' => array(),
		'unbind_user' => array(),
	),
	'module' => array(
		'default' => '',
		'direct' => array(),
		'vice_founder' => array('module*'),
		'owner' => array(
			'manage-account',
			'manage-system',
			'permission',
			'display',
			'welcome',
			'link',
			'link-account',
			'shortcut',
			'plugin',
		),
		'manager' => array(
			'manage-account',
			'display',
			'welcome',
			'link',
			'link-account',
			'shortcut',
			'plugin',
		),
		'operator' => array(
			'manage-account',
			'display',
			'welcome',
			'link',
			'link-account',
			'shortcut',
			'plugin',
		),
		'clerk' => array(
			'display',
			'manage-account',
			'welcome',
			'link',
			'link-account',
			'shortcut',
			'plugin',
		),
		'unbind_user' => array(),
	),
	'platform' => array(
		'default' => '',
		'direct' => array(),
		'vice_founder' => array('platform*'),
		'owner' => array('platform*'),
		'manager' => array(
			'cover',
			'reply',
			'menu',
			'qr',
			'mass',
			'material',
			'material-post',
			'url2qr',
		),
		'operator' => array(
			'cover',
			'reply',
			'material',
			'material-post',
			'url2qr',
		),
		'clerk' => array(
			'reply',
			'cover',
			'material',
			'material-post'
		),
		'unbind_user' => array(),
	),
	'profile' => array(
		'default' => '',
		'direct' => array(),
		'vice_founder' => array('profile*'),
		'owner' => array('profile*'),
		'manager' => array(
			'remote',
			'passport',
			'tplnotice',
			'notify',
			'common',
			'payment',
			'refund',
			'module-link-uniacid',
			'bind-domain',
		),
		'operator' => array(),
		'clerk' => array(),
		'unbind_user' => array('profile*'),
	),
	'site' => array(
		'default' => '',
		'direct' => array(
			'entry',
		),
		'vice_founder' => array('site*'),
		'owner' => array('site*'),
		'manager' => array(
			'editor',
			'article',
			'category',
			'style',
			'nav',
			'slide',
			'multi',
			'comment',
		),
		'operator' => array(
			'editor',
			'article',
			'category',
			'style',
			'nav',
			'slide',
		),
		'clerk' => array(
			'nav',
		),
		'unbind_user' => array(),
	),
	'statistics' => array(
		'default' => '',
		'direct' => array(),
		'vice_founder' => array('statistics*'),
		'owner' => array('statistics*'),
		'manager' => array(
			'app',
			'site',
			'setting',
			'fans',
		),
		'operator' => array(),
		'clerk' => array(),
		'unbind_user' => array(),
	),
	'store' => array(
		'default' => '',
		'direct' => array(),
		'vice_founder' => array(
			'goods-buyer',
			'orders',
		),
		'owner' => array(
			'goods-buyer',
			'orders',
		),
		'manager' => array(
			'goods-buyer',
			'orders',
		),
		'operator' => array(
			'goods-buyer',
			'orders',
		),
		'clerk' => array(),
		'unbind_user' => array(),
		'expired' => array(
			'goods-buyer',
			'orders',
		)
	),
	'system' => array(
		'default' => '',
		'direct' => array(),
		'vice_founder' => array(
			'template',
			'updatecache',
			'attachment',
		),
		'owner' => array(
			'updatecache',
		),
		'manager' => array(
			'updatecache',
		),
		'operator' => array(
			'account',
			'updatecache',
		),
		'clerk' => array(),
		'unbind_user' => array(
			'updatecache',
		),
		'expired' => array(
			'updatecache',
		)
	),
	'user' => array(
		'default' => 'display',
		'direct' => array(
			'login',
			'register',
			'logout',
			'find-password',
			'third-bind',
			'agreement-show'
		),
		'vice_founder' => array('user*'),
		'owner' => array(
			'profile',
		),
		'manager' => array(
			'profile',
		),
		'operator' => array(
			'profile',
		),
		'clerk' => array(
			'profile',
		),
		'unbind_user' => array(
			'profile',
		),
		'expired' => array(
			'user*',
		)
	),
	'miniapp' => array(
		'default' => '',
		'direct' => array(),
		'vice_founder' => array('miniapp*'),
		'owner' => array('miniapp*'),
		'manager' => array(
			'display',
			'version',
			'post',
		),
		'operator' => array(
			'display',
			'version',
			'post',
		),
		'clerk' => array(
			'display',
			'version',
			'post',
		),
		'unbind_user' => array(),
	),
	'wxapp' => array(
		'default' => '',
		'direct' => array(),
		'vice_founder' => array('wxapp*'),
		'owner' => array('wxapp*'),
		'manager' => array(
			'display',
			'version',
			'post',
			'auth',
		),
		'operator' => array(
			'display',
			'version',
			'post',
			'auth',
		),
		'clerk' => array(
			'display',
		),
		'unbind_user' => array(),
	),
	'webapp' => array(
		'default' => '',
		'direct' => array(),
		'vice_founder' => array('webapp*'),
		'owner' => array('webapp*'),
		'manager' => array(
			'home',
			'manage',
		),
		'operator' => array(
			'home',
			'manage'
		),
		'clerk' => array(
			'home'
		),
		'unbind_user' => array(),
	),
	'xzapp' => array(
		'default' => '',
		'direct' => array(),
		'vice_founder' => array('xzapp*'),
		'owner' => array('xzapp*'),
		'manager' => array(),
		'operator' => array(
			'post-step',
			'create',
		),
		'clerk' => array(),
		'unbind_user' => array(),
	),
	'phoneapp' => array(
		'default' => '',
		'direct' => array(),
		'vice_founder' => array('phoneapp*'),
		'owner' => array('phoneapp*'),
		'manager' => array(
			'display',
			'manage',
			'version'
		),
		'operator' => array(
			'display',
			'manage',
			'version'
		),
		'clerk' => array(
			'display'
		),
	),
	'utility' => array(
		'default' => '',
		'direct' => array(
			'verifycode',
			'code',
			'file',
			'bindcall',
			'subscribe',
			'wxcode',
			'modules',
			'link',
			'visit'
		),
		'vice_founder' => array(
			'user',
			'emulator',
		),
		'owner' => array(
			'emulator',
		),
		'manager' => array(
			'emulator',
		),
		'operator' => array(
			'emulator',
		),
		'unbind_user' => array(),
	),
	'append' => array('append*'),
	'see_more_info' => array(
		'founder' => array(
			'see_module_manage_system_except_installed',
			'see_module_manage_system_ugrade',
			'see_module_manage_system_stop',
			'see_module_manage_system_install',
			'see_module_manage_system_shopinfo',
			'see_module_manage_system_info_edit',
			'see_module_manage_system_detailinfo',
			'see_module_manage_system_group_add',
			'see_module_manage_system_welcome_support',
			'see_account_post_modules_tpl_edit_store_endtime',
			'see_account_manage_module_tpl_all_permission',
			'see_account_manage_sms_blance',
			'see_account_manage_users_edit_vicefounder',
			'see_account_manage_users_edit_owner',
			'see_account_manage_users_set_permission_for_manager',
			'see_account_manage_users_set_permission_for_operator',
			'see_account_manage_users_addmanager',
			'see_account_manage_users_delmanager',
			'see_account_manage_users_deloperator',
			'see_account_manage_users_adduser',
			'see_account_manage_users_add_viceuser',
			'see_account_manage_display',
			'see_account_manage_recycle',
			'see_account_manage_system_platform',
			'see_account_manage_expired_message',
			'see_permission_create_account_group',
			'see_permission_module_group',
			'see_permission_user_group',
			'see_permission_founder_group',
			'see_system_upgrade',
			'see_system_updatecache',
			'see_system_manage_clerk',
			'see_system_manage_vice_founder',
			'see_system_add_vice_founder',
			'see_notice_post',
			'see_message_official',
			'see_message_order',
			'see_message_register',
			'see_message_worker',
			'see_module_manage_system_newversion',
			'see_user_edit_base_founder_name',
			'see_user_create_own_vice_founder',
			'see_user_profile_edit_username',
			'see_user_manage_display',
			'see_user_manage_founder',
			'see_user_manage_clerk',
			'see_user_manage_check',
			'see_user_manage_recycle',
			'see_user_manage_fields',
			'see_user_manage_expire',
			'see_workorder',
			'see_modules_deactivate',
		),
		'vice_founder' => array(
			'see_account_manage_users_adduser',
			'see_account_manage_users_edit_owner',
			'see_account_manage_users_set_permission_for_manager',
			'see_account_manage_users_set_permission_for_operator',
			'see_account_manage_users_deloperator',
			'see_account_manage_users_delmanager',
			'see_account_manage_display',
			'see_account_manage_recycle',
			'see_module_manage_system_group_add',
			'see_permission_create_account_group',
			'see_permission_module_group',
			'see_permission_user_group',
			'see_user_add_welcome_account',
			'see_user_profile_account_num',
			'see_user_manage_display',
			'see_user_manage_check',
			'see_user_manage_recycle',
			'see_system_updatecache',
		),
		'owner' => array(
			'see_system_updatecache',
			'see_account_manage_users_set_permission_for_manager',
			'see_account_manage_users_set_permission_for_operator',
			'see_account_manage_users_deloperator',
			'see_account_manage_users_delmanager',
			'see_account_manage_display',
			'see_account_manage_recycle',
			'see_modules_recharge',
			'see_user_add_welcome_account',
			'see_user_profile_account_num',
		),
		'manager' => array(
			'see_account_manage_users_set_permission_for_operator',
			'see_account_manage_users_deloperator',
			'see_account_manage_display',
			'see_user_profile_welcome',
			'see_system_updatecache',
			'see_user_add_welcome_account',
			'see_user_profile_account_num',
		),
		'operator' => array(
			'see_account_manage_display',
			'see_user_profile_welcome',
			'see_system_updatecache',
			'see_user_add_welcome_account',
			'see_user_profile_account_num',
		),
		'clerk' => array(

		),
		'unbind_user' => array(
			'see_user_profile_welcome',
			'see_system_updatecache',
		),
		'expired' => array(),
	),
);

return $we7_file_permission;