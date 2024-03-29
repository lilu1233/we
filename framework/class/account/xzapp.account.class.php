<?php
defined('IN_IA') or exit('Access Denied');

class XzappAccount extends WeAccount {
	protected $tablename = 'account_xzapp';
	protected $menuFrame = 'account';
	protected $type = ACCOUNT_TYPE_XZAPP_NORMAL;
	protected $typeName = '熊掌号';
	protected $typeSign = XZAPP_TYPE_SIGN;
	protected $typeTempalte = '-xzapp';


	protected function getAccountInfo($acid) {
		return table('account_xzapp')->getByAcid($acid);
	}

	public function checkSign() {
		$arrParams = array(
			$token = $this->account['token'],
			$intTimeStamp = $_GET['timestamp'],
			$strNonce = $_GET['nonce'],
		);
		sort($arrParams, SORT_STRING);
		$strParam = implode($arrParams);
		$strSignature = sha1($strParam);
		return $strSignature == $_GET['signature'];
	}

	public function getAccessToken() {
		$cachekey = cache_system_key('accesstoken', array('uniacid' => $this->account['uniacid']));
		$cache = cache_load($cachekey);

		if (!empty($cache) && !empty($cache['token']) && $cache['expire'] > TIMESTAMP) {
			$this->account['access_token'] = $cache;
			return $cache['token'];
		}

		if (empty($this->account['key']) || empty($this->account['secret'])) {
			return error('-1', '未填写熊掌号的 appid 或者 appsecret！');
		}

		$url = "https://openapi.baidu.com/oauth/2.0/token?grant_type=client_credentials&client_id={$this->account['key']}&client_secret={$this->account['secret']}";
		$content = ihttp_get($url);
		$token = @json_decode($content['content'], true);

		$record = array();
		$record['token'] = $token['access_token'];
		$record['expire'] = TIMESTAMP + $token['expires_in'] - 200;
		$this->account['access_token'] = $record;

		cache_write($cachekey, $record);
		return $record['token'];
	}

	/**
	 * 生成签名
	 * @param string $encrypt_msg
	 * @return string
	 */
	public function buildSignature($encrypt_msg) {
		$token = $this->account['token'];
		$array = array($encrypt_msg, $token, $_GET['timestamp'], $_GET['nonce']);
		sort($array, SORT_STRING);
		$str = implode($array);
		$str = sha1($str);
		return $str;
	}

	/**
	 * 验证签名是否合法
	 * @param string $encrypt_msg
	 * @return boolean
	 */
	public function checkSignature($encrypt_msg) {
		$str = $this->buildSignature($encrypt_msg);
		return $str == $_GET['msg_signature'];
	}

	/**
	 * 消息加密
	 * @param $text
	 * @return array
	 */
	public function encryptMsg($text) {
		$appid = $this->account['key'];
		$encodingaeskey = $this->account['encodingaeskey'];
		$key = base64_decode($encodingaeskey . '=');

		static $blockSize = 32;

		// pack
		$text = substr(md5(time()), 0, 16) . pack('N', strlen($text)) . $text . $appid;

		// 填充位数
		$padLen = $blockSize - (strlen($text) % $blockSize);
		$text .= str_repeat(chr($padLen), $padLen == 0 ? $blockSize : $padLen);

		// 加密
		$iv = substr($key, 0, 16);
		$encoded = openssl_encrypt($text, 'AES-256-CBC', $key, OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING, $iv);

		$encrypt_msg = base64_encode($encoded);

		//生成的签名
		$signature = $this->buildSignature($encrypt_msg);
		return array($signature, $encrypt_msg);
	}

	/**
	 * 对消息进行解密
	 *
	 * @param array $postData
	 * @return error 或 string
	 */
	public function decryptMsg($postData) {
		$appid = $this->account['key'];
		$encodingaeskey = $this->account['encodingaeskey'];
		$key = base64_decode($encodingaeskey . '=');

		// 提取密文
		$packet = $this->xmlExtract($postData);
		if (is_error($packet)) {
			return error(-1, $packet['message']);
		}
		$encrypt = base64_decode($packet['encrypt']);
		//检验签名
		$istrue = $this->checkSignature($packet['encrypt']);
		if(!$istrue) {
			return error(-1, "熊掌号签名错误！");
		}

		// 解密
		$iv = substr($key, 0, 16);
		$decoded = openssl_decrypt($encrypt, 'AES-256-CBC', $key, OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING, $iv);

		// 去掉填充位数
		$pad = ord(substr($decoded, -1));
		$pad = ($pad < 1 || $pad > 32) ? 0 : $pad;
		$decoded = substr($decoded, 0, strlen($decoded) - $pad);

		// unpack
		$text = substr($decoded, 16, strlen($decoded));
		$unpack = unpack('Nlen/', substr($text, 0, 4));
		$content = substr($text, 4, $unpack['len']);
		$clientId = substr($text, $unpack['len'] + 4);

		if ($clientId != $appid) {
			return error(-1, 'ERR: decode clientId is ' . $clientId . ', need client is ' . $appid);
		}
		return $content;
	}

	/**
	 * 从xml中提取密文
	 *
	 * @param string $message
	 * @return array error/array
	 */
	public function xmlExtract($message) {
		$packet = array();
		if (!empty($message)){
			$obj = isimplexml_load_string($message, 'SimpleXMLElement', LIBXML_NOCDATA);
			if($obj instanceof SimpleXMLElement) {
				$packet['encrypt'] = strval($obj->Encrypt);
				$packet['to'] = strval($obj->ToUserName);
			}
		}
		if(!empty($packet['encrypt'])) {
			return $packet;
		} else {
			return error(-1, "熊掌号返回接口错误");
		}
	}

	/**
	 * 生成加密后xml
	 *
	 * @param array $data
	 * @return string xml
	 */
	function xmlDetract($data) {
		//生成xml
		$xml['Encrypt'] = $data[1];
		$xml['MsgSignature'] = $data[0];
		$xml['TimeStamp'] = $_GET['timestamp'];
		$xml['Nonce'] = $_GET['nonce'];
		return array2xml($xml);
	}

	protected function requestApi($url, $post = '') {
		$response = ihttp_request($url, $post);
		$result = @json_decode($response['content'], true);
		if ($result['error_code']) {
			return error(-1, "访问熊掌号接口失败, 错误代码：【{$result['error_code']}】, 错误信息：【{$result['error_msg']}】");
		}
		return $result;
	}

	public function checkIntoManage() {
		if (empty($this->account) || (!empty($this->uniaccount['account']) && $this->uniaccount['type'] != ACCOUNT_TYPE_XZAPP_NORMAL && !defined('IN_MODULE'))) {
			return false;
		}
		return true;
	}

	public function getOauthCodeUrl($callback, $state = '') {
		$this->account['callbackurl'] = $callback;
		return "https://openapi.baidu.com/oauth/2.0/authorize?response_type=code&client_id={$this->account['key']}&redirect_uri={$callback}&scope=snsapi_base&state={$state}";
	}

	public function getOauthUserInfoUrl($callback, $state = '') {
		$this->account['callbackurl'] = $callback;
		return "https://openapi.baidu.com/oauth/2.0/authorize?response_type=code&client_id={$this->account['key']}&redirect_uri={$callback}&scope=snsapi_userinfo&state={$state}";
	}

	public function getOauthInfo($code = '') {
		global $_W,$_GPC;
		if (!empty($_GPC['code'])) {
			$code = $_GPC['code'];
		}
		if (empty($code)) {
			$oauth_url = uni_account_oauth_host();
			$url = urlencode($oauth_url . "app/index.php?{$_SERVER['QUERY_STRING']}");
			$forward = $this->getOauthCodeUrl($url);
			header('Location: ' . $forward);
			exit;
		}

		$str = '';
		if(uni_is_multi_acid()) {
			$str = "&j={$_W['acid']}";
		}
		$oauth_type = $_GPC['scope'];
		$oauth_url = uni_account_oauth_host();
		$url = $oauth_url . "app/index.php?i={$_W['uniacid']}{$str}&c=auth&a=oauth&scope=" . $oauth_type;
		$callback = urlencode($url);
		$oauth_info = $this->getOauthAccessToken($code, $callback);
		$user_info_url = "https://openapi.baidu.com/rest/2.0/cambrian/sns/userinfo?access_token={$oauth_info['token']}&openid={$oauth_info['openid']}";
		$response = $this->requestApi($user_info_url);
		return $response;
	}

	public function getOauthAccessToken($code, $callback) {
		$cachekey = cache_system_key('oauthaccesstoken', array('acid' => $this->account['acid']));
		$cache = cache_load($cachekey);
		if (!empty($cache) && !empty($cache['token']) && $cache['expire'] > TIMESTAMP) {
			return $cache;
		}

		$url = "https://openapi.baidu.com/oauth/2.0/token?grant_type=authorization_code&code={$code}&client_id={$this->account['key']}&client_secret={$this->account['secret']}&redirect_uri={$callback}";
		$oauth_info = $this->requestApi($url);
		$record = array();
		$record['token'] = $oauth_info['access_token'];
		$record['openid'] = $oauth_info['openid'];
		$record['expire'] = TIMESTAMP + $oauth_info['expires_in'] - 200;
		cache_write($cachekey, $record);
		return $record;
	}

	public function isTagSupported() {
		if (!empty($this->account['key']) && !empty($this->account['secret'])) {
			return true;
		} else {
			return false;
		}
	}

	public function fansTagFetchAll() {
		$token = $this->getAccessToken();

		if (is_error($token)) {
			return $token;
		}

		$url = "https://openapi.baidu.com/rest/2.0/cambrian/tags/get?access_token={$token}";
		$result = $this->requestApi($url);
		return $result;
	}

	public function fansAll($startopenid = '') {
		global $_W;
		$token = $this->getAccessToken();
		if (is_error($token)) {
			return $token;
		}

		$url = "https://openapi.baidu.com/rest/2.0/cambrian/user/get?start_index=0&access_token={$token}";
		if (!empty($_GPC['next_openid'])) {
			$url .= '&start_index=' . $_GPC['next_openid'];
		}

		$res = ihttp_get($url);
		$content = json_decode($res['content'], true);

		if ($content['error_code']) {
			return error(-1, '访问熊掌号接口失败, 错误代码: 【' . $content['error_code'] . '】, 错误信息：【' . $content['error_msg'] . '】');
		}

		$return = array();
		$return['total'] = $content['total'];
		$return['fans'] = $content['data'];
		$return['next'] = $content['start_index'];
		return $return;
	}

	/**
	 * 获取用户基本信息(单个)
	 * @param $uniid
	 * @param bool $isOpen
	 * @return array
	 */
	public function fansQueryInfo($uniid, $isOpen = true) {
		if ($isOpen) {
			$openid = $uniid;
		} else {
			exit('error');
		}
		$token = $this->getAccessToken();
		if(is_error($token)){
			return $token;
		}
		$data = array(
			'user_list' => array(
				array(
					'openid' => $uniid,
				)
			),
		);
		$url = "https://openapi.baidu.com/rest/2.0/cambrian/user/info?access_token={$token}";

		$result = $this->requestApi($url, json_encode($data));
		return $result['user_info_list'][0];
	}

	/**
	 * 获取用户基本信息(批量)
	 * @param $data
	 * @return array
	 */
	public function fansBatchQueryInfo($data) {
		if (empty($data)) {
			return error(-1, '粉丝 openid 错误');
		}

		$token = $this->getAccessToken();
		if (is_error($token)) {
			return $token;
		}

		$list['user_list'] = array();
		foreach ($data as $da) {
			$list['user_list'][] = array('openid' => $da);
		}

		$url = "https://openapi.baidu.com/rest/2.0/cambrian/user/info?access_token={$token}";
		$result = $this->requestApi($url, json_encode($list));
		return $result['user_info_list'];
	}

	/**
	 * 创建粉丝标签
	 * @param 	array 		$tagname
	 * @return 	array 		$result
	 *
	 */
	public function fansTagAdd($tagname) {
		if(empty($tagname)) {
			return error(-1, '请填写标签名称');
		}
		$token = $this->getAccessToken();
		if(is_error($token)){
			return $token;
		}

		$url = "https://openapi.baidu.com/rest/2.0/cambrian/tags/create?access_token={$token}";
		$data = stripslashes(ijson_encode(array('tag' => array('name' => $tagname)), JSON_UNESCAPED_UNICODE));
		$result = $this->requestApi($url, $data);
		return $result;
	}

	/**
	 * 单个粉丝打标签
	 * @param $openid
	 * @param $tagids
	 * @return array|bool|mixed
	 */
	public function fansTagTagging($openid, $tagids) {
		$openid = (string) $openid;
		$tagids = (array) $tagids;

		if (empty($openid)) {
			return error(-1, '没有填写用户openid');
		}
		if (empty($tagids)) {
			return error(-1, '没有填写标签');
		}
		if (count($tagids) > 3) {
			return error(-1, '最多3个标签');
		}
		$token = $this->getAccessToken();
		if (is_error($token)) {
			return $token;
		}

		//删除粉丝之前的标签
		$fetch_result = $this->fansTagFetchOwnTags($openid);
		if (is_error($fetch_result)) {
			return $fetch_result;
		}
		foreach ($fetch_result['tagid_list'] as $del_tagid) {
			$this->fansTagBatchUntagging($openid, $del_tagid);
		}

		$url = "https://openapi.baidu.com/rest/2.0/cambrian/tags/batchtagging?access_token={$token}";
		foreach ($tagids as $tagid) {
			$data = array(
				'openid_list' => array($openid),
				'tagid' => $tagid
			);
			$data = json_encode($data);
			$result = $this->requestApi($url, $data);
			if (is_error($result)) {
				return $result;
			}
		}
		return true;
	}

	/**
	 * 获取用户身上的标签列表
	 * @param $openid
	 * @return array|mixed
	 */
	public function fansTagFetchOwnTags($openid) {
		$openid = (string)$openid;
		if (empty($openid)) {
			return error(-1, '没有填写用户openid');
		}
		$token = $this->getAccessToken();
		if (is_error($token)) {
			return $token;
		}

		$url = "https://openapi.baidu.com/rest/2.0/cambrian/tags/getidlist?access_token={$token}";
		$data = json_encode(array('openid' => $openid));
		$result = $this->requestApi($url, $data);
		return $result;
	}

	/**
	 * 批量为用户取消标签
	 * @param $openid_list
	 * @param $tagid
	 * @return array|bool|mixed
	 */
	public function fansTagBatchUntagging($openid_list, $tagid) {
		$openid_list = (array)$openid_list;
		$tagid = (int)$tagid;
		if (empty($openid_list)) {
			return error(-1, '缺少openid参数');
		}
		if (empty($tagid)) {
			return error(-1, '没有填写tagid');
		}

		$token = $this->getAccessToken();
		if (is_error($token)) {
			return $token;
		}
		$url = "https://openapi.baidu.com/rest/2.0/cambrian/tags/batchuntagging?access_token={$token}";
		$data = array(
			'openid_list' => $openid_list,
			'tagid' => $tagid
		);
		$data = json_encode($data);
		$result = $this->requestApi($url, $data);
		if (is_error($result)) {
			return $result;
		}
		return true;
	}

	/**
	 * 批量为用户打标签
	 * @param $openid_list
	 * @param $tagid
	 * @return array|bool|mixed
	 */
	public function fansTagBatchTagging($openid_list, $tagid) {
		$openid_list = (array)$openid_list;
		$tagid = (int)$tagid;
		if(empty($openid_list)){
			return error(-1, '没有填写用户openid列表');
		}
		if(empty($tagid)) {
			return error(-1, '没有填写tagid');
		}
		$token = $this->getAccessToken();
		if(is_error($token)){
			return $token;
		}
		$url = "https://openapi.baidu.com/rest/2.0/cambrian/tags/batchtagging?access_token={$token}";
		$data = array(
			'openid_list' => $openid_list,
			'tagid' => $tagid
		);
		$result = $this->requestApi($url, json_encode($data));
		if (is_error($result)) {
			return $result;
		}
		return true;
	}

	# 自定义菜单
	/**
	 * API配置自定义菜单查询
	 * @return array|mixed
	 */
	public function menuCurrentQuery() {
		$token = $this->getAccessToken();
		if (is_error($token)) {
			return $token;
		}
		$url = "https://openapi.baidu.com/rest/2.0/cambrian/menu/get?access_token={$token}";
		$res = $this->requestApi($url);
		return $res;
	}

	/**
	 * 自定义菜单创建
	 * @param $post
	 * @return array|mixed
	 */
	public function menuCreate($menu) {
		global $_W;
		$token = $this->getAccessToken();
		if(is_error($token)){
			return $token;
		}
		$data['menues'] = json_encode($menu);
		$url = "https://openapi.baidu.com/rest/2.0/cambrian/menu/create?access_token={$token}";
		$res = $this->requestApi($url, $data);
		if (is_error($res)) {
			return $res;
		} else {
			return 0;
		}
	}

	/**
	 * 构造可直接请求自定义菜单创建接口的数据
	 * @param $post
	 * @return array
	 */
	public function menuBuild($post, $is_conditional = false) {
		$menu = array();
		foreach ($post['button'] as $button) {
			$temp = array();
			$temp['name'] = $button['name'];
			if (empty($button['sub_button'])) {
				$temp['type'] = $button['type'];
				if ($button['type'] == 'click') {
					if (!empty($button['media_id']) && empty($button['key'])) {
						$temp['key'] = $button['media_id'];
						$temp['msg'] = array(
							'text' => '',
							'type' => 'view_limited',
							'materialId' => $button['media_id']
						);
					}
					if (!empty($button['key']) && $button['key'] == $button['msg']['materialId']) {
						$temp['msg'] = $button['msg'];
						$temp['key'] = $button['key'];
					}

				} elseif ($button['type'] == 'view') {
					$temp['url'] = $button['url'];
				}
			} else {
				foreach ($button['sub_button'] as $sub_button) {
					$sub_temp = array();
					$sub_temp['name'] = $sub_button['name'];
					$sub_temp['type'] = $sub_button['type'];
					if ($sub_button['type'] == 'click') {
						if (!empty($sub_button['media_id']) && empty($sub_button['key'])) {
							$sub_temp['key'] = $sub_button['media_id'];
							$sub_temp['msg'] = array(
								'text' => '',
								'type' => 'view_limited',
								'materialId' => $sub_button['media_id']
							);
						}
						if (!empty($sub_button['key']) && $sub_button['key'] == $sub_button['msg']['materialId']) {
							$sub_temp['msg'] = $sub_button['msg'];
							$sub_temp['key'] = $sub_button['key'];
						}

					} elseif ($sub_button['type'] == 'view') {
						$sub_temp['url'] = $sub_button['url'];
					}
					$temp['sub_button'][] = $sub_temp;
				}
			}
			$menu['button'][] = $temp;
		}
		return $menu;
	}

	# 素材
	/**
	 * 获取熊掌号素材列表（熊掌号只支持图片和图文）
	 * @param string $type	素材的类型:image/news
	 * @param int $offset	素材偏移的位置，从０开始
	 * @param int $count	素材的数量，取值在１－２０之间，默认２０
	 * @return array|mixed
	 */
	public function batchGetMaterial($type = 'news', $offset = 0, $count = 20) {
		global $_W;
		$token = $this->getAccessToken();
		if (is_error($token)) {
			return $token;
		}
		$url = "https://openapi.baidu.com/rest/2.0/cambrian/material/batchget_material?access_token={$token}&type={$type}&offset={$offset}&count={$count}";

		$response = $this->requestApi($url);
		if (!is_error($response)) {
			foreach ($response['item'] as $key => &$item) {
				foreach ($item['content']['news_item'] as $news_key => &$news_item) {
					$content = json_decode($news_item['content'], true);
					if (!empty($content) && is_array($content) && !empty($content['orihtml'])){
						$news_item['content'] = $content['orihtml'];
					}
					$news_info = $this->getMaterial($news_item['thumb_media_id']);
					$news_item['thumb_url'] = $news_info['url'];
				}
			}
		}
		return $response;
	}

	/**
	 * 删除永久素材
	 * @param $media_id
	 * @return array|mixed
	 */
	public function delMaterial($media_id) {
		$media_id = trim($media_id);
		if (empty($media_id)) {
			return error(-1, '素材media_id错误');
		}
		$token = $this->getAccessToken();
		if (is_error($token)) {
			return $token;
		}
		$url = "https://openapi.baidu.com/rest/2.0/cambrian/material/del_material?access_token=" . $token . "&media_id=" . $media_id;

		$response = $this->requestApi($url);
		return $response;
	}

	/**
	 * 新增永久图文素材
	 * @param $data
	 * @return array
	 */
	public function addMatrialNews($data) {
		$token = $this->getAccessToken();
		if(is_error($token)){
			return $token;
		}
		$url = "https://openapi.baidu.com/rest/2.0/cambrian/material/add_news?access_token={$token}";
		$data = stripslashes(urldecode(ijson_encode($data, JSON_UNESCAPED_UNICODE)));

		$response = $this->requestApi($url, $data);
		return $response['media_id'];
	}

	/**
	 * 修改永久图文素材
	 * @param $data
	 * @return array|mixed
	 */
	public function editMaterialNews($data) {
		$token = $this->getAccessToken();
		if(is_error($token)){
			return $token;
		}
		$url = "https://openapi.baidu.com/rest/2.0/cambrian/material/update_news?access_token={$token}";

		$response = $this->requestApi($url, stripslashes(ijson_encode($data, JSON_UNESCAPED_UNICODE)));
		return $response;
	}

	/**
	 * 获取永久素材
	 * @param $media_id
	 * @return array|mixed
	 */
	public function getMaterial($media_id) {
		$token = $this->getAccessToken();
		if (is_error($token)) {
			return $token;
		}

		$url = "https://openapi.baidu.com/rest/2.0/cambrian/material/get_material?access_token={$token}&media_id={$media_id}";

		$response = $this->requestApi($url);
		return $response;
	}

	/**
	 * 上传图文消息内的图片获取URL
	 * @param $thumb
	 * @return array
	 */
	public function uploadNewsThumb($thumb) {
		$token = $this->getAccessToken();
		if (is_error($token)) {
			return $token;
		}
		if (!file_exists($thumb)) {
			return error(1, '文件不存在');
		}

		$data = array(
			'media' => '@' . $thumb,
		);

		$url = "https://openapi.baidu.com/rest/2.0/cambrian/media/uploadimg?access_token={$token}";

		$response = $this->requestApi($url, $data);
		return $response['url'];
	}

	public function uploadMediaFixed($path, $type = 'images') {
		# 未测试
		if (empty($path)) {
			return error(-1, '参数错误');
		}
		if (in_array(substr(ltrim($path, '/'), 0, 6), array('images', 'videos', 'audios', 'thumb', 'voices'))) {
			$path = ATTACHMENT_ROOT . ltrim($path, '/');
		}
		if (!file_exists($path)) {
			return error(1, '文件不存在');
		}
		$token = $this->getAccessToken();
		if (is_error($token)){
			return $token;
		}
		$data = array(
			'media' => '@' . $path
		);
		$url = "https://openapi.baidu.com/rest/2.0/cambrian/media/add_material?access_token={$token}";

		$response = $this->requestApi($url, $data);
		return $response;
	}

	/*发送客服消息*/
	public function sendCustomNotice($data) {
		if(empty($data)) {
			return error(-1, '参数错误');
		}
		$token = $this->getAccessToken();
		if(is_error($token)){
			return $token;
		}
		$url = "https://openapi.baidu.com/rest/2.0/cambrian/message/custom_send?access_token={$token}";
		$response = $this->requestApi($url, urldecode(json_encode($data)));
		WeUtility::logging('$resonse', var_export($response, true));
		if (is_error($response)) {
			return $response;
		}
		return true;
	}

	/**
	 * 发送模板消息
	 *  @param string $touser 粉丝openid
	 *  @param string $tpl_id_short 模板id
	 *  @param array $postdata 根据模板规则完善消息
	 *  @param string $url 详情页链接
	 */
	public function sendTplNotice($touser, $template_id, $postdata, $url = '') {
		if(empty($touser)) {
			return error(-1, '参数错误,粉丝openid不能为空');
		}
		if(empty($template_id)) {
			return error(-1, '参数错误,模板标示不能为空');
		}
		if(empty($postdata) || !is_array($postdata)) {
			return error(-1, '参数错误,请根据模板规则完善消息内容');
		}
		$token = $this->getAccessToken();
		if (is_error($token)) {
			return $token;
		}

		$data = array();
		$data['touser'] = $touser;
		$data['template_id'] = trim($template_id);
		$data['url'] = trim($url);
		$data['data'] = $postdata;
		$data = json_encode($data);
		$post_url = "https://openapi.baidu.com/rest/2.0/cambrian/template/send?access_token={$token}";
		$response = $this->requestApi($post_url, $data);
		if(is_error($response)) {
			return error(-1, "访问公众平台接口失败, 错误: {$response['message']}");
		}
		return true;
	}

	/**
	 * 消息群发
	 * @param $group
	 * @param $msgtype
	 * @param $media_id
	 * @return array|mixed
	 */
	public function fansSendAll($group, $msgtype, $media_id) {
		$types = array('basic' => 'text', 'image' => 'image', 'news' => 'mpnews', 'voice' => 'voice');
		if (empty($types[$msgtype])) {
			return error(-1, '消息类型不合法');
		}
		$send_conent = ($types[$msgtype] == 'text') ? array('content' => $media_id) : array('media_id' => $media_id);
		if ($group == -1) {
			$data = array(
				'filter' => array(
					'is_to_all' => true,
					'group_id' => $group
				),
				'msgtype' => $types[$msgtype],
				$types[$msgtype] => $send_conent,
			);
		} else {
			$openids = $this->getFansByTag($group);
			$data = array(
				'touser' => $openids,
				'msgtype' => $types[$msgtype],
				$types[$msgtype] => $send_conent,
			);
		}
		$token = $this->getAccessToken();
		if(is_error($token)){
			return $token;
		}
		$url = "https://openapi.baidu.com/rest/2.0/cambrian/message/sendall?access_token={$token}";
		$response = $this->requestApi($url, json_encode($data));
		return $response;
	}

	/**
	 * 获取标签下粉丝列表
	 * @param $tagid
	 * @return array
	 */
	public function getFansByTag($tagid){
		$token = $this->getAccessToken();
		if(is_error($token)){
			return $token;
		}
		$url = "https://openapi.baidu.com/rest/2.0/cambrian/tag/get?access_token={$token}";
		$data = array('tagid' => $tagid);
		$response = $this->requestApi($url, json_encode($data));
		return $response['data']['openid'];
	}

	/**
	 * jsapi_ticket
	 * @return array|mixed
	 */
	public function getJsApiTicket() {
		$cachekey = cache_system_key('jsticket', array('acid' => $this->account['acid']));
		$cache = cache_load($cachekey);
		if(!empty($cache) && !empty($cache['ticket']) && $cache['expire'] > TIMESTAMP) {
			return $cache['ticket'];
		}
		$access_token = $this->getAccessToken();
		if(is_error($access_token)){
			return $access_token;
		}

		$url = "https://openapi.baidu.com/rest/2.0/cambrian/jssdk/getticket?access_token={$access_token}";
		$response = $this->requestApi($url);
		if (is_error($response)) {
			return $response;
		}

		$record = array();
		$record['ticket'] = $response['ticket'];
		$record['expire'] = TIMESTAMP + $response['expires_in'] - 200;

		$this->account['jsapi_ticket'] = $record;
		cache_write($cachekey, $record);

		return $record['ticket'];
	}

	/**
	 * 获取 jssdk config
	 * @return array
	 */
	public function getJssdkConfig($url = '') {
		global $_W;
		$jsapiTicket = $this->getJsApiTicket();
		if (is_error($jsapiTicket)) {
			$jsapiTicket = $jsapiTicket['message'];
		}
		$nonceStr = random(25);
		$timestamp = TIMESTAMP;
		$url = empty($url) ? $_W['siteurl'] : $url;
		$arr = array(
			"jsapi_ticket" => $jsapiTicket,
			"nonce_str" => $nonceStr,
			"timestamp" => $timestamp,
			"url" => urlencode($url)
		);
		ksort($arr);
		$string1 = http_build_query($arr);
		$signature = sha1($string1);
		$config = array(
			"appId"		=> $this->account['original'],
			"nonceStr"	=> $nonceStr,
			"timestamp" => "$timestamp",
			"signature" => $signature,
			"url" => urlencode($url),
		);
		return $config;
	}

	/**
	 * 获取熊掌号前端显示的素材支持内容
	 * @return array
	 */
	public function getMaterialSupport() {
		return array(
			'mass' => array('news'=> false, 'image'=> false,'voice'=> false,'basic'=> false),
			'chats' => array('basic'=> false,'news'=> false,'image'=> false,'music'=> true,'voice'=> false,'video'=> true)
		);
	}

}