<?php
/**
 *
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 */
defined('IN_IA') or exit('Access Denied');

if (in_array($action, array('app', 'setting', 'site', 'fans'))) {
	$account_api = WeAccount::createByUniacid();
	if (is_error($account_api)) {
		message($account_api['message'], url('account/display'));
	}
	$check_manange = $account_api->checkIntoManage();

	if (is_error($check_manange)) {
		itoast('', $account_api->displayUrl);
	} else {
		$account_type = $account_api->menuFrame;
		define('FRAME', $account_type);
	}
}

if (in_array($action, array('account'))) {
	define('FRAME', 'system');
}