<?php
/**
 * 公众号 - 数据同步
 * [WeEngine System] Copyright (c) 2014 WE7.CC
 */
defined('IN_IA') or exit('Access Denied');

$dos = array('module_link_uniacid', 'search_link_account', 'module_unlink_uniacid');
$do = in_array($do, $dos) ? $do : 'module_link_uniacid';

if ($do == 'module_link_uniacid') {
	if (checksubmit('submit')) {
		$module_name = trim($_GPC['module_name']);
		$uniacid = intval($_GPC['uniacid']);
		if (empty($module_name) || empty($uniacid)) {
			iajax('1', '参数错误！');
		}
		$module = module_fetch($module_name);
		if (empty($module)) {
			iajax('1', '模块不存在！');
		}
		$link_uniacid_table = table('uni_link_uniacid');
		$sub_uniacids = $link_uniacid_table->getSubUniacids($_W['uniacid'], $module_name);
		if (!empty($sub_uniacids)) {
			iajax('1', '模块已被其他账号关联！');
		}
		//记录关联数据
		$link_uniacid_table->fill(array(
			'uniacid' => $_W['uniacid'],
			'link_uniacid' => $uniacid,
			'module_name' => $module_name,
		));
		$main_uniacid = $link_uniacid_table->getMainUniacid($_W['uniacid'], $module_name);
		if (!empty($main_uniacid)) {
			$link_uniacid_table->searchWithUniacidModulenameVersionid($_W['uniacid'], $module_name);
		}
		$link_uniacid_table->save();

		if (!empty($main_uniacid)) {
			cache_clean(cache_system_key('module_setting', array('module_name' => $module_name, 'uniacid' => $main_uniacid)));
		}
		cache_clean(cache_system_key('module_setting', array('module_name' => $module_name, 'uniacid' => $uniacid)));
		cache_build_module_info($module_name);
		iajax(0, '关联成功');
	}

	$modules = uni_modules();
	$link_uniacid_table = table('uni_link_uniacid');
	//1.过滤不支持关联的模块,2.获取已关联模块的uniacid信息,3.获取被关联模块的uniacid信息
	foreach ($modules as $key => $value) {
		if (!empty($value['issystem'])) {
			unset($modules[$key]);
			continue;
		}
		$has_non_other_support = true;
		foreach (module_support_type() as $support => $item) {
			if ($item['type'] == $_W['account']['type_sign'] || $item['type'] == 'welcome') {
				continue;
			}
			if ($value[$support] == $item['support'])  {
				$has_non_other_support = false;
				break;
			}
		}
		if ($has_non_other_support) {
			unset($modules[$key]);
			continue;
		}
		$link_uniacid = $link_uniacid_table->getMainUniacid($_W['uniacid'], $value['name']);
		if (!empty($link_uniacid)) {
			$account = uni_fetch($link_uniacid);
			$modules[$key]['link_uniacid_info'] = $account->account;
			$modules[$key]['link_uniacid_info']['logo'] = $account->logo;
			continue;
		}
		$passive_link_uniacid = $link_uniacid_table->getSubUniacids($_W['uniacid'], $value['name']);
		if (!empty($passive_link_uniacid)) {
			foreach ($passive_link_uniacid as $passive_uniacid) {
				$modules[$key]['other_link'][] = uni_fetch($passive_uniacid);
			}
		}
	}
	template('profile/module-link-uniacid');
}

if ($do == 'module_unlink_uniacid') {
	$module_name = safe_gpc_string(trim($_GPC['module_name']));
	if (empty($module_name)) {
		iajax(-1, '参数错误！');
	}
	$module = module_fetch($module_name);
	if (empty($module)) {
		iajax(-1, '模块不存在！');
	}
	$link_uniacid_table = table('uni_link_uniacid');
	$main_uniacid = $link_uniacid_table->getMainUniacid($_W['uniacid'], $module_name);
	if (empty($main_uniacid)) {
		iajax(0, '删除失败！', referer());
	}
	$result = $link_uniacid_table->searchWithUniacidModulenameVersionid($_W['uniacid'], $module_name)->delete();
	if ($result) {
		cache_delete(cache_system_key('module_setting', array('module_name' => $module_name, 'uniacid' => $main_uniacid)));
		cache_clean(cache_system_key('module_setting', array('module_name' => $module_name, 'uniacid' => $_W['uniacid'])));
		cache_build_module_info($module_name);
		iajax(0, '删除成功！', referer());
	} else {
		iajax(0, '删除失败！', referer());
	}
}

if ($do == 'search_link_account') {
	$module_name = safe_gpc_string($_GPC['module_name']);
	$account_type_sign = safe_gpc_string($_GPC['type_sign']);
	if (empty($module_name) || empty($account_type_sign)) {
		iajax(1, '参数不能为空');
	}
	$module = module_fetch($module_name);
	if (empty($module)) {
		iajax(1, '模块不存在或已删除');
	}

	$all_account_type_sign = uni_account_type_sign();
	if (!empty($_W['account']) && $_W['account']->typeSign != WXAPP_TYPE_SIGN) {
		unset($all_account_type_sign[$_W['account']->typeSign]); //除小程序外,不可关联与自身同类的账号
	}
	if (!in_array($account_type_sign, array_keys($all_account_type_sign))) {
		iajax(1, '账号类型不存在');
	}
	//已关联过其他账号的账号
	$link_sub_uniacids = table('uni_link_uniacid')->getAllSubUniacidsByModuleName($module_name);
	//查找可关联的应用，并删除已关联的
	$account_list = uni_search_link_account($module_name, $account_type_sign, $_W['uniacid']);
	$account_type_info = uni_account_type();
	if (!empty($account_list)) {
		foreach ($account_list as $key => $account) {
			if (in_array($account['uniacid'], $link_sub_uniacids)) {
				unset($account_list[$key]);
				continue;
			}
			$account_list[$key]['type_sign'] = $account_type_info[$account['type']]['type_sign'];
			$account_list[$key]['type_title'] = $account_type_info[$account['type']]['title'];
			$account_list[$key]['logo'] = is_file(IA_ROOT . '/attachment/headimg_' . $account['acid'] . '.jpg') ? tomedia('headimg_'.$account['acid']. '.jpg').'?time='.time() : './resource/images/nopic-107.png';
			$account_list[$key]['module_name'] = $module_name;
		}
	}
	iajax(0, $account_list);
}