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

    //初始方法
    public function index()
    {

    }

    //获取绑定服务
    public function getService()
    {
        //获取POST请求数据
        $data = _POST();

        //查询成功,,并返回响应结果
        $this->model->getService($data);
    }

    //绑定微信
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
        if (empty($data['wxUnionid'] )) {
            _ERROR('000001', '微信Unionid不能为空');
        }

        //绑定成功,,并返回响应结果
        $this->model->setWxService($data);
    }
}