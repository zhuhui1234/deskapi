<?php
/**
 * Created by iResearch
 * Login 过滤层
 * Author JOSON <joson@iresearch.com.cn>
 * Create 2017-01-22 11:01
 * Update 2017-01-22 11:01
 * FileName:controller.login.php
 * 描述:
 */
class LoginController extends Controller
{
    private $model;
    const M = "Login";

    public function __construct()
    {
        $this->model = Model::instance(self::M);
    }

    //初始方法
    public function index(){

    }

    //用户注销
    public function cancel()
    {
        //获取POST请求数据
        $data = _POST();

        //验证参数-管理员GUID
        if($data['userID'] === null OR $data['userID'] === ''){ _ERROR('000001','用户ID不能为空'); }

        //注销成功,,并返回响应结果
        $this->model->cancel($data);
    }

}