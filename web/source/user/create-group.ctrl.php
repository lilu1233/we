<?php
/**
 * 帐号权限组管理
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 */
defined('IN_IA') or exit('Access Denied');

$dos = array('display', 'delete', 'post', 'save');
$do = !empty($_GPC['do']) ? $_GPC['do'] : 'display';

$account_group_table = table('users_create_group');
if ($do == 'display') {
	$pageindex = max(1, intval($_GPC['page']));
	$pagesize = 10;

	$condition = '';
	$params = array();
	$group_name = safe_gpc_string($_GPC['group_name']);

	if (!empty($group_name)) {
		$account_group_table->searchLikeGroupName($group_name);
	}

	$account_group_table->searchWithPage($pageindex, $pagesize);
	$lists = $account_group_table->getCreateGroupList();
	$total = $account_group_table->getLastQueryTotal();
	$pager = pagination($total, $pageindex, $pagesize);

	

	template('user/create-group-display');
}

if ($do == 'post') {
	$id = intval($_GPC['id']);
	if (!empty($id)) {
		$account_group_info = $account_group_table->getCreateGroupInfoById($id);
	}
	$account_all_type = uni_account_type();
	$account_all_type_sign = array_keys(uni_account_type_sign());
	if (checksubmit('submit')) {
		$user_account_group = array(
			'id' => intval($_GPC['id']),
			'group_name' => safe_gpc_string($_GPC['group_name']),
		);
		$max_type_all = 0;
		foreach ($account_all_type_sign as $account_type) {
			$maxtype = 'max' . $account_type;
			$user_account_group[$maxtype] = intval($_GPC[$maxtype]);
			$max_type_all += $_GPC[$maxtype];
		}

		if ($max_type_all <= 0) {
			itoast('至少能创建一个账号!', '', '');
		}

		$res = user_save_create_group($user_account_group);

		if (is_error($res)) {
			itoast($res['message'], '', '');
		}
		itoast('操作成功!', url('user/create-group/display'), '');
	}

	template('user/create-group-post');
}

if ($do == 'del') {
	$id = intval($_GPC['id']);
	$res = $account_group_table->deleteById($id);
	table('users_founder_own_create_groups')->where('create_group_id', $id)->delete();
	$url = url('user/create-group/display');
	$msg = $res ? '成功' : '失败';

	itoast('操作' . $msg, $url);
}

