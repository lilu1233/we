<?php
/**
 * 微信卡券回复模块
 * 
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 */
defined('IN_IA') or exit('Access Denied');

class WxcardModule extends WeModule {
	public $tablename = 'wxcard_reply';
	public $replies = array();
}