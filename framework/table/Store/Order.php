<?php
/**
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 */
namespace We7\Table\Store;

class Order extends \We7Table {
	protected $tableName = 'site_store_order';
	protected $primaryKey = 'id';
	protected $field = array(
		'orderid',
		'goodsid',
		'duration',
		'buyer',
		'buyerid',
		'amount',
		'type',
		'changeprice',
		'createtime',
		'uniacid',
		'endtime',
		'wxapp',
		'is_wish',
	);
	protected $default = array(
		'orderid' => '',
		'goodsid' => 0,
		'duration' => 0,
		'buyer' => '',
		'buyerid' => 0,
		'amount' => 0,
		'type' => 0,
		'changeprice' => 0,
		'createtime' => 0,
		'uniacid' => '',
		'endtime' => '',
		'wxapp' => '',
		'is_wish' => 0,
	);


}