<?php
/**
 * Created by PhpStorm.
 * User: rhino
 * Date: 18-9-5
 * Time: 下午3:12
 */

defined('IN_IA') or exit('Access Denied');

load()->model('material');
$dos = array('message_list', 'message_info', 'message_reply', 'message_mark', 'message_del', 'message_reply_del', 'message_switch');
$do = in_array($do , $dos) ? $do : 'message_list';

if ($do == 'message_list') {
	$pindex = max(1, intval($_GPC['page']));
	$psize = 20;
	$condition = array('uniacid' => $_W['uniacid'], 'acid' => $_W['acid'], 'status' => 0);
	$lists = pdo_getall('mc_mass_record', $condition, array(), '', 'id DESC', 'LIMIT ' . ($pindex-1) * $psize . ',' . $psize);
	$pager = pagination($total, $pindex, $psize);

	$news_arr = array();
	if (is_array($lists) && !empty($lists)) {
		foreach ($lists as $key => &$record) {
			$material = material_get($record['attach_id']);
			if (is_array($material['news']) && !empty($material['news'])) {
				foreach ($material['news'] as $news_key => &$news) {
					$news['msg_id'] = $record['msg_id'];
					$news['msg_data_id'] = $record['msg_data_id'];
					$news['index'] = $news_key;
					$news_arr[] = $news;
				}
			} else {
				unset($lists[$key]);
			}
		}
	}

	template('mc/message_list');
}

if ($do == 'message_info') {
	$index = intval($_GPC['index']) > 0 ? intval($_GPC['index']) : 0;
	$msg_data_id = safe_gpc_string($_GPC['msg_data_id']);
	$type = intval($_GPC['type']) > 0 ? intval($_GPC['type']) : 0;

	$account_api = WeAccount::createByUniacid();
	$res = $account_api->getComment($msg_data_id, $index, $type);
	$comments = $res['comment'];
	$total = $res['total'];

	if(is_array($comments) && !empty($comments)) {
		foreach ($comments as $key => &$comment) {
			$comment['index'] = $index;
			$comment['msg_data_id'] = $msg_data_id;
			$comment['create_time'] = date('Y-m-d H:i:s', $comment['create_time']);
			$fans_info = pdo_get('mc_mapping_fans', array('openid' => $comment['openid']));
			if (is_base64($fans_info['tag'])) {
				$fans_info['tag'] = base64_decode($fans_info['tag']);
			}

			if (!empty($fans_info['tag']) && is_string($fans_info['tag'])) {
				if (is_base64($fans_info['tag'])) {
					$fans_info['tag'] = base64_decode($fans_info['tag']);
				}
				// report warning
				if (is_serialized($fans_info['tag'])) {
					$fans_info['tag'] = @iunserializer($fans_info['tag']);
				}
				if (!empty($fans_info['tag']['headimgurl'])) {
					$fans_info['avatar'] = tomedia($fans_info['tag']['headimgurl']);
				}
				if (empty($fans_info['nickname']) && !empty($fans_info['tag']['nickname'])) {
					$fans_info['nickname'] = strip_emoji($fans_info['tag']['nickname']);
				}
			}
			$comment['fans_info'] = $fans_info;
		}
	}
	template('mc/message_info');
}

if ($do == 'message_reply') {
	$msg_data_id = safe_gpc_string($_GPC['msg_data_id']);
	$index = intval($_GPC['index']);
	$user_comment_id = intval($_GPC['user_comment_id']);
	$content = safe_gpc_string($_GPC['replycontent']);

	$account_api = WeAccount::createByUniacid();
	$res = $account_api->commentReply($msg_data_id, $user_comment_id, $content, $index);
	if (is_error($res)) {
		iajax($res['errno'], $res['message']);
	} else {
		iajax(0, '回复成功!');
	}
}

if ($do == 'message_mark') {
	$msg_data_id = safe_gpc_string($_GPC['msg_data_id']);
	$index = intval($_GPC['index']);
	$user_comment_id = intval($_GPC['user_comment_id']);
	$comment_type = intval($_GPC['comment_type']);

	$account_api = WeAccount::createByUniacid();
	$res = $account_api->commentMark($msg_data_id, $user_comment_id, $comment_type, $index);
	if (is_error($res)) {
		iajax($res['errno'], $res['message']);
	} else {
		if ($comment_type == 1) {
			$message = '取消精选成功!';
		} else {
			$message = '精选成功!';
		}
		iajax(0, $message);
	}
}

if ($do == 'message_del') {
	$msg_data_id = safe_gpc_string($_GPC['msg_data_id']);
	$index = intval($_GPC['index']);
	$user_comment_id = intval($_GPC['user_comment_id']);

	$account_api = WeAccount::createByUniacid();
	$res = $account_api->commentDelete($msg_data_id, $user_comment_id, $index);
	if (is_error($res)) {
		iajax($res['errno'], $res['message']);
	} else {
		iajax(0, '删除成功!');
	}
}

if ($do == 'message_reply_del') {
	$msg_data_id = safe_gpc_string($_GPC['msg_data_id']);
	$index = intval($_GPC['index']);
	$user_comment_id = intval($_GPC['user_comment_id']);
	$account_api = WeAccount::createByUniacid();
	$res = $account_api->commentReplyDelete($msg_data_id, $user_comment_id, $index);
	if (is_error($res)) {
		iajax($res['errno'], $res['message']);
	} else {
		iajax(0, '删除成功!');
	}
}

if ($do == 'message_switch') {
	$msg_data_id = safe_gpc_string($_GPC['msg_data_id']);
	$index = intval($_GPC['index']);
	$need_open_comment = intval($_GPC['need_open_comment']);
	$attach_id = intval($_GPC['attach_id']);
	$title = safe_gpc_string($_GPC['title']);

	$account_api = WeAccount::createByUniacid();
	$res = $account_api->commentSwitch($msg_data_id, $need_open_comment, $index);
	if (is_error($res)) {
		iajax($res['errno'], $res['message']);
	} else {
		$update_message = "，修改数据失败! 文章attach_id : {$attach_id} , 文章标题： {$title}";
		if ($need_open_comment == 1) {
			$message = '关闭评论';
			$res = pdo_update('wechat_news', array('need_open_comment' => 0), array('attach_id' => $attach_id, 'title' => $title, 'uniacid' => $_W['uniacid']));
			if(!$res) {
				iajax(-1, $message . $update_message);
			}
		} else {
			$message = '打开评论';
			$res = pdo_update('wechat_news', array('need_open_comment' => 1), array('attach_id' => $attach_id, 'title' => $title, 'uniacid' => $_W['uniacid']));
			if(!$res) {
				iajax(-1, $message . $update_message);
			}
		}
		iajax(0, $message . '成功!');
	}
}

