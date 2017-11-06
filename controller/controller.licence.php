<?php
/**
 * Created by PhpStorm.
 * User: zh
 * Date: 2017/10/27
 * Time: 15:13
 */
class LicenceController extends Controller
{
    private $model;
    const M = "Licence";

    public function __construct()
    {
        $this->model = Model::instance(self::M);
    }

    /**
     * 获取公司下所有许可证
     */
    public function getLicencesByCompanyFullNameID()
    {
        //获取POST请求数据
        $data = _POST();

        //查询成功,并返回响应结果
        $this->model->getLicencesByCompanyFullNameID($data);
    }

    /**
     * 获取公司下所有许可证
     */
    public function getLicencesByUserID()
    {
        //获取POST请求数据
        $data = _POST();

        //查询成功,并返回响应结果
        $this->model->getLicencesByUserID($data);
    }

    /**
     * 修改许可证
     */
    public function editLicencesByUserID()
    {
        //获取POST请求数据
        $data = _POST();

        //查询成功,并返回响应结果
        $this->model->editLicencesByUserID($data);
    }

    /**
     * 移除许可证
     */
    public function removeLicencesByLicenceKey()
    {
        //获取POST请求数据
        $data = _POST();

        //查询成功,并返回响应结果
        $this->model->removeLicencesByLicenceKey($data);
    }

    /**
     * 积分列表
     */
    public function getPointLogByLicenceKey()
    {
        $data = _POST();

        $this->model->getPointLogByLicenceKey($data);
    }

    /**
     * 用户列表
     */
    public function getUserList()
    {
        $data = _POST();

        $this->model->getUserList($data);
    }
}