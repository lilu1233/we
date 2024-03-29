<?php
/**
 * 创建小程序
 * [WeEngine System] Copyright (c) 2014 WE7.CC.
 */
defined('IN_IA') or exit('Access Denied');

load()->model('permission');
load()->model('module');
load()->func('communication');
load()->classs('wxapp.platform');

$dos = array('design_method', 'post', 'get_wxapp_modules', 'module_binding');
$do = in_array($do, $dos) ? $do : 'post';
$account_info = permission_user_account_num($_W['uid']);


if ($do == 'design_method') {
	// 1 普通小程序  2 授权小程序
	$choose = isset($_GPC['choose_type']) ? intval($_GPC['choose_type']) : 0;
	$uniacid = intval($_GPC['uniacid']);
	if ($choose) {
		template('wxapp/design-method');
	} else {
		if (!permission_user_account_creatable($_W['uid'], WXAPP_TYPE_SIGN)) {
			$authurl = "javascript:alert('创建小程序已达上限！');";
		}
		if (empty($authurl) && !empty($_W['setting']['platform']['authstate'])) {
			$account_platform = new WxappPlatform();
			$authurl = $account_platform->getAuthLoginUrl();
		}
		template('wxapp/choose-type');
	}
}
if ($do == 'post') {
	$uniacid = intval($_GPC['uniacid']);
	$design_method = intval($_GPC['design_method']);
	$create_type = intval($_GPC['create_type']);
	$is_submit = checksubmit('submit');
	if (empty($unicid) && !permission_user_account_creatable($_W['uid'], WXAPP_TYPE_SIGN)) {
		$is_submit ? iajax(-1, '创建的小程序已达上限') : itoast('创建的小程序已达上限！', '', '');
	}

	$version_id  = intval($_GPC['version_id']);
	$isedit  =  $version_id > 0 ? 1 : 0;
	if ($isedit) {
		$wxapp_version = miniapp_version($version_id);
	}
	if (empty($design_method)) {
		$is_submit ? iajax(-1, '请先选择要添加小程序类型') : itoast('请先选择要添加小程序类型', referer(), 'error');
	}
	if ($design_method == WXAPP_TEMPLATE) {
		$is_submit ? iajax(-1, '拼命开发中。。。') : itoast('拼命开发中。。。', referer(), 'info');
	}

	if ($is_submit) {
		if ($design_method == WXAPP_TEMPLATE && empty($_GPC['choose']['modules'])) {
			iajax(2, '请选择要打包的模块应用', url('wxapp/post'));
		}
		if (!preg_match('/^[0-9]{1,2}\.[0-9]{1,2}(\.[0-9]{1,2})?$/', trim($_GPC['version']))) {
			iajax('-1', '版本号错误，只能是数字、点，数字最多2位，例如 1.1.1 或1.2');
		}
		//新建小程序公众号
		if (empty($uniacid)) {
			if (empty($_GPC['name'])) {
				iajax(1, '请填写小程序名称', url('wxapp/post'));
			}
			$account_wxapp_data = array(
				'name' => trim($_GPC['name']),
				'description' => trim($_GPC['description']),
				'original' => trim($_GPC['original']),
				'level' => 1,
				'key' => trim($_GPC['appid']),
				'secret' => trim($_GPC['appsecret']),
				'type' => ACCOUNT_TYPE_APP_NORMAL,
				'headimg' => file_is_image( $_GPC['headimg']) ?  $_GPC['headimg'] : '',
				'qrcode' => file_is_image( $_GPC['qrcode']) ?  $_GPC['qrcode'] : '',
			);
			$uniacid = miniapp_create($account_wxapp_data);

			$unisettings['creditnames'] = array('credit1' => array('title' => '积分', 'enabled' => 1), 'credit2' => array('title' => '余额', 'enabled' => 1));
			$unisettings['creditnames'] = iserializer($unisettings['creditnames']);
			$unisettings['creditbehaviors'] = array('activity' => 'credit1', 'currency' => 'credit2');
			$unisettings['creditbehaviors'] = iserializer($unisettings['creditbehaviors']);
			$unisettings['uniacid'] = $uniacid;
			pdo_insert('uni_settings', $unisettings);

			if (is_error($uniacid)) {
				iajax(3, '添加小程序信息失败', url('wxapp/post'));
			}
		} else {
			$wxapp_info = miniapp_fetch($uniacid);
			if (empty($wxapp_info)) {
				iajax(4, '小程序不存在或是已经被删除', url('wxapp/post'));
			}
		}

		//小程序版本信息，打包多模块时，每次更改需要重建版本
		//打包单模块时，每添加一个模块算是一个版本
		$wxapp_version = array(
			'uniacid' => $uniacid,
			'multiid' => '0',
			'description' => trim($_GPC['description']),
			'version' => trim($_GPC['version']),
			'modules' => '',
			'design_method' => $design_method,
			'quickmenu' => '',
			'createtime' => TIMESTAMP,
			'template' => $design_method == WXAPP_TEMPLATE ? intval($_GPC['choose']['template']) : 0,
			//是否公众号应用 1 是 0默认小程序
			'type' => 0
		);
		
		//多模块打包，每个版本对应一个微官网
		if ($design_method == WXAPP_TEMPLATE) {
			$multi_data = array(
				'uniacid' => $uniacid,
				'title' => $account_wxapp_data['name'],
				'styleid' => 0,
			);
			pdo_insert('site_multi', $multi_data);
			$wxapp_version['multiid'] = pdo_insertid();
		}

		//打包模块
		if (!empty($_GPC['choose']['modules'])) {
			$select_modules = array();
			foreach ($_GPC['choose']['modules'] as $post_module) {
				$module = module_fetch($post_module['module']);
				if (empty($module)) {
					continue;
				}

				$select_modules[$module['name']] = array('name' => $module['name'],
					'newicon' => $post_module['newicon'],
					'version' => $module['version'], 'defaultentry'=>$post_module['defaultentry']);
			}

			$wxapp_version['modules'] = serialize($select_modules);
		}

		//快捷菜单
		if (!empty($_GPC['quickmenu'])) {
			$quickmenu = array(
				'color' => $_GPC['quickmenu']['bottom']['color'],
				'selected_color' => $_GPC['quickmenu']['bottom']['selectedColor'],
				'boundary' => $_GPC['quickmenu']['bottom']['boundary'],
				'bgcolor' => $_GPC['quickmenu']['bottom']['bgcolor'],
				'show' => $_GPC['quickmenu']['show'] == 'true' ? 1 : 0,
				'menus' => array(),
			);
			if (!empty($_GPC['quickmenu']['menus'])) {

				foreach ($_GPC['quickmenu']['menus'] as $row) {
					$quickmenu['menus'][] = array(
						'name' => $row['name'],
						'icon' => $row['defaultImage'],
						'selectedicon' => $row['selectedImage'],
						'url' => $row['module']['url'],
						'defaultentry' => $row['defaultentry']['eid'],
					);
				}
			}

			$wxapp_version['quickmenu'] = serialize($quickmenu);
		}
		if ($isedit) {
			$msg = '小程序修改成功';
			pdo_update('wxapp_versions', $wxapp_version, array('id'=>$version_id, 'uniacid'=>$uniacid));
			cache_delete(cache_system_key('miniapp_version', array('version_id' => $version_id)));
		} else {
			$msg = '小程序创建成功';
			pdo_insert('wxapp_versions', $wxapp_version);
			$version_id = pdo_insertid();
		}
		cache_delete(cache_system_key('user_accounts', array('type' => 'wxapp', 'uid' => $_W['uid'])));
		iajax(0, $msg, url('account/display/switch', array('uniacid' => $uniacid, 'type' => ACCOUNT_TYPE_APP_NORMAL)));
	}
	if (!empty($uniacid)) {
		$wxapp_info = miniapp_fetch($uniacid);
	}
	template('wxapp/post');
}

//获取所有支持小程序的模块
if ($do == 'get_wxapp_modules') {
	$wxapp_modules = miniapp_support_wxapp_modules();
	foreach ($wxapp_modules as $name => $module) {
		if ($module['issystem']) {
			$path = '/framework/builtin/'.$module['name'];
		} else {
			$path = '../addons/'.$module['name'];
		}
		$icon = $path.'/icon-custom.jpg';
		if (!file_exists($cion)) {
			$icon = $path.'/icon.jpg';
			if (!file_exists($icon)) {
				$icon = './resource/images/nopic-small.jpg';
			}
		}
		$module['logo'] = $icon;
	}
	iajax(0, $wxapp_modules, '');
}


if ($do == 'module_binding') {
	$modules = $_GPC['modules'];
	if (empty($modules)) {
		iajax(1, '参数无效');
		return;
	}
	$modules = explode(',', $modules);
	$modules = array_map(function($item) {
		return trim($item);
	}, $modules);

	$modules = table('modules')->with(array('bindings' => function($query){
		return $query->where('entry', 'cover');
	}))->where('name', $modules)->getall();

	$modules = array_filter($modules, function($module){
		return count($module['bindings']) > 0;
	});
	iajax(0, $modules);
}

