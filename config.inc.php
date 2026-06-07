<?php
// site root path
define('__TYPECHO_ROOT_DIR__', dirname(__FILE__));

// plugin directory (relative path)
define('__TYPECHO_PLUGIN_DIR__', '/usr/plugins');

// theme directory (relative path)
define('__TYPECHO_THEME_DIR__', '/usr/themes');

// admin directory (relative path)
define('__TYPECHO_ADMIN_DIR__', '/admin/');

// register autoload
require_once __TYPECHO_ROOT_DIR__ . '/var/Typecho/Common.php';

// init
\Typecho\Common::init();

// config db

$dbPrefix = getenv('TYPECHO_PREFIX') ?: 'typecho_'; // 默认表前缀
$dbHost = getenv('TYPECHO_HOST');
$dbPort = getenv('TYPECHO_PORT');
$dbUser = getenv('TYPECHO_USERNAME');
$dbPassword = getenv('TYPECHO_PASSWORD');
$dbName = getenv('TYPECHO_DATABASE');
$dbCharset = getenv('TYPECHO_CHARSET') ?: 'utf8mb4'; // 默认字符集
$dbEngine = getenv('TYPECHO_ENGINE') ?: 'MyISAM'; // 默认存储引擎
$dbSslCa = getenv('TYPECHO_SSL_CA');

$db = new \Typecho\Db('Pdo_Mysql', $dbPrefix);
$db->addServer(array (
  'host' => $dbHost,
  'port' => $dbPort,
  'user' => $dbUser,
  'password' => $dbPassword,
  'charset' => $dbCharset,
  'database' => $dbName,
  'engine' => $dbEngine,
  'sslCa' => $dbSslCa,
  'sslVerify' => false,
), \Typecho\Db::READ | \Typecho\Db::WRITE);
\Typecho\Db::set($db);
