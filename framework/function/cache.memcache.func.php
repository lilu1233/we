<?php 
/**
 * MemCached缓存
 * 
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 */
defined('IN_IA') or exit('Access Denied');

function cache_memcache() {
	global $_W;
	static $memcacheobj;
	if (!extension_loaded('memcache')) {
		return error(1, 'Class Memcache is not found');
	}
	if (empty($memcacheobj)) {
		$config = $_W['config']['setting']['memcache'];
		$memcacheobj = new Memcache();
		if($config['pconnect']) {
			$connect = $memcacheobj->pconnect($config['server'], $config['port']);
		} else {
			$connect = $memcacheobj->connect($config['server'], $config['port']);
		}
		if(!$connect) {
			return error(-1, 'Memcache is not in work');
		}
	}
	return $memcacheobj;
}

/**
 * 取出缓存的单条数据
 *
 * @param 缓存键名 ，多个层级或分组请使用:隔开
 * @return mixed
 */
function cache_read($key, $forcecache = true) {
	$key = cache_namespace($key);
	
	$memcache = cache_memcache();
	if (is_error($memcache)) {
		return $memcache;
	}
	$result = $memcache->get(cache_prefix($key));
	if (empty($result) && empty($forcecache)) {
		$dbcache = pdo_get('core_cache', array('key' => $key), array('value'));
		if (!empty($dbcache['value'])) {
			$result = iunserializer($dbcache['value']);
			$memcache->set(cache_prefix($key), $result);
		}
	}
	return $result;
}


/**
 * 检索缓存中指定层级或分组的所有缓存
 *
 * @param 缓存分组
 * @return mixed
 */
function cache_search($key) {
	return cache_read(cache_prefix($key));
}

/**
 * 将值序列化并写入缓存
 *
 * @param string $key
 * @param mixed $data
 * @return mixed
 */
function cache_write($key, $value, $ttl = 0, $forcecache = true) {
	$key = cache_namespace($key);
	$memcache = cache_memcache();
	if (is_error($memcache)) {
		return $memcache;
	}
	if (empty($forcecache)) {
		$record = array();
		$record['key'] = $key;
		$record['value'] = iserializer($value);
		pdo_insert('core_cache', $record, true);
	}
	if ($memcache->set(cache_prefix($key), $value, MEMCACHE_COMPRESSED, $ttl)) {
		return true;
	} else {
		return false;
	}
}

/**
 * 删除某个键的缓存数据
 * @param string $key
 * @return mixed 
 */
function cache_delete($key, $forcecache = true) {
	$memcache = cache_memcache();
	if (is_error($memcache)) {
		return $memcache;
	}
	$cache_relation_keys = cache_relation_keys($key);
	if (is_error($cache_relation_keys)) {
		return $cache_relation_keys;
	}
	if (is_array($cache_relation_keys) && !empty($cache_relation_keys)) {
		foreach ($cache_relation_keys as $key) {
			$cache_info = cache_load($key);
			if (!empty($cache_info)) {
				$origins_cache_key = $key;
				$key = cache_namespace($key);
				if (empty($forcecache)) {
					pdo_delete('core_cache', array('key' => $key));
				}
				$result = $memcache->delete(cache_prefix($key));
				unset($GLOBALS['_W']['cache'][$origins_cache_key]);
				if (!$result) {
					return error(1, '缓存: ' . $key . ' 删除失败！');
				}
			}
		}
	}
	return true;
}

/**
 * 清空缓存指定前缀或所有数据
 * @param string $prefix
 */
function cache_clean($prefix = '') {
	if (!empty($prefix)) {
		$cache_relation_keys = cache_relation_keys($prefix);
		if (is_error($cache_relation_keys)) {
			return $cache_relation_keys;
		}
		if (is_array($cache_relation_keys) && !empty($cache_relation_keys)) {
			foreach ($cache_relation_keys as $key) {
				preg_match_all('/\:([a-zA-Z0-9\-\_]+)/', $key, $matches);
				$cache_namespace = cache_namespace('we7:' . $matches[1][0]);
				pdo_delete('core_cache', array('key LIKE' => $cache_namespace . '%'));
				unset($GLOBALS['_W']['cache']);
				cache_namespace('we7:' . $matches[1][0], true);
			}
		}
		return true;
	}
	$memcache = cache_memcache();
	if (is_error($memcache)) {
		return $memcache;
	}
	if ($memcache->flush()) {
		unset($GLOBALS['_W']['cache']);
		pdo_delete('core_cache');
		return true;
	} else {
		return false;
	}
}

/**
 * Memcache不支持命名空间，自己实现一个
 * 将key中第一段的值做为命名空间，方便按前缀删除（系统有自己的前缀所以以第一，第二段）
 * @param string $key
 * @return mixed
 */
function cache_namespace($key, $forcenew = false) {
	if (!strexists($key, ':')) {
		$namespace_cache_key = $key;
	} else {
		list($key1, $key2) = explode(':', $key);
		if ($key1 == 'we7') {
			$namespace_cache_key = $key2;
		} else {
			$namespace_cache_key = $key1;
		}
	}
	if (!in_array($namespace_cache_key, array('unimodules', 'user_modules', 'system_frame'))) {
		return $key;
	}
	
	//获取命名空间
	$namespace_cache_key = 'cachensl:' . $namespace_cache_key;
	$memcache = cache_memcache();
	if (is_error($memcache)) {
		return $memcache;
	}
	$namespace = $memcache->get(cache_prefix($namespace_cache_key));
	if (empty($namespace) || $forcenew) {
		$namespace = random(5);
		$memcache->set(cache_prefix($namespace_cache_key), $namespace, MEMCACHE_COMPRESSED, 0);
	}
	return $namespace . ':' . $key;
}

function cache_prefix($key) {
	return $GLOBALS['_W']['config']['setting']['authkey'] . $key;
}