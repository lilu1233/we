<?php
/**
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 * $sn$
 */
defined('IN_IA') or exit('Access Denied');

class BaiduappAccount extends WeAccount {
	protected $tablename = 'account_baiduapp';
	protected $menuFrame = 'wxapp';
	protected $type = ACCOUNT_TYPE_BAIDUAPP_NORMAL;
	protected $typeName = '百度小程序';
	protected $typeTempalte = '-baiduapp';
	protected $typeSign = BAIDUAPP_TYPE_SIGN;
	protected $supportVersion = STATUS_ON;

	protected function getAccountInfo($acid) {
		return table('account_baiduapp')->getAccount($acid);
	}

	public function checkIntoManage() {
		if (empty($this->account) || (!empty($this->account['account']) && $this->account['type'] != ACCOUNT_TYPE_BAIDUAPP_NORMAL && !defined('IN_MODULE'))) {
			return false;
		}
		return true;
	}
}