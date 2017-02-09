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

    public function __construct()
    {
        $this->model = Model::instance(self::M);
    }

    //初始方法
    public function index(){

    }

    //用户登录
    public function login()
    {
        //获取POST请求数据
        $data = _POST();

        //验证参数-登录账号
        if($data['Account'] === null OR $data['Account'] === ''){ _ERROR('000001','登录账号不能为空'); }
        //验证参数-账号密码
        if($data['LoginKey'] === null OR $data['LoginKey'] === ''){ _ERROR('000001','登录密钥不能为空'); }
        //验证参数-登录类型
        if($data['LoginType'] === null OR $data['LoginType'] === ''){ _ERROR('000001','登录类型不能为空'); }
        //验证参数-登录类型
        if($data['LoginType'] != 'weixin' AND $data['LoginType'] != 'mobile'){ _ERROR('000001','登录类型出错'); }

        //登录成功,,并返回响应结果
        $this->model->login($data);
    }

    //用户注册
    public function addUser()
    {
        //获取POST请求数据
        $data = _POST();

        //验证码不能为空
        if($data['loginKey'] == null OR $data['loginKey'] == ""){ _ERROR('000001','验证码不能为空'); }
        //手机不能为空
        if($data['loginMobile'] == null OR $data['loginMobile'] == ""){ _ERROR('000001','手机不能为空'); }
        //微信Openid不能为空
        if($data['wxOpenid'] == null OR $data['wxOpenid'] == ""){ _ERROR('000001','微信Openid不能为空'); }
        //微信Unionid不能为空
        if($data['wxUnionid'] == null OR $data['wxUnionid'] == ""){ _ERROR('000001','微信Unionid不能为空'); }

        //绑定或新增用户
        $this->model->addUser($data);
    }

    //发送短信验证
    public function setMobileKey()
    {
        //获取POST请求数据
        $data = _POST();

        //验证参数-手机号码
        if($data['Mobile'] === null OR $data['Mobile'] === ""){ _ERROR('000001','手机号码不能为空'); }

        //发送短信,,并返回响应结果
        $this->model->setMobileKey($data);
    }

    //绑定产品Key
    public function setProductKey()
    {
        //获取POST请求数据
        $data = _POST();

        //验证参数-产品账号
        if($data['account'] === null OR $data['account'] === ''){ _ERROR('000001','产品账号不能为空'); }
        //验证参数-产品密码
        if($data['password'] === null OR $data['password'] === ''){ _ERROR('000001','产品密码不能为空'); }

        //绑定产品KEY,并返回响应结果
        $this->model->setProductKey($data);
    }

    //获取用户资料
    public function getUserInfo()
    {
        //获取POST请求数据
        $data = _POST();

        //获取用户资料,并返回响应结果
        $this->model->getUserInfo($data);
    }

    //修改用户资料
    public function editUserInfo()
    {
        //获取POST请求数据
        $data = _POST();

        //验证参数-姓名
        if(($data['uname'] === null OR $data['uname'] === '') AND ($data['position'] === null OR $data['position'] === '') AND ($data['headImg'] === null OR $data['headImg'] === '')){
            _ERROR('000001','参数不能全部为空');
        }

        //修改用户资料,并返回响应结果
        $this->model->editUserInfo($data);
    }

    //冰结用户
    public function iceUser()
    {
        //获取POST请求数据
        $data = _POST();

        //验证参数-用户ID
        if($data['uid'] === null OR $data['uid'] === ''){ _ERROR('000001','用户ID不能为空'); }

        //解冻成功,并返回响应结果
        $this->model->iceUser($data);
    }

    //解冰用户
    public function thawUser()
    {
        //获取POST请求数据
        $data = _POST();

        //验证参数-用户ID
        if($data['uid'] === null OR $data['uid'] === ''){ _ERROR('000001','用户ID不能为空'); }

        //解冻成功,并返回响应结果
        $this->model->thawUser($data);
    }

    //获取用户List
    public function userList()
    {
        //获取POST请求数据
        $data = _POST();

        //验证参数-查询页数
        if($data['pageNo'] <= 0){ _ERROR('000001','查询页数不能小于1'); }
        //验证参数-查询数据
        if($data['pageSize'] > 100){ _ERROR('000001','查询数据一次不能超过100条'); }


        //查询成功,并返回响应结果
        $this->model->userList($data);
    }

}