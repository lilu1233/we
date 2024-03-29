<?php
/**
 * 收银台
 * 
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 */
defined('IN_IA') or exit('Access Denied');

class PaycenterModule extends WeModule {
	public $tablename = 'wxcard_reply';
	public $replies = array();

	public function fieldsFormDisplay($rid = 0) {
		if(!empty($rid)) {
			$replies = pdo_getall($this->tablename, array('rid' => $rid));
		}
		include $this->template('display');
	}
	
	public function fieldsFormValidate($rid = 0) {
		global $_GPC;
		$this->replies = @json_decode(htmlspecialchars_decode($_GPC['replies']), true);
		if(empty($this->replies)) {
			return '必须填写有效的回复内容.';
		}
		foreach($this->replies as $k => &$row) {
			if(empty($row['cid']) || empty($row['card_id'])) {
				unset($k);
			}
		}
		if(empty($this->replies)) {
			return '必须填写有效的回复内容.';
		}
		return '';
	}
	
	public function fieldsFormSubmit($rid = 0) {
		global $_W, $_GPC;
		$rid = intval($rid);
		$rule_exists = pdo_get('rule', array('id' => $rid, 'uniacid' => $_W['uniacid']));
		if (empty($rule_exists)) {
			return false;
		}
		pdo_delete($this->tablename, array('rid' => $rid));
		
		foreach($this->replies as $reply) {
			$data = array(
				'rid' => $rid,
				'title' => $reply['title'],
				'card_id' => $reply['card_id'],
				'cid' => $reply['cid'], //对应卡券表的id
				'brand_name' => $reply['brand_name'],
				'logo_url' => $reply['logo_url'],
				'success' => trim($_GPC['success']),
				'error' => trim($_GPC['error'])
			);
			pdo_insert($this->tablename, $data);
		}
		return true;
	}
	
	public function ruleDeleted($rid = 0) {
		global $_W;
		$rid = intval($rid);
		$rule_exists = pdo_get('rule', array('id' => $rid, 'uniacid' => $_W['uniacid']));
		if (empty($rule_exists)) {
			return false;
		}
		pdo_delete($this->tablename, array('rid' => $rid));
		return true;
	}
}