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
        session_start();
        if(empty($_SESSION['idexuserInfo']['token'])){
            _ERROR('000001', '登录超时');
        }else{
            $rs = Model::instance("user")->_getUserInfoByToken($_SESSION['idexuserInfo']['token']);
            if($rs['permissions'] != 2){
                _ERROR('000002', '无操作权限');
            }
        }
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
     * 设置许可证子产品到期时间
     */
    public function setSubProductPermissionsByLicenceKey()
    {
        //获取POST请求数据
        $data = _POST();

        //查询成功,并返回响应结果
        $this->model->setSubProductPermissionsByLicenceKey($data);
    }

    /**
     * 设置许积分充值
     */
    public function topUp()
    {
        //获取POST请求数据
        $data = _POST();

        //查询成功,并返回响应结果
        $this->model->topUp($data);
    }

    /**
     * 积分列表
     */
    public function getPointLogByLicenceKey()
    {
        $data = _POST();

        $this->model->getPointLogByLicenceKey($data);
    }
}