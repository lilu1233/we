#!/usr/bin/env php
<?php
/**
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 * User: fanyk
 * Date: 2017/8/10
 * Time: 16:24.
 *  创建更新文件 php console make:upgrade name=create_update_wxapp
 *  执行更新     php console upgrade.
 */
error_reporting(0);

if (strtoupper(php_sapi_name()) != 'CLI') {
	We7Command::line('只能在命令行执行');
}

set_exception_handler(function(Error $ex){
//	echo $ex->getTraceAsString();
//	echo $ex->getMessage();
	return true;
});
set_error_handler(function($errno, $errstr, $errfile, $errline){
//	echo $errno.':'.$errstr.'file:'.$errfile.':line'.$errline;
	return true;
});

if(!function_exists('pdoQuery')) {
	function pdoQuery() {
		load()->classs('query');
		return new Query();

	}
}

$_W = array();
set_time_limit(0);
error_reporting(0);
ini_set('display_errors', 1);
@ini_set('memory_limit', '1356M');
$path = dirname(__FILE__);
chdir($path);

We7Command::execute();

abstract class We7Command {
	protected $name;
	protected $description;

	public function __construct()
	{
		$this->init();
	}

	/**
	 *  安装脚本不能加载此文件 因config.php 文件不存在
	 */
	protected function init() {
		global $_W;
//		error_reporting(0);
		error_reporting(E_ALL);
		include_once __DIR__.'/framework/bootstrap.inc.php';
	}

	public static function execute() {
		$command = self::createCommand();
		if ($command) {
			$command->handle();

			return;
		}
		self::line('当前命令不存在');
		self::line('支持的命令如下:');
		self::line('php console make:upgrade name=更新的文件名 => 创建更新文件');
		self::line('php console upgrade => 执行更新 ');
		self::line('php console install => 执行安装微擎脚本');
	}

	public static function createCommand() {
		$argv = $_SERVER['argv'];
		$argc = $_SERVER['argc'];

		if ($argc > 1) {
			$commandName = $argv[1];
			if ($commandName == 'make:upgrade') {
				return new We7CreateUpgradeCommand();
			}
			if ($commandName == 'upgrade') {
				return new We7UpgradeCommand();
			}
			if ($commandName == 'install') {
				return new We7InstallCommand();
			}
		}

		return null;
	}

	abstract public function handle();

	/**
	 * @param string|int $name
	 *
	 * @return bool
	 */
	public function hasArgument($name) {
		return !is_null($this->argument($name));
	}

	/**
	 * Get the value of a command argument.
	 *
	 * @param string $key
	 *
	 * @return string|array
	 */
	public function argument($key) {
		$arguments = $this->arguments();
		if (isset($arguments[$key])) {
			return $arguments[$key];
		}

		return null;
	}

	/**
	 * Get all of the arguments passed to the command.
	 *
	 * @return array
	 */
	public function arguments() {
		$argv = $_SERVER['argv'];
		$argc = $_SERVER['argc'];
		$result = array();
		if ($argc >= 2) {
			$args = array_slice($argv, 2);
			array_map(function ($item) use (&$result) {
				list($key, $value) = explode('=', $item);
				$result[$key] = $value;
			}, $args);

			return $result;
		}

		return $result;
	}

	public static function line($string, $eol = true) {
		if (strtoupper(PHP_OS) != 'LINUX') {
//			$string = iconv('UTF-8', 'GBK', $string);
		}
		$string = $eol ? $string.PHP_EOL : $string;
		fwrite(STDOUT, $string.PHP_EOL);
	}

	/**
	 *  确定是否更新.
	 *
	 * @param string $question
	 * @param bool   $default
	 *
	 * @return bool
	 */
	public function confirm($question, $yes = 'Y') {
		$this->line($question.'(Y/N)');
		$value = fscanf(STDIN, '%s');
		if (strtoupper($value[0]) == 'Y') {
			return true;
		}

		return false;
	}

	public function input($question, $default = null) {
		$defaults = is_array($default) ? implode($default, '|') : $default;
		$this->line("$question($defaults) :", false);
		$value = fscanf(STDIN, '%s');
		$value = $value[0];
		if (empty($value)) {
			return $default;
		}
		if (is_array($default) && !in_array($value, $default)) {
			return null;
		}
		return $value;

	}

	protected function debug($value) {
		$this->line(is_array($value) ? var_export($value, true) : $value);
	}
}

class We7CreateUpgradeCommand extends We7Command {
	protected $name = 'make:upgrade';

	protected function init()
	{
		parent::init(); // TODO: Change the autogenerated stub

	}

	public function handle() {
		$name = $this->argument('name');
		$path = $this->getPath();
		$prefix = date('Y_m_d_His');
		@mkdir($path, 0777, true);
		$filepath = $path.'/'.$prefix.'_'.$name.'.php';
		echo $filepath;
		file_put_contents($filepath, $this->template($name));
	}

	private function getPath() {
		$dir = $this->getDir();

		return 'upgrade'.DIRECTORY_SEPARATOR.$dir;
	}

	private function getDir() {
		return ''.$this->getVersion();
	}

	private function getVersion() {
		return IMS_VERSION;
	}

	private function toClassName($name) {
		$value = ucwords(str_replace(array('-', '_'), ' ', $name));

		return str_replace(' ', '', $value);
	}

	private function template($name) {
		$time = time();
		$version = $this->getVersion();
		$namespace = 'We7\V'.str_replace('.', '', $version);
		$name = $this->toClassName($name);
		$template = <<<EOT
<?php

namespace $namespace;

defined('IN_IA') or exit('Access Denied');
/**
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 * Time: $time
 * @version $version
 */

class $name {

	/**
	 *  执行更新
	 */
	public function up() {

	}
	
	/**
	 *  回滚更新
	 */
	public function down() {
		

	}
}
		
EOT;

		return $template;
	}
}

class We7UpgradeCommand extends We7Command {
	protected $name = 'upgrade';
	private $project_upgrade_files = array(); // upgrade 目录下的所有文件

	/**
	 *  处理更新脚本.
	 */
	public function handle() {
		$filename = $this->argument('filename');
		$version = $this->argument('version');
		// 强制执行单个文件更新
		if (!empty($filename) && !empty($version)) {
			$this->update_single_file($filename, $version);

			return;
		}
		//执行全部更新
		$this->update();
	}

	private function update_single_file($filename, $version) {
		$filepath = 'upgrade'.DIRECTORY_SEPARATOR.$version.DIRECTORY_SEPARATOR.$filename.'.php';
		$include = include_once $filepath;
		if (!$include) {
			self::line($filepath.'文件未找到');

			return;
		}
		$batch = $this->get_max_batch();
		$this->update_single($filename, $version, $batch);

		return;
	}

	public function update() {
		$this->check_table();
		$files = $this->diff_files();
		echo 'filecount'.count($files);
		if (count($files) == 0) {
			$this->line('没有要更新的文件');

			return;
		}
		echo 'confirm';
		if ($this->confirm('确认更新吗?')) {
			echo 'doupdate';
			$this->doUpgrade($files);
		}
	}

	/**
	 * 执行 更新.
	 */
	private function doUpgrade($files) {
		$batch = $this->get_max_batch();
		foreach ($files as $filename => $version) {
			$this->update_single($filename, $version, $batch);
		}
	}

	private function get_max_batch() {
		$batch = pdo_fetch('SELECT MAX(batch) as batch FROM '.tablename('upgrade'));
		$batch = $batch['batch'] + 1;

		return $batch;
	}

	private function update_single($filename, $version, $batch) {
		$include_path = 'upgrade/'.$version.'/'.$filename.'.php';
		include_once $include_path;
		$class = $this->resolve($filename, $version);
		$ignore = 'ignore';
		if (property_exists($class, $ignore)) {
			return; //如果有ignore属性 直接略过 (云服务更新可能不需要这个更新文件)
		}
		$description = 'description';
		$des_exits = property_exists($class, $description);
		$upinfo = '更新'.$version.':'.$filename.'';
		if ($des_exits) {
			$upinfo .= ':'.$class->{$description};
		}
		$this->line($upinfo);
		if (method_exists($class, 'up')) {
			call_user_func(array($class, 'up'));
		}
		pdo_insert('upgrade', array('file' => $filename, 'batch' => $batch, 'version' => $version, 'createtime' => time()));
	}

	public function resolve($file, $version) {
		$class = $this->studly(implode('_', array_slice(explode('_', $file), 4)));
		$namespace = '\\We7\\V'.str_replace('.', '', $version);
		$class = $namespace.'\\'.$class;

		return new $class();
	}

	/**
	 *  名称转class 名.
	 *
	 * @param $name
	 *
	 * @return string
	 */
	private function studly($name) {
		$value = ucwords(str_replace(array('-', '_'), ' ', $name));

		return str_replace(' ', '', $value);
	}

	/**
	 *  获取diff 文件.
	 *
	 * @return array
	 */
	private function diff_files() {
		$dbfiles = pdoQuery()->from('upgrade')->getall('file'); //获取数据库目录
		$dbfiles = array_keys($dbfiles); //数据库文件
		$this->project_upgrade_files = $this->get_project_upgrade_files();
		$files = array_keys($this->project_upgrade_files);
		$diff_files = array_diff_key($this->project_upgrade_files, array_flip($dbfiles)); // 比对差异文件
		return $diff_files;
	}

	/**
	 *  检查表是否存在.
	 */
	private function check_table() {
		$exits = pdo_tableexists('upgrade');
		if (!$exits) {
			$isCreate = pdo_query("CREATE TABLE IF NOT EXISTS `ims_upgrade` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`file` varchar(255) NOT NULL COMMENT 'upgrade 目录名',
`createtime` int(11) NOT NULL COMMENT '创建时间',
`batch` int(11) NOT NULL COMMENT '批次',
`version` varchar(255) NOT NULL DEFAULT '' COMMENT '版本号',
PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8; ");
		}
	}

	/**
	 *  获取upgrade 下的所有文件
	 * return [filename=>version].
	 *
	 * @return array
	 */
	private function get_project_upgrade_files() {
		$directory = new \RecursiveDirectoryIterator('./upgrade');
		$iterator = new \RecursiveIteratorIterator($directory);
		$files = array();
		foreach ($iterator as $file) {
			if ('php' == pathinfo($file->getFilename(), PATHINFO_EXTENSION)) {
				$path = $file->getPath();
				$version = pathinfo($path, PATHINFO_BASENAME);
				$basename = $file->getBasename('.php');
				if ($basename != 'upgrade' && $basename != 'upgrade_0.x_to_1.0') {
					$files[$basename] = $version;
				}
			}
		}

		return $files;
	}
}

class We7InstallCommand extends We7Command {

	protected $name = 'install';
	private $table_pre = 'ims_';

	protected function init()
	{
//		parent::init(); // TODO: Change the autogenerated stub
	}

	public function handle()
	{
		gc_disable();
		if (extension_loaded('pdo') && !extension_loaded('pdo_mysql')) {
			$this->line('安装出错 请确保pdo 和 pdo_mysql扩展已开启');
			return;
		}
		ini_set('memory_limit', '1024M');
		$this->installDB();
		$this->addAdmin();
		$this->updateTable();
		$this->line('微擎数据安装成功 默认用户名 admin 密码 123456');
	}



	private function host() {
		$result = $this->input("请输入有效的数据库域名或ip<please enter db host or ip>", '127.0.0.1');
		if (!filter_var($result, FILTER_VALIDATE_IP)) {
			return $this->host();
		}
		return $result;
	}

	private function port() {
		$result = $this->input('请输入数据库端口(please enter db port)', 3306);
		$port = intval($result);
		if (intval($port) <= 1024) {
			$this->line("请输入有效的数据库端口<please enter valid port>", false);
			return $this->port();
		}
		return $port;
	}

	private function dbName() {
		$result = $this->input("请输入数据库名称<please enter valid db name>", 'we7');
		if (empty($result)) {
			return $this->dbName();
		}
		return $result;
	}

	private function dbUserName() {
		$result = $this->input('请输入数据库用户名<please enter valid db username>', 'root');
		if (empty($result)) {
			return $this->dbUserName();
		}
		return $result;
	}

	private function dbPassword() {
		$result = $this->input('请输入数据库密码<please enter valid db password>', '');
		return $result;
	}

	private function dbconfig() {
		$this->host = $this->host();
		$this->port = $this->port();
		$this->dbName = $this->dbName();
		$this->userName = $this->dbUserName();
		$this->password  = $this->dbPassword();
	}

	private function createDB($dbName) {
		$dsn = "mysql:dbName=;host={$this->host};port={$this->port};charset=utf8";
		$pdo = new PDO($dsn, $this->userName, $this->password);
		$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
		$pdo->exec('create database '.$dbName);
		return $pdo;
	}
	/**
	 * config 文件未写入 pdo 内置方法暂不可用
	 */
	private function createPDO($useDbName = true) {
		$dbName = $useDbName ? $this->dbName : '';
		$dsn = "mysql:dbname={$dbName};host={$this->host};port={$this->port};charset=utf8";
		$pdo = new PDO($dsn, $this->userName, $this->password);
		$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
		return $pdo;
	}
	/**
	 *  检查数据库连接
	 */
	private function checkConnection() {
		try {
			$pdo = $this->createPDO();
			$sql = "SET NAMES 'utf8';";
			$pdo->exec($sql);
		}catch (PDOException $e) {
			if ($e->getCode() == 1045) {
				$this->line('数据库连接错误, 请关闭终端重新执行此命令'.$e->getCode().':'.$e->getMessage());
				return false;
			}
		}
		return true;
	}

	private function exec($sql, $throw = true) {
		try {
			$pdo = $this->createPDO();
			$default = "SET NAMES 'utf8';";
			$pdo->exec($default);
			$pdo->exec($sql);
		}catch (PDOException $e) {
			if ($throw) {
				$this->line($e->getMessage());
				throw $e;
			}
		}
		return true;
	}

	/**
	 * 检查数据库是否存在
	 * @return bool
	 */
	private function checkDBName() {
		try {
			$pdo = $this->createPDO(false);
			$sql = "SELECT * FROM information_schema.SCHEMATA where SCHEMA_NAME='{$this->dbName}'";
			$result = $pdo->query($sql)->fetch();
			if (!empty($result)) {
				$this->line('数据库已存在,请重新指定数据库',false);
				$this->dbName = $this->dbName();
				return $this->checkDBName();
			}
		}catch (PDOException $e) {
			if ($e->getCode() == 1045) {
				$this->line('数据库连接错误, 请关闭终端重新执行此命令'.$e->getCode().':'.$e->getMessage());
				return false;
			}
			$this->line('dbname'.$e->getMessage());
			return false;
		}

		try {
			$this->debug('create database');
			$this->createDB($this->dbName);
		}catch (Exception $e) {
			$this->line('创建数据库失败:'.$e->getMessage());
			return false;
		}

		return true;
	}

	/**
	 *   创建ip 端口
	 */
	private function createConfig() {
		return  <<<EOT

<?php
defined('IN_IA') or exit('Access Denied');

\$config = array();

\$config['db']['master']['host'] = '$this->host';
\$config['db']['master']['username'] = '$this->userName';
\$config['db']['master']['password'] = '$this->password';
\$config['db']['master']['port'] = '$this->port';
\$config['db']['master']['database'] = '$this->dbName';
\$config['db']['master']['charset'] = 'utf8';
\$config['db']['master']['pconnect'] = 0;
\$config['db']['master']['tablepre'] = 'ims_';

\$config['db']['slave_status'] = false;
\$config['db']['slave']['1']['host'] = '';
\$config['db']['slave']['1']['username'] = '';
\$config['db']['slave']['1']['password'] = '';
\$config['db']['slave']['1']['port'] = '3307';
\$config['db']['slave']['1']['database'] = '';
\$config['db']['slave']['1']['charset'] = 'utf8';
\$config['db']['slave']['1']['pconnect'] = 0;
\$config['db']['slave']['1']['tablepre'] = 'ims_';
\$config['db']['slave']['1']['weight'] = 0;

\$config['db']['common']['slave_except_table'] = array('core_sessions');

// --------------------------  CONFIG COOKIE  --------------------------- //
\$config['cookie']['pre'] = '{cookiepre}';
\$config['cookie']['domain'] = '';
\$config['cookie']['path'] = '/';

// --------------------------  CONFIG SETTING  --------------------------- //
\$config['setting']['charset'] = 'utf-8';
\$config['setting']['cache'] = 'mysql';
\$config['setting']['timezone'] = 'Asia/Shanghai';
\$config['setting']['memory_limit'] = '256M';
\$config['setting']['filemode'] = 0644;
\$config['setting']['authkey'] = '{authkey}';
\$config['setting']['founder'] = '1';
\$config['setting']['development'] = 0;
\$config['setting']['referrer'] = 0;

// --------------------------  CONFIG UPLOAD  --------------------------- //
\$config['upload']['image']['extentions'] = array('gif', 'jpg', 'jpeg', 'png');
\$config['upload']['image']['limit'] = 5000;
\$config['upload']['attachdir'] = 'attachment';
\$config['upload']['audio']['extentions'] = array('mp3');
\$config['upload']['audio']['limit'] = 5000;

// --------------------------  CONFIG MEMCACHE  --------------------------- //
\$config['setting']['memcache']['server'] = '';
\$config['setting']['memcache']['port'] = 11211;
\$config['setting']['memcache']['pconnect'] = 1;
\$config['setting']['memcache']['timeout'] = 30;
\$config['setting']['memcache']['session'] = 1;


// --------------------------  CONFIG PROXY  --------------------------- //
\$config['setting']['proxy']['host'] = '';
\$config['setting']['proxy']['auth'] = '';

EOT;

	}

	private function rootPath() {
		return getcwd();
	}

	/**
	 *  获取数据库建表语句和插入数据sql
	 */
	private function dbSql() {
		$path = $this->rootPath().'/data/db.php';
		if (!file_exists($path)) {
			$this->line('数据库sql 脚本不存在, 请确保'.$path.'文件存在', false);
		}
		$dst = include_once $path;
		return $dst;
	}
	/**
	 *  执行安装数据库
	 */
	private function installDB() {
		ini_set('display_errors', 1);

//		set_exception_handler(function($e){
//			var_dump($e);
//		});
//
//		set_error_handler(function(){
//			var_dump(func_get_args());
//		});
		$this->dbconfig();
		$connect = $this->checkConnection();
		if (!$connect) {
			return;
		}
		if (!$this->checkDBName()) {
			return ;
		}

		if (!$scheme_sql = $this->dbSql()) {
			return;
		}
		foreach ($scheme_sql['schemas'] as $schema) {
			$this->line('新建:'.$schema['tablename'].'表', false);
			$sql = $this->local_create_sql($schema);
			try {
				$this->exec($sql);
			}catch (Exception $e) {
				$this->debug('debug start');
				$this->debug($sql);
				$this->debug('debug end');
				break;
			}

		}

		foreach ($scheme_sql['datas'] as $sql) {
			try {
				$this->exec($sql);
			}catch (Exception $e) {
				$this->debug('debug start');
				$this->debug($sql);
				$this->debug('debug end');
			}
		}

		$config = $this->createConfig();
		file_put_contents($this->rootPath().'/data/config.php', $config);
	}


	private function addAdmin() {
		$tableName = $this->table_pre.'users';
		$sql = <<<EOT
		 INSERT INTO $tableName(`uid`, `owner_uid`, `groupid`, `founder_groupid`, `username`, `password`, `salt`, `type`, `status`, `joindate`, `joinip`, `lastvisit`, `lastip`, `remark`, `starttime`, `endtime`, `register_type`, `openid`) VALUES (1, 0, 0, 1, 'admin', 'b8ae8320fa6192d40ab1aac7c191272056055299', '2b82020d', 2, 127, 1520388458, '1520388458', 444, '127.0.0.1', '中华人民共和国万岁和国万岁和国万岁和国万岁和国万岁和国万岁', 1456743079, 0, 1, '0');
EOT;
		try {
			$this->exec($sql);
		}catch (Exception $e) {
			$this->line('插入默认用户出错,请手动执行本sql');
			$this->line('start sql');
			$this->line($sql);
			$this->line('end sql');
		}


	}

	private function updateTable() {
		$this->line('开始数据库表结构更新: start update table schama....');
		$command = new We7UpgradeCommand();
		$command->update();
		$this->line('开始数据库表结构更新完成: end update table schama');
	}


	/**
	 * schme 返回建表语句
	 * @param $schema
	 * @return string
	 */
	private function local_create_sql($schema) {
		$pieces = explode('_', $schema['charset']);
		$charset = $pieces[0];
		$engine = $schema['engine'];
		$sql = "CREATE TABLE IF NOT EXISTS `{$schema['tablename']}` (\n";
		foreach ($schema['fields'] as $value) {
			if(!empty($value['length'])) {
				$length = "({$value['length']})";
			} else {
				$length = '';
			}

			$signed  = empty($value['signed']) ? ' unsigned' : '';
			if(empty($value['null'])) {
				$null = ' NOT NULL';
			} else {
				$null = '';
			}
			if(isset($value['default'])) {
				$default = " DEFAULT '" . $value['default'] . "'";
			} else {
				$default = '';
			}
			if($value['increment']) {
				$increment = ' AUTO_INCREMENT';
			} else {
				$increment = '';
			}

			$sql .= "`{$value['name']}` {$value['type']}{$length}{$signed}{$null}{$default}{$increment},\n";
		}
		foreach ($schema['indexes'] as $value) {
			$fields = implode('`,`', $value['fields']);
			if($value['type'] == 'index') {
				$sql .= "KEY `{$value['name']}` (`{$fields}`),\n";
			}
			if($value['type'] == 'unique') {
				$sql .= "UNIQUE KEY `{$value['name']}` (`{$fields}`),\n";
			}
			if($value['type'] == 'primary') {
				$sql .= "PRIMARY KEY (`{$fields}`),\n";
			}
		}
		$sql = rtrim($sql);
		$sql = rtrim($sql, ',');

		$sql .= "\n) ENGINE=$engine DEFAULT CHARSET=$charset;\n\n";
		return $sql;
	}
}
