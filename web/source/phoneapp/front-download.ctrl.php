<?php
/**
 * 小程序下载
 * [WeEngine System] Copyright (c) 2014 WE7.CC.
 */
defined('IN_IA') or exit('Access Denied');

load()->model('phoneapp');
load()->classs('cloudapi');
load()->classs('uploadedfile');

$dos = array('front_download', 'getpackage');
$do = in_array($do, $dos) ? $do : 'front_download';

$type = $_GPC['type'] == 'ipa' ? 'ipa' : 'apk';

$version_id = intval($_GPC['version_id']);

// 是否是模块打包小程序
$is_module_wxapp = false;
if (!empty($version_id)) {
	$version_info = phoneapp_version($version_id);
	$module = current($version_info['modules']);
}

if ($do == 'front_download') {
	$appurl = $_W['siteroot'].'/app/index.php';
	$uptype = $_GPC['uptype'];
	$account_info = phoneapp_version($version_id);
	$siteurl = $_W['siteroot'].'app/index.php';
	if (!empty($account_info['appdomain'])) {
		$siteurl = $account_info['appdomain'];
	}
	$siteinfo = array(
		'name' => $account_info['name'],
		'm'=> $account_info['modules'][0]['name'],
		'uniacid' => $account_info['uniacid'],
		'acid' => $account_info['acid'],
		'version' => $account_info['version'],
		'siteroot' => $siteurl,
	);
	template('phoneapp/front-download');
}

if ($do == 'getpackage') {
	if (empty($version_id)) {
		itoast('参数错误！', '', '');
	}
	$account_info = phoneapp_version($version_id);
	if (empty($account_info)) {
		itoast('版本不存在！', referer(), 'error');
	}
	if (count($account_info['modules']) == 0) {
		itoast('请先配置模块');
	}
	$m = current($account_info['modules']);
	$m = $m['name'];

	$type = $_GPC['type'] == 'apk' ? 'apk' : 'ipa';
	$result = phoneapp_getpackage(array('m'=>$m, 'type'=>$_GPC['type']));

	if (is_error($result)) {
		itoast($result['message'], '', '');
	} else {
		$filename = $m.'.'.$type;
		header('content-type: application/zip');
		header('content-disposition: attachment; filename='.$filename);
		echo $result['message'];
	}
	exit;
}
