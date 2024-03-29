<?php
/**
 * 编辑用户
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 */
defined('IN_IA') or exit('Access Denied');

load()->model('user');
load()->func('file');
load()->func('cache');
load()->model('visit');
load()->model('module');

$dos = array('edit_base', 'edit_modules_tpl', 'edit_account', 'edit_users_permission', 'edit_account_dateline', 'edit_create_account_list', 'edit_user_group', 'edit_user_extra_limit', 'edit_user_extra_group', 'edit_uni_groups', 'edit_extra_modules', 'delete_user_group', 'operators');

$do = in_array($do, $dos) ? $do: 'edit_base';

$uid = intval($_GPC['uid']);
$user = user_single($uid);
if (empty($user)) {
	itoast('访问错误, 未找到该操作员.', url('user/display'), 'error');
}
if ($user['status'] != USER_STATUS_NORMAL) {
	itoast('', url('user/display'), 'info');
}

$founders = explode(',', $_W['config']['setting']['founder']);
$profile = pdo_get('users_profile', array('uid' => $uid));
if (!empty($profile)) $profile['avatar'] = tomedia($profile['avatar']);

//编辑用户基础信息
if ($do == 'edit_base') {
	$account_num = permission_user_account_num($uid);
	$user['last_visit'] = date('Y-m-d H:i:s', $user['lastvisit']);
	$user['joindate'] = date('Y-m-d H:i:s', $user['joindate']);
	$user['endtype'] = $user['endtime'] == 0 ? 1 : 2;
	$user['url'] = user_invite_register_url($uid);

	$user['end'] = user_end_time($uid);
	$user['end'] = $user['end'] == 0 ? '永久' : $user['end'];
	$profile = user_detail_formate($profile);
	$extra_fields = user_available_extra_fields();
	template('user/edit-base');
}

if ($do == 'edit_modules_tpl') {
	if ($_W['isajax'] && $_W['ispost']) {
		if ($user['status'] == USER_STATUS_CHECK || $user['status'] == USER_STATUS_BAN) {
			iajax(-1, '访问错误，该用户未审核或者已被禁用，请先修改用户状态！', '');
		}
		if (intval($_GPC['groupid']) == $user['groupid']){
			iajax(2, '未做更改！');
		}

		if (!empty($_GPC['type']) && !empty($_GPC['groupid'])) {
			$data['uid'] = $uid;
			$data['groupid'] = intval($_GPC['groupid']);
			$update_res = user_update($data);
			cache_clean(cache_system_key('user_modules'));
			cache_clean(cache_system_key('unimodules'));

			$user_uni_accounts = uni_user_accounts($uid);
			foreach ($user_uni_accounts as $uni_account_key => $uni_account_val) {
				cache_build_account_modules($uni_account_key, $uid);
			}

			if ($update_res) {
				visit_system_delete($uid);
				if (user_is_vice_founder($uid)) {
					$group_info = user_founder_group_detail_info($data['groupid']);
				} else {
					$group_info = user_group_detail_info($_GPC['groupid']);
				}
				iajax(0, $group_info, '');
			} else {
				iajax(1, '更改失败！', '');
			}
		} else {
			iajax(-1, '参数错误！', '');
		}
	}
	cache_clean(cache_system_key('unimodules'));

	$modules = user_modules($_W['uid']);
	$templates = pdo_getall('site_templates', array(), array('id', 'name', 'title'));

	$groups = user_group();
	$group_info = user_group_detail_info($user['groupid']);

	$extend = array();
	$users_extra_template_table = table('users_extra_templates');
	$user_extend_templates_ids = array_keys($users_extra_template_table->getExtraTemplatesIdsByUid($_GPC['uid']));
	if (!empty($user_extend_templates_ids)) {
		$extend['templates'] = pdo_getall('site_templates', array('id' => $user_extend_templates_ids), array('id', 'name', 'title'));
	}

	if (!empty($templates) && !empty($user_extend_templates_ids)) {
		foreach($templates as $template_key => $template_val) {
			if (in_array($template_val['id'], $user_extend_templates_ids)) {
				$templates[$template_key]['checked'] = 1;
			}
		}
	}

	# 应用权限组(还需要优化，不要全取出来，ajax获取)
	$group_keys = array();
	if (user_is_vice_founder($_W['uid'])) {
		$founder_own_table = table('users_founder_own_uni_groups');
		$founder_own_uni_groups = $founder_own_table->getOwnUniGroupsByFounderUid($_W['uid']);
		$group_keys = array_keys((array)$founder_own_uni_groups);
	}

	$uni_groups = uni_groups($group_keys);
	$users_extra_group_table = table('users_extra_group');
	$user_extra_groups = $users_extra_group_table->getUniGroupsByUid($uid);
	$user_extra_groups = !empty($user_extra_groups) ? uni_groups(array_keys($user_extra_groups)) : array();
	if (!empty($uni_groups)) {
		foreach ($uni_groups as $module_group_key => &$module_group_val) {
			if (!empty($user_extra_groups[$module_group_key])) {
				$module_group_val['checked'] = 1;
			} else {
				$module_group_val['checked'] = 0;
			}
			unset($module_group_val);
		}
	}

	$users_extra_modules_table = table('users_extra_modules');
	$user_extend_modules = $users_extra_modules_table->where('uid', $uid)->getall('id');
	$extra_module_types = array();
	if (!empty($user_extend_modules)) {
		foreach($user_extend_modules as $extend_module_info) {
			$module_info = module_fetch($extend_module_info['module_name']);
			$module_info['support'] = $extend_module_info['support'];
			if (!empty($module_info)) {
				$extend['modules'][] = $module_info;
				$extra_module_types[] = $extend_module_info['module_name'] . $module_info['support'];
			}
		}
	}

	$module_support_type = module_support_type();
	if (!empty($modules)) {
		foreach ($modules as $item) {
			if ($item['issystem'] == 0) {
				foreach ($module_support_type as $module_support_type_key => $module_support_type_val) {
					if ($item[$module_support_type_key] == $module_support_type_val['support']) {
						$item['support'] = $module_support_type_key;
						$item['checked'] = 0;
						$user_modules['modules'][] = $item;
					}
				}
			}
		}
	}

	foreach ($user_modules['modules'] as &$user_module_info) {
		if (in_array($user_module_info['name'] . $user_module_info['support'], $extra_module_types)) {
			$user_module_info['checked'] = 1;
		}
		unset($user_module_info);
	}

	template('user/edit-modules-tpl');
}

if ($do == 'edit_account') {
	$account_detail = user_account_detail_info($uid);
	$account_list = array();
	if (!empty($account_detail)) {
		foreach($account_detail as $account_type => $accounts) {
			foreach ($accounts as $uniacid => $account) {
				$account['type_name'] = $account_type == 'wechats' ? 'account' : $account_type;
				$account_list[$uniacid] = $account;
			}
		}
	}
	template('user/edit-account');
}

if ($do == 'edit_users_permission') {
	if ($_W['isajax'] && $_W['ispost']) {
		$uid = intval($_GPC['uid']);

		$modules = array_unique(safe_gpc_array($_GPC['modules']));
		$templates = safe_gpc_array($_GPC['templates']);

		$users_extra_template_table = table('users_extra_templates');
		$users_extra_modules_table = table('users_extra_modules');
		if (!empty($modules)) {
			$users_extra_modules_table->deleteExtraModulesByUid($uid);
			foreach($modules as $module_name) {
				$users_extra_modules_table->addExtraModule($uid, $module_name);
			}
		}

		if (!empty($templates)) {
			$users_extra_template_table->deleteExtraTemplatesByUid($uid);
			foreach($templates as $template_id) {
				$add_res = $users_extra_template_table->addExtraTemplate($uid, $template_id);
			}
		}

		iajax(0, '修改成功', '');
	}
}

if ($do == 'edit_account_dateline') {
	if (user_is_vice_founder($uid)) {
		$groups = user_founder_group();
		$group_info = table('users_founder_group')->getById($user['groupid']);
	} else {
		$groups = user_group();
		$group_info = table('users_group')->getById($user['groupid']);
	}

	$extra_limit_table = table('users_extra_limit');
	$extra_limit_info = $extra_limit_table->getExtraLimitByUid($uid);

	$endtime = $user['endtime'];
	$total_timelimit = $group_info['timelimit'] + $extra_limit_info['timelimit'];
	$user_end_time_check = $user['endtime'] == strtotime($total_timelimit . ' days', $user['joindate']);
	if (!$user_end_time_check && $group_info['timelimit'] != 0) {
		user_update(array('uid' => $uid, 'endtime' => strtotime($total_timelimit . ' days', $user['joindate'])));
	}

	if (!empty($group_info) && $group_info['timelimit'] == 0) {
		user_update(array('uid' => $uid, 'endtime' => USER_ENDTIME_GROUP_UNLIMIT_TYPE));
	}
	if ($endtime == USER_ENDTIME_GROUP_EMPTY_TYPE || $endtime == USER_ENDTIME_GROUP_UNLIMIT_TYPE) {
		$total_timelimit = '永久';
		$endtime = '永久';
	} elseif ($endtime == USER_ENDTIME_GROUP_DELETE_TYPE && $total_timelimit == 0) {
		$endtime = $total_timelimit == 0 ? date('Y-m-d', $user['joindate']) : date('Y-m-d', $user['endtime']);
	} else {
		$endtime = date('Y-m-d', $endtime);
	}

	template('user/edit-account-dateline');
}

if ($do == 'edit_create_account_list') {
	$uid = intval($_GPC['uid']);
	$user_permission_account = permission_user_account_num($uid);
	if (user_is_vice_founder()) {
		$create_groups = table('users_founder_own_create_groups')->getallGroupsByFounderUid($_W['uid']);
	} else {
		$create_groups = table('users_create_group')->getall();
	}
	$extra_groupids = array();
	if (!empty($user_permission_account['create_groups'])) {
		foreach ($user_permission_account['create_groups'] as $item) {
			$extra_groupids[] = $item['id'];
		}
	}
	foreach ($create_groups as &$group) {
		if (in_array($group['id'], $extra_groupids)) {
			$group['checked'] = 1;
		} else {
			$group['checked'] = 0;
		}
	}
	$create_numbers = array();
	$module_support_type = module_support_type();
	foreach ($module_support_type as $info) {
		if ($info['type'] == WELCOMESYSTEM_TYPE_SIGN) {
			continue;
		}
		$max_type = 'max' . $info['type'];
		$extra_type = 'extra_' . $info['type'];
		$create_numbers[$max_type] = $user_permission_account[$extra_type];
	}
	$create_account = array(
		'create_groups' => $create_groups,
		'create_numbers' => $create_numbers,
	);

	if (user_is_vice_founder($uid)) {
		$user_groups = user_founder_group();
		$group_info = user_founder_group_detail_info($user['groupid']);
	} else {
		$user_groups = user_group();
		$group_info = user_group_detail_info($user['groupid']);
	}

	template('user/edit-create-account-list');
}

if ($do == 'edit_user_group') {
	if ($_W['isajax'] && $_W['ispost']) {
		$user = array(
			'groupid' => intval($_GPC['groupid']),
			'uid' => $uid,
		);
		$res = user_update($user);
		if ($res) {
			iajax(0, '修改成功');
		} else {
			iajax(-1, '修改失败');
		}
	}
}

if ($do == 'edit_user_extra_limit') {
	$extra_limit_table = table('users_extra_limit');
	$extra_limit_info = $extra_limit_table->getExtraLimitByUid($uid);
	$post_timelimit = intval($_GPC['timelimit']);
	$time_limit = $post_timelimit - $extra_limit_info['timelimit'];
	$data = array(
		'timelimit' => $post_timelimit,
	);

	if (user_is_vice_founder()) {
		$permission_check_result = permission_check_vice_founder_limit($data);
		if (is_error($permission_check_result)) {
			iajax(-1, $permission_check_result['message']);
		}
	}

	if ($extra_limit_info) {
		$data['uid'] = $extra_limit_info['uid'];
	}
	if ($_W['isajax'] && $_W['ispost']) {
		$res = $extra_limit_table->saveExtraLimit($data, $uid);
		if ($res) {
			if ($user['endtime'] != USER_ENDTIME_GROUP_EMPTY_TYPE && $user['endtime'] != USER_ENDTIME_GROUP_UNLIMIT_TYPE) {
				$user_endtime = $user['endtime'] == USER_ENDTIME_GROUP_DELETE_TYPE ? $user['joindate'] : $user['endtime'];
				$end_time = strtotime($time_limit . ' days', $user_endtime);
				user_update(array('endtime' => $end_time, 'uid' => $uid));
			}
			iajax(0, '修改成功', url('user/edit/edit_account_dateline', array('uid' => $uid)));
		} else {
			iajax(-1, '修改失败');
		}
	}
}

if ($do == 'edit_user_extra_group') {
	$operate = $_GPC['operate'];
	$extra_group_table = table('users_extra_group');

	if ($operate == 'delete') {
		$group_ids = safe_gpc_array($_GPC['group_ids']);
		$extra_group_table->searchWithUidCreateGroupId($uid, $group_ids)->delete();

	} elseif ($operate == 'extend_group') {
		//附加账号权限组
		$group_ids = safe_gpc_array($_GPC['group_ids']);
		$del_ids = safe_gpc_array($_GPC['del_ids']);
		if (!empty($group_ids)) {
			foreach ($group_ids as $group_id) {
				$extra_group = $extra_group_table->searchWithUidCreateGroupId($uid, $group_id)->get();
				if (!empty($extra_group)) {
					continue;
				}
				$extra_group_table->addExtraCreateGroup($uid, $group_id);
			}
		}
		if (!empty($del_ids)) {
			$extra_group_table->searchWithUidCreateGroupId($uid, $del_ids)->delete();
		}

	} elseif ($operate == 'extend_numbers') {
		$extra_limit_table = table('users_extra_limit');
		$uni_account_types = uni_account_type();
		$uni_account_type_signs = array_keys(uni_account_type_sign());
		foreach ($uni_account_type_signs as $type_sign_name) {
			$max_type = 'max' . $type_sign_name;
			$data[$max_type] = intval($_GPC['numbers'][$max_type]);
		}

		if (user_is_vice_founder()) {
			$permission_check_result = permission_check_vice_founder_limit($data);
			if (is_error($permission_check_result)) {
				iajax(-1, $permission_check_result['message']);
			}
		}

		$extra_limit_info = $extra_limit_table->getExtraLimitByUid($uid);
		if ($extra_limit_info) {
			$data['uid'] = $extra_limit_info['uid'];
		}
		$extra_limit_table->saveExtraLimit($data, $uid);
	}
	iajax(0, '修改成功', referer());
}

/**
 * 修改用户权限组
 */
if ($do == 'edit_uni_groups') {
	$uni_group_ids = $_GPC['uni_groups'];
	$ext_group_table = table('users_extra_group');
	if (!empty($uni_group_ids)) {
		$ext_group_table->where(array('uid' => $uid, 'uni_group_id !=' => 0))->delete();
		foreach ($uni_group_ids as $uni_group_id) {
			$ext_group_table->addExtraUniGroup($uid, $uni_group_id);
		}
	} else {
		$ext_group_table->where(array('uid' => $uid))->delete();
	}
	iajax(0, '修改成功!', referer());
}

/*
 * 修改用户附加权限
 * */
if ($do == 'edit_extra_modules') {
	$extra_modules = $_GPC['extra_modules'];
	$extra_modules_table = table('users_extra_modules');
	$extra_modules_table->where(array('uid' => $uid))->delete();
	foreach ($extra_modules as $module_info) {
		$extra_modules_table->addExtraModule($uid, $module_info['name'], $module_info['support']);
	}

	$templates = $_GPC['extra_templates'];
	$users_extra_template_table = table('users_extra_templates');
	$users_extra_template_table->deleteExtraTemplatesByUid($uid);
	if (!empty($templates)) {
		foreach($templates as $template_id) {
			$users_extra_template_table->addExtraTemplate($uid, $template_id['id']);
		}
	}

	iajax(0, '修改成功!', referer());
}

if ($do == 'delete_user_group') {
	$groupid = intval($_GPC['groupid']);

	if (!user_is_founder($_W['uid'])) {
		itoast('权限错误', referer(), 'error');
	}

	if (user_is_vice_founder($_W['uid'])) {
		$founder_own_users = table('users_founder_own_users')->getFounderOwnUsersList($_W['uid']);
		if (!in_array($uid, array_keys($founder_own_users))) {
			itoast('信息有误', referer(), 'error');
		}
	}

	if (user_is_vice_founder($uid)) {
		$group_info = user_founder_group_detail_info($groupid);
	} else {
		$group_info = user_group_detail_info($groupid);
	}

	if ($user['groupid'] != $groupid) {
		itoast('信息有误', referer(), 'error');
	}

	$user_end_time = $user['endtime'];
	$users_extra_limit_table = table('users_extra_limit');
	$extra_limit_info = $users_extra_limit_table->getExtraLimitByUid($uid);

	if (empty($extra_limit_info)) {
		$result = user_update(array('uid' => $uid, 'groupid' => '', 'endtime' => 1));
	} else {
		$group_info_timelimit = $group_info['timelimit'];
		if ($group_info_timelimit == 0) {
			$end_time = !empty($extra_limit_info) && $extra_limit_info['timelimit'] > 0 ? strtotime($extra_limit_info['timelimit'] . ' days', $user['joindate']) : $user['joindate'];
		} else {
			$end_time = strtotime('-' . $group_info_timelimit . ' days', $user_end_time);
		}

		$result = user_update(array('uid' => $uid, 'groupid' => '', 'endtime' => $end_time));
	}

	if ($result) {
		itoast('修改成功', referer(), 'success');
	} else {
		itoast('修改失败', referer(), 'error');
	}
}

if ($do == 'operators') {
	if ($user['type'] == 3) {
		itoast('', referer());
	}
	$page = max(1, intval($_GPC['page']));
	$username = safe_gpc_string($_GPC['username']);
	$page_size = 15;
	$clerks = array();
	$total = 0;

	$account_table = table('account');
	$account_table->where('c.role', array(ACCOUNT_MANAGE_NAME_OWNER, ACCOUNT_MANAGE_NAME_VICE_FOUNDER));
	$accounts = $account_table->searchAccount(false, 'a.uniacid', 1, $uid)->getall('uniacid');

	if (!empty($accounts)) {
		$permission_table = table('users_permission');
		$permission_table->searchWithPage($page, $page_size);
		$clerks = $permission_table->getClerkPermissionList(array_keys($accounts), '', $username);
		if (!empty($clerks)) {
			$total = $permission_table->getLastQueryTotal();
			$modules_info = array();
			foreach ($clerks as $k => $clerk) {
				$modules_info[$clerk['type']] = module_fetch($clerk['type']);
				$clerks[$k]['permission'] = explode('|', $clerk['permission']);

				if (empty($modules_info[$clerk['type']]['main_module'])) {
					$clerks[$k]['main_module'] = '';
					$clerks[$k]['permission_module'] = $clerk['type'];
				} else {
					$clerks[$k]['main_module'] = $clerks[$k]['permission_module'] = $modules_info[$clerk['type']]['main_module'];
				}
			}
			$accounts_info = pdo_getall('uni_account', array('uniacid' => array_column($clerks, 'uniacid')), array('uniacid','name'), 'uniacid');
			$users_info = pdo_getall('users', array('uid' => array_column($clerks, 'uid')), array('uid','username'), 'uid');
		}
	}
	$pager = pagination($total, $page, $page_size);
	template('user/edit-operatoers');
}
