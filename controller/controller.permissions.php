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

    /**
     * 根据token，以及产品验证用户是否可以使用改产品
     * http://iutmain.itracker.cn/NLogin.aspx?guid=8fc6ed3b-8ce5-40a2-b0b5-5281cec92a01&irv_callback=http://10.10.21.163/iResearchDataWeb/?m=irdata&a=classicSys&ppname=PC%E7%AB%AF%E7%94%A8%E6%88%B7%E8%A1%8C%E4%B8%BA%E7%9B%91%E6%B5%8B&backType=1
     */
    public function checkUserProPer(){
        $data = json_decode(file_get_contents('php://input'), true);
        if($data['token'] === null OR $data['token'] === ''){ _ERROR('000001','TOKEN不能为空'); }
        if($data['productID'] === null OR $data['productID'] === ''){ _ERROR('000001','产品ID不能为空'); }
        $this->model->checkUserProPer($data);
    }
}