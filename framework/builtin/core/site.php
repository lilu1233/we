<?php
/**
 * 系统中一些必须以模块形式表现的功能
 * @author 微擎团队
 * @url http://bbs.w7.cc/
 */
defined('IN_IA') or exit('Access Denied');

class CoreModuleSite extends WeModuleSite {
	public function doMobilePaymethod() {
		global $_W, $_GPC;
		$params = array(
			'fee' => floatval($_GPC['fee']),
			'tid' => $_GPC['tid'],
			'module' => $_GPC['module'],
		);
		if (empty($params['tid']) || empty($params['fee']) || empty($params['module'])) {
			message(error(1, '支付参数不完整'));
		}
		//如果价格为0 直接执行模块支付回调方法
		if($params['fee'] <= 0) {
			$notify_params = array(
				'form' => 'return',
				'result' => 'success',
				'type' => '',
				'tid' => $params['tid'],
			);
			$site = WeUtility::createModuleSite($params['module']);
			$method = 'payResult';
			if (method_exists($site, $method)) {
				$site->$method($notify_params);
				message(error(-1, '支付成功'));
			}
		}
		
		$log = pdo_get('core_paylog', array('uniacid' => $_W['uniacid'], 'module' => $params['module'], 'tid' => $params['tid']));
		if (empty($log)) {
			$log = array(
				'uniacid' => $_W['uniacid'],
				'acid' => $_W['acid'],
				'openid' => $_W['member']['uid'],
				'module' => $params['module'],
				'tid' => $params['tid'],
				'fee' => $params['fee'],
				'card_fee' => $params['fee'],
				'status' => '0',
				'is_usecard' => '0',
			);
			pdo_insert('core_paylog', $log);
		}
		if($log['status'] == '1') {
			message(error(1, '订单已经支付'));
		}
		$setting = uni_setting($_W['uniacid'], array('payment', 'creditbehaviors'));
		if(!is_array($setting['payment'])) {
			message(error(1, '暂无有效支付方式'));
		}
		$pay = $setting['payment'];
		if (empty($_W['member']['uid'])) {
			$pay['credit']['switch'] = false;
		}
		if (!empty($pay['credit']['switch'])) {
			$credtis = mc_credit_fetch($_W['member']['uid']);
		}
		
		include $this->template('pay');
	}

	/**
	 * 各个支付都配置有支付开关：pay_switch和充值开关：recharge_switch,
	 * 支付中使用pay_switch(如本方法中),充值中使用recharge_switch(recharge/site.php中,目前充值只有这一个),
	 * 最后在common/paycenter中统一使用switch
	 */
	public function doMobilePay() {
		global $_W, $_GPC;
		
		$moduels = uni_modules();
		$params = $_POST;
		
		if(empty($params) || !array_key_exists($params['module'], $moduels)) {
			message(error(1, '模块不存在'), '', 'ajax', true);
		}
		
		$setting = uni_setting($_W['uniacid'], 'payment');
		$dos = array();
		if(!empty($setting['payment']['credit']['pay_switch'])) {
			$dos[] = 'credit';
		}
		if(!empty($setting['payment']['alipay']['pay_switch'])) {
			$dos[] = 'alipay';
		}
		if(!empty($setting['payment']['wechat']['pay_switch'])) {
			$dos[] = 'wechat';
		}
		if(!empty($setting['payment']['delivery']['pay_switch'])) {
			$dos[] = 'delivery';
		}
		if(!empty($setting['payment']['unionpay']['pay_switch'])) {
			$dos[] = 'unionpay';
		}
		if(!empty($setting['payment']['baifubao']['pay_switch'])) {
			$dos[] = 'baifubao';
		}
		$type = in_array($params['method'], $dos) ? $params['method'] : '';
		if(empty($type)) {
			message(error(1, '暂无有效支付方式,请联系商家'), '', 'ajax', true);
		}
		$moduleid = pdo_getcolumn('modules', array('name' => $params['module']), 'mid');
		$moduleid = empty($moduleid) ? '000000' : sprintf("%06d", $moduleid);
		$uniontid = date('YmdHis').$moduleid.random(8,1);
		
		$paylog = pdo_get('core_paylog', array('uniacid' => $_W['uniacid'], 'module' => $params['module'], 'tid' => $params['tid']));
		if (empty($paylog)) {
			$paylog = array(
				'uniacid' => $_W['uniacid'],
				'acid' => $_W['acid'],
				'openid' => $_W['member']['uid'],
				'module' => $params['module'],
				'tid' => $params['tid'],
				'uniontid' => $uniontid,
				'fee' => $params['fee'],
				'card_fee' => $params['fee'],
				'status' => '0',
				'is_usecard' => '0',
			);
			pdo_insert('core_paylog', $paylog);
			$paylog['plid'] = pdo_insertid();
		}
		if(!empty($paylog) && $paylog['status'] != '0') {
			message(error(1, '这个订单已经支付成功, 不需要重复支付.'), '', 'ajax', true);
		}
		if (!empty($paylog) && empty($paylog['uniontid'])) {
			pdo_update('core_paylog', array(
				'uniontid' => $uniontid,
			), array('plid' => $paylog['plid']));
		}
		$paylog['title'] = $params['title'];
		if (intval($_GPC['iswxapp'])) {
			message(error(2, $_W['siteroot']."app/index.php?i={$_W['uniacid']}&c=wxapp&a=home&do=go_paycenter&title={$params['title']}&plid={$paylog['plid']}"), '', 'ajax', true);
		}

		if ($params['method'] == 'wechat') {
			return $this->doMobilePayWechat($paylog);
		} elseif ($params['method'] == 'alipay') {
			return $this->doMobilePayAlipay($paylog);
		} else {
			$params['tid'] = $paylog['plid'];
			$sl = base64_encode(json_encode($params));
			$auth = sha1($sl . $_W['uniacid'] . $_W['config']['setting']['authkey']);
			message(error(0, $_W['siteroot'] . "/payment/{$type}/pay.php?i={$_W['uniacid']}&auth={$auth}&ps={$sl}"), '', 'ajax', true);
			exit();
		}
	}
	
	private function doMobilePayWechat($paylog = array()) {
		global $_W;
		load()->model('payment');
		
		pdo_update('core_paylog', array(
			'openid' => $_W['openid'], 
			'tag' => iserializer(array('acid' => $_W['acid'], 'uid' => $_W['member']['uid']))
		), array('plid' => $paylog['plid']));
		
		$_W['uniacid'] = $paylog['uniacid'];
		
		$setting = uni_setting($_W['uniacid'], array('payment'));
		$wechat_payment = $setting['payment']['wechat'];
		
		$account = pdo_get('account_wechats', array('acid' => $wechat_payment['account']), array('key', 'secret'));
		
		$wechat_payment['appid'] = $account['key'];
		$wechat_payment['secret'] = $account['secret'];
		
		$params = array(
			'tid' => $paylog['tid'],
			'fee' => $paylog['card_fee'],
			'user' => $paylog['openid'],
			'title' => urldecode($paylog['title']),
			'uniontid' => $paylog['uniontid'],
		);
		if (intval($wechat_payment['switch']) == PAYMENT_WECHAT_TYPE_SERVICE || intval($wechat_payment['switch']) == PAYMENT_WECHAT_TYPE_BORROW) {
			if (!empty($_W['openid'])) {
				$params['sub_user'] = $_W['openid'];
				$wechat_payment_params = wechat_proxy_build($params, $wechat_payment);
			} else {
				$params['tid'] = $paylog['plid'];
				//借用服务商支付时，没有获取到当前公众号的Openid时，只能跳转至收银台继续付款
				$params['title'] = urlencode($params['title']);
				$sl = base64_encode(json_encode($params));
				$auth = sha1($sl . $paylog['uniacid'] . $_W['config']['setting']['authkey']);
				
				$callback = urlencode($_W['siteroot'] . "payment/wechat/pay.php?i={$_W['uniacid']}&auth={$auth}&ps={$sl}");
				$proxy_pay_account = payment_proxy_pay_account();
				if (!is_error($proxy_pay_account)) {
					$forward = $proxy_pay_account->getOauthCodeUrl($callback, 'we7sid-'.$_W['session_id']);
					message(error(2, $forward), $forward, 'ajax');
					exit;
				}
			}
		} else {
			unset($wechat_payment['sub_mch_id']);
			$wechat_payment_params = wechat_build($params, $wechat_payment);
		}
		if (is_error($wechat_payment_params)) {
			message($wechat_payment_params, '', 'ajax', true);
		} else {
			message(error(0, $wechat_payment_params), '', 'ajax', true);
		}
	}

	private function doMobilePayAlipay($paylog = array()) {
		global $_W;

		load()->model('payment');
		load()->func('communication');

		$_W['uniacid'] = $paylog['uniacid'];

		$setting = uni_setting($_W['uniacid'], array('payment'));
		$params = array(
			'tid' => $paylog['tid'],
			'fee' => $paylog['card_fee'],
			'user' => $paylog['openid'],
			'title' => urldecode($paylog['title']),
			'uniontid' => $paylog['uniontid'],
		);
		$alipay_payment_params = alipay_build($params, $setting['payment']['alipay']);
		if($alipay_payment_params['url']) {
			message(error(0, $alipay_payment_params['url']), '', 'ajax', true);
			exit();
		}
	}
	public function doMobileDetail() {
		global $_W, $_GPC;
		$id = intval($_GPC['id']);
		$sql = "SELECT * FROM " . tablename('news_reply') . " WHERE `id`=:id";
		$row = pdo_fetch($sql, array(':id'=>$id));
		$createtime = $row['createtime'];
		if (!empty($row['url'])) {
			header("Location: ".$row['url']);
			exit;
		}
		//兼容0.8写法，在此回复新版1.0本地素材
		if (!empty($row['media_id']) && intval($row['media_id']) != 0) {
			$row = pdo_get('wechat_news', array('attach_id' => $row['media_id'], 'displayorder' => $row['displayorder']));
			$row['createtime'] = $createtime;
			if (!empty($row['content_source_url'])) {
				header("Location: ".$row['content_source_url']);
				exit;
			}
		}
		$row = istripslashes($row);
		$title = $row['title'];
		/*获取引导素材*/
		if($_W['os'] == 'android' && $_W['container'] == 'wechat' && $_W['account']['account']) {
			$subscribeurl = "weixin://profile/{$_W['account']['account']}";
		} else {
			$sql = 'SELECT `subscribeurl` FROM ' . tablename('account_wechats') . " WHERE `acid` = :acid";
			$subscribeurl = pdo_fetchcolumn($sql, array(':acid' => intval($_W['acid'])));
		}
		include $this->template('detail');
	}
}