<?php
/**
 * Copyright © 艾瑞咨询集团(http://www.iresearch.com.cn/)
 * 相关函数
 * Author Zhangwenjun <zhangwenjun@iresearch.com.cn>
 * Create 13-11-15 09:45
 */

//调试信息
function debugInfo($info = array())
{
    global $i;
    $i++;
    foreach ($info as $v) {
        $v['trace'] = array_reverse($v['trace']);
        echo '第 ', $i, ' 次数据操作', '<br>';
        echo 'MYSQL连接时间：', $v['connect_time'], '<br>';
        echo '执行SQL：', $v['sql'], '<br>';
        echo 'SQL执行时间：', $v['execute_time'], '<br>';
        echo 'SQL错误：', $v['error'], '<br>';
        echo '代码跟踪：访问了', '<br>';
        echo '控制器:', $v['trace'][1]['file'], ',第 ', $v['trace'][1]['line'], ' 行, ', $v['trace'][1]['func'], '()', ' 方法', '<br>';
        echo '模型层:', $v['trace'][2]['file'], '第 ', $v['trace'][2]['line'], ' 行, ', $v['trace'][2]['func'], '()', ' 方法', '<br>';
        echo '代理层:', $v['trace'][3]['file'], '第 ', $v['trace'][3]['line'], ' 行, ', $v['trace'][3]['func'], '()', ' 方法', '<br><br>';

    }

}

//调试信息
function debug_info($info = array())
{
    global $i;
    $i++;
    foreach ($info as $v) {
        $v['trace'] = array_reverse($v['trace']);
        echo '第 ', $i, ' 次数据操作', '<br>';
        echo 'MYSQL连接时间：', $v['connect_time'], '<br>';
        echo '执行SQL：', $v['sql'], '<br>';
        echo 'SQL执行时间：', $v['execute_time'], '<br>';
        echo 'SQL错误：', $v['error'], '<br>';
        echo '代码跟踪：访问了', '<br>';
        echo '控制器:', $v['trace'][1]['file'], ',第 ', $v['trace'][1]['line'], ' 行, ', $v['trace'][1]['func'], '()', ' 方法', '<br>';
        echo '模型层:', $v['trace'][2]['file'], '第 ', $v['trace'][2]['line'], ' 行, ', $v['trace'][2]['func'], '()', ' 方法', '<br>';
        echo '代理层:', $v['trace'][3]['file'], '第 ', $v['trace'][3]['line'], ' 行, ', $v['trace'][3]['func'], '()', ' 方法', '<br><br>';

    }

}

function l($txt = '{}')
{
    $new_log = ROOT_PATH . 'log/' . date('Ymd') . '/log_new/' . date('G') . '/';
    if (!is_dir($new_log)) {
        mkdirs($new_log);
    }
    $content = '[' . date('Y-m-d H:i:s') . ']:' . ' ip=' . getIp() . ',' . ' come_from=http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . ',' . ' content=' . $txt . "\r\n";
    funWriteFile($new_log . 'log.txt', $content);
}

function gUid()
{
    $guid = isset($_GET['guid']) ? $_GET['guid'] : '';
    if (!empty($guid)) {
        Session::instance()->set('guid', $guid);
    }
}

//当前周、月、年 转换时间
function playTime($w = 'week')
{
    if ($w == 'week') {
        $date = date('Y-m-d', strtotime("+7 day"));
    } elseif ($w == 'month') {
        $date = date('Y-m-d', strtotime("+1 month"));
    } elseif ($w == 'year') {
//半年
        $date = date('Y-m-d', strtotime("+6 month"));
    }
    return $date;
}

function removeM($ret)
{
    foreach ($ret['content'][0] as $k => $v) {
        $kk = array_keys($v);
        $ret['content'][2][1] = $kk[6];
        $ret['content'][2][2] = $kk[7];
        $ret['content'][2][3] = $kk[8];
        $ret['content'][2][4] = $kk[9];
        $ret['content'][2][5] = $kk[10];
        $ret['content'][2][6] = $kk[11];
        $ret['content'][2][7] = $kk[12];
        $ret['content'][0][$k]['monday'] = $v[$kk[6]];
        if ($v[$kk[5]] > $v[$kk[6]]) {
            $ret['content'][0][$k]['mondays'] = 'caret-down red';
        }
        if ($v[$kk[5]] < $v[$kk[6]]) {
            $ret['content'][0][$k]['mondays'] = 'caret-up green';
        }
        if ($v[$kk[5]] == $v[$kk[6]]) {
            $ret['content'][0][$k]['mondays'] = 'minus gray';
        }
        //////////////////////////////////////
        $ret['content'][0][$k]['tuesday'] = $v[$kk[7]];
        if ($v[$kk[6]] > $v[$kk[7]]) {
            $ret['content'][0][$k]['tuesdays'] = 'caret-down red';
        }
        if ($v[$kk[6]] < $v[$kk[7]]) {
            $ret['content'][0][$k]['tuesdays'] = 'caret-up green';
        }
        if ($v[$kk[6]] == $v[$kk[7]]) {
            $ret['content'][0][$k]['tuesdays'] = 'minus gray';
        }
        //////////////////////////////////////
        $ret['content'][0][$k]['wednesday'] = $v[$kk[8]];
        if ($v[$kk[7]] > $v[$kk[8]]) {
            $ret['content'][0][$k]['wednesdays'] = 'caret-down red';
        }
        if ($v[$kk[7]] < $v[$kk[8]]) {
            $ret['content'][0][$k]['wednesdays'] = 'caret-up green';
        }
        if ($v[$kk[7]] == $v[$kk[8]]) {
            $ret['content'][0][$k]['wednesdays'] = 'minus gray';
        }
        //////////////////////////////////////
        $ret['content'][0][$k]['thursday'] = $v[$kk[9]];
        if ($v[$kk[8]] > $v[$kk[9]]) {
            $ret['content'][0][$k]['thursdays'] = 'caret-down red';
        }
        if ($v[$kk[8]] < $v[$kk[9]]) {
            $ret['content'][0][$k]['thursdays'] = 'caret-up green';
        }
        if ($v[$kk[8]] == $v[$kk[9]]) {
            $ret['content'][0][$k]['thursdays'] = 'minus gray';
        }
        //////////////////////////////////////
        $ret['content'][0][$k]['friday'] = $v[$kk[10]];
        if ($v[$kk[9]] > $v[$kk[10]]) {
            $ret['content'][0][$k]['fridays'] = 'caret-down red';
        }
        if ($v[$kk[9]] < $v[$kk[10]]) {
            $ret['content'][0][$k]['fridays'] = 'caret-up green';
        }
        if ($v[$kk[9]] == $v[$kk[10]]) {
            $ret['content'][0][$k]['fridays'] = 'minus gray';
        }
        //////////////////////////////////////
        $ret['content'][0][$k]['saturday'] = $v[$kk[11]];
        if ($v[$kk[10]] > $v[$kk[11]]) {
            $ret['content'][0][$k]['saturdays'] = 'caret-down red';
        }
        if ($v[$kk[10]] < $v[$kk[11]]) {
            $ret['content'][0][$k]['saturdays'] = 'caret-up green';
        }
        if ($v[$kk[10]] == $v[$kk[11]]) {
            $ret['content'][0][$k]['saturdays'] = 'minus gray';
        }
        //////////////////////////////////////
        $ret['content'][0][$k]['sunday'] = $v[$kk[12]];
        if ($v[$kk[11]] > $v[$kk[12]]) {
            $ret['content'][0][$k]['sundays'] = 'caret-down red';
        }
        if ($v[$kk[11]] < $v[$kk[12]]) {
            $ret['content'][0][$k]['sundays'] = 'caret-up green';
        }
        if ($v[$kk[11]] == $v[$kk[12]]) {
            $ret['content'][0][$k]['sundays'] = 'minus gray';
        }
    }
    return $ret;
}

function remove($ret, $top)
{
    foreach ($ret['list']['content'][0] as $k => $v) {
        if ($k % 10 == 0) {
            $list[$k]['listname'] = $v['listname'];
            $list[$k]['listname'] = str_replace("_", " ", $list[$k]['listname']);
        }
//                pr($k);

    }
    for ($i = 0; $i < count($ret['list']['content'][0]); $i++) {
        $ret['list']['content'][0][$i]['listname'] = str_replace("_", " ", $ret['list']['content'][0][$i]['listname']);
    }
    foreach ($list as $k => $v) {
        foreach ($ret['list']['content'][0] as $gk => $gv) {
            if ($gv['listname'] == $v['listname']) {
                $list[$k]['list'][$gk] = $gv;
            }
        }
    }
    foreach ($list as $k => $v) {
//        $i=0;
        $list[$k]['list'] = array();
        for ($j = 0; $j < 10; $j++) {
            foreach ($v['list'] as $lv) {
//            if()
                if ($lv['ranking'] == $j + $top + 1) {
                    $list[$k]['list'][$j] = $lv;
                }

//                $list[$k]['list'][$i]['newname']=cnSubStr($list[$k]['list'][$i]['gamename'],7).'...';
                //                $i++;

            }
            if ($list[$k]['list'][$j]['ranking'] == '') {
                $list[$k]['list'][$j]['ranking'] = $j + 1;
                $list[$k]['list'][$j]['gamename'] = '暂无数据';
            }
        }

    }
    return $list;
}


//截取中文字符
function cnSubStr($string, $sublen, $tip = '')
{
    $len = strlen($string);
    if ($sublen >= $len) {
        return $string;
    }
    $s = mb_substr($string, 0, $sublen, 'utf-8');
    if (strlen($s) >= $len) {
        return $s;
    }

    return $s . $tip;
}

//周转换成日期
function weekDate($date)
{
    $year = substr($date, 0, 4);
    $week = substr($date, 4);
    $day = 0;
    $last_year = strtotime(($year - 1) . '-12-31');
    $last_date_in_week = date('N', $last_year);
    $days = $week * 7 + 1 + $day - $last_date_in_week;
    $the_day = strtotime("+$days days", $last_year);
    return date('Y-m-d', $the_day);
}

//debug
function pr($data, $type = 0)
{
    $type == 0 ? print_r($data) : print_r(json_decode($data, true));
}

//获取HOST
function getHttpHost()
{
    return "http://" . (isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : (isset($_SERVER['']) ? $_SERVER['HTTP_HOST'] : ''));
}

//跳转
function redirect($uri = '/', $method = 'location', $http_response_code = 302)
{
    switch ($method) {
        case 'refresh':
            header("Refresh:0;url=" . $uri);
            break;
        default:
            header("Location: " . $uri, true, $http_response_code);
            break;
    }
    exit;
}

//容量转换
function tosize($bytes)
{
    //自定义一个文件大小单位转换函数
    if ($bytes >= pow(2, 40)) { //如果提供的字节数大于等于2的40次方，则条件成立
        $return = round($bytes / pow(1024, 4), 2); //将字节大小转换为同等的T大小
        $suffix = "TB"; //单位为TB
    } elseif ($bytes >= pow(2, 30)) { //如果提供的字节数大于等于2的30次方，则条件成立
        $return = round($bytes / pow(1024, 3), 2); //将字节大小转换为同等的G大小
        $suffix = "GB"; //单位为GB
    } elseif ($bytes >= pow(2, 20)) { //如果提供的字节数大于等于2的20次方，则条件成立
        $return = round($bytes / pow(1024, 2), 2); //将字节大小转换为同等的M大小
        $suffix = "MB"; //单位为MB
    } elseif ($bytes >= pow(2, 10)) { //如果提供的字节数大于等于2的10次方，则条件成立
        $return = round($bytes / pow(1024, 1), 2); //将字节大小转换为同等的K大小
        $suffix = "KB"; //单位为KB
    } else { //否则提供的字节数小于2的10次方，则条件成立
        $return = $bytes; //字节大小单位不变
        $suffix = "Byte"; //单位为Byte
    }
    return $return . " " . $suffix; //返回合适的文件大小和单位
}

//判断是否irsearch域名
function isIreDomain($url)
{
    $tmp = parse_url($url);
    $tmp_host = array_reverse(explode('.', $tmp['host']));
    $host_domain = $tmp_host[1] . '.' . $tmp_host[0];
    if ($host_domain == 'iresearch.com') {
        return true;
    } else {
        return false;
    }

}

function gbk2utf8($data)
{
    if (is_array($data)) {
        return array_map('gbk2utf8', $data);
    } else if (is_object($data)) {
        return array_map('gbk2utf8', get_object_vars($data));
    }
    return mb_convert_encoding($data, 'UTF-8', 'GBK');
}

function utf8togbk($data)
{
    if (is_array($data)) {
        return array_map('utf8togbk', $data);
    } else if (is_object($data)) {
        return array_map('utf8togbk', get_object_vars($data));
    }
    return mb_convert_encoding($data, 'GBK', 'UTF-8');
}

function isCli()
{
    return PHP_SAPI == 'cli' && empty($_SERVER['REMOTE_ADDR']);
}

//测试打印
function p($info, $exit = true, $ret = false)
{
    if (defined('DEBUG')) {
// && DEBUG == true
        $debug = debug_backtrace();
        $output = '';

        if (isCli()) {
            $output .= '[TRACE]' . PHP_EOL;
            foreach ($debug as $v) {
                $output .= 'File:' . $v['file'];
                $output .= 'Line:' . $v['line'];
                $output .= 'Args:' . implode(',', $v['args']) . PHP_EOL;
            }
            $output .= '[Info]' . PHP_EOL;
            $output .= var_export($info, true) . PHP_EOL;
        } else {
            foreach ($debug as $v) {
                $output .= '<b>File</b>:' . $v['file'] . '&nbsp;';
                $output .= '<b>Line</b>:' . $v['line'] . '&nbsp;';
                $output .= $v['class'] . $v['type'] . $v['function'] . '(\'';
                //$output .= implode('\',\' ', $v['args']);
                $output .= '\')<br/>';
            }
            $output .= '<b>Info</b>:<br/>';
            $output .= '<pre>';
            $output .= var_export($info, true);
            $output .= '</pre>';
        }

        if ($ret) {
            return $output;
        } else {
            echo $output;
        }

        if ($exit) {
            exit;
        }

    } else {
        return;
    }
}

//去除空格
function trimSpace($s)
{
    $s = mb_ereg_replace('^(　| )+', '', $s);
    $s = mb_ereg_replace('(　| )+$', '', $s);
    return $s;
}

//判断是否url
function isUrl($url)
{
    return preg_match('/^(http|https):\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+)/i', $url);
}

//得到来路ip函数
function getIp($type = '')
{

    if (isset($_SERVER)) {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $realip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $realip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            $realip = $_SERVER['REMOTE_ADDR'];
        }
    } else {
        if (getenv("HTTP_X_FORWARDED_FOR")) {
            $realip = getenv("HTTP_X_FORWARDED_FOR");
        } elseif (getenv("HTTP_CLIENT_IP")) {
            $realip = getenv("HTTP_CLIENT_IP");
        } else {
            $realip = getenv("REMOTE_ADDR");
        }
    }
    if ($type == 'int') {
        return (float)bindec(decbin(ip2long($realip)));
    }

    return $realip;
}

//随机数
function prorand($length)
{
    $key = '';
    $pattern = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLOMNOPQRSTUVWXYZ'; //字符池
    for ($i = 0; $i < $length; $i++) {
        $key .= $pattern{mt_rand(0, 9)}; //生成php随机数
    }
    return $key;
}

//创建多级目录
function mkdirs($dir, $mode = 0777)
{
    if (is_dir($dir) || @mkdir($dir, $mode)) {
        return true;
    }

    if (!mkdirs(dirname($dir), $mode)) {
        return false;
    }

    return @mkdir($dir, $mode);
}

function zipfile($file = "", $type = "php")
{
    $tmpfile = $data = "";
    $type = strtolower($type);
    if (is_file($file) && file_exists($file)) {
        if ($type == "php") {
            $zipfile = 'php_strip_whitespace';
        } else {
            $zipfile = 'file_get_contents';
        }
        $data = $zipfile($file);
        $tmpfile = dirname($file) . "\~tmp_" . basename($file);
    } else {
        $data = str_replace("\t", "", $file);
    }
    $data = trim($data);
    if ($type != 'php' && $data) {
        $cleanstring = array("/[\r\n]\/\/[^\r\n]*[\r\n]/", "/\/\/[^\r\n\"']*[\r\n]/", "/\/\*.*?\*\//s", "/\/\*.*\*\//Us", "/\s(?=\s)/");
        $data = preg_replace($cleanstring, "", $data);
    } else {
        $cleanstring = array("/[\n\r\t]/");
        $data = preg_replace($cleanstring, "", $data);
    }
    //if($tmpfile)@file_put_contents($tmpfile,$data);
    return $data;
}

//替换字符并清除2端的逗号和空格
function funTrimString($string = "")
{
    $string = funStrHtml($string);
    $string = trim($string, ",");
    $string = trim($string);
    return $string;
}

//HTML运行模式换成HTML识别模式
function funStrHtml($str)
{
    //$str=trim($str);
    $str = funStrRehtml($str);
    $str = htmlspecialchars($str);
    $str = str_replace('&amp;', '&', $str);
    $str = str_replace("'", '&apos;', $str); //&#039;
    $str = str_replace('&', '&amp;', $str);
    $str = str_replace('%', '％', $str);
    return $str;
}

//HTML识别模式切换成HTML运行模式
function funStrRehtml($str)
{
    $str = str_replace('&amp;', '&', $str);
    $str = str_replace('&apos;', "'", $str); //&#039;
    $str = stripslashes($str);
    $str = str_replace('％', '%', $str);
    return $str;
}

//脚本输出
function script($s)
{
    echo("
    <SCRIPT LANGUAGE=\"JavaScript\">
    $s
    </SCRIPT>
    ");
    exit;
}

//计算中文字符长度：len('你hao')=4
function len($l1)
{
    $I2 = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/";
    preg_match_all($I2, $l1, $I3);
    return count($I3[0]);
}

function getVisiteFolder()
{
    $visit_path = $_SERVER["SCRIPT_NAME"];
    $arr = explode('/', $visit_path);
    return strtolower($arr[count($arr) - 2]);
}

//JS解码的Php实现方式
function jsUnescape($str)
{
    $ret = '';
    $len = strlen($str);

    for ($i = 0; $i < $len; $i++) {
        if ($str[$i] == '%' && $str[$i + 1] == 'u') {
            $val = hexdec(substr($str, $i + 2, 4));

            if ($val < 0x7f) {
                $ret .= chr($val);
            } else if ($val < 0x800) {
                $ret .= chr(0xc0 | ($val >> 6)) . chr(0x80 | ($val & 0x3f));
            } else {
                $ret .= chr(0xe0 | ($val >> 12)) . chr(0x80 | (($val >> 6) & 0x3f)) . chr(0x80 | ($val & 0x3f));
            }

            $i += 5;
        } else if ($str[$i] == '%') {
            $ret .= urldecode(substr($str, $i, 3));
            $i += 2;
        } else {
            $ret .= $str[$i];
        }

    }
    return $ret;
}

function funGetKeyword($str, $k = array(), $color = 'red')
{
    //关键字加亮
    for ($i = 0; $i < count($k); $i++) {
        $str = ereg_replace("/" . quotemeta($k[$i]) . "/", '<span style="color:' . $color . '">' . $k[$i] . '</span>', $str);
    }
    return $str;
}

//写文件
function funWriteFile($path, $content, $auth = 'a')
{
    if (@$fp = fopen($path, $auth)) {
        @fwrite($fp, $content);
        @fclose($fp);
        @chmod($path, 0777);
        return true;
    } else {
        return false;
    }
}

//删除文件
function funDelFiles($files)
{
    if (funChkFiles($files)) {
        @unlink($files);
    }
}

//检查文件是否存在
function funChkFiles($files)
{
    if (file_exists($files)) {
        return true;
    } else {
        return false;
    }
}

//创建文件夹
function funSetMkdir($file)
{
    //if(!@is_dir($file)&&!@mkdir($file))@mkdir($file,0777);
    if (@is_dir($file) || @mkdir($file, 0777)) {
        return true;
    };
    if (funSetMkdir(dirname($file), 0777)) {
        return @mkdir($file, 0777);
    };
}

function template_ini($tpl, $title = '', $meta_key = '', $meta_des = '')
{

    if ($title == '') {
        $title = WEBSITE_TITLE;
    } else {
        $title .= ' - ' . WEBSITE_TITLE;
    }
//
//    require_once(ROOT_PATH . CONTROLLER .DS .CONTROLLER . '.menu.php');
//    $menu = new MenuController();
//    $menu = $menu->index();
    $_session = Session::instance();
    //所有模板均调用以下接口去获取该用户是否管理员??并该接口直接读取sqlserver,造成页面并发速度慢.
//    $admin = Model::instance('apply')->adminApply(array('apply_adminid'=>$_session->get( 'uid' )));
//    echo Session::instance()->get('admin_auth');
    $tpl->assign(array('AUTH' => Session::instance()->get('admin_auth'), 'USER_NAME' => $_session->get('user_name'), 'PAGE_TITLE' => $title, 'META_KEY' => $meta_key, 'META_DES' => $meta_des, 'WEBSITE_URL' => WEBSITE_URL, 'WEBSITE_SOURCE_URL' => WEBSITE_SOURCE_URL, 'EXPORT_PIC' => EXPORT_PIC));
}

//模板初始化
function templateIni($tpl, $title = '', $meta_key = '', $meta_des = '')
{

    if ($title == '') {
        $title = WEBSITE_TITLE;
    } else {
        $title .= ' - ' . WEBSITE_TITLE;
    }
//
    //    require_once(ROOT_PATH . CONTROLLER .DS .CONTROLLER . '.menu.php');
    //    $menu = new MenuController();
    //    $menu = $menu->index();
    $_session = Session::instance();
    //所有模板均调用以下接口去获取该用户是否管理员??并该接口直接读取sqlserver,造成页面并发速度慢.
    //    $admin = Model::instance('apply')->adminApply(array('apply_adminid'=>$_session->get( 'uid' )));
    //    echo Session::instance()->get('admin_auth');
    $tpl->assign(array('AUTH' => Session::instance()->get('admin_auth'), 'USER_NAME' => $_session->get('user_name'), 'PAGE_TITLE' => $title, 'META_KEY' => $meta_key, 'META_DES' => $meta_des, 'WEBSITE_URL' => WEBSITE_URL, 'WEBSITE_SOURCE_URL' => WEBSITE_SOURCE_URL, 'EXPORT_PIC' => EXPORT_PIC));
}

function iArrayColumn($input, $columnKey, $indexKey = null)
{
    if (!function_exists('array_column')) {
        $columnKeyIsNumber = (is_numeric($columnKey)) ? true : false;
        $indexKeyIsNull = (is_null($indexKey)) ? true : false;
        $indexKeyIsNumber = (is_numeric($indexKey)) ? true : false;
        $result = array();
        foreach ((array)$input as $key => $row) {
//            if($columnKeyIsNumber){
            //                echo "111--";
            //                $tmp= array_slice($row, $columnKey, 1);
            //                $tmp= (is_array($tmp) && !empty($tmp))?current($tmp):null;
            //            }else{
            //                echo "222--";
            $tmp = isset($row[$columnKey]) ? $row[$columnKey] : null;
//            }

//            if(!$indexKeyIsNull){
            //                if($indexKeyIsNumber){
            //                    $key = array_slice($row, $indexKey, 1);
            //                    $key = (is_array($key) && !empty($key))?current($key):null;
            //                    $key = is_null($key)?0:$key;
            //                }else{
            //                    $key = isset($row[$indexKey])?$row[$indexKey]:0;
            //                }
            //            }
            $result[$key] = $tmp;
        }
        return $result;
    } else {
        return array_column($input, $columnKey, $indexKey);
    }
}

//二维数组
function arrayToNumber($array)
{
    $countarray1 = count($array);
    $countarray2 = count($array[0]);
    for ($i = 0; $i < $countarray1; $i++) {
        for ($j = 1; $j < $countarray2; $j++) {
            if (is_numeric($array[$i][$j])) {
                $array[$i][$j] = $array[$i][$j] + 0;
            }
        }
    }
    return $array;
}

////一纬数组
//function arrayToNumberOne($array)
//{
//    $countarray1 = count($array);
//    for ($i = 0; $i < $countarray1; $i++) {
//        if (is_numeric($array[$i][$j])) {
//            $array[$i] = $array[$i] + 0;
//        }
//    }
//    return $array;
//}
//转换并将数据设置为0
//function arrayToNumberOneNull($array)
//{
//    $countarray1 = count($array);
//    for ($i = 0; $i < $countarray1; $i++) {
//        if (is_numeric($array[$i][$j])) {
//            if ($array[$i] == 0) {
//                $array[$i] = null;
//            } else {
//                $array[$i] = $array[$i] + 0;
//            }
//        }
//    }
//    return $array;
//}
function uploadFiles($file, $editor = 0)
{
    $name = $file['name'];
    $type = $file['type'];
    $size = $file['size'];
    $tmp_name = $file['tmp_name'];
    $url = dirname(dirname(__FILE__)) . "\\public\\upload\\"; //文件路径
    $tmp_url = $url . $name;
    $tpname = substr(strrchr($name, '.'), 1); //获取文件后缀
    $types = array('jpg', 'png', 'jpeg', 'bmp', 'gif');
    $filesize = 1024 * 1024 * 100;
    if ($size > $filesize) {
        //              echo "<script>alert('退出成功!');location.href='".$_SERVER["HTTP_REFERER"]."';</script>";
        echo "<script>alert('文件过大!');history.go(-1)</script>";
        exit;
    } else if (!in_array($tpname, $types)) {
        echo "<script>alert('文件类型不符合!');history.go(-1)</script>";
        exit;
    } else if (!move_uploaded_file($tmp_name, $tmp_url)) {
        echo "<script>alert('移动文件失败!');history.go(-1)</script>";
        exit;
    } else {
        if ($editor == 1) {
            echo "<script>window.parent.InsertHTML('<div><img src=\"public/upload/" . $name . "\" border=\"0\" width=\"400\" height=\"300\"></div>');</script>";
        }
        move_uploaded_file($tmp_name, $tmp_url);
        $size = round($size / 1024 / 1024, 2); //转换成Mb
        $upload = array('size' => $size, 'url' => $tmp_url, 'name' => $name, 'type' => $tpname);
        return $upload;
    }

}

function i_array_column($input, $columnKey, $indexKey = null)
{
    if (!function_exists('array_column')) {
        $columnKeyIsNumber = (is_numeric($columnKey)) ? true : false;
        $indexKeyIsNull = (is_null($indexKey)) ? true : false;
        $indexKeyIsNumber = (is_numeric($indexKey)) ? true : false;
        $result = array();
        foreach ((array)$input as $key => $row) {
//            if($columnKeyIsNumber){
//                echo "111--";
//                $tmp= array_slice($row, $columnKey, 1);
//                $tmp= (is_array($tmp) && !empty($tmp))?current($tmp):null;
//            }else{
//                echo "222--";
            $tmp = isset($row[$columnKey]) ? $row[$columnKey] : null;
//            }

//            if(!$indexKeyIsNull){
//                if($indexKeyIsNumber){
//                    $key = array_slice($row, $indexKey, 1);
//                    $key = (is_array($key) && !empty($key))?current($key):null;
//                    $key = is_null($key)?0:$key;
//                }else{
//                    $key = isset($row[$indexKey])?$row[$indexKey]:0;
//                }
//            }
            $result[$key] = $tmp;
        }
        return $result;
    } else {
        return array_column($input, $columnKey, $indexKey);
    }
}

/**
 * 权限认证
 */
function signAuth()
{
    $headers = getallheaders();
    $sign = $headers['token'];
    $now = $headers['now'];
    $method = $_GET['a'];
    $csign = md5(KEY . $method . $now);
    if ($_GET['a'] == 'userRecoverResult') {//此类方法不使用签名认证
        return true;
    }
    if ($sign != $csign) {
        $arr = array(
            'ret' => -1,
            'msg' => '没有相关权限',
            'content' => 'null',
        );
        echo json_encode($arr);
        die;
    }
}

/**
 * 网页抓取图片另存为
 *
 * @param        $url
 * @param string $filename
 *
 * @return bool|string
 */
function GrabImage($url, $filename = "")
{
//$url 为空则返回 false;
    if ($url == "") {
        return false;
    }
    //$ext = strrchr($url, ".");//得到图片的扩展名
    //if($ext != ".gif" && $ext != ".jpg" && $ext != ".bmp"){echo "格式不支持！";return false;}
    $ext = ".jpg";
    if ($filename == "") {
        $filename = time() . "$ext";
    } //以时间戳另起名
    //开始捕捉
    ob_start();
    readfile($url);
    $img = ob_get_contents();
    ob_end_clean();
    $size = strlen($img);
    $fp2 = fopen(RECEIVE_PATH . $filename, "a");
    fwrite($fp2, $img);
    fclose($fp2);
    return $filename;
}

/**
 * write log
 *
 * @param $str
 * @param $path
 */
function write_to_log($str, $pre = '')
{
    if (DEBUG_LOG) {
        $str = $str . PHP_EOL;
        $time = time();
        $log_file = 'idata_' . date('Y-m-d', $time) . $pre . '.log';
        if ($fd = fopen(LOG_PATH . $log_file, "a")) {
            fwrite($fd, date('Y-m-d H:i:s', $time) . '  ' . getIp() . ': ' . $str);
            fclose($fd);
        }
        unset($str);
    }
}

/**
 * [countPage description]
 *
 * @param  [type] $page_total [description]
 * @param  [type] $pagesize   [description]
 *
 * @return [type]             [description]
 */
function countPage($page_total, $pagesize)
{
    $p = $page_total / $pagesize;
    if ($p <= 1) {
        return 1;
    } else {
        if (($page_total % $pagesize) > 0) {
            return (int)$p + 1;
        } else {
            return (int)$p;
        }
    }
}

/***********************************************************************JOSON*************************************************************************/

//创建GUID
function getGUID($namespace = '')
{
    static $guid = '';
    $uid = uniqid("", true);
    $data = $namespace;
    $data .= $_SERVER['REQUEST_TIME'];
    $data .= $_SERVER['HTTP_USER_AGENT'];
    $data .= $_SERVER['LOCAL_ADDR'];
    $data .= $_SERVER['LOCAL_PORT'];
    $data .= $_SERVER['REMOTE_ADDR'];
    $data .= $_SERVER['REMOTE_PORT'];
    $hash = strtoupper(hash('ripemd128', $uid . $guid . md5($data)));
    return substr($hash, 0, 8) .
        '-' .
        substr($hash, 8, 4) .
        '-' .
        substr($hash, 12, 4) .
        '-' .
        substr($hash, 16, 4) .
        '-' .
        substr($hash, 20, 12);
//    return $guid;
}

//上传文件
function upFile($filebase64, $type, $path = '', $name = '')
{
    //初始化文件名
    if ($name === '') {
        $name = substr(md5(rand(100000001, 999999999)), 0, 10) . time();
    }
    //文件不能为空
    if ($filebase64 === '') {
        return "文件不能为空";
        exit();
    }
    //文件类型不能为空
    if ($type === '') {
        return "文件类型不能为空";
        exit();
    }

    //设置全路径
    $filepath = "/upload/{$path}{$name}.{$type}";
    //打开文件准备写入
    $file = fopen("." . $filepath, "w");
    //写入
    $retfile = fwrite($file, base64_decode($filebase64));
    //关闭
    fclose($file);

    $ret['retfile'] = $retfile;//返回上传结果
    $ret['filepath'] = $filepath;//返回上传路径

    return $ret;
}

//获取POST数据
function _POST()
{
    //获取POST数据
    $arr = file_get_contents('php://input');
    write_to_log(' RECEIVE POST ' . $arr, '_conapi');
    $arr = json_decode($arr, true);
    return $arr;
}

//返回正确信息
function _SUCCESS($resCode = '000000', $resMsg = '处理成功', $data = '')
{
    if (!DEBUG) {
        @ob_clean();
    }

    $arr = array(
        'resTime' => time() . '',
        'resCode' => $resCode,
        'resMsg' => $resMsg,
        'data' => $data,
    );

    Model::instance('tools')->logs($resCode, $resMsg, $data);
    $ret = json_encode($arr, JSON_UNESCAPED_UNICODE);
    //写日志


//    header('Content-type: application/json;charset=utf-8');
//    header('Content-Encoding: utf-8');
    echo $ret;
    if (isset($data['avatar_base'])) {
        unset($data['avatar_base64']);
    }

    if (isset($data['avatar_base64'])) {
        unset($data['avatar_base64']);
    }

    $arr = array(
        'resTime' => time() . '',
        'resCode' => $resCode,
        'resMsg' => $resMsg,
        'data' => $data,
    );
    write_to_log(' RESPONSE SUCCESS ' . json_encode($arr, JSON_UNESCAPED_UNICODE), '_conapi');

    die;
}

//返回错误信息
function _ERROR($resCode = '999999', $resMsg = '处理失败', $data = '')
{
    $arr = array(
        'resTime' => time() . '',
        'resCode' => $resCode,
        'resMsg' => $resMsg,
        'data' => empty($data) ? [] : $data
    );
    if (!DEBUG) {
        @ob_clean();
    }
    //写日志
    Model::instance('tools')->logs($resCode, $resMsg, $data);
    $ret = json_encode($arr, JSON_UNESCAPED_UNICODE);
    write_to_log(' RESPONSE ERROR' . $ret, '_conapi');
    echo $ret;
    die;
}

//导航层级化-无限级
function _findChildren($list, $p_id, $cleaningList = false)
{
    $r = array();
    foreach ($list as $item) {
        if ($item['sid'] == $p_id) {
            $length = count($r);
            //格式化&过滤元素
            if ($cleaningList == false) {
                $Citem = $item;
            } else {
                foreach ($cleaningList as $a => $v) {
                    $Citem[$a] = $item[$v];
                }
            }
            $r[$length] = $Citem;

            if ($t = _findChildren($list, $item['id'], $cleaningList)) {
                $r[$length]['lowerTree'] = $t;
            }
        }
    }
    return $r;
}

/**
 *
 * @param $sValue
 * @param $sSecretKey
 * @return string
 */
function fnEncrypt($sValue, $sSecretKey)
{
    write_to_log('value:' . $sValue, '_ird');
    write_to_log('sSecret: ' . $sSecretKey, '_ird');
    return rtrim(
        base64_encode(
            mcrypt_encrypt(
                MCRYPT_RIJNDAEL_128,
                $sSecretKey, $sValue,
                MCRYPT_MODE_CBC,
                "1234567812345678"
            )
        )
        , "\0"
    );
}

/**
 * get request headers
 *
 * @return array
 */
function getRequestHeaders()
{
    $headers = array();
    foreach ($_SERVER as $key => $value) {
        if (substr($key, 0, 5) <> 'HTTP_') {
            continue;
        }
        $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
        $headers[$header] = $value;
    }
    return $headers;
}


/**
 * check time out
 *
 * @param int $time
 * @param int $v_value
 * @param int $checkTime
 * @return bool
 */
function checkTimeOut(int $time, $v_value = 60, $checkTime = 60)
{

    if ((time() - $time) / $v_value > $checkTime)
        return false;

    if (empty($time))
        return false;


    return true;
}