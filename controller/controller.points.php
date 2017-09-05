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

    1： 充值
    2： 撤回分值（加）
    3： 赠送分值
    4： 预留
    5： 预留
    ----------------------------
    6： 定制报告
    7： 预留
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
     * 充值
     */
    public function topUp()
    {
        $data = _POST();
        $this->model->topUp($data);
    }

    /**
     * cancel order
     */
    public function cancel()
    {
        $data = _POST();
        $this->model->cancel($data);
    }

    /**
     * point list
     */
    public function pointList()
    {
        $data = _POST();
        $this->model->poinList($data);
    }

    /**
     * get point
     */
    public function getPoint()
    {
        $data = _POST();
        $this->model->computePoint($data);
    }

}