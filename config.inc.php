<?php
/**
 * config.inc.php 网站配置文件
 */
date_default_timezone_set("Asia/Shanghai");
session_set_cookie_params(0);
//    set_time_limit(0);
//问卷接口key
define('ONLINE_API_KEY', 'F3CA98CC222E4507ADBB955A60EEC6E1C8F9453A3E4B45EE814F98646809FC1D');
//基础路径配置
define('DS', DIRECTORY_SEPARATOR);
define('ROOT_PATH', dirname(__FILE__) . DS);
define('MODEL', 'model');
define('CONTROLLER', 'controller');
define('VIEW', 'view');
define('LIB', 'lib');
define('COMMON', 'common');
define('PUBLIC', 'public');
define('WIDGET', 'widget');
define('API', 'api');
define('LIB_PATH', ROOT_PATH . LIB . DS);
define('MODEL_PATH', ROOT_PATH . MODEL . DS);
define('API_PATH', ROOT_PATH . API . DS);
define('CONTROLLER_PATH', ROOT_PATH . CONTROLLER . DS);
define('VIEW_PATH', ROOT_PATH . VIEW . DS);
define('LOG_PATH', ROOT_PATH . 'log' . DS);
//密码key
define('PWD_KEY', 'F3CA98CC222E4507ADBB955A60EEC6E1C8F9453A3E4B45EE814F98646809FC1D');
//IRD产品整合接口
define('IRD_SERVER_URL', 'http://sys.itracker.cn/api/WebForm1.aspx');
define('IRD_SERVER_URL_SUPER', 'http://sys.itracker.cn/api/WebForm4.aspx');
//******************** 邮件配置信息 ********************************
define('EMAIL_SMTPSERVER', 'smtp.partner.outlook.cn');//SMTP服务器
define('EMAIL_SMTPSERVERPORT', '587');//SMTP服务器端口
define('EMAIL_SMTPUSER', 'no-reply@iresearch.com.cn');//SMTP服务器的用户帐号
define('EMAIL_SMTPPASS', 'DyxW0286');//SMTP服务器的用户密码
//******************************************************************
//********************** 浪池短信配置信息 **************************
define('SMS_SERVER_URL', 'http://www.lanz.net.cn/LANZGateway/DirectSendSMSs.asp');
define('SMS_SERVER_IP', 'www.lanz.net.cn');
define('SMS_USER_ID', '813860');
define('SMS_ACCOUNT', 'data5');
define('SMS_PWD', '82D05B779D9301FBB9D3BB1D46C6181F29DC0323');
define('SMS_SIGNATURE', '【艾瑞数据】');
define('SMS_CONTENT', '您的验证码为(CODE)，该验证码5分钟内有效。如非本人操作请忽略此短信！');
define('SMS_CONTENT_CHECK', '您的手机验证码为(CODE)，该验证码5分钟内有效。如非本人操作请忽略此短信！');
//************************* 国际短信 **************************
define('NATION_SMS_URL', 'https://sms.yunpian.com/v2/');

define('IS_TEST', false);

define('SMS_TEMP_LOGIN', '2232144');
define('SMS_TEMP_NATION_LOGIN', '2269430');
define('SMS_TEMP_CHECK', '');

define('NATION_API', 'a2bb4bd8287acc2115db17fa300ca766');
//define('NATION_API', 'd409b7b08bfc58fd6aa0ef7993b9323f');
define('SMS_CONTENT_NATION', '【iResearch】Your verification code: #CODE#');
define('Android_APP_key', 'iDataApp');
//************* IP-API *****************

define('IP_API', 'http://ip-api.com/php/');
define('IP_FIELDS', 262143);

//************************** 阿里云短信 *****************
define('ALI_ACCESS_KEY', 'LTAInR6x2qbAD7lR');
define('ALI_ACCESS_KEY_SECRET', '5WgaBDi0nidcThqPQDqvj9ug5ALUle');

//******************************************************************
//站点配置
//	define('WEBSITE','http://localhost');
define('WEBSITE', $_SERVER['SERVER_ADDR']);
define('WEBSITE_URL', '');
define('WEBSITE_SOURCE_URL', WEBSITE_URL . 'public');
define('WEBSITE_TITLE', '艾瑞iClick');
define('RECEIVE_PATH', ROOT_PATH . '/userFiles/'); //接受上传目录
//    define('USER_PIC', 'http://10.10.21.163/iclick-api/userFiles/'); //文件查看路径
//    define('API_URL','http://ifocus.iclick.com.cn:8888/services/'); //后台同步接口
//    define('API_KEY','56f57434b7d36a5d4f0931b9978f5f47'); //后台同步KEY
//导出报表配置
//define('EXPORT_PIC','http://203.156.255.148:81/chart.php');
//    define('EXPORT_PIC', 'http://180.169.19.166/graph_api/chart.php');
//页面条数
define("__PAGENUM__", 10);
//session 前缀
define('SITE_PREFIX', 'idex');
//session 失效时间
define('SESSION_TIME_OUT', false);
//cookie 失效时间
define('COOKIE_TIME_OUT', 7 * 24 * 3600);
//redis 失效时间
define('REDIS_TIME_OUT', 86400);
define('TOKEN_TIME_OUT', 8); // hours
define('VERSION', 'idata_desktop_api');
//下拉框
define('SELECT_HOUR', 24 * 3600);
define('SELECT_DAY', 30 * 86400);
define('KEY', '534b44a19bf18d20b71ecc4eb77c572f');
//cookie 域名
define('COOKIE_DOMAIN', '');
//是否开启缓存
define('CACHE_ON', false);
//是否开启调试
define('DEBUG', false || isset($_GET['debug']));
define('DEBUG_LOG', TRUE); //记录日志
define('START_TIME', microtime(true));
define('OPEN_ME', false);

define('NEED_MAIL', ['wanghaiyan@iresearch.com.cn', 'cswdc@icloud.com', 'rai@valuesccg.com', 'hank@iresearch.com.cn']);

define('NOW', date('Y-m-d H:i:s'));
if (DEBUG) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL ^ E_NOTICE);
} else {
    error_reporting(0);
}

require_once(ROOT_PATH . 'vendor' . DS . 'autoload.php');

//引用COMMON库类文件
require_once(ROOT_PATH . COMMON . DS . COMMON . '.fun.php');
require_once(ROOT_PATH . COMMON . DS . COMMON . '.request.php');
require_once(ROOT_PATH . COMMON . DS . COMMON . '.session.php');
require_once(ROOT_PATH . COMMON . DS . COMMON . '.response.php');
require_once(ROOT_PATH . COMMON . DS . COMMON . '.cookie.php');
require_once(ROOT_PATH . COMMON . DS . COMMON . '.ipinfo.php');
//    require_once(ROOT_PATH . COMMON . DS . COMMON . '.page.php');
//    require_once(ROOT_PATH . COMMON . DS . COMMON . '.ajaxpage.php');
//require_once(ROOT_PATH . COMMON . DS . COMMON . '.email.php');//开启邮件服务
require_once(ROOT_PATH . COMMON . DS . COMMON . '.sms.php');//开启短信服务
//引用LIB库类文件
require_once(ROOT_PATH . LIB . DS . LIB . '.model.php');
require_once(ROOT_PATH . LIB . DS . LIB . '.agentmodel.php');
require_once(ROOT_PATH . LIB . DS . LIB . '.controller.php');


//初始化方法
$_request = Request::instance();
//验证token
$_request->validation();


//
//header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
//header("Cache-Control: post-check=0, pre-check=0", false);
//header("Pragma: no-cache"); // HTTP/1.0