<?php
/**
 * 用户注册设置
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 */
defined('IN_IA') or exit('Access Denied');
load()->model('setting');

$dos = array('display', 'clerk');
$do = in_array($do, $dos) ? $do : 'display';

$copyright = $_W['setting']['copyright'];
$settings = $_W['setting']['register'];
if (empty($settings['clerk'])) {
	$settings['clerk'] = array('verify' => 0);
}
if (empty($copyright['clerk'])) {
	$copyright['clerk'] = array('bind' => 'null');
}
if (checksubmit('submit')) {
	$is_copyright = false;
	switch ($_GPC['key']) {
		case 'open':
			$settings['open'] = intval($_GPC['value']);
			break;
		case 'verify':
			$settings['verify'] = intval($_GPC['value']);
			break;
		case 'code':
			$settings['code'] = intval($_GPC['value']);
			break;
		case 'groupid':
			$settings['groupid'] = intval($_GPC['value']);
			break;
		case 'safe':
			$settings['safe'] = intval($_GPC['value']);
			break;
		case 'agreement_status':
			$settings['agreement_status'] = intval($_GPC['value']);
			break;
		case 'mobile_status':
			$copyright['mobile_status'] = intval($_GPC['value']);
			$is_copyright = true;
			break;
		case 'verifycode':
			$copyright['verifycode'] = intval($_GPC['value']);
			$is_copyright = true;
			break;
		case 'refused_login_limit':
			$copyright['refused_login_limit'] = intval($_GPC['value']);
			$is_copyright = true;
			break;
		case 'clerk_verify':
			$settings['clerk']['verify'] = intval($_GPC['value']);
			break;
		case 'clerk_bind':
			$copyright['clerk']['bind'] = safe_gpc_string($_GPC['value']);
			$copyright['clerk']['bind'] = $copyright['clerk']['bind'] == 'null' ? '' : $copyright['clerk']['bind'];
			$is_copyright = true;
			break;
	}
	if ($is_copyright) {
		setting_save($copyright, 'copyright');
	} else {
		setting_save($settings, 'register');
		cache_delete(cache_system_key('defaultgroupid', array('uniacid' => $_W['uniacid'])));
	}
	iajax(0, '更新设置成功！', referer());
}

if ($do == 'display') {
	$settings['mobile_status'] = $copyright['mobile_status'];

	$groups = user_group();
	if (empty($groups)) {
		$groups = array(array('id' => 0, 'name' => '请选择所属用户组'));
	} else {
		array_unshift($groups, array('id' => 0, 'name' => '请选择所属用户组'));
	}

	$group = array();
	foreach ($groups as $item) {
		if ($item['id'] == $settings['groupid']) {
			$group = $item;
			break;
		}
	}
}

if ($do == 'clerk') {
	$binds = array(array('name' => '无', 'id' => 'null'));
	foreach (OAuth2Client::supportBindTypeInfo() as $info) {
		$binds[] = array('name' => $info['title'], 'id' => $info['type']);
	}
	$bind = array();
	foreach ($binds as $item) {
		if ($item['id'] == $copyright['clerk']['bind']) {
			$bind = $item;
			break;
		}
	}
	if (empty($bind)) {
		$bind = array('name' => '无', 'id' => 'null');
	}
}

template('user/registerset');