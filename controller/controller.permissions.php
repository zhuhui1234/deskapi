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
    public function index()
    {

    }

    //获取菜单导航
    public function getMenuList()
    {
        //获取POST请求数据
        $data = _POST();

        //验证参数-用户ID
        if ($data['userID'] === null OR $data['userID'] === '') {
            _ERROR('000001', '用户GUID不能为空');
        }

        //获取菜单导航,并返回响应结果
        $this->model->getMenuList($data);
    }

    //获取用户权限
    public function getPermissionsList()
    {
        //获取POST请求数据
        $data = _POST();

        //验证参数-用户ID
        if ($data['userID'] === null OR $data['userID'] === '') {
            _ERROR('000001', '用户GUID不能为空');
        }

        //获取菜单导航,并返回响应结果
        $this->model->getPermissionsList($data);
    }

    //获取首页菜单导航
    public function getHomeMenu()
    {
        //获取POST请求数据
        $data = _POST();

        //验证参数-公司ID
        if ($data['companyID'] === null OR $data['companyID'] === '') {
            _ERROR('000001', '公司ID不能为空');
        }

        //获取首页菜单导航,并返回响应结果
        $this->model->getHomeMenu($data);
    }

    /**
     *
     * 根据token，以及产品验证用户是否可以使用改产品
     *
     * http://iutmain.itracker.cn/NLogin.aspx?
     * guid=8fc6ed3b-8ce5-40a2-b0b5-5281cec92a01&
     * irv_callback=http://10.10.21.163/iResearchDataWeb/?m=irdata&
     * a=classicSys&ppname=PC%E7%AB%AF%E7%94%A8%E6%88%B7%E8%A1%8C%E4%B8%BA%E7%9B%91%E6%B5%8B&backType=1
     */
    public function checkUserProPer()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        if ($data['token'] === null OR $data['token'] === '') {
            _ERROR('000001', 'TOKEN不能为空');
        }
        if ($data['productID'] === null OR $data['productID'] === '') {
            _ERROR('000001', '产品ID不能为空');
        }
        $this->model->checkUserProPer($data);
    }

    /**
     * get user info by token
     */
    public function getUserInfo()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!empty($data['token'])) {
            _SUCCESS('20000', done, Model::instance('user')->_getUserInfoByToken($data));
        } else {
            _ERROR('000003', 'token is empty');
        }
    }

    /**
     * check permission
     */
    public function checkPermission()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!empty($data['token'])) {

            $ret = $this->model->getPermissionInfo($data);

            if ($ret) {
                $userInfoByToken = Model::instance('user')->_getUserInfoByToken($data);

                $exp = (time() - strtotime($userInfoByToken['tokenDate'])) / (60 * 60);
                write_to_log('exp: '.$exp,'_time');
                write_to_log('time_out: '.TOKEN_TIME_OUT, '_time');
                if ($exp > TOKEN_TIME_OUT) {
                    _ERROR('40004', 'TOKEN超时');
                }

                _SUCCESS('20000', 'done', [
                    'state' => 'allow',
                    'data' => $ret,
                    'userInfo' => $userInfoByToken
                ]);
            } else {
                _ERROR('40000', '无权使用', [
                    'state' => 'deny', 'data' => $this->model->getPdtInfo($data['pdt_id'])]);
            };
        } else {
            _ERROR('40004', 'TOKEN不能为空');
        }
    }

    /**
     * check permission for mobile
     */
    public function checkPermissionForMobile()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!empty($data['userID'])) {
            $ret = $this->model->getPermissionInfoByUserID($data);
            if ($ret) {
                _SUCCESS('20000', 'done', ['state' => 'allow', 'data' => $ret, 'userInfo']);
            } else {
                _ERROR('40000', '无权使用', [
                    'state' => 'deny', 'data' => $this->model->getPdtInfo($data['pdt_id'])]);
            };

        } else {
            _ERROR('40004', 'user id 不能为空');
        }
    }

    /**
     * 根据URI判断权限
     */
    public function checkPermissionURI()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!empty($data['token'])) {
            $ret = $this->model->getPermissionInfoByURI($data);
            if ($ret) {
                _SUCCESS('20000', 'done', ['state' => 'allow', 'data' => $ret]);
            } else {
                _ERROR('40000', '无权使用', [
                    'state' => 'deny', 'data' => $this->model->getPdtInfo($data['pdt_id'])]);
            };
        } else {
            _ERROR('40004', 'TOKEN不能为空');
        }
    }

    public function getProduct()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        if (isset($data['pdt_id'])) {
            $ret = $this->model->getProduct($data);
            _SUCCESS('0000000', 'check product', $ret);
        } else {
            _ERROR('40000', '没有产品id');
        }

    }


    /**
     * apply product!
     */
    public function applyPermission()
    {
        $data = _POST();
        if (empty($data['userID'])) {
            _ERROR('40000', '用户没有登入或是注册');
        }

        if (empty($data['username'])) {
            _ERROR('40000', '名字不能为空');
        }

        if (empty($data['mobile'])) {
            _ERROR('40000', '没有手机号');
        }

        if (empty($data['pdt_id'])) {
            _ERROR('40000', '没有产品');
        }

        if (empty($data['mail'])) {
            _ERROR('40000', '没有邮箱');
        }

        if (empty($data['region'])) {
            _ERROR('40000', '没有选择地区');
        }

        $ret = $this->model->applyPermission($data);
//        var_dump($ret);
        if ($ret) {
            _SUCCESS('20000', 'done');
        } else {
            _ERROR('40000', '申请失败');
        }
    }

    /**
     * check mail
     */
    public function checkMail()
    {
        $data = _POST();
        if (empty($data['pi'])) {
            _ERROR('40000', '缺少参数');
        }

        if (empty($data['cd'])) {
            _ERROR('40000', '缺少参数');
        }

        $ret = $this->model->checkCode($data);

        if ($ret) {
            _SUCCESS('20000', '验证通过,我们的销售会在三个工作日内联系您.');
        } else {
            _ERROR('40000', '验证失败');
        }

    }
}