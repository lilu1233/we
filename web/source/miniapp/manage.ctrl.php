<?php
/**
 * 管理公众号
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 */
defined('IN_IA') or exit('Access Denied');

define('FRAME', 'system');
load()->model('system');
load()->model('miniapp');

$dos = array('display', 'edit_version', 'del_version');
$do = in_array($do, $dos) ? $do : 'display';

$uniacid = intval($_GPC['uniacid']);
if (empty($uniacid)) {
	itoast('请选择要编辑的小程序', referer(), 'error');
}

$state = permission_account_user_role($_W['uid'], $uniacid);
$role_permission = in_array($state, array(ACCOUNT_MANAGE_NAME_OWNER, ACCOUNT_MANAGE_NAME_FOUNDER, ACCOUNT_MANAGE_NAME_MANAGER, ACCOUNT_MANAGE_NAME_VICE_FOUNDER));
if (!$role_permission) {
	itoast('无权限操作！', referer(), 'error');
}

$account = uni_fetch($uniacid);
if (is_error($account) || empty($account['type'])) {
	itoast($account['message'], url('account/manage'), 'error');
}

if ($do == 'display') {
	$miniapp_info = pdo_get($account_all_type[$account['type']]['table_name'], array('uniacid' => $account['uniacid']));
	$version_exist = miniapp_fetch($account['uniacid']);
	if (!empty($version_exist)) {
		$version_lists = miniapp_version_all($uniacid);
		if (!empty($version_lists)) {
			foreach ($version_lists as &$row) {
				if (!empty($row['modules'])) {
					$row['module']['module_info'] = current($row['modules']);
				}
				if (!empty($row['last_modules'])) {
					$row['last_modules'] = current($row['last_modules']);
					$module = module_fetch($row['last_modules']['name']);
					if (!empty($module)) {
						$row['last_modules'] = array_merge($module, $row['last_modules']);
					}
				}
				if (empty($row['last_modules'])) {
					$row['last_modules'] = $row['module'];
				}
			}
			unset($row);
		}

		$miniapp_modules = miniapp_support_uniacid_modules($uniacid);
	}
	template('miniapp/manage');
}

if ($do == 'edit_version') {
	$versionid = intval($_GPC['version_id']);
	$module_name = safe_gpc_string($_GPC['name']);

	if (empty($module_name)) {
		iajax(1, '模块名不可为空！');
	}
	if (empty($versionid)) {
		iajax(1, '版本号不可为空!');
	}
	$module_info = module_fetch($module_name);
	if (empty($module_info)) {
		iajax(1, '模块不存在！');
	}
	$version_exist = miniapp_fetch($uniacid, $versionid);
	if(empty($version_exist)) {
		iajax(1, '版本不存在或已删除！');
	}
	$miniapp_modules = miniapp_support_uniacid_modules($uniacid);
	$supoort_modulenames = array_keys($miniapp_modules);
	if (!in_array($module_name, $supoort_modulenames)) {
		iajax(1, '没有模块：' . $module_info['title'] . '的权限！');
	}

	$new_module_data = array();
	$new_module_data[$module_name] = array(
		'name' => $module_name,
		'version' => $module_info['version']
	);
	pdo_update($account_all_type[$account['type']]['version_tablename'], array('modules' => iserializer($new_module_data)), array('id' => $versionid, 'uniacid' => $uniacid));
	$version_module = current($version_exist['version']['modules']);
	if (!empty($version_module['uniacid']) && !empty($version_module['name'])) {
		table('uni_link_uniacid')->searchWithUniacidModulenameVersionid($uniacid, $version_module['name'], $versionid)->delete();
	}
	cache_delete(cache_system_key('miniapp_version', array('version_id' => $versionid)));
	iajax(0, '修改成功！', referer());
}

if ($do == 'del_version') {
	$versionid = intval($_GPC['versionid']);
	if (empty($versionid)) {
		iajax(1, '参数错误！');
	}
	$version_exist = miniapp_fetch($uniacid, $versionid);
	if (empty($version_exist)) {
		iajax(1, '模块版本不存在！');
	}
	$version_module = current($version_exist['version']['modules']);
	if (!empty($version_module['name'])) {
		table('uni_link_uniacid')->searchWithUniacidModulenameVersionid($uniacid, $version_module['name'], $versionid)->delete();
	}
	$result = pdo_delete($account_all_type[$account['type']]['version_tablename'], array('id' => $versionid, 'uniacid' => $uniacid));
	if (!empty($result)) {
		iajax(0, '删除成功！', referer());
	} else {
		iajax(1, '删除失败，请稍候重试！');
	}
}