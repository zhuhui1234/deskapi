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
     * @param $filter
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


    public function request($index = '', $default = null)
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
     * @param $url
     * @param array $data
     * @param string $cookiepath
     * @param int $timeout
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
        if($_GET['m']=='user' AND $_GET['a']=='login'//登录
            OR $_GET['m']=='user' AND $_GET['a']=='setMobileKey'//短信服务
            OR $_GET['m']=='user' AND $_GET['a']=='addUser'//用户注册
        ){
            //不作任务处理
        } else {
//            //接收POST请求数据
//            $where = json_decode(file_get_contents('php://input'), true);
//            //初始返回值
//            $ret_data = array(
//                'resTime' => time().'',
//                'data' => '',
//                'resCode' => '',
//                'resMsg' => ''
//            );
//            //TOKEN判空
//            if($where['token']==null){
//                $ret_data['resCode'] = '000001';
//                $ret_data['resMsg'] = 'TOKEN不能为空!!!';
//                echo json_encode($ret_data,JSON_UNESCAPED_UNICODE);
//                exit;
//            }
//            //验证TOKEN
//            $where_token['token'] = $where['token'];
//            $ret = Model::instance('token')->isToken($where_token);
//            if($ret[0]<=0){
//                $ret_data['resCode'] = '000002';
//                $ret_data['resMsg'] = '用户过期!!!';
//                echo json_encode($ret_data,JSON_UNESCAPED_UNICODE);
//                exit;
//            }
        }
    }

}
