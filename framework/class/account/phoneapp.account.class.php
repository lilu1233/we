<?php
/**
 *
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 */
defined('IN_IA') or exit('Access Denied');

class PhoneappAccount extends WeAccount {
	protected $tablename = 'account_phoneapp';
	protected $menuFrame = 'wxapp';
	protected $type = ACCOUNT_TYPE_PHONEAPP_NORMAL;
	protected $typeSign = PHONEAPP_TYPE_SIGN;
	protected $typeName = 'APP';
	protected $typeTempalte = '-phoneapp';
	protected $supportVersion = STATUS_ON;

	public function checkIntoManage() {
		if (empty($this->account) || (!empty($this->account['account']) && $this->account['type'] != ACCOUNT_TYPE_PHONEAPP_NORMAL && !defined('IN_MODULE'))) {
			return false;
		}
		return true;
	}

	protected function getAccountInfo($acid) {
		return table('account_phoneapp')->getAccount($acid);
	}
}