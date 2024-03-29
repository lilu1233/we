<?php
/**
 *
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 */
defined('IN_IA') or exit('Access Denied');

if (!empty($_GPC['uniacid']) && intval($_GPC['uniacid']) != $_W['uniacid']) {
	itoast('', url('account/display/switch', array('uniacid' => intval($_GPC['uniacid']), 'version_id' => intval($_GPC['version_id']))));
}

if (!in_array($action, array('display', 'manage'))) {
	$account_api = WeAccount::createByUniacid();
	if (is_error($account_api)) {
		message($account_api['message'], url('account/display', array('type' => PHONEAPP_TYPE_SIGN)));
	}
	$check_manange = $account_api->checkIntoManage();
	if (is_error($check_manange)) {
		itoast('', $account_api->displayUrl);
	}
}

if ($action == 'manage') {
	define('FRAME', '');
}

if (in_array($action, array('description', 'front-download'))) {
	$account_type = $account_api->menuFrame;
	define('FRAME', $account_type);
}