<?php

/**
 * Created by iResearch
 * Service 过滤层
 * Author JOSON <joson@iresearch.com.cn>
 * Create 2017-01-24 15:38
 * Update 2017-01-24 15:38
 * FileName:controller.service.php
 * 描述:
 */
class ServiceController extends Controller
{
    private $model;
    const M = "Service";

    public function __construct($className)
    {
        parent::__construct($className);
        $this->model = Model::instance(self::M);
    }

    /**
     * 初始方法
     */
    public function index()
    {

    }

    /**
     * 获取绑定服务
     */
    public function getService()
    {
        //获取POST请求数据
        $data = _POST();

        //查询成功,,并返回响应结果
        $this->model->getService($data);
    }

    /**
     * 绑定微信
     */
    public function setWxService()
    {
        //获取POST请求数据
        $data = _POST();

        //验证参数-微信名称
        if (empty($data['wxName'])) {
            _ERROR('000001', '微信名称不能为空');
        }
        //验证参数-微信Openid
        if (empty($data['wxOpenid'])) {
            _ERROR('000001', '微信Openid不能为空');
        }
        //验证参数-微信Unionid
        if (empty($data['wxUnionid'])) {
            _ERROR('000001', '微信Unionid不能为空');
        }

        //绑定成功,,并返回响应结果
        $this->model->setWxService($data);
    }

    /*
    ==============================
           MSG SERVICE

           - msg_type:
             1: all, just public msg list
             2: only user msg
             3: product msg
             4: product msg without user

    ==============================
    */

    /*
     * msg list
     *
     * @param array $data
     *
     * -type:
     *      -1: all, without user msg
        1: all, just public msg list
        2: only user msg
        3: product msg with user ()
        4: product msg without user
     *  5: knowledge base
     */

    public function msgList()
    {
        $data = _POST();

        if (!is_array($data) or empty($data)) {
            _ERROR('000001', '参数错误');
        }


        if ((empty($data['userID']) and empty($data['pdtID'])) or empty($data['type'])) {
            _ERROR('000001', '缺少参数');
        }

        $this->model->msgList($data);

    }

    public function countUnMsg()
    {
        $data = _POST();

        if (!is_array($data) or empty($data)) {
            _ERROR('000001', '参数错误');
        }


        if ((empty($data['userID']) and empty($data['pdtID'])) or empty($data['type'])) {
            _ERROR('000001', '缺少参数');
        }

        $this->model->countUnMsg($data);
    }

    public function msgDetail()
    {
        $data = _POST();
        if (empty($data['msgID'])) {
            _ERROR('000001', '缺少参数');
        }

        if (empty($data['userID'])) {
            _ERROR('000001', '缺少参数');
        }

        $this->model->readMsg($data['msgID'], $data['userID']);

    }

    /**
     * remove msg for user
     *
     */
    public function rmMsgForUser()
    {
        $data = _POST();


        if (empty($data['msgID']) or empty($data['userID'])) {
            _ERROR('000001', '不能为空');
        }

        $this->model->rmMsg($data['msgID'], $data['userID']);

    }

    /**
     * create single msg
     */
    public function createSingleMsg()
    {
        $data = _POST();

        if (
            !empty($data['title']) and
            !empty($data['content']) and
            !empty($data['auth']) and
            !empty($data['userID']) and
            !empty($data['pdtID'])
        ) {
            $this->model->createSingleMsg($data['title'], $data['content'], $data['auth'], $data['userID'], $data['pdtID']);
        } else {
            _ERROR('000001', '缺少参数');
        }

    }

    /**
     * get msg head for idata
     */
    public function msgHeads()
    {
        $data = _POST();

        $userModel = Model::instance('user');

        $userModel->hasProductList($data['u_id']);

    }

    public function testIp()
    {
        $data = _POST();
        $log_model = Model::instance('Logs')->test($data);
        var_dump($log_model);
    }

    /*
    ==============================
           TEXT SMS SERVICE

    ==============================
    */

    public function sendSingleSMS()
    {

        $v = $this->__checkHead();
        if ($v) {
            $sms = SMS::instance();
            $data = _POST();
            switch ($data['type']) {
                default:
                case 'register':
                    $sms = $sms->aliSMS($data['mobile'], 'SMS_137955318', $this->__registerText($data));
                    break;

                case 'event_notice':
//                    $sms = SMS::instance()->sendSingleSMS($this->__irsResearchText($data), null, true);
                    $sms = $sms->aliSMS($data['mobile'], 'SMS_138065649', $data, '艾瑞咨询');
                    break;
            }

            if ($sms->Code == 'OK') {
                _SUCCESS('000000', '发送成功');
            } else {
                write_to_log(json_encode((array)$sms), '_sms');
                _ERROR('000002', '发送失败,SMS错误');
            }


        }


    }


    /**
     * for app login
     */
    public function verifyUserFromApp()
    {
        $v = $this->__checkHead();

        if ($v) {

            $data = _POST();
            if (empty($data['LoginType'])) {
                _ERROR('0000002', '没有提交验证类型');
            }
            $userModel = Model::instance('user');

            $userModel->loginApp($data);

        } else {
            _ERROR('000001', '未知错误');
        }
    }


    private function __checkHead()
    {
        $header = getRequestHeaders();

        if (!isset($header['Userid'])) {
            _ERROR('000001', 'VERIFY FAILS');
        }

        if (empty($header['Sign'])) {
            _ERROR('000001', 'VERIFY FAILS');
        }

        if (empty($header['Timestamp']) and (int)$header['Timestamp'] > 0) {
            _ERROR('000001', 'VERIFY FAILS');
        }

        if (empty($header['Appversion'])) {
            _ERROR('000001', 'VERIFY FAILS');
        }

        return $this->__verify($header['Userid'], $header['Appversion'], $header['Timestamp'],
            1, 3600, Android_APP_key, $header['Sign']);
    }

    /**
     * @param int $user_id
     * @param $appVersion
     * @param int $timestamp 时间
     * @param int $check_time_value 检查
     * @param int $v_value
     * @param string $api_key
     * @param $md
     * @return bool
     */
    private function __verify($user_id = 0, $appVersion, int $timestamp, $check_time_value = 1, $v_value = 3600,
                              $api_key = Android_APP_key, $md)
    {

        if (checkTimeOut($timestamp, $v_value, $check_time_value)) {
            //生成方式md5(userId + appVersion + iData +timestamp)然后大写
            $key = mb_strtoupper(md5($user_id . $appVersion . $api_key . $timestamp));
            if ($key == $md) {
                return true;
            } else {
                _ERROR('000001', 'VERIFY FAILS');
            }

        } else {
            _ERROR('000001', 'Sign TIME OUT');
        }

    }

    /**
     * 研究院
     *
     * @param $data
     * @return array
     */
    private function __irsResearchText($data)
    {
        $ret = ['tpl_id' => '2287054',
            'tpl_value' =>
                urlencode('#event_name#') . '=' . urlencode($data['event_name']) . '&'
                . urlencode('#event_address#') . '=' . urlencode($data['event_address']) . '&'
                . urlencode('#event_datetime#') . '=' . urlencode($data['event_datetime']),
            'apikey' => NATION_API, 'mobile' => $data['mobile']

        ];

        return $ret;
    }

    /**
     * register text for mobile
     * @param $data
     * @return array
     */
    private function __registerText($data)
    {
        if (!isset($data['timeout_value']))
            $data['timeout_value'] = 30;

        return $data;

    }
}