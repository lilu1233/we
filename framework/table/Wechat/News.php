<?php
/**
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 */
namespace We7\Table\Wechat;

class News extends \We7Table {
	protected $tableName = 'wechat_news';
	protected $primaryKey = 'id';
	protected $field = array(
		'uniacid',
		'attach_id',
		'thumb_media_id',
		'thumb_url',
		'title',
		'author',
		'digest',
		'content',
		'content_source_url',
		'show_cover_pic',
		'url',
		'displayorder',
		'need_open_comment',
		'only_fans_can_comment',
	);
	protected $default = array(
		'uniacid' => '0',
		'attach_id' => '0',
		'thumb_media_id' => '',
		'thumb_url' => '',
		'title' => '',
		'author' => '',
		'digest' => '',
		'content' => '',
		'content_source_url' => '',
		'show_cover_pic' => '0',
		'url' => '',
		'displayorder' => '0',
		'need_open_comment' => '1',
		'only_fans_can_comment' => '1',
	);

	public function getAllByAttachId($attach_id) {
		return $this->query->where('attach_id', $attach_id)->orderby('displayorder', 'ASC')->getall();
	}

	public function searchKeyword($keyword) {
		return $this->query->where('title LIKE', $keyword)->whereor('author LIKE', $keyword)->whereor('digest LIKE', $keyword);
	}

	public function searchWithUniacid($uniacid) {
		return $this->query->where('uniacid', $uniacid);
	}
}