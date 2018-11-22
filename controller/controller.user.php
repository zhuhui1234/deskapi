<?php

/**
 * Created by iResearch
 * User 控制层
 * Author JOSON <joson@iresearch.com.cn>
 * Create 2016-09-18 11:02
 * Update 2016-12-28 14:49
 */
class UserController extends Controller
{
    private $model;
    const M = "User";

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
     * 用户登录
     */
    public function login()
    {
        //获取POST请求数据
        $data = _POST();
        //验证参数-登录账号
        if (empty($data['loginMobile']) && empty($data['Account'])) {
            _ERROR('000001', '登录账号不能为空');
        }
        //验证参数-账号密码
        if (empty($data['LoginKey'])) {
            _ERROR('000001', '登录密钥不能为空');
        }
        //验证参数-登录类型
        if (empty($data['LoginType'])) {
            _ERROR('000001', '登录类型不能为空');
        }
        //验证参数-登录类型
        if ($data['LoginType'] != 'weixin' AND $data['LoginType'] != 'mobile') {
            _ERROR('000001', '登录类型出错');
        }

        //登录成功,,并返回响应结果
        $this->model->login($data);
    }


    /**
     * 新版用户登录
     */
    public function b_login()
    {
        //获取POST请求数据
        $data = _POST();

        switch ($data['LoginType']){
            case 'mobile':
                if (empty($data['loginMobile']) && empty($data['Account'])) {
                    _ERROR('000001', '登录账号不能为空');
                }
                break;
            case 'mail':
                if (empty($data['loginMail']) ) {
                    _ERROR('000001', '邮箱不能为空');
                }
                break;

            case 'sp_mail':
                break;

            case 'weixin':

                break;
            default:
                _ERROR('000001', '登录类型出错');
        }


        //登录成功,,并返回响应结果
        $this->model->b_login($data);
    }

    /**
     * 新增用户
     */
    public function addUser()
    {
        //获取POST请求数据
        $data = _POST();

        //验证码不能为空
        if (empty($data['loginKey'])) {
            _ERROR('000001', '验证码不能为空');
        }
        //手机不能为空
        if (empty($data['loginMobile'])) {
            _ERROR('000001', '手机不能为空');
        }
        //微信Openid不能为空
        if (empty($data['wxOpenid'])) {
            _ERROR('000001', '微信Openid不能为空');
        }
        //微信Unionid不能为空
        if (empty($data['wxUnionid'])) {
            _ERROR('000001', '微信Unionid不能为空');
        }

        //绑定或新增用户
        $this->model->addUser($data);
    }

    /**
     * 发送短信验证
     */
    public function setMobileKey()
    {
        //获取POST请求数据
        $data = _POST();

        //验证参数-手机号码
        if (empty($data['Mobile'])) {
            _ERROR('000001', '手机号码不能为空');
        }

        //发送短信,,并返回响应结果
        $this->model->setMobileKey($data);
    }


    /**
     * new verify mobile func
     */
    public function setVerKey()
    {
        //获取POST请求数据
        $data = _POST();

        switch ($data['LoginType']) {

            case 'mobile':
                if (empty($data['Mobile'])) {
                    _ERROR('000001', '手机号码不能为空');
                }
                break;
            case 'mail':
                if (empty($data['Mail'])) {
                    _ERROR('000001', '邮箱不能为空');
                }
                break;

            default:
                //验证参数-手机号码
                if (empty($data['Mobile'])) {
                    _ERROR('000001', '手机号码不能为空');
                }
        }

        //发送短信,,并返回响应结果
        $this->model->setVerKey($data);
    }


    /**
     * 绑定产品Key
     */
    public function setProductKey()
    {
        //获取POST请求数据
        $data = _POST();

        //验证参数-产品账号
        if (empty($data['account'])) {
            _ERROR('000001', '产品账号不能为空');
        }
        //验证参数-产品密码
        if (empty($data['password'])) {
            _ERROR('000001', '产品密码不能为空');
        }

        //绑定产品KEY,并返回响应结果
        $this->model->setProductKey($data);
    }

    /**
     * 获取用户资料
     */
    public function getUserInfo()
    {
        //获取POST请求数据
        $data = _POST();

        //获取用户资料,并返回响应结果
        $this->model->getUserInfo($data);
    }

    /**
     * 修改用户资料
     */
    public function editUserInfo()
    {
        //获取POST请求数据
        $data = _POST();

        //验证参数-姓名
        if (empty($data['uname']) AND empty($data['position']) AND empty($data['headImg'])) {
            _ERROR('000001', '参数不能全部为空');
        }

        //修改用户资料,并返回响应结果
        $this->model->editUserInfo($data);
    }

    /**
     * 锁用户
     */
    public function iceUser()
    {
        //获取POST请求数据
        $data = _POST();

        //验证参数-用户ID
        if (empty($data['userID'])) {
            _ERROR('000001', '用户ID不能为空');
        }

        //解冻成功,并返回响应结果
        $this->model->iceUser($data);
    }

    /**
     * 解锁
     */
    public function thawUser()
    {
        //获取POST请求数据
        $data = _POST();

        //验证参数-用户ID
        if (empty($data['userID'])) {
            _ERROR('000001', '用户ID不能为空');
        }

        //解冻成功,并返回响应结果
        $this->model->thawUser($data);
    }

    /**
     * 获取用户List
     */
    public function userList()
    {
        //获取POST请求数据
        $data = _POST();

        //验证参数-查询页数
        if ($data['pageNo'] <= 0) {
            _ERROR('000001', '查询页数不能小于1');
        }
        //验证参数-查询数据
        if ($data['pageSize'] > 100) {
            _ERROR('000001', '查询数据一次不能超过100条');
        }

        //查询成功,并返回响应结果
        $this->model->userList($data);
    }

    /**
     * 用户产品列表
     */
    public function userProductInfo()
    {
        //获取POST请求数据
        $data = _POST();

        //查询成功,并返回响应结果
        $this->model->userProductInfo($data);
    }

    /**
     * 获取产品List
     */
    public function getProductList()
    {
        //获取POST请求数据
        $data = _POST();

        //查询成功,并返回响应结果
        $this->model->getProductList($data);
    }

    /**
     * 移出用户
     */
    public function removeUser()
    {
        //获取POST请求数据
        $data = _POST();

        //查询成功,并返回响应结果
        $this->model->removeUser($data);
    }

    /**
     * irc 客户端登入
     *
     * @return mixed
     */
    public function ircLogin()
    {
        $data = _POST();
        $ret = $this->model->ircLogin($data);
        if ($ret['state']) {

            $cp = $this->model->checkPdtPermissions($data['pdtID'], $ret['irUserInfo']['pplist']);

            if ($cp) {
                //权限匹配

            } else {
                //不匹配
            }
        }
//        pr($ret);
        //var_dump($cp);
        return $ret;
    }

    /**
     * app login
     *
     * @return array
     */
    public function appLogin()
    {
        $data = _POST();
        if (!isset($data['type'])) {
            header('Content-Type: application/json');
            echo json_encode(['code' => '500', 'state' => false, 'msg' => '缺少参数或是参数不能为空']);
        } else {
            if ($data['type'] == 'wechat') {
                //wechat login
                if (!isset($data['key']) or !isset($data['uuid'])) {
                    header('Content-Type: application/json');
                    echo json_encode(['code' => '500', 'state' => false, 'msg' => '缺少参数或是参数不能为空']);
                    exit();
                }

                if ($data['key'] != md5($data['uuid'] . KEY)) {
                    header('Content-Type: application/json');
                    echo json_encode(['code' => '500', 'state' => false, 'msg' => '验证码失败']);
                    exit();
                }

                if (!empty($data['key']) and !empty($data['uuid'])) {
                    $ret = $this->model->appLogin($data['uuid']);
                    header('Content-Type: application/json');
                    echo json_encode($ret);
                } else {
                    header('Content-Type: application/json');
                    echo json_encode(['code' => '500', 'state' => false, 'msg' => '缺少参数或是参数不能为空']);
                }
            } else if ($data['type'] == 'mobile') {
                //mobile login
                if (!isset($data['key']) or !isset($data['mobile'])) {
                    header('Content-Type: application/json');
                    echo json_encode(['code' => '500', 'state' => false, 'msg' => '缺少参数或是参数不能为空']);
                    exit();
                }

                if ($data['key'] != md5($data['mobile'] . KEY)) {

                    header('Content-Type: application/json');
                    echo json_encode(['code' => '500', 'state' => false, 'msg' => '验证码失败']);
                    exit();
                }

                if (!empty($data['key']) and !empty($data['mobile'])) {
                    $ret = $this->model->appMobileLogin($data['mobile']);
                    header('Content-Type: application/json');
                    echo json_encode($ret);
                } else {
                    header('Content-Type: application/json');
                    echo json_encode(['code' => '500', 'state' => false, 'msg' => '缺少参数或是参数不能为空']);
                }
            } else {
                //error login
                header('Content-Type: application/json');
                echo json_encode(['code' => '500', 'state' => false, 'msg' => '缺少参数或是参数不能为空']);
            }
        }


    }


    public function appBindingAccount()
    {
        $data = _POST();

        if (!isset($data['key']) or !isset($data['uuid']) or !isset($data['u_mobile'])) {
            header('Content-Type: application/json');
            echo json_encode(['code' => '500', 'state' => false, 'msg' => '缺少参数或是参数不能为空']);
            exit();
        }

        if ($data['key'] != md5($data['uuid'] . $data['u_mobile'] . KEY)) {
            header('Content-Type: application/json');
            echo json_encode(['code' => '500', 'state' => false, 'msg' => '验证码失败']);
            exit();
        }

        if (!empty($data['key']) and !empty($data['uuid']) and !empty($data['u_mobile'])) {
            $ret = $this->model->appBindAccount($data['u_mobile'], $data['uuid']);
            header('Content-Type: application/json');
            echo json_encode($ret);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['code' => '500', 'state' => false, 'msg' => '缺少参数或是参数不能为空']);
        }
    }

    public function addMyEmployee()
    {
        $data = _POST();

        $this->model->addMyEmployee($data);
    }

    public function sendKey()
    {
        $data = _POST();
//        var_dump($data);
        $this->model->sendKey($data);
    }

    public function addEmployee()
    {
        $data = _POST();
        $this->model->addEmployee($data);
    }

    public function getIRVuserid()
    {
        $data = _POST();
        $this->model->getIRVuserid($data);
    }

    public function checkToken()
    {
        $data = _POST();
        $ret = $this->model->checkToken($data);
        if ($ret) {
            _SUCCESS('0000000', 'OK');
        } else {
            _ERROR('000001', 'FAILS');
        }
    }

    public function industryList()
    {
        $data = _POST();
        $ret = $this->model->industryList();
        if ($ret) {
            _SUCCESS('0000000', 'OK', $ret);
        } else {
            _ERROR('000001', 'FAILS');
        }
    }

    public function regionList()
    {
        $data = _POST();
        $ret = $this->model->regionList();
        if ($ret) {
            _SUCCESS('0000000', 'OK', $ret);
        } else {
            _ERROR('000001', 'FAILS');
        }
    }

    public function productInfo()
    {
        $data = _POST();
        $ret = $this->model->productInfo($data);
        if ($ret) {
            _SUCCESS('0000000', 'OK', $ret);
        } else {
            _ERROR('000001', 'FAILS');
        }
    }

    /**
     * 通过IRD获取用户信息
     */
    public function getUserInfoByIRD()
    {
        $data = _POST();

        if (empty($data))
            _ERROR('000001', '缺少参数');

        if (empty($data['iUserID']))
            _ERROR('000001', '缺少参数');

        $ret = $this->model->getUserInfoByIRD($data['iUserID']);

        if ($ret) {
            return _SUCCESS('0000000', 'ok', $ret[0]);
        } else {
            _ERROR('000001', '没有绑定');
        }

    }
}