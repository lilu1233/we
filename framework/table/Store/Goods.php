<?php
/**
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 */
namespace We7\Table\Store;

class Goods extends \We7Table {
	protected $tableName = 'site_store_goods';
	protected $primaryKey = 'id';
	protected $field = array(
		'type',
		'title',
		'module',
		'module_group',
		'user_group',
		'account_num',
		'wxapp_num',
		'api_num',
		'price',
		'user_group_price',
		'unit',
		'slide',
		'category_id',
		'title_initial',
		'status',
		'createtime',
		'synopsis',
		'description',
		'is_wish',
		'logo',
	);
	protected $default = array(
		'type' => 0,
		'title' => '',
		'module' => '',
		'module_group' => 0,
		'user_group' => 0,
		'account_num' => 0,
		'wxapp_num' => 0,
		'api_num' => 0,
		'price' => 0,
		'user_group_price' => '',
		'unit' => '',
		'slide' => '',
		'category_id' => 0,
		'title_initial' => '',
		'status' => 0,
		'createtime' => 0,
		'synopsis' => '',
		'description' => '',
		'is_wish' => 0,
		'logo' => '',
	);

	public function searchWithIswishAndStatus($is_wish, $status) {
		$this->query->where(array(
			'is_wish' => $is_wish,
			'status' => $status
		));
		return $this;
	}

	public function searchWithTypeAndTitle($type = 0, $title = '') {
		if (!empty($type) && is_numeric($type)) {
			$this->query->where('type', $type);
		}
		if (!empty($title)) {
			$this->query->where('title LIKE', "%$title%");
		}
		return $this;
	}

	/**
	 * @param $group
	 * 		模块 module
	 * 		平台个数 account_num
	 * 		平台续费 renew
	 */
	public function searchWithTypeGroup($group_name) {
		if (!empty($group_name) && !is_numeric($group_name)) {
			load()->model('store');
			$types = store_goods_type_info($group_name);
			$this->query->where('type', array_keys($types));
		}
		return $this;
	}

	public function getGoods($is_wish = 0, $status = 1) {
		$data = $this->query
			->where(array('is_wish' => $is_wish, 'status' => $status))
			->orderby('id', 'DESC')
			->getall();

		if (!empty($data)) {
			load()->model('store');
			$types = store_goods_type_info();

			foreach ($data as &$item) {
				$item['user_group_price'] = iunserializer($item['user_group_price']);
				$item['slide'] = iunserializer($item['slide']);
				$item['type_info'] = $types[$item['type']];
			}
		}
		return $data;
	}

}