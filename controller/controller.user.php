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
    public function login(){
        //接收登录信息
        $data = json_decode(file_get_contents('php://input'), true);
        //服务器响应时间
        $rs = array( 'resTime' => time().'' );

        //检查是否为新用户注册——手机
        if($data['LoginType'] == 'mobile'){
            //检查验证手机
            $ret_chkMobileUser = $this->model->chkMobileUser($data);
            if($ret_chkMobileUser != '1'){
                $rs['data'] = '';
                $rs['resCode'] = '000002';
                $rs['resMsg'] = '登录失败';
                echo json_encode($rs,JSON_UNESCAPED_UNICODE);
                exit();
            }
        }

        //登录验证
        $ret = $this->model->login($data);

        //验证登录
        if(count($ret) <= 0){
            if($data['LoginType'] == 'weixin'){
                $rs['data'] = $data;
            } else {
                $rs['data'] = '';
            }
            $rs['resCode'] = '000001';
            $rs['resMsg'] = '登录失败';
        } else {

            //更新验证码状态(手机验证码登录方式)
            if($data['LoginType'] == 'mobile'){
                $ret_upCodeSate = $this->model->upResCode($data);
                if($ret_upCodeSate != '1'){
                    $rs['data'] = '';
                    $rs['resCode'] = '000002';
                    $rs['resMsg'] = '登录失败';
                    echo json_encode($rs,JSON_UNESCAPED_UNICODE);
                    exit();
                }
            }

            //返回结果数据
            $rs_data = array(
                'guid' => $ret[0]['guid'],
                'mobile' => $ret[0]['mobile'],
                'uname' => $ret[0]['uname'],
                'permissions' => $ret[0]['permissions'],
                'token' => $ret[0]['token']
            );

            $rs['data'] = $rs_data;
            $rs['resCode'] = '000000';
            $rs['resMsg'] = '登录成功';
        }

        //返回用户信息
        echo json_encode($rs,JSON_UNESCAPED_UNICODE);

    }

    //发送短信验证
    public function setMobileKey(){
        //接收发送手机号码
        $data = json_decode(file_get_contents('php://input'), true);

        //服务器响应时间
        $rs = array( 'resTime' => time().'','data' => '' );

        //手机号码不能为空
        if($data['Mobile'] == null OR $data['Mobile'] == ""){
            $rs['resCode'] = '000001';
            $rs['resMsg'] = '手机号码不能为空';
            echo json_encode($rs,JSON_UNESCAPED_UNICODE);
            exit();
        }

        //验证同一号码一分钟以内不能发送多条
        $ret_mcodetime = $this->model->getMCodeTime($data);
        if($ret_mcodetime != "" OR $ret_mcodetime != null){
            $rs['resCode'] = '000002';
            $rs['resMsg'] = '发送失败,操作频繁,请稍后尝试';
            echo json_encode($rs,JSON_UNESCAPED_UNICODE);
            exit();
        }

        //获取五分钟以内的最新验证码
        $ret_mCode = $this->model->getMobileKey($data);

        //短信验证码
        if($ret_mCode == null OR $ret_mCode == null){
            $data['Code'] = rand(100001,999999);
        } else {
            $data['Code'] = $ret_mCode;
        }

        //发送短信
        $ret = $this->model->setMobileKey($data);

        //验证
        if($ret == "1"){
            $content = str_replace("(CODE)",$data['Code'],SMS_CONTENT);
            $phones = $data['Mobile'];
            $sms = Sms::instance()->sendSms($content,$phones);
            if($sms == '发送成功'){
                $rs['resCode'] = '000000';
                $rs['resMsg'] = '发送成功';
            } else {
                $rs['resCode'] = '000002';
                $rs['resMsg'] = '发送失败';
            }
        } else {
            $rs['resCode'] = '000001';
            $rs['resMsg'] = '发送失败';
        }

        echo json_encode($rs,JSON_UNESCAPED_UNICODE);
    }

    //用户注册
    public function addUser(){

        //接收发送手机号码
        $data = json_decode(file_get_contents('php://input'), true);
        //服务器响应时间
        $rs = array( 'resTime' => time().'','data' => '' );

        //验证码不能为空
        if($data['loginKey'] == null OR $data['loginKey'] == ""){
            $rs['resCode'] = '000001';
            $rs['resMsg'] = '验证码不能为空';
            echo json_encode($rs,JSON_UNESCAPED_UNICODE);
            exit();
        }

        //手机不能为空
        if($data['loginMobile'] == null OR $data['loginMobile'] == ""){
            $rs['resCode'] = '000001';
            $rs['resMsg'] = '手机不能为空';
            echo json_encode($rs,JSON_UNESCAPED_UNICODE);
            exit();
        }

        //微信Openid不能为空
        if($data['wxOpenid'] == null OR $data['wxOpenid'] == ""){
            $rs['resCode'] = '000001';
            $rs['resMsg'] = '微信Openid不能为空';
            echo json_encode($rs,JSON_UNESCAPED_UNICODE);
            exit();
        }

        //微信Unionid不能为空
        if($data['wxUnionid'] == null OR $data['wxUnionid'] == ""){
            $rs['resCode'] = '000001';
            $rs['resMsg'] = '微信Unionid不能为空';
            echo json_encode($rs,JSON_UNESCAPED_UNICODE);
            exit();
        }

        //绑定或新增用户
        $ret = $this->model->addUser($data);

        //验证
        if($ret['chk'] == "1"){
            $rs['data'] = $ret['data'][0];
            $rs['resCode'] = '000000';
            $rs['resMsg'] = '绑定成功';
        } else if($ret['chk'] == "2"){
            $rs['resCode'] = '000001';
            $rs['resMsg'] = '绑定失败,验证码错误或不存在';
        } else if($ret['chk'] == "3"){
            $rs['resCode'] = '000001';
            $rs['resMsg'] = '绑定失败,该微信已绑定帐号';
        } else {
            $rs['resCode'] = '000001';
            $rs['resMsg'] = '绑定失败';
        }

        echo json_encode($rs,JSON_UNESCAPED_UNICODE);

    }

    //绑定产品Key
    public function setProductKey(){
        //接收发送手机号码
        $data = json_decode(file_get_contents('php://input'), true);
        //服务器响应时间
        $rs = array( 'resTime' => time().'','data' => '' );

        //绑定产品Key
        $ret = $this->model->setProductKey($data);

        //验证
        if($ret['chk'] == 1){
            $rs['data'] = $ret['data'];
            $rs['resCode'] = '000000';
            $rs['resMsg'] = '绑定成功';
        } else if($ret['chk'] == 2){
            $rs['resCode'] = '000002';
            $rs['resMsg'] = '绑定失败(已绑定账号,请先联系管理员解绑)';
        } else if($ret['chk'] == 3){
            $rs['resCode'] = '000002';
            $rs['resMsg'] = '绑定失败(该产品已绑定其它账号,请联系管理员)';
        } else {
            $rs['resCode'] = '000002';
            $rs['resMsg'] = '绑定失败';
        }

        echo json_encode($rs,JSON_UNESCAPED_UNICODE);
    }























































































































    //用户注销
    public function setCancellation(){

        //接收冰结参数
        $where = json_decode(file_get_contents('php://input'), true);
        $ret_data = array( 'resTime' => time().'', 'data' => '', 'resCode' => '', 'resMsg' => '' );//初始返回值

        //注销操作
        $ret = $this->model->setCancellation($where);

        //验证
        if($ret == "1"){
            $ret_data['resMsg'] = '注销成功';
            $ret_data['resCode'] = '000000';
            echo json_encode($ret_data,JSON_UNESCAPED_UNICODE);
        } else {
            $ret_data['resCode'] = '000002';
            $ret_data['resMsg'] = '注销失败';
            echo json_encode($ret_data,JSON_UNESCAPED_UNICODE);
        }

    }

    //微信登录
    public function wxlogin(){
        //接收登录信息
        $where = json_decode(file_get_contents('php://input'), true);
        $ret = $this->model->wxlogin($where);

        //服务器响应时间
        $rs = array( 'resTime' => time().'' );

        //验证登录
        if(count($ret) <= 0){
            $rs['data'] = '';
            $rs['resCode'] = '000002';
            $rs['resMsg'] = '微信登录失败或未绑定！！！';
        } else {
            $rs['data'] = $ret[0];
            $rs['resCode'] = '000000';
            $rs['resMsg'] = '微信登录成功！！！';
        }

        //返回用户信息
        echo json_encode($rs,JSON_UNESCAPED_UNICODE);
    }

    //微信绑定
    public function bindingWeixin(){

        //接收用户注册信息
        $where = json_decode(file_get_contents('php://input'), true);
        $rs = array( 'resTime' => time().'', 'data' => '', 'resCode' => '', 'resMsg' => '' );//初始返回值

        //绑定参数
        $setweixin_where['u_account'] = $where['u_account'];//用户帐号
        $setweixin_where['u_wxopid'] = $where['loginOpenid'];//微信openid
        $setweixin_where['u_wxunid'] = $where['loginUnionid'];//微信unionid

        //验证是否已绑定其他账号
        $ret_ckweixin = $this->model->ckweixin($setweixin_where);
        if($ret_ckweixin[0] > 0){
            $rs['resCode'] = '000002';
            $rs['resMsg'] = '绑定失败,该微信号已绑定其他账号';
            echo json_encode($rs,JSON_UNESCAPED_UNICODE);
            exit();
        }

        //绑定微信
        $ret = $this->model->bindingWeixin($setweixin_where);

        //验证
        if(count($ret) <= 0){
            $rs['resCode'] = '000002';
            $rs['resMsg'] = '绑定失败';
        } else {
            $rs['resCode'] = '000000';
            $rs['resMsg'] = '绑定成功';
        }

        //返回用户信息
        echo json_encode($rs,JSON_UNESCAPED_UNICODE);

    }

    //用户注册_发送邮件
    public function mailService(){
        //接收用户注册_发送邮件信息
        $where = json_decode(file_get_contents('php://input'), true);
        $smtpusermail = $where['smtpusermail'];//SMTP服务器作者
        $smtpemailto = $where['smtpemailto'];//发送给谁
        $mailtype = $where['mailtype'];//邮件类型
        $mailtitle = $where['mailtitle'];//邮件主题
        $mailcontent = $where['mailcontent'];//邮件内容(默认HTML内容)
        $ret_data = array( 'resTime' => time().'', 'data' => '', 'resCode' => '', 'resMsg' => '' );//初始返回值

        //验证-邮件发送类型不能为空
        if($smtpusermail==""){
            $ret_data['resCode'] = '000001';
            $ret_data['resMsg'] = '对不起，邮件发送SMTP服务器作者不能为空！！！';
            echo json_encode($ret_data,JSON_UNESCAPED_UNICODE);
            exit();
        }
        //验证-邮件发送类型不能为空
        if($mailtype==""){
            $ret_data['resCode'] = '000001';
            $ret_data['resMsg'] = '对不起，邮件发送类型不能为空！！！';
            echo json_encode($ret_data,JSON_UNESCAPED_UNICODE);
            exit();
        }
        //验证-邮件发送对象不能为空
        if($smtpemailto==""){
            $ret_data['resCode'] = '000001';
            $ret_data['resMsg'] = '对不起，邮件发送对象不能为空！！！';
            echo json_encode($ret_data,JSON_UNESCAPED_UNICODE);
            exit();
        }
        //验证-邮件发送主题不能为空
        if($mailtitle==""){
            $ret_data['resCode'] = '000001';
            $ret_data['resMsg'] = '对不起，邮件发送主题不能为空！！！';
            echo json_encode($ret_data,JSON_UNESCAPED_UNICODE);
            exit();
        }
        //验证-邮件发送内容不能为空
        if($mailcontent==""){
            $ret_data['resCode'] = '000001';
            $ret_data['resMsg'] = '对不起，邮件发送内容不能为空！！！';
            echo json_encode($ret_data,JSON_UNESCAPED_UNICODE);
            exit();
        }

        //获取邮箱后缀
        $mailsuffix = "@".explode('@', $smtpemailto)[1];
        $retthrough = $this->model->ckcompmail($mailsuffix);
        //验证-邮箱后缀是否有权限注册
        if($retthrough[0] <= 0){
            $ret_data['resCode'] = '000002';
            $ret_data['resMsg'] = '该公司未开通服务或已冻结！！！';
            echo json_encode($ret_data,JSON_UNESCAPED_UNICODE);
            exit();
        }

        //验证mail是否已注册
        $ckuinfo_where['mailname'] = $where['smtpemailto'];
        $retMailCount = $this->model->ckmailcount($ckuinfo_where);
        if($mailtype == 1){
            //注册验证-邮箱重复不可注册
            if($retMailCount[0] >= 1)
            {
                $ret_data['resCode'] = '000002';
                $ret_data['resMsg'] = '注册失败,邮件已注册！！！';
                echo json_encode($ret_data,JSON_UNESCAPED_UNICODE);
                exit();
            }
        } else if($mailtype == 2) {
            //重置密码验证-邮箱未注册不能注册
            if($retMailCount[0] <= 0)
            {
                $ret_data['resCode'] = '000002';
                $ret_data['resMsg'] = '找回失败,邮件不存在！！！';
                echo json_encode($ret_data,JSON_UNESCAPED_UNICODE);
                exit();
            }
        }

        //生成/更新EmailKey
        $mailkey_where['mlk_mail'] = $smtpemailto;//验证邮箱
        $mailkey_where['mlk_type'] = $mailtype;//邮件验证服务类型(1.注册 2.找回密码)
        $mailkey_where['mlk_key'] = md5(rand(1000000001,9999999999));//验证KEY
        $ret_setmailkey = $this->model->setmailkey($mailkey_where);
        //验证-正确生成EmailKey
        if($ret_setmailkey >= 1){
            //邮件内容追加mailkey
            $mailcontent = "<p>".$mailcontent."mailto=".$smtpemailto."&mailkey=".$mailkey_where['mlk_key']."</p><p>睿见运营团队<p>";
            //发送邮件
            $email = Email::instance()->sendmail($smtpemailto, $smtpusermail, $mailtitle, $mailcontent);

            //验证SMTP服务
            if($email==""){
                $ret_data['resCode'] = '000002';
                $ret_data['resMsg'] = '对不起，邮件发送失败！请检查邮箱填写是否有误！！！';
                echo json_encode($ret_data,JSON_UNESCAPED_UNICODE);
                exit();
            } else {
                $ret_data['resCode'] = '000000';
                $ret_data['resMsg'] = '恭喜！邮件发送成功！！！';
                echo json_encode($ret_data,JSON_UNESCAPED_UNICODE);
            }
        }

    }

    //用户注册
    public function createUserinfo(){

        //接收用户注册信息
        $where = json_decode(file_get_contents('php://input'), true);
        $ret_data = array( 'resTime' => time().'', 'data' => '', 'resCode' => '', 'resMsg' => '' );//初始返回值

        //校验数据
        $ckuinfo_where['mailkey'] = $where['mailkey'];
        $ckuinfo_where['mailtype'] = 1;
        $ckuinfo_where['mailname'] = $where['u_account'];//用户帐号
        $retMailkeyCount = $this->model->ckmailkey($ckuinfo_where);
        //验证mailkey是否有效
        if($retMailkeyCount[0] <= 0)
        {
            $ret_data['resCode'] = '000002';
            $ret_data['resMsg'] = '注册失败,邮件KEY失效或不存在！！！';
            echo json_encode($ret_data,JSON_UNESCAPED_UNICODE);
            exit();
        }
        //验证mail是否已注册
        $retMailCount = $this->model->ckmailcount($ckuinfo_where);
        if($retMailCount[0] >= 1)
        {
            $ret_data['resCode'] = '000002';
            $ret_data['resMsg'] = '注册失败,邮件已注册！！！';
            echo json_encode($ret_data,JSON_UNESCAPED_UNICODE);
            exit();
        }

        //用户注册
        $userinfo_where['u_account'] = $where['u_account'];//用户帐号
        $userinfo_where['u_password'] = md5($where['u_password'].PWD_KEY);//用户密码
        $userinfo_where['u_name'] = $where['u_name'];//姓名
        $userinfo_where['u_department'] = $where['u_department'];//部门
        $userinfo_where['u_position'] = $where['u_position'];//职位
        $userinfo_where['u_mobile'] = $where['u_mobile'];//联系电话(移动)
        $ret = $this->model->setUserinfo($userinfo_where);

        //验证
        if($ret == "1"){
            $ret_data['resCode'] = '000000';
            $ret_data['resMsg'] = '注册成功';
            echo json_encode($ret_data,JSON_UNESCAPED_UNICODE);
        } else {
            $ret_data['resCode'] = '000002';
            $ret_data['resMsg'] = '注册失败';
            echo json_encode($ret_data,JSON_UNESCAPED_UNICODE);
        }

    }

    //重置密码
    public function resetPassword(){

        //接收重置密码信息
        $where = json_decode(file_get_contents('php://input'), true);
        $ret_data = array( 'resTime' => time().'', 'data' => '', 'resCode' => '', 'resMsg' => '' );//初始返回值

        //校验数据
        $ckuinfo_where['mailkey'] = $where['mailkey'];
        $ckuinfo_where['mailtype'] = 2;
        $ckuinfo_where['mailname'] = $where['u_account'];//用户帐号
        $retMailkeyCount = $this->model->ckmailkey($ckuinfo_where);
        //验证mailkey是否有效
        if($retMailkeyCount[0] <= 0)
        {
            $ret_data['resCode'] = '000002';
            $ret_data['resMsg'] = '注册失败,邮件KEY失效或不存在！！！';
            echo json_encode($ret_data,JSON_UNESCAPED_UNICODE);
            exit();
        }

        //用户注册
        $userinfo_where['u_account'] = $where['u_account'];//用户帐号
        $userinfo_where['u_password'] = md5($where['u_password'].PWD_KEY);//用户密码
        $ret = $this->model->resetPassword($userinfo_where);

        //返回用户信息
        $ret_userinfo = $this->model->getUserinfo($userinfo_where);

        //验证
        if($ret == "1"){
            $ret_data['resCode'] = '000000';
            $ret_data['resMsg'] = '重置成功';
            $ret_data['data'] = $ret_userinfo[0];
            echo json_encode($ret_data,JSON_UNESCAPED_UNICODE);
        } else {
            $ret_data['resCode'] = '000002';
            $ret_data['resMsg'] = '重置失败';
            echo json_encode($ret_data,JSON_UNESCAPED_UNICODE);
        }

    }

    //用户详情
    public function getUserInfo(){

        //接收重置密码信息
        $where = json_decode(file_get_contents('php://input'), true);
        $rs = array( 'resTime' => time().'', 'data' => '', 'resCode' => '', 'resMsg' => '' );//初始返回值

        //返回用户详情
        $ret_userinfo = $this->model->getUserinfo($where);

        //返回结果数据
        $rs['resCode'] = '000000';
        $rs['resMsg'] = '';
        $rs['data'] = $ret_userinfo[0];
        echo json_encode($rs,JSON_UNESCAPED_UNICODE);
    }

    //用户列表
    public function getUserInfoList(){

        //接收重置密码信息
        $where = json_decode(file_get_contents('php://input'), true);
        $rs = array( 'resTime' => time().'', 'data' => '', 'resCode' => '', 'resMsg' => '' );//初始返回值

        //返回用户信息LIST
        $ret_userinfo = $this->model->getUserinfoList($where);

        //返回结果数据
        $rs['resCode'] = '000000';
        $rs['resMsg'] = '';
        $rs['data']['UserInfoList'] = $ret_userinfo['data'];
        $rs['data']['totalSize'] = $ret_userinfo['size'][0];
        echo json_encode($rs,JSON_UNESCAPED_UNICODE);

    }

    //用户冰结
    public function setState(){

        //接收冰结参数
        $where = json_decode(file_get_contents('php://input'), true);
        $ret_data = array( 'resTime' => time().'', 'data' => '', 'resCode' => '', 'resMsg' => '' );//初始返回值

        //冰结操作
        $ret = $this->model->setState($where);

        //验证
        if($ret == "1"){
            if($where['operation'] == 1){
                $ret_data['resMsg'] = '冰结成功';
            } else if($where['operation'] == 0){
                $ret_data['resMsg'] = '解冰成功';
            }
            $ret_data['resCode'] = '000000';
            echo json_encode($ret_data,JSON_UNESCAPED_UNICODE);
        } else {
            $ret_data['resCode'] = '000002';
            $ret_data['resMsg'] = '操作失败';
            echo json_encode($ret_data,JSON_UNESCAPED_UNICODE);
        }

    }

    //用户编辑
    public function setUserInfo(){

        //接收冰结参数
        $where = json_decode(file_get_contents('php://input'), true);
        $rs = array( 'resTime' => time().'', 'data' => '', 'resCode' => '', 'resMsg' => '' );//初始返回值

        //用户编辑
        $ret = $this->model->setupUserinfo($where);

        //验证
        if($ret == "1"){
            $rs['resMsg'] = '编辑成功';
            $rs['resCode'] = '000000';
            echo json_encode($rs,JSON_UNESCAPED_UNICODE);
        } else {
            $rs['resCode'] = '000002';
            $rs['resMsg'] = '编辑失败';
            echo json_encode($rs,JSON_UNESCAPED_UNICODE);
        }
    }

    //权限列表
    public function getPermissionsList(){

        //接收冰结参数
        $where = json_decode(file_get_contents('php://input'), true);
        $rs = array( 'resTime' => time().'', 'data' => '', 'resCode' => '', 'resMsg' => '' );//初始返回值

        //用户编辑
        $ret = $this->model->getPermissionsList($where);

//        //返回结果
//        $rs['resCode'] = '000000';
//        $rs['resMsg'] = '查询成功';
//        $rs['data']['PermissionsList'] = $ret['data'];
//        $rs['data']['totalSize'] = $ret['size'][0];
//
//        echo json_encode($rs,JSON_UNESCAPED_UNICODE);

        echo $ret;

    }

    public function getGUID(){
//        for($i=0;$i<=100;$i++){
//            echo getGUID()."<br />";
//        }
        echo "aaaaa";
    }

}