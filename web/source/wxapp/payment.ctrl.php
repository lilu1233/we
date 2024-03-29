<?php
/**
 * 支付参数配置
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 */
defined('IN_IA') or exit('Access Denied');

$dos = array('get_setting', 'display', 'save_setting');
$do = in_array($do, $dos) ? $do : 'display';
permission_check_account_user('wxapp_payment_pay');

$pay_setting = miniapp_payment_param();
$wxapp_info = miniapp_fetch($_W['uniacid']);

if ($do == 'get_setting') {
	iajax(0, $pay_setting, '');
}

if ($do == 'display') {
	if (empty($pay_setting) || empty($pay_setting['wechat'])) {
		$pay_setting = array(
			'wechat' => array('mchid' => '', 'signkey' => '')
		);
	}
}

if ($do == 'save_setting') {
	if (!$_W['isajax'] || !$_W['ispost']) {
		iajax(-1, '非法访问');
	}
	$type = $_GPC['type'];
	if ($type != 'wechat') {
		iajax(-1, '参数错误');
	}
	$param = $_GPC['param'];
	$param['account'] = $_W['acid'];
	$pay_setting[$type] = $param;
	$payment = iserializer($pay_setting);
	uni_setting_save('payment', $payment);
	iajax(0, '设置成功', url('account/display', array('do' => 'switch', 'uniacid' => $_W['uniacid'])));
}
template('wxapp/payment');