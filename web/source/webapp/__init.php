<?php
/**
 *
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 */
defined('IN_IA') or exit('Access Denied');

$account_api = WeAccount::createByUniacid();

if ($action != 'manage' && $do != 'switch') {
	if (is_error($account_api)) {
		message($account_api['message'], url('account/display'));
	}
	$check_manange = $account_api->checkIntoManage();
	if (is_error($check_manange)) {
		itoast('', $account_api->displayUrl);
	}
}

if ($action == 'manage') {
	define('FRAME', '');
} else {
	$account_type = $account_api->menuFrame;
	define('FRAME', $account_type);
}