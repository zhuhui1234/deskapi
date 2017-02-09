<?php
/**
 * Created by iResearch
 * Permissions 控制层
 * Author JOSON <joson@iresearch.com.cn>
 * Create 2016-09-18 11:02
 * Update 2016-12-28 14:49
 */
class PermissionsController extends Controller
{
    private $model;
    const M = "Permissions";

    public function __construct()
    {
        $this->model = Model::instance(self::M);
    }

    //初始方法
    public function index(){

    }

    //获取菜单导航
    public function getMenuList()
    {
        //获取POST请求数据
        $data = _POST();

        //验证参数-用户ID
        if($data['userID'] === null OR $data['userID'] === ''){ _ERROR('000001','用户GUID不能为空'); }

        //获取菜单导航,并返回响应结果
        $this->model->getMenuList($data);
    }

    //获取用户权限
    public function getPermissionsList()
    {
        //获取POST请求数据
        $data = _POST();

        //验证参数-用户ID
        if($data['userID'] === null OR $data['userID'] === ''){ _ERROR('000001','用户GUID不能为空'); }

        //获取菜单导航,并返回响应结果
        $this->model->getPermissionsList($data);
    }

    //获取首页菜单导航
    public function getHomeMenu()
    {
        //获取POST请求数据
        $data = _POST();

        //验证参数-公司ID
        if($data['companyID'] === null OR $data['companyID'] === ''){ _ERROR('000001','公司ID不能为空'); }

        //获取首页菜单导航,并返回响应结果
        $this->model->getHomeMenu($data);
    }
}