<?php
/**
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 */
namespace We7\Table\Uni;

class AccountUsers extends \We7Table {
	protected $tableName = 'uni_account_users';
	protected $primaryKey = 'id';
	protected $field = array(
		'uniacid',
		'uid',
		'role',
		'rank',
		'createtime',
	);
	protected $default = array(
		'uniacid' => '',
		'uid' => '',
		'role' => '',
		'rank' => '0',
		'createtime' => '',
	);

	public function searchWithUserRole($role) {
		return $this->query->where('role', $role);
	}

	public function getUsableAccountsByUid($uid) {
		return $this->query->where('uid', $uid)->getall('uniacid');
	}

	public function getOwnedAccountsByUid($uid) {
		return $this->query->where('uid', $uid)->where('role', ACCOUNT_MANAGE_NAME_OWNER)->getall('uniacid');
	}

	public function searchWithRole($role) {
		return $this->query->where('u.role', $role);
	}

	public function getCommonUserOwnAccountUniacids($uid) {
		return $this->query
			->from('uni_account_users', 'u')
			->select('u.uniacid, a.type')
			->innerjoin('account', 'a')
			->on(array('u.uniacid' => 'a.uniacid'))
			->where('u.uid', $uid)
			->getall('uniacid');
	}

	public function getAllUserRole($uid) {
		return $this->query->where('uid', $uid)->getall('role');
	}

	public function getUserRoleByUniacid($uid, $uniacid) {
		$info = $this->query->where(array('uid' => $uid, 'uniacid' => $uniacid))->get();
		return $info['role'];
	}
}