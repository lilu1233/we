<?php
/**
 * 欢迎页，统计等信息
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 */
defined('IN_IA') or exit('Access Denied');

load()->model('welcome');
load()->model('module');
load()->model('system');
load()->model('miniapp');
load()->model('message');
load()->model('visit');
load()->model('cloud');

$dos = array('platform', 'system', 'ext', 'account_ext', 'get_fans_kpi', 'get_system_upgrade', 'get_upgrade_modules', 'get_module_statistics', 'get_ads', 'get_not_installed_modules', 'system_home', 'set_top', 'set_default', 'add_welcome', 'ignore_update_module', 'get_workerorder', 'add_welcome_shortcut', 'remove_welcome_shortcut', 'build_account_modules');
$do = in_array($do, $dos) ? $do : 'platform';

if ($_W['highest_role'] == ACCOUNT_MANAGE_NAME_CLERK && in_array($do, array('platform', 'system_home', 'system'))) {
	itoast('', url('module/display'));
}
if ($_W['highest_role'] == ACCOUNT_MANAGE_NAME_EXPIRED && in_array($do, array('system_home'))) {
	cache_updatecache();
	itoast('', url('user/profile'));
}

if ($do == 'get_not_installed_modules') {
	$not_installed_modules = module_uninstall_list();
	iajax(0, $not_installed_modules);
}


	if ($do == 'ext') {
		if (!empty($_GPC['version_id'])) {
			$version_info = miniapp_version($_GPC['version_id']);
		}
		$account_api = WeAccount::createByUniacid();
		if (is_error($account_api)) {
			message($account_api['message'], url('account/display'));
		}
		$check_manange = $account_api->checkIntoManage();
		if (is_error($check_manange)) {
			itoast('', $account_api->displayUrl);
		}
	}



if ($do == 'platform') {
	if (empty($_W['account'])) {
		itoast('账号信息有误!', url('account/manage'), 'info');
	}
	if (!empty($_W['account']['endtime']) && $_W['account']['endtime'] != USER_ENDTIME_GROUP_EMPTY_TYPE && $_W['account']['endtime'] != USER_ENDTIME_GROUP_UNLIMIT_TYPE && $_W['account']['endtime'] < time() && !user_is_founder($_W['uid'], true)) {
		itoast('平台账号已到服务期限，请联系管理员并续费', url('account/manage'), 'info');
	}
	//公告
	$notices = welcome_notices_get();
	template('home/welcome');
}

if ($do == 'system') {
	if(!$_W['isfounder'] || user_is_vice_founder()){
		header('Location: ' . url('home/welcome/system_home'));
		exit;
	}
	$reductions = system_database_backup();
	if (!empty($reductions)) {
		$last_backup = array_shift($reductions);
		$last_backup_time = $last_backup['time'];
		$backup_days = welcome_database_backup_days($last_backup_time);
	} else {
		$backup_days = 0;
	}
	$today_start = strtotime(date('Y-m-d',time()));
	$yestoday_start = strtotime(date('Y-m-d',strtotime("-1 days")));

	$statistics_order['today'] = table('store')->getStatisticsOrderInfoByDate($today_start, $today_start + 86400);
	$statistics_order['yestoday'] = table('store')->getStatisticsOrderInfoByDate($yestoday_start, $yestoday_start + 86400);

	$system_check = cache_load(cache_system_key('system_check'));
	template('home/welcome-system');
}

if ($do =='get_module_statistics') {
	$install_modules = module_installed_list();

	$module_statistics = array(
		'account' => array(
			'total' => array(
				'uninstall' => module_uninstall_total('account'),
				'upgrade' => module_upgrade_total('account'),
				'all' => 0
			),
		),
		'wxapp' => array(
			'total' => array(
				'uninstall' => module_uninstall_total('wxapp'),
				'upgrade' => module_upgrade_total('wxapp'),
				'all' => 0,
			)
		),
	);

	//因权限问题，用户所分配的模块不同，所以此处直接count安装列表
	$module_statistics['account']['total']['all'] = $module_statistics['account']['total']['uninstall'] + count((array)$install_modules['account']);
	$module_statistics['wxapp']['total']['all'] = $module_statistics['wxapp']['total']['uninstall'] + count((array)$install_modules['wxapp']);

	iajax(0, $module_statistics, '');
}

if ($do == 'ext') {
	$uniacid = intval($_GPC['uniacid']);
	$modulename = $_GPC['m'];
	if (!empty($modulename)) {
		$_W['current_module'] = module_fetch($modulename);
	}

	define('IN_MODULE', $modulename);
	if ($_GPC['system_welcome'] && $_W['isfounder']) {
		define('SYSTEM_WELCOME_MODULE', true);
		$frames = buildframes('system_welcome');
	} else {

		//模块插件不进系统给定的模块欢迎页（非开发者module.php中的模块欢迎页）
		if ($modulename != 'store' && empty($_W['current_module']['main_module']) && module_get_direct_enter_status($modulename) != STATUS_ON || $_GPC['tohome'] == STATUS_ON) {
			itoast('', url('module/welcome', array('m' => $modulename, 'uniacid' => $uniacid)));
		}
		//统一进模块欢迎页,后面的代码暂时保留(先不要删)
		$site = WeUtility::createModule($modulename);
		if (!is_error($site)) {
			$method = 'welcomeDisplay';
			if(method_exists($site, $method)){
				define('FRAME', 'module_welcome');
				$entries = module_entries($modulename, array('menu', 'home', 'profile', 'shortcut', 'cover', 'mine'));
				$site->$method($entries);
				exit;
			}
		}
		$frames = buildframes('account');
	}

	foreach ($frames['section'] as $secion) {
		foreach ($secion['menu'] as $menu) {
			if (!empty($menu['url'])) {
				if ($menu['module_welcome_display'] && !empty($_W['current_module']['main_module'])) {
					continue;
				}
				header('Location: ' . $_W['siteroot'] . 'web/' . $menu['url']);
				exit;
			}
		}
	}

	template('home/welcome-ext');
}

if ($do == 'account_ext') {
	$modulename = $_GPC['m'];
	if (!empty($modulename)) {
		$module_info = module_fetch($modulename);
	}
	if (empty($module_info)) {
		itoast('抱歉，你操作的模块不能被访问！');
	}
	$link_uniacid = table('uni_link_uniacid')->getMainUniacid($_W['uniacid'], $modulename, intval($_GPC['version_id']));
	$redirect_uniacid = empty($link_uniacid) ? $_W['uniacid'] : $link_uniacid;
	switch_save_account_display($redirect_uniacid);

	template('home/welcome-ext');
}

if ($do == 'get_fans_kpi') {
	uni_update_week_stat();
	//今日昨日指标
	$yesterday = date('Ymd', strtotime('-1 days'));
	$yesterday_stat = pdo_get('stat_fans', array('date' => $yesterday, 'uniacid' => $_W['uniacid']));
	$yesterday_stat['new'] = intval($yesterday_stat['new']);
	$yesterday_stat['cancel'] = intval($yesterday_stat['cancel']);
	$yesterday_stat['jing_num'] = intval($yesterday_stat['new']) - intval($yesterday_stat['cancel']);
	$yesterday_stat['cumulate'] = intval($yesterday_stat['cumulate']);
	//今日粉丝详情
	$today_stat = pdo_get('stat_fans', array('date' => date('Ymd'), 'uniacid' => $_W['uniacid']));
	$today_stat['new'] = intval($today_stat['new']);
	$today_stat['cancel'] = intval($today_stat['cancel']);
	$today_stat['jing_num'] = $today_stat['new'] - $today_stat['cancel'];
	$today_stat['cumulate'] = intval($today_stat['jing_num']) + $yesterday_stat['cumulate'];
	$today_stat['cumulate'] = max(0, $today_stat['cumulate']);

	iajax(0, array('yesterday' => $yesterday_stat, 'today' => $today_stat, 'all' => $today_stat['cumulate']), '');
}

if ($do == 'get_system_upgrade') {
	//系统更新信息
	$upgrade = welcome_get_cloud_upgrade();
	iajax(0, $upgrade, '');
}

if ($do == 'get_upgrade_modules') {
	$module_support_types = module_support_type();
	//可升级应用
	module_upgrade_info();
	$upgrade_modules = module_upgrade_list();

	if (!empty($_GPC['unstall'])) {
		$module_cloud_table = table('modules_cloud');
		$module_cloud_table->where('install_status', array(MODULE_LOCAL_UNINSTALL, MODULE_CLOUD_UNINSTALL));
		$module_cloud_table->orderby('buytime', 'desc');
		$module_cloud_table->orderby('lastupdatetime', 'asc');
		$unstall_moudle_list = $module_cloud_table->getall('name');
		if (!empty($unstall_moudle_list)) {
			foreach ($unstall_moudle_list as $module_key => &$module_val) {
				$module_val['unstall'] = 1;
			}
		}
		$upgrade_modules = array_merge($upgrade_modules, $unstall_moudle_list);
	}

	if (!empty($upgrade_modules)) {
		//模块停用删除数据
		$modulenames = array();
		foreach ($upgrade_modules as $module) {
			if (!empty($module['name']) && !in_array($module['name'], $modulenames)) {
				$modulenames[] = $module['name'];
			}
		}
		$module_recycle_support = array();
		if ($modulenames) {
			$modules_recycle = table('modules_recycle')->getByName($modulenames, '');
			if (!empty($modules_recycle)) {
				foreach ($modules_recycle as $info) {
					foreach ($module_support_types as $support => $value) {
						if (empty($module_recycle_support[$info['name']][$support])) {
							$module_recycle_support[$info['name']][$support] = $info[$support];
						}
					}
				}
			}
		}
		//unset 数据中已停用删除的支持
		foreach ($upgrade_modules as $key => $module) {
			$is_unset = true;
			foreach ($module_support_types as $support => $value) {
				if (!empty($module_recycle_support[$module['name']][$support])) {
					$module[$support] = $value['not_support'];
				}
				if ($module[$support] == $value['support']) {
					$is_unset = false;
				}
			}
			if ($is_unset) {
				unset($upgrade_modules[$key]);
			}
		}
	}
	iajax(0, $upgrade_modules, '');
}

if ($do == 'get_ads') {
	$ads = welcome_get_ads();
	if (is_error($ads)) {
		iajax(1, $ads['message']);
	} else {
		iajax(0, $ads);
	}
}

if ($do == 'system_home') {
	$user_info = user_single($_W['uid']);
	$account_num = permission_user_account_num($_W['uid']);
	$redirect_urls = array(
		array('id' => WELCOME_DISPLAY_TYPE, 'name' => '用户欢迎页'),
		array('id' => PLATFORM_DISPLAY_TYPE, 'name' => '平台入口'),
		array('id' => MODULE_DISPLAY_TYPE, 'name' => '应用入口'),
	);

	$user_founder_info = table('users_founder_own_users')->getFounderByUid($user_info['uid']);

	$uni_modules_table = table('uni_modules');

	$uni_modules_table->searchGroupbyModuleName();
	$own_account_modules_all = $uni_modules_table->getModulesByUid($_W['uid']);

	if (!empty($own_account_modules_all)) {
		foreach($own_account_modules_all['modules'] as $key => &$value) {
			$module_info = module_fetch($value['module_name']);
			$value['title'] = $module_info['title'];
			$value['logo'] = tomedia($module_info['logo']);
			$value['checked'] = 0;
			$own_account_modules_all['modules'][$key] = $value;

			if ($value['role'] == ACCOUNT_MANAGE_NAME_CLERK || $value['role'] == ACCOUNT_MANAGE_NAME_OPERATOR) {
				$user_permission_table = table('users_permission');
				$operator_modules_permissions = $user_permission_table->getAllUserModulePermission($_W['uid'], $value['uniacid']);

				$user_module_permission_info = $user_permission_table->getUserPermissionByType($_W['uid'], $value['uniacid'], $value['module_name']);
				if (!$user_module_permission_info && !empty($operator_modules_permissions)) {
					unset($own_account_modules_all['modules'][$key]);
				}
			}

			unset($value);
		}
	}

	$core_menu_shortcut_table = table('core_menu_shortcut');
	$user_welcome_modules = $core_menu_shortcut_table->getUserWelcomeShortcutList($_W['uid']);

	$last_accounts = array();
	$last_modules = array();
	if (!empty($user_welcome_modules)) {
		foreach ($user_welcome_modules as $info) {
			if (empty($info['uniacid'])) {
				continue;
			}
			if (!in_array($info['modulename'], array_column($own_account_modules_all['modules'], 'module_name'))) {
				continue;
			}
			$last_modules_account_info = uni_fetch($info['uniacid']);
			if (is_error($last_modules_account_info) || $last_modules_account_info['isdeleted'] == 1) {
				continue;
			}
			if (!empty($info['modulename'])) {
				$info['module'] = module_fetch($info['modulename']);
				$info['module']['switchurl'] = url('module/display/switch', array('module_name' => $info['modulename']));
				$info['account'] = $last_modules_account_info;
				$last_modules[$info['modulename']] = $info;
			}else {
				$info['account'] = $last_modules_account_info;
				$last_accounts[$info['uniacid']] = $info;
 			}
		}
	}

	# 如果常用平台为空,默认添加5条
	if (empty($last_accounts)) {
		$is_empty_accounts = 1;
		$uni_account_users_table = table('uni_account_users');
		$uni_account_users_table->searchWithPage(1, 5);
		$uni_account_users_table->searchWithUserRole(ACCOUNT_MANAGE_NAME_OWNER);
		$user_accounts = $uni_account_users_table->getUsableAccountsByUid($_W['uid']);
		if (!empty($user_accounts)) {
			foreach ($user_accounts as $user_account_info) {
				$account_info['account'] = uni_fetch($user_account_info['uniacid']);
				if ($account_info['account']->typeSign == WXAPP_TYPE_SIGN) {
					$version_info = miniapp_version_all($user_account_info['uniacid']);
					if (empty($version_info)) {
						continue;
					}
					foreach ($version_info as $version_key => $version_val) {
						$last_version_modules = $version_val['last_modules'] ? current($version_val['last_modules']) : array();
					}
					$modules = current($version_info[0]['modules']);
					$account_info['account']['need_upload'] = $last_version_modules['version'] < $modules['version'] ? 1 : 0;
				}
				if ($account_info['account']['isdeleted']) {
					continue;
				}
				$core_menu_shortcut_table->saveUserWelcomeShortcut($_W['uid'], $user_account_info['uniacid'], '');
				$last_accounts[] = $account_info;
			}
		}
	} else {
		$is_empty_accounts = 0;
	}

	# 如果常用应用为空,默认添加5条
	if (empty($last_modules) && !empty($own_account_modules_all['modules'])) {
		$is_empty_modules = 1;
		$i = 0;
		foreach ($own_account_modules_all['modules'] as $m_key => $m_val) {
			if ($i >= 5) {
				break;
			}
			$core_menu_shortcut_table->saveUserWelcomeShortcut($_W['uid'], $m_val['uniacid'], $m_val['module_name']);
			$module_info['module'] = module_fetch($m_val['module_name']);
			$module_info['module']['switchurl'] = url('module/display/switch', array('module_name' => $m_val['module_name']));
			$module_info['account'] = uni_fetch($m_val['uniacid']);
			$last_modules[$m_val['module_name']] = $module_info;
			$i++;
		}
	} else {
		$is_empty_modules = 0;
	}

	# 模块默认账号
	$default_module_list = user_lastuse_module_default_account();
	if (!empty($last_modules) && !empty($default_module_list)) {
		foreach ($last_modules as $last_module_key => &$last_module_val) {
			if (in_array($last_module_key, array_keys($default_module_list))) {
				$last_module_val['default_uniacid'] = $default_module_list[$last_module_key]['default_uniacid'];
				$last_module_val['default_account_name'] = $default_module_list[$last_module_key]['default_account_name'];
				$last_module_val['default_account_info'] = uni_fetch($last_module_val['default_uniacid']);
			}
		}
	}

	$types = array(MESSAGE_ACCOUNT_EXPIRE_TYPE, MESSAGE_WECHAT_EXPIRE_TYPE, MESSAGE_WEBAPP_EXPIRE_TYPE, MESSAGE_USER_EXPIRE_TYPE, MESSAGE_WXAPP_MODULE_UPGRADE);
	$messages = pdo_getall('message_notice_log', array('uid' => $_W['uid'], 'type' => $types, 'is_read' => MESSAGE_NOREAD), array(), '', array('id desc'), 10);
	$messages = message_list_detail($messages);
	$notices = welcome_notices_get();

	template('home/welcome-system-home');
}

if ($do == 'set_top') {
	$id = intval($_GPC['id']);
	$displayorder = pdo_get('core_menu_shortcut', array('position' => 'home_welcome_system_common'), 'MAX(displayorder)');
	$displayorder = current($displayorder);

	$update_data['displayorder'] = ++$displayorder;
	$update_data['updatetime'] = TIMESTAMP;
	pdo_update('core_menu_shortcut', $update_data, array('id' => $id));
	iajax(0, '', referer());
}

if ($do == 'add_welcome_shortcut') {
	$core_menu_shortcut_table = table('core_menu_shortcut');
	$shortcuts = safe_gpc_array($_GPC['shortcuts']);

	if (!empty($shortcuts)) {
		foreach ($shortcuts as $shortcut_info) {
			$modulename = empty($shortcut_info['module_name']) ? '' : $shortcut_info['module_name'];
			$core_menu_shortcut_table->saveUserWelcomeShortcut($_W['uid'], $shortcut_info['uniacid'], $modulename);
		}
	}
}

if ($do == 'remove_welcome_shortcut') {
	$core_menu_shortcut_table = table('core_menu_shortcut');
	$uniacid = intval($_GPC['uniacid']);
	$module_name = safe_gpc_string($_GPC['module_name']);
	$module_name = empty($module_name) ? '' : $module_name;
	pdo_delete('core_menu_shortcut', array('uid' => $_W['uid'], 'uniacid' => $uniacid, 'modulename' => $module_name, 'position' => 'home_welcome_system_common'));
}

if ($do == 'set_default') {
	$uniacid = intval($_GPC['uniacid']);
	$module_name = safe_gpc_string($_GPC['module_name']);
	switch_save_module($uniacid, $module_name);
	iajax(0, '', '');
}

if ($do == 'ignore_update_module') {
	if (empty($_GPC['name'])) {
		iajax(1, '参数错误');
	}
	$module_info = module_fetch($_GPC['name']);
	if (empty($module_info)) {
		iajax(1, '参数错误');
	}
	$upgrade_version = table('modules_cloud')->getByName($module_info['name']);
	table('modules_ignore')->add($module_info['name'], $upgrade_version['version']);
	iajax(0, '');
}

if ($do == 'get_workerorder') {
	$workorder_info = cloud_workorder();
	iajax(0, $workorder_info, '');
}

if ($do == 'build_account_modules') {
	cache_build_account_modules($_W['uniacid']);
	iajax(0, '');
}