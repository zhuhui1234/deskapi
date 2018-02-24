<?php

/**
 * Created by PhpStorm.
 * User: robinwong51
 * Date: 01/09/2017
 * Time: 4:44 PM
 */
class PointsController extends Controller
{
    /*
    point type:
    包含5分以及以上为增加积分，大于5的时候，则减积分

        加法：
            1： 充值
            2： 撤回分值（加）
            3： 赠送分值
            4： 预留
            5： 预留

        ----------------------------
        减法：
            6： 定制报告
            7： 撤销分值（减）

        ----------------------------
        转账计算
            21： 公司加法，用户减法
            22： 公司减法，用户加法

    state
        - 0: 为有效
        - 1：为失效，通常情况

*/

    private $model;

    public function __construct($classname)
    {
        parent::__construct($classname);
        $this->model = Model::instance('Points');
    }

    /**
     * 定制报告减积分
     */
    public function addCustomizationReport()
    {
        $data = _POST();
        $this->model->addCustomizationReport($data);
    }

    /**
     * 撤销定制报告，返还积分
     */
    public function cancelCustomizationReport()
    {
        $data = _POST();
        $this->model->cancelCustomizationReport($data);
    }

    /**
     * cancel order
     */
    public function cancel()
    {
        $data = _POST();
        $this->model->cancel($data);
    }

    public function allotUser()
    {
        $data = _POST();
        $this->model->allotUser($data);
    }


    public function putBackPointToCompany()
    {
        $data = _POST();
        $this->model->putBackPointToCompany($data);
    }

    /**
     * get user total point
     */
    public function getPoint()
    {
        $data = _POST();
        $this->model->computePoint($data);
    }

    /**
     * get company total points
     */
    public function getCompanyPoint()
    {
        $data = _POST();
        $this->model->computePointForCompany($data);
    }

    /**
     * history points list for company
     */
    public function getPointListCompany()
    {
        $data = _POST();
        $this->model->getPointListCompany($data);
    }

    /**
     * history poins list for user
     */
    public function getPointListUser()
    {
        $data = _POST();
        $this->model->getPointListUser($data);
    }
}