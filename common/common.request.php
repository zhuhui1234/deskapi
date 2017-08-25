<?php

/**
 * Copyright © 艾瑞咨询集团(http://www.iresearch.com.cn/)
 * 处理请求参数
 * request 类
 * Author Zhangwenjun <zhangwenjun@iresearch.com.cn>
 * Create 13-11-15 09:45
 */
class Request
{
    private $filter = null;

    /**
     * _filter function
     *
     * @param $array
     */
    private function _filter(&$array)
    {
        $filter = $this->filter;
        if (is_array($filter)) {//如果是布尔型并且为真则去除所有空字符串类型的数值
            foreach ($filter as $k => $v) {
                if (is_array($v) && $k == 'params') {
                    foreach ($array as $kk => $vv) {
                        if (in_array($kk, $v)) {
                            unset($array[$kk]);
                        }
                    }
                } elseif ($k == 'empty') {
                    foreach ($array as $kk => $vv) {
                        if (!strlen($vv)) {
                            unset($array[$kk]);
                        }
                    }
                }
            }
        } elseif (is_string($filter)) {
            if ($filter == 'empty') {
                foreach ($array as $k => $v) {
                    if (!strlen($v)) {
                        unset($array[$k]);
                    }
                }
            }
        }
    }

    /**
     * filter function
     *
     * @param $filter
     *
     * @return $this
     */
    public function filter($filter)
    {
        $this->filter = $filter;

        return $this;
    }

    static public function instance()
    {
        return new self();
    }

    private function _fetch_from_array(&$array, $index = '', $default = null)
    {
        if (!isset($array[$index])) {
            return $default;
        }

        return $array[$index];
    }


    public function get($index = '', $default = null)
    {
        $this->_filter($_GET);
        if ($index) {
            return $this->_fetch_from_array($_GET, $index, $default);
        } else {
            return $_GET;
        }
    }


    public function post($index = '', $default = null)
    {
        $this->_filter($_POST);
        if ($index) {
            return $this->_fetch_from_array($_POST, $index, $default);
        } else {
            return $_POST;
        }
    }


    public function __construct($index = '', $default = null)
    {
        $this->_filter($_GET);
        $this->_filter($_POST);
        if (!$index) {
            return $_REQUEST;
        }

        if (!isset($_POST[$index])) {
            return $this->get($index, $default);
        } else {
            return $this->post($index, $default);
        }
    }


    public function isRequest($method)
    {
        $request_method = $_SERVER['REQUEST_METHOD'];
        if ($request_method == $method) {
            return true;
        }
        return false;
    }

    public function isPost()
    {
        $request_method = $_SERVER['REQUEST_METHOD'];
        if ($request_method == 'POST') {
            return true;
        }
        return false;
    }

    public function isGet()
    {
        $request_method = $_SERVER['REQUEST_METHOD'];
        if ($request_method == 'GET') {
            return true;
        }
        return false;
    }

    public function isAjax()
    {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');
    }


    public function server($index = '', $default = '')
    {
        $this->_filter($_SERVER);
        if (!$index) {
            return $_SERVER;
        }

        return $this->_fetch_from_array($_SERVER, $index, $default);
    }

    /**
     * _curlPost
     *
     * @param        $url
     * @param array  $data
     * @param string $cookiepath
     * @param int    $timeout
     *
     * @return mixed|string
     */
    public function _curlPost($url, $data = array(), $method, $cookiepath = '/', $timeout = 300)
    {
        $userAgent = 'Mozilla/4.0+(compatible;+MSIE+6.0;+Windows+NT+5.1;+SV1)';
        $referer = $url;
        if (!is_array($data) || !$url) {
            return '';
        }
//        $data['logIP'] = getIp();
//        $data['logUID'] = Session::instance()->get('uid');
        $post = '';
        $nurl = $url;
        $post = json_encode($data);
        if (DEBUG) {
            echo '访问路径:';
            echo $url, '<br>';
            echo '<br>';
            echo '提交的数据:</br>';
            var_dump($data);
            echo '</br>';
            echo $post;

        }
        $now = time();
        $token = md5(KEY . $method . $now);
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        // post数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json; charset=utf-8",
            "token:" . $token, "now:" . $now,
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $output = curl_exec($ch);
        curl_close($ch);
        if (DEBUG) {
            echo '返回值:';
            pr($output, 1);
        }
        return $output;
    }

    //验证TOKEN
    public function validation()
    {
        if (
            ($_GET['m'] == 'User' AND $_GET['a'] == 'login') //登录
            OR (strtolower($_GET['m']) == 'user' AND $_GET['a'] == 'setMobileKey') //短信服务
            OR (strtolower($_GET['m']) == 'user' AND $_GET['a'] == 'addUser') //用户注册
            OR (strtolower($_GET['m']) == 'permissions' AND $_GET['a'] == 'checkUserProPer') //验证用户权限
            OR (strtolower($_GET['m']) == 'permissions' AND $_GET['a'] == 'checkPermission') //验证用户权限
            OR (strtolower($_GET['m']) == 'permissions' AND $_GET['a'] == 'checkPermissionURI') //验证用户权限
            OR (strtolower($_GET['m']) == 'permissions' AND $_GET['a'] == 'checkPermissionForMobile') //验证用户权限
            OR (strtolower($_GET['m']) == 'permissions' AND $_GET['a'] == 'getHomeMenu')
            OR (strtolower($_GET['m']) == 'permissions' AND $_GET['a'] == 'getProduct')
            OR (strtolower($_GET['m']) == 'permissions' AND $_GET['a'] == 'checkMail')
            OR (strtolower($_GET['m']) == 'user' AND $_GET['a'] == 'ircLogin')
            OR (strtolower($_GET['m']) == 'logs' AND $_GET['a'] == 'pushLog')
            OR (strtolower($_GET['m']) == 'user' AND $_GET['a'] == 'appLogin')
            OR (strtolower($_GET['m']) == 'user' AND $_GET['a'] == 'appBindingAccount')
            OR (strtolower($_GET['m']) == 'service' AND $_GET['a'] == 'createSingleMsg')
            OR (strtolower($_GET['m']) == 'service')
        ) {
            //不作任何操作
        } else {
            //获取POST请求数据
            $data = _POST();
            //验证参数-登录账号
            if ($data['TOKEN'] === null OR $data['TOKEN'] === '') {
                _ERROR('000001', 'TOKEN不能为空');
            }
            //验证参数-用户GUID
            if ($data['userID'] === null OR $data['userID'] === '') {
                _ERROR('000001', '用户ID不能为空');
            }

            //验证TOKEN
            Model::instance('tools')->isToken($data);
        }
    }

    /**
     * 接收POST JOSON数据
     * POST ->
     * data JSON数组
     * errorCode 错误状态码
     * errorMessage 错误信息
     */
    public function getPostJson()
    {
        //接收POST数据
        $postVar = file_get_contents('php://input');
        //数据转义数组
        $postJson = json_decode($postVar, JSON_UNESCAPED_UNICODE);

        //验证数据类型(必须为JSON数组)
        if (!is_array($postJson)) {
            exit("非法格式");
        }

        //返回结果
        return $postJson;
    }

    //RAD POST请求方法
    function _curlRADPost($url, $data = array(), $cookiepath = '/', $timeout = 300)
    {
        $userAgent = 'Mozilla/4.0+(compatible;+MSIE+6.0;+Windows+NT+5.1;+SV1)';
        $referer = $url;
        if (!is_array($data) || !$url) return '';
        $data['userIP'] = getIp();
//        $post = json_encode($data);
        $post = $data;
        if (DEBUG) {
            echo $url, '<br>';
            print_r($post);
            echo '<br>';
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);                // 设置访问的url地址
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);        // 设置超时
        curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);    // 用户访问代理 User-Agent
        curl_setopt($ch, CURLOPT_REFERER, $referer);        // 设置 referer
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);        // 跟踪301
        curl_setopt($ch, CURLOPT_POST, 1);                    // 指定post数据
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);        // 添加变量
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiepath);    // COOKIE的存储路径,返回时保存COOKIE的路径
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);        // 返回结果
        $content = curl_exec($ch);
        curl_close($ch);
        if (DEBUG) {
            pr($content, 1);
            echo '<br>';
        }

//    //LOG
        write_to_log('POST URL:' . $url, '_ird');
        write_to_log('POST VALUE' . json_encode($post), '_ird');
        write_to_log('RETURN: ' . $content, '_ird');
        return $content;
    }

}
