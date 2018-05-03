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
}