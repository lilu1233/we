<?php
/**
 * 验证手机号
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 */
defined('IN_IA') or exit('Access Denied');

load()->model('user');
load()->model('setting');
load()->model('utility');
$dos = array('valid_mobile', 'set_password', 'success');
$do = in_array($do, $dos) ? $do : '';

$setting_sms_sign = setting_load('site_sms_sign');
$find_password_sign = !empty($setting_sms_sign['site_sms_sign']['find_password']) ? $setting_sms_sign['site_sms_sign']['find_password'] : '';

if (!empty($do)) {
	$mobile = safe_gpc_string($_GPC['receiver']);
	$find_mobile = user_check_mobile($mobile);
	if (is_error($find_mobile)) {
		iajax($find_mobile['errno'], $find_mobile['message']);
	}
}


if ($do == 'valid_mobile') {
	iajax(0, '手机号正确.');
}

if ($do == 'set_password') {
	if ($_W['isajax'] && $_W['ispost']) {
		$code = safe_gpc_string($_GPC['code']);
		if (empty($code)) {
			iajax(-1, '短信验证码不能为空');
		}

		$verify_res = utility_smscode_verify(0, $mobile, $code);
		if (is_error($verify_res)) {
			iajax($verify_res['errno'], $verify_res['message']);
		}

		$password = safe_gpc_string($_GPC['password']);
		$repassword = safe_gpc_string($_GPC['repassword']);
		if (empty($password) || empty($repassword)) {
			iajax(-1, '密码不能为空');
		}

		if ($password != $repassword) {
			iajax(-1, '两次密码不一致');
		}

		$user_info = user_single($find_mobile['uid']);
		$password = user_password($password, $find_mobile['uid']);
		if ($password == $user_info['password']) {
			iajax(-2, '密码未做更改');
		}
		$result = pdo_update('users', array('password' => $password), array('uid' => $user_info['uid']));
		if ($result) {
			iajax(0, '设置密码成功');
		} else {
			iajax(-1, '密码设置失败！');
		}
	}
}
template('user/find-password');