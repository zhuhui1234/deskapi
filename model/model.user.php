<?php
/**
 * Created by iResearch
 * User: JOSON
 * Date: 16-08-23
 * Time: 下午16:26
 * Email:joson@iresearch.com.cn
 * FileName:controller.user.php
 * 描述:
 */
class UserModel extends AgentModel
{

    public function __consturct()
    {

    }

    //登录
    public function login($data)
    {
        //当前时间
        $uptimes = date("Y-m-d H:i:s");
        //创建TOKEN
        $upToken = md5(rand(1000000001,9999999999));

        //登录方式
        if($data['LoginType'] === 'mobile'){
            //游客注册
            $sql_chkMUser = "SELECT COUNT(1) mobile_num FROM idt_user WHERE u_mobile='{$data['Account']}'";
            $ret_chkMUser = $this->mysqlQuery($sql_chkMUser, "all");
            if($ret_chkMUser[0]['mobile_num'] <= 0){
                //创建游客
                $where_addMUser['u_id'] = getGUID();
                $where_addMUser['u_mobile'] = $data['Account'];//手机号码
                $where_addMUser['u_permissions'] = 0;//用户身份(0普通用户 1公司用户)
                $where_addMUser['u_token'] = $upToken;//用户token
                $where_addMUser['u_cdate'] = $uptimes;//创建时间
                $where_addMUser['u_edate'] = $uptimes;//最后登录时间
                $ret_addMUser = $this->mysqlInsert('idt_user',$where_addMUser);
                if($ret_addMUser != '1'){ _ERROR('000002','登录失败,创建游客失败'); }
            }

            $sql = "SELECT dba.u_id userid,dba.u_mobile mobile,dba.u_head headimg,dba.u_product_key productkey,dbc.cpy_validity validity,dba.u_name uname,dba.u_permissions permissions,dba.u_token token FROM idt_user dba LEFT JOIN idt_mobilekey dbb ON(dba.u_mobile=dbb.mik_mobile) LEFT JOIN idt_company dbc ON (dba.cpy_id=dbc.cpy_id) WHERE dba.u_mobile='{$data['Account']}' AND dbb.mik_key='{$data['LoginKey']}' AND dbb.mik_state=0 AND ROUND((UNIX_TIMESTAMP('{$uptimes}')-UNIX_TIMESTAMP(mik_cdate))/60)<=5";
        } else if ($data['LoginType'] === 'weixin'){
            $sql = "SELECT dba.u_id userid,dba.u_mobile mobile,dba.u_head headimg,dba.u_product_key productkey,dbb.cpy_validity validity,dba.u_name uname,dba.u_permissions permissions,dba.u_token token FROM idt_user dba LEFT JOIN idt_company dbb ON (dba.cpy_id=dbb.cpy_id) WHERE dba.u_wxopid='{$data['Account']}' AND dba.u_wxunid='{$data['LoginKey']}'";
        } else {
            //登录失败,参数错误
            _ERROR('000001','未知登录类型');
        }

        //登录
        $ret = $this->mysqlQuery($sql, "all");

        //验证登录&USER GUID不为空
        if(count($ret) > 0 AND ($ret[0]['userid']!=null OR $ret[0]['userid']!="")){
            //更新TOKEN
            $where_upToken['u_token'] = $upToken;//更新TOKEN
            $id_upToken = " u_id='".$ret[0]['userid']."'";//用户GUID
            $ret_upToken = $this->mysqlEdit('idt_user',$where_upToken,$id_upToken);

            //验证登录状态
            if($ret_upToken == '1'){
                //更新验证码状态
                if($data['LoginType'] === 'mobile'){
                    $where_upResCode['mik_state'] = 1; //验证码状态
                    $id_upResCode = " mik_mobile='".$data['Account']."' AND mik_key='".$data['LoginKey']."'";//用户帐号
                    $ret_upResCode = $this->mysqlEdit('idt_mobilekey',$where_upResCode,$id_upResCode);
                    if($ret_upResCode != '1'){ _ERROR('000002','登录失败,更新验证码状态失败'); }
                }

                //返回用户信息
                $rs['headImg'] = $ret[0]['headimg']; //头像
                $rs['mobile'] = $ret[0]['mobile']; //用户手机
                $rs['permissions'] = $ret[0]['permissions']; //用户身份 0游客 1企业用户 2企业管理员
                $rs['productKey'] = $ret[0]['productkey']; //产品Key
                $rs['token'] = $upToken; //用户token
                $rs['uname'] = $ret[0]['uname']; //用户姓名
                $rs['userID'] = $ret[0]['userid']; //用户GUID
                $rs['validity'] = $ret[0]['validity']; //账号有效期
                _SUCCESS('000000','登录成功',$rs);

            } else {
                _ERROR('000002','登录失败,更新token失败');
            }
        } else {
            _ERROR('000002','登录失败,账号不存在或验证码失效');
        }
    }

    //用户注册且绑定微信
    public function addUser($data)
    {
        //响应时间
        $uptimes = date("Y-m-d H:i:s");
        //创建token
        $upToken = md5(rand(1000000001,9999999999));

        //验证手机验证码
        $sql_resCode = "SELECT COUNT(1) chk_codenum FROM idt_mobilekey WHERE mik_mobile='{$data['loginMobile']}' AND mik_type=0 AND mik_state=0 AND mik_key='{$data['loginKey']}' AND ROUND((UNIX_TIMESTAMP('{$uptimes}')-UNIX_TIMESTAMP(mik_cdate))/60)<=5";
        $ret_resCode = $this->mysqlQuery($sql_resCode, "all");
        if($ret_resCode[0]['chk_codenum'] <= 0){ _ERROR('000002','登录失败,验证码错误或不存在'); }

        //检查微信是否已绑定
        $sql_wxnum = "SELECT COUNT(1) weixin_num FROM idt_user WHERE u_wxopid='{$data['wxOpenid']}'";
        $ret_wxnum = $this->mysqlQuery($sql_wxnum, "all");
        if($ret_wxnum[0]['weixin_num'] > 0){ _ERROR('000002','登录失败,该微信已绑定帐号'); }

        //查询手机号是否已存在
        $sql_mnum = "SELECT COUNT(1) mobile_num FROM idt_user WHERE u_mobile='{$data['loginMobile']}'";
        $ret_mnum = $this->mysqlQuery($sql_mnum, "all");

        if($ret_mnum[0]['mobile_num'] > 0){
            //如果手机已存在，更新微信绑定
            $where_editwx['u_wxopid'] = $data['wxOpenid'];//微信Openid
            $where_editwx['u_wxunid'] = $data['wxUnionid'];//微信Unionid
            $where_editwx['u_edate'] = $uptimes;//最后更新时间
            $id_editwx = " u_mobile='".$data['loginMobile']."'";//用户帐号
            $ret_chk = $this->mysqlEdit('idt_user',$where_editwx,$id_editwx);
        } else {
            //创建用户
            $where_addWMuser['u_id'] = getGUID();
            $where_addWMuser['u_mobile'] = $data['loginMobile'];//手机号码
            $where_addWMuser['u_wxopid'] = $data['wxOpenid'];//微信Openid
            $where_addWMuser['u_wxunid'] = $data['wxUnionid'];//微信Unionid
            $where_addWMuser['u_permissions'] = 0;//用户身份(0普通用户 1公司用户)
            $where_addWMuser['u_token'] = $upToken;//用户token
            $where_addWMuser['u_cdate'] = $uptimes;//创建时间
            $where_addWMuser['u_edate'] = $uptimes;//最后登录时间
            $ret_chk = $this->mysqlInsert('idt_user',$where_addWMuser);
        }

        //获取用户
        if($ret_chk == '1'){
            //更新TOKEN
            $where_upToken['u_token'] = $upToken;//更新TOKEN
            $id_upToken = " u_mobile='".$data['loginMobile']."'";//用户GUID
            $ret_upToken = $this->mysqlEdit('idt_user',$where_upToken,$id_upToken);
            if($ret_upToken != '1'){ _ERROR('000002','登录失败,更新token出错'); }

            //更新TOKEN
            $where_upCodeState['mik_state'] = 1;//更新TOKEN
            $id_upCodeState = " mik_mobile='".$data['loginMobile']."' AND mik_key='".$data['loginKey']."'";//手机验证码
            $ret_upCodeState = $this->mysqlEdit('idt_mobilekey',$where_upCodeState,$id_upCodeState);
            if($ret_upCodeState != '1'){ _ERROR('000002','登录失败,更新验证码状态出错'); }

            //获取用户
            $sql = "SELECT dba.u_id userid,dba.u_mobile mobile,dba.u_head headimg,dba.u_product_key productkey,dbb.cpy_validity validity,dba.u_name uname,dba.u_permissions permissions,dba.u_token token FROM idt_user dba LEFT JOIN idt_company dbb ON (dba.cpy_id=dbb.cpy_id) WHERE u_mobile='{$data['loginMobile']}'";
            $ret = $this->mysqlQuery($sql, "all");

            //返回用户信息
            $rs['headImg'] = $ret[0]['headimg']; //头像
            $rs['mobile'] = $ret[0]['mobile']; //用户手机
            $rs['permissions'] = $ret[0]['permissions']; //用户身份 0游客 1企业用户 2企业管理员
            $rs['productKey'] = $ret[0]['productkey']; //产品Key
            $rs['token'] = $upToken; //用户token
            $rs['uname'] = $ret[0]['uname']; //用户姓名
            $rs['userID'] = $ret[0]['userid']; //用户GUID
            $rs['validity'] = $ret[0]['validity']; //账号有效期
            _SUCCESS('000000','登录成功',$rs);
        } else {
            _ERROR('000002','登录失败');
        }
    }

    //发送验证码
    public function setMobileKey($data)
    {
        //当前时间
        $uptimes = date("Y-m-d H:i:s");

        //验证短信30内秒不可重复操作
        $sql_codeTime = "SELECT mik_key FROM idt_mobilekey WHERE mik_mobile='{$data['Mobile']}' AND mik_state='0' AND ROUND((UNIX_TIMESTAMP('{$uptimes}')-UNIX_TIMESTAMP(mik_cdate))/60)<=0.01 ORDER BY mik_cdate DESC LIMIT 1";
        $ret_codeTime = $this->mysqlQuery($sql_codeTime, "all");

        //验证短信30内秒不可重复操作
        if($ret_codeTime[0]['mik_key'] != "" OR $ret_codeTime[0]['mik_key'] != null){
            _ERROR('000002','发送失败,操作频繁,请稍后尝试');
        } else {
            //验证五分钟以内不产生新的验证码
            $sql_codeNews = "SELECT mik_key FROM idt_mobilekey WHERE mik_mobile='{$data['Mobile']}' AND mik_state='0' AND ROUND((UNIX_TIMESTAMP('{$uptimes}')-UNIX_TIMESTAMP(mik_cdate))/60)<=5 ORDER BY mik_cdate DESC LIMIT 1";
            $ret_codeNews = $this->mysqlQuery($sql_codeNews, "all");
            //生成短信验证码
            if($ret_codeNews[0]['mik_key'] == null OR $ret_codeNews[0]['mik_key'] == null){
                $data['Code'] = rand(100001,999999);
            } else {
                $data['Code'] = $ret_codeNews[0]['mik_key'];
            }

            //发送验证码
            $where['mik_mobile'] = $data['Mobile'];//手机号码
            $where['mik_type'] = 0;//验证类型(0.登录 1.注册 2.找回密码)
            $where['mik_key'] = $data['Code'];//验证码
            $where['mik_cdate'] = $uptimes;//创建时间
            $ret_codeSend = $this->mysqlInsert('idt_mobilekey',$where);
            if($ret_codeSend == '1'){
                //调用SMS,发送验证码
                $content = str_replace("(CODE)",$data['Code'],SMS_CONTENT);
                $phones = $data['Mobile'];
                $sms = Sms::instance()->sendSms($content,$phones);
                if($sms == '发送成功'){
                    _SUCCESS('000000','发送成功');
                } else {
                    _ERROR('000002','发送失败,SMS错误');
                }
            } else {
                _ERROR('000002','发送失败,数据异常');
            }
        }
    }

    //绑定产品KEY
    public function setProductKey($data)
    {
        //查询产品Key
        $sql_productkey = "SELECT u_product_key FROM idt_user WHERE u_id='{$data['userID']}'";
        $ret_productkey = $this->mysqlQuery($sql_productkey, "all");

        //查询产品Key是否已绑定其它账号
        $sql_keyNum = "SELECT COUNT(1) keyNum FROM idt_user WHERE u_product_key='{$data['productKey']}'";
        $ret_keyNum = $this->mysqlQuery($sql_keyNum, "row");

        //产品Key为空时才执行绑定操作
        if($ret_productkey[0]['u_product_key'] == "" OR $ret_productkey[0]['u_product_key'] == null){
            if($ret_keyNum[0] > 0){
                //绑定失败,该产品KEY已绑定其它账号
                _ERROR('000002','绑定失败,该产品KEY已绑定其它账号');
            } else {
                //绑定成功,更新产品KEY
                $where['u_product_key'] = $data['productKey']; //产品Key
                $productkey = " u_id='".$data['userID']."'";//用户GUID
                $ret_productkey = $this->mysqlEdit('idt_user',$where,$productkey);
                if($ret_productkey == 1){
                    _SUCCESS('000000','绑定成功');
                } else {
                    _ERROR('000002','绑定失败');
                }
            }
        } else {
            //绑定失败,该账号已绑定产品Key
            _ERROR('000002','绑定失败,该账号已绑定产品KEY');
        }
    }

    //获取用户资料
    public function getUserInfo($data)
    {
        //查询产品Key
        $sql = "SELECT dbb.cpy_cname,dba.u_mail,dba.u_head,dba.u_mobile,dba.u_position,dba.u_name FROM idt_user dba LEFT JOIN idt_company dbb ON (dba.cpy_id=dbb.cpy_id) WHERE dba.u_id='{$data['userID']}'";
        $ret = $this->mysqlQuery($sql, "all");

        //返回用户信息
        $rs['company'] = $ret[0]['cpy_cname']; //公司
        $rs['companyEmail'] = $ret[0]['u_mail']; //公司邮箱
        $rs['headImg'] = $ret[0]['u_head']; //头像
        $rs['mobile'] = $ret[0]['u_mobile']; //手机
        $rs['position'] = $ret[0]['u_position']; //职位
        $rs['uname'] = $ret[0]['u_name']; //姓名
        _SUCCESS('000000','获取成功',$rs);
    }

    //修改用户资料
    public function editUserInfo($data)
    {
        //修改用户资料
        $where['u_position'] = $data['position']; //职位
        $where['u_name'] = $data['uname']; //姓名
        $id = " u_id='".$data['userID']."'";//用户GUID
        $ret = $this->mysqlEdit('idt_user',$where,$id);

        //返回响应结果
        if($ret == '1'){
            _SUCCESS('000000','修改成功');
        } else {
            _ERROR('000002','修改失败');
        }
    }














//    //用户编辑
//    public function setupUserinfo($where)
//    {
//        //用户参数
//        $uptimes = date("Y-m-d H:i:s");//new date
//        $upuserinfo_where['u_head'] = $where['u_head'];//头像
//        $upuserinfo_where['u_name'] = $where['u_name'];//姓名
//        $upuserinfo_where['u_department'] = $where['u_department'];//部门
//        $upuserinfo_where['u_position'] = $where['u_position'];//职位
//        $upuserinfo_where['u_mobile'] = $where['u_mobile'];//联系电话(移动)
//        $upuserinfo_where['u_edate'] = $uptimes;//最后更新时间
//        $id = " u_account='".$where['u_account']."'";//用户帐号
//        $ret = $this->mysqlEdit('ivw_user',$upuserinfo_where,$id);
//
//        return $ret;
//    }
//
//    //用户注销
//    public function setCancellation($where)
//    {
//        //用户注销
//        $tokeyData['u_token'] = md5(rand(1000000001,9999999999));
//        $id = " u_account='".$where['u_account']."'";//用户帐号
//        $ret = $this->mysqlEdit('ivw_user',$tokeyData,$id);
//
//        return $ret;
//    }
//
//    //微信登录
//    public function wxlogin($where)
//    {
//        //更新TOKEN
//        $tokeyData['u_token'] = md5(rand(1000000001,9999999999));
//        $tokeyId = " u_wxopid='{$where['loginOpenid']}' AND u_wxunid='{$where['loginUnionid']}'";
//        $this->mysqlEdit('ivw_user',$tokeyData,$tokeyId);
//        //查询用户信息(包括最新TOKEN)
//        $sql = "SELECT u_id,u_account,u_head,u_name,u_department,u_position,u_mobile,u_token,u_cdate,u_edate FROM ivw_user WHERE 1=1 AND".$tokeyId;
//        $ret = $this->mysqlQuery($sql, "all");
//        return $ret;
//    }
//
//    //微信绑定
//    public function bindingWeixin($where)
//    {
//        //微信绑定
//        $weixinData['u_wxopid'] = $where['u_wxopid'];
//        $weixinData['u_wxunid'] = $where['u_wxunid'];
//        $tokeyId = " u_account='{$where['u_account']}'";
//        $ret = $this->mysqlEdit('ivw_user',$weixinData,$tokeyId);
//
//        return $ret;
//    }
//
//    //验证微信是否已绑定其他账号
//    public function ckweixin($where)
//    {
//        //验证微信是否已绑定其他账号
//        $sql = "SELECT COUNT(*) FROM ivw_user WHERE u_wxopid='{$where['u_wxopid']}' AND u_account NOT IN ('{$where['u_account']}')";
//        $ret = $this->mysqlQuery($sql, "row");
//        return $ret;
//    }
//
//    //验证邮件是否可注册
//    public function ckcompmail($mailsuffix)
//    {
//        //查询用户信息(包括最新TOKEN)
//        $sql = "SELECT COUNT(1) FROM ivw_company WHERE cpy_mail='{$mailsuffix}' AND cpy_state='0'";
//        $ret = $this->mysqlQuery($sql, "row");
//        return $ret;
//    }
//
//    //创建邮件服务KEY
//    public function setmailkey($mailkey_where)
//    {
//        //查询用户信息(包括最新TOKEN)
//        $uptimes = date("Y-m-d H:i:s");//new date
//        $mailkey_where['mlk_state'] = "0";//验证状态(0.进行中 1.正在处理 2.处理完成)
//        $mailkey_where['mlk_udate'] = $uptimes;//创建时间
//        $mailkey_where['mlk_edate'] = $uptimes;//最后更新时间
//        $ret = $this->mysqlInsert('ivw_mailkey',$mailkey_where);
//
//        return $ret;
//    }
//
//    //创建邮件服务KEY
//    public function setUserinfo($userinfo_where)
//    {
//        //用户参数
//        $uptimes = date("Y-m-d H:i:s");//new date
//        $where['u_id'] = getGUID();//用户ID
//        $where['u_account'] = $userinfo_where['u_account'];//用户帐号
//        $where['u_password'] = $userinfo_where['u_password'];//用户密码
//        $where['u_head'] = $userinfo_where['u_head'];//头像
//        $where['u_name'] = $userinfo_where['u_name'];//姓名
//        $where['u_department'] = $userinfo_where['u_department'];//部门
//        $where['u_position'] = $userinfo_where['u_position'];//职位
//        $where['u_mobile'] = $userinfo_where['u_mobile'];//联系电话(移动)
//        $where['u_token'] = md5(rand(1000000001,9999999999));//用户token
//        $where['u_cdate'] = $uptimes;//创建时间
//        $where['u_edate'] = $uptimes;//最后更新时间
//        $ret = $this->mysqlInsert('ivw_user',$where);
//
//        return $ret;
//    }
//
//    //验证邮件KEY
//    public function ckmailkey($where)
//    {
//        //验证邮件mailkey(包括最新mailkey)
//        $uptimes = date("Y-m-d H:i:s");//new date
//        $sql = "SELECT COUNT(*) FROM ivw_mailkey WHERE mlk_mail='{$where['mailname']}' AND mlk_key='{$where['mailkey']}' AND mlk_type='{$where['mailtype']}' AND ROUND((UNIX_TIMESTAMP('{$uptimes}')-UNIX_TIMESTAMP(mlk_edate))/60)<=30 ORDER BY mlk_edate DESC LIMIT 1";
//        $retcount = $this->mysqlQuery($sql, "row");
//        return $retcount;
//     }
//
//    //验证邮件是否已注册
//    public function ckmailcount($where)
//    {
//        //验证邮件mailkey(包括最新mailkey)
//        $sql = "SELECT COUNT(*) FROM ivw_user WHERE u_account='{$where['mailname']}'";
//        $retcount = $this->mysqlQuery($sql, "row");
//        return $retcount;
//    }
//
//    //重置密码
//    public function resetPassword($pwdinfo_where)
//    {
//        //用户参数
//        $uptimes = date("Y-m-d H:i:s");//new date
//        $where['u_password'] = $pwdinfo_where['u_password'];//用户密码
//        $where['u_token'] = md5(rand(1000000001,9999999999));//用户token
//        $where['u_edate'] = $uptimes;//最后更新时间
//        $id = " u_account='".$pwdinfo_where['u_account']."'";//用户帐号
//        $ret = $this->mysqlEdit('ivw_user',$where,$id);
//
//        return $ret;
//    }
//
//    //获取用户详情
//    public function getUserinfo($where)
//    {
//        //获取用户详情
//        $sql = "SELECT u_id,u_account,u_head,u_name,u_department,u_position,u_mobile,u_token,u_cdate,u_edate FROM ivw_user WHERE u_account='{$where['u_account']}'";
//        $retcount = $this->mysqlQuery($sql, "all");
//        return $retcount;
//    }
//
//    //获取用户列表
//    public function getUserinfoList($where)
//    {
//        //查询初始化条件
//        $where['orderByColumn'] == null ? $orderByColumn = 'u_cdate' : $orderByColumn = $where['orderByColumn']; //排序字段
//        $where['orderByType'] == null ? $orderByType = 'asc' : $orderByType = $where['orderByType']; //排序方式
//        $where['pageSize'] == null ? $pageSize = '10' : $pageSize = $where['pageSize']; //查询数据
//        $where['pageNo'] == null ? $pageNo = '0' : $pageNo = ($where['pageNo'] - 1) * $pageSize; //查询页数
//        $where['keyword'] == null ? $keyword = '' : $keyword = " AND u_account LIKE '%" . $where['keyword'] . "%'"; //查询条件
//        $mailsuffix = strstr($where['u_account'], '@');//截取邮箱后缀
//
//        //查询用户LIST
//        $sql = "SELECT u_id,u_account,u_head,u_name,u_department,u_position,u_mobile,u_token,u_state,u_cdate,u_edate FROM ivw_user WHERE 1=1 AND u_account LIKE '%{$mailsuffix}' {$keyword} order by {$orderByColumn} {$orderByType} limit {$pageNo},{$pageSize}";
//        $ret['data'] = $this->mysqlQuery($sql, "all");
//        //查询总条数
//        $sql = "SELECT COUNT(*) FROM ivw_user WHERE 1=1 AND u_account LIKE '%{$mailsuffix}' {$keyword}";
//        $ret['size'] = $this->mysqlQuery($sql, "row");
//
//        return $ret;
//    }
//
//    //冰结用户
//    public function setState($where)
//    {
//        //冰结操作
//        $setstate_where['u_state'] = $where['operation'];
//        $id = " u_account='".$where['u_account']."'";//用户帐号
//        $ret = $this->mysqlEdit('ivw_user',$setstate_where,$id);
//
//        return $ret;
//    }
//
//    //权限列表
//    public function getPermissionsList($where)
//    {
//        //查询初始化条件
//        $where['orderByColumn'] == null ? $orderByColumn = 'u_cdate' : $orderByColumn = $where['orderByColumn']; //排序字段
//        $where['orderByType'] == null ? $orderByType = 'asc' : $orderByType = $where['orderByType']; //排序方式
//        $where['pageSize'] == null ? $pageSize = '10' : $pageSize = $where['pageSize']; //查询数据
//        $where['pageNo'] == null ? $pageNo = '0' : $pageNo = ($where['pageNo'] - 1) * $pageSize; //查询页数
//        $where['keyword'] == null ? $keyword = '' : $keyword = " AND db1.u_account LIKE '%" . $where['keyword'] . "%'"; //查询条件
//
//        //获取公司ID
//        $sql_cuid = "SELECT u_id,cpy_id FROM ivw_user WHERE 1=1 AND u_account='".$where['u_account']."'";
//        $ret_cuid = $this->mysqlQuery($sql_cuid, "all");
//
//        //查询权限LIST
//        $sql_data = "SELECT {$where['cfg_id']} cfg_id, db1.u_account u_account, IF(db2.adt_state != '',db2.adt_state,0) adt_state, db1.u_cdate u_cdate FROM ivw_user db1 LEFT JOIN (SELECT u_id,adt_state FROM ivw_audit WHERE cfg_id={$where['cfg_id']}) db2 ON (db1.u_id = db2.u_id) WHERE 1=1{$keyword} AND db1.cpy_id={$ret_cuid[0]['cpy_id']} order by {$orderByColumn} {$orderByType} limit {$pageNo},{$pageSize}";
//        $ret['data'] = $this->mysqlQuery($sql_data, "all");
//        //查询总条数
//        $sql_size = "SELECT COUNT(1) FROM ivw_user db1 LEFT JOIN (SELECT u_id,adt_state FROM ivw_audit WHERE cfg_id={$where['cfg_id']}) db2 ON (db1.u_id = db2.u_id) WHERE 1=1{$keyword} AND db1.cpy_id={$ret_cuid[0]['cpy_id']}";
//        $ret['size'] = $this->mysqlQuery($sql_size, "row");
//
////        return $ret;
//        return $sql_data;
//    }

}
