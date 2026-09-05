<?php

// 在myproject目录下添加cli.php, 这个是启动项目的入口文件

date_default_timezone_set('Asia/Shanghai');
include __DIR__ . '/vendor/autoload.php';

$appName = ucfirst($_SERVER['argv'][2]);
// 定义app name
define('APP_NAME', $appName);
// 启动目录
defined('START_DIR_ROOT') or define('START_DIR_ROOT', __DIR__);
// composer安装时，必须定义成如下路径
defined('SRC_DIR_ROOT') or define('SRC_DIR_ROOT', __DIR__ . "/vendor/bingcool/swoolefy/src");
// 应用父目录
defined('ROOT_PATH') or define('ROOT_PATH', __DIR__);
// 应用目录
defined('APP_PATH') or define('APP_PATH', __DIR__ . '/' . $appName);

registerNamespace(APP_PATH);

// 你的项目命名为App，对应协议为http协议服务器，支持多个项目的，只需要在这里添加好项目名称与对应的协议即可
define('APP_META_ARR', [
    'App' => [
        'protocol' => 'http',
        'worker_port' => 9502,
    ]
]);
// 定义服务端口
define('WORKER_PORT', APP_META_ARR[$appName]['worker_port']);
define('IS_WORKER_SERVICE', 0);
define('IS_DAEMON_SERVICE', 0);
define('IS_SCRIPT_SERVICE', 0);
define('IS_CRON_SERVICE', 0);
define('PHP_BIN_FILE', '/usr/bin/php');

define('WORKER_START_SCRIPT_FILE', str_contains($_SERVER['SCRIPT_FILENAME'], $_SERVER['PWD']) ? $_SERVER['SCRIPT_FILENAME'] : $_SERVER['PWD'] . '/' . $_SERVER['SCRIPT_FILENAME']);
define('WORKER_SERVICE_NAME', makeServerName($appName));
define('WORKER_PID_FILE_ROOT', '/tmp/workerfy/log/' . WORKER_SERVICE_NAME);
define('WORKER_CTL_LOG_FILE', WORKER_PID_FILE_ROOT . '/ctl.log');
define('CLI_TO_WORKER_PIPE', WORKER_PID_FILE_ROOT . '/cli.pipe');
define('WORKER_TO_CLI_PIPE', WORKER_PID_FILE_ROOT . '/ctl.pipe');
define('SERVER_START_LOG_JSON_FILE', WORKER_PID_FILE_ROOT . '/start.json');

// nacos.yaml 完整路径（环境变量 NACOS_FILE_PATH 可覆盖，默认 APP_PATH/nacos.yaml）
$nacosFilePath = getenv('NACOS_FILE_PATH');
define('NACOS_FILE_PATH', (false !== $nacosFilePath && '' !== $nacosFilePath) ? $nacosFilePath : APP_PATH . '/nacos.yaml');
include dirname(SRC_DIR_ROOT) . '/swoolefy';