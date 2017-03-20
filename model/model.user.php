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

    public function __construct()
    {

    }

    //登录
    public function login($data)
    {
        //当前时间
        $uptimes = date("Y-m-d H:i:s");
        //创建TOKEN
        $upToken = md5(rand(1000000001, 9999999999));

        //登录方式
        if ($data['LoginType'] === 'mobile') {
            //游客注册
            $sql_chkMUser = "SELECT COUNT(1) mobile_num FROM idt_user WHERE u_mobile='{$data['Account']}'";
            $ret_chkMUser = $this->mysqlQuery($sql_chkMUser, "all");
            if ($ret_chkMUser[0]['mobile_num'] <= 0) {
                //创建游客
                $where_addMUser['u_id'] = getGUID();
                $where_addMUser['u_mobile'] = $data['Account'];//手机号码
                $where_addMUser['u_permissions'] = 0;//用户身份(0普通用户 1公司用户)
                $where_addMUser['u_token'] = $upToken;//用户token
                $where_addMUser['u_cdate'] = $uptimes;//创建时间
                $where_addMUser['u_edate'] = $uptimes;//最后登录时间
                $ret_addMUser = $this->mysqlInsert('idt_user', $where_addMUser);
                if ($ret_addMUser != '1') {
                    _ERROR('000002', '登录失败,创建游客失败');
                }
            }

            $sql = "SELECT dba.u_id userid,dba.u_mobile mobile,dbc.cpy_id cpy_id,dbc.cpy_cname cpy_cname,dba.u_head headimg,dba.u_product_key productkey,dbc.cpy_validity validity,dba.u_name uname,dba.u_permissions permissions,dba.u_token token,dba.u_state u_state FROM idt_user dba LEFT JOIN idt_mobilekey dbb ON(dba.u_mobile=dbb.mik_mobile) LEFT JOIN idt_company dbc ON (dba.cpy_id=dbc.cpy_id) WHERE dba.u_mobile='{$data['Account']}' AND dbb.mik_key='{$data['LoginKey']}' AND dbb.mik_state=0 AND ROUND((UNIX_TIMESTAMP('{$uptimes}')-UNIX_TIMESTAMP(mik_cdate))/60)<=5";
        } else if ($data['LoginType'] === 'weixin') {
            $sql = "SELECT dba.u_id userid,dba.u_mobile mobile,dbb.cpy_id cpy_id,dbb.cpy_cname cpy_cname,dba.u_head headimg,dba.u_product_key productkey,dbb.cpy_validity validity,dba.u_name uname,dba.u_permissions permissions,dba.u_token token,dba.u_state u_state FROM idt_user dba LEFT JOIN idt_company dbb ON (dba.cpy_id=dbb.cpy_id) WHERE dba.u_wxopid='{$data['Account']}' AND dba.u_wxunid='{$data['LoginKey']}'";
        } else {
            //登录失败,参数错误
            _ERROR('000001', '未知登录类型');
        }

        //登录
        $ret = $this->mysqlQuery($sql, "all");

        //验证冰结用户
        if ($ret[0]['u_state'] == 1) {
            _ERROR('000002', '登录失败,该用户已冰结');
        }

        //验证登录&USER GUID不为空
        if (count($ret) > 0 AND ($ret[0]['userid'] != null OR $ret[0]['userid'] != "")) {
            //更新TOKEN
            $where_upToken['u_token'] = $upToken;//更新TOKEN
            $id_upToken = " u_id='" . $ret[0]['userid'] . "'";//用户GUID
            $ret_upToken = $this->mysqlEdit('idt_user', $where_upToken, $id_upToken);

            //验证登录状态
            if ($ret_upToken == '1') {
                //更新微信名称
                if ($data['LoginType'] === 'weixin') {
                    $where_upwxName['u_wxname'] = urlencode($data['wxName']);//微信名称
                    $id_upwxName = " u_wxopid='" . $data['Account'] . "' AND u_wxunid='" . $data['LoginKey'] . "'";//微信帐号
                    $ret_upwxName = $this->mysqlEdit('idt_user', $where_upwxName, $id_upwxName);
                    if ($ret_upwxName != '1') {
                        _ERROR('000002', '登录失败,更新微信名称失败');
                    }
                }

                //更新验证码状态
                if ($data['LoginType'] === 'mobile') {
                    $where_upResCode['mik_state'] = 1; //验证码状态
                    $id_upResCode = " mik_mobile='" . $data['Account'] . "' AND mik_key='" . $data['LoginKey'] . "'";//用户帐号
                    $ret_upResCode = $this->mysqlEdit('idt_mobilekey', $where_upResCode, $id_upResCode);
                    if ($ret_upResCode != '1') {
                        _ERROR('000002', '登录失败,更新验证码状态失败');
                    }
                }

                //产品权限
                if ($ret[0]['productkey'] == 0 OR $ret[0]['productkey'] == null OR $ret[0]['productkey'] == "") {
                    $productKey = 0;
                } else {
                    $productKey = 1;
                }
                //返回用户信息
                $rs['headImg'] = $ret[0]['headimg']; //头像
                $rs['mobile'] = $ret[0]['mobile']; //用户手机
                $rs['companyID'] = $ret[0]['cpy_id']; //公司ID
                $rs['companyName'] = $ret[0]['cpy_cname']; //公司名称
                $rs['permissions'] = $ret[0]['permissions']; //用户身份 0游客 1企业用户 2企业管理员
                $rs['productKey'] = $productKey; //产品权限
                $rs['token'] = $upToken; //用户token
                $rs['uname'] = $ret[0]['uname']; //用户姓名
                $rs['userID'] = $ret[0]['userid']; //用户GUID
                $rs['validity'] = $ret[0]['validity']; //账号有效期
                _SUCCESS('000000', '登录成功', $rs);

            } else {
                _ERROR('000002', '登录失败,更新token失败');
            }
        } else {
            _ERROR('000002', '登录失败,账号不存在或验证码失效');
        }
    }

    //用户注册且绑定微信
    public function addUser($data)
    {
        //响应时间
        $uptimes = date("Y-m-d H:i:s");
        //创建token
        $upToken = md5(rand(1000000001, 9999999999));

        //验证手机验证码
        $sql_resCode = "SELECT COUNT(1) chk_codenum FROM idt_mobilekey WHERE mik_mobile='{$data['loginMobile']}' AND mik_type=0 AND mik_state=0 AND mik_key='{$data['loginKey']}' AND ROUND((UNIX_TIMESTAMP('{$uptimes}')-UNIX_TIMESTAMP(mik_cdate))/60)<=5";
        $ret_resCode = $this->mysqlQuery($sql_resCode, "all");
        if ($ret_resCode[0]['chk_codenum'] <= 0) {
            _ERROR('000002', '登录失败,验证码错误或不存在');
        }

        //检查微信是否已绑定
        $sql_wxnum = "SELECT COUNT(1) weixin_num FROM idt_user WHERE u_wxopid='{$data['wxOpenid']}'";
        $ret_wxnum = $this->mysqlQuery($sql_wxnum, "all");
        if ($ret_wxnum[0]['weixin_num'] > 0) {
            _ERROR('000002', '登录失败,该微信已绑定帐号');
        }

        //查询手机号是否已存在
        $sql_mnum = "SELECT COUNT(1) mobile_num FROM idt_user WHERE u_mobile='{$data['loginMobile']}'";
        $ret_mnum = $this->mysqlQuery($sql_mnum, "all");

        if ($ret_mnum[0]['mobile_num'] > 0) {
            //如果手机已存在，更新微信绑定
            $where_editwx['u_wxname'] = $data['wxName'];//微信名称
            $where_editwx['u_wxopid'] = $data['wxOpenid'];//微信Openid
            $where_editwx['u_wxunid'] = $data['wxUnionid'];//微信Unionid
            $where_editwx['u_edate'] = $uptimes;//最后更新时间
            $id_editwx = " u_mobile='" . $data['loginMobile'] . "'";//用户帐号
            $ret_chk = $this->mysqlEdit('idt_user', $where_editwx, $id_editwx);
        } else {
            //创建用户
            $where_addWMuser['u_id'] = getGUID();
            $where_addWMuser['u_mobile'] = $data['loginMobile'];//手机号码
            $where_addWMuser['u_wxname'] = $data['wxName'];//微信名称
            $where_addWMuser['u_wxopid'] = $data['wxOpenid'];//微信Openid
            $where_addWMuser['u_wxunid'] = $data['wxUnionid'];//微信Unionid
            $where_addWMuser['u_permissions'] = 0;//用户身份(0普通用户 1公司用户)
            $where_addWMuser['u_token'] = $upToken;//用户token
            $where_addWMuser['u_cdate'] = $uptimes;//创建时间
            $where_addWMuser['u_edate'] = $uptimes;//最后登录时间
            $ret_chk = $this->mysqlInsert('idt_user', $where_addWMuser);
        }

        //获取用户
        if ($ret_chk == '1') {
            //更新TOKEN
            $where_upToken['u_token'] = $upToken;//更新TOKEN
            $id_upToken = " u_mobile='" . $data['loginMobile'] . "'";//用户GUID
            $ret_upToken = $this->mysqlEdit('idt_user', $where_upToken, $id_upToken);
            if ($ret_upToken != '1') {
                _ERROR('000002', '登录失败,更新token出错');
            }

            //更新TOKEN
            $where_upCodeState['mik_state'] = 1;//更新TOKEN
            $id_upCodeState = " mik_mobile='" . $data['loginMobile'] . "' AND mik_key='" . $data['loginKey'] . "'";//手机验证码
            $ret_upCodeState = $this->mysqlEdit('idt_mobilekey', $where_upCodeState, $id_upCodeState);
            if ($ret_upCodeState != '1') {
                _ERROR('000002', '登录失败,更新验证码状态出错');
            }

            //获取用户
            $sql = "SELECT dba.u_id userid,dba.u_mobile mobile,dbb.cpy_id cpy_id,dbb.cpy_cname cpy_cname,dba.u_head headimg,dba.u_product_key productkey,dbb.cpy_validity validity,dba.u_name uname,dba.u_permissions permissions,dba.u_token token,dba.u_state u_state FROM idt_user dba LEFT JOIN idt_company dbb ON (dba.cpy_id=dbb.cpy_id) WHERE u_mobile='{$data['loginMobile']}'";
            $ret = $this->mysqlQuery($sql, "all");

            //验证冰结用户
            if ($ret[0]['u_state'] == 1) {
                _ERROR('000002', '登录失败,该用户已冰结');
            }

            //产品权限
            if ($ret[0]['productkey'] == 0 OR $ret[0]['productkey'] == null OR $ret[0]['productkey'] == "") {
                $productKey = 0;
            } else {
                $productKey = 1;
            }
            //返回用户信息
            $rs['headImg'] = $ret[0]['headimg']; //头像
            $rs['mobile'] = $ret[0]['mobile']; //用户手机
            $rs['companyID'] = $ret[0]['cpy_id']; //公司ID
            $rs['companyName'] = $ret[0]['cpy_cname']; //公司名称
            $rs['permissions'] = $ret[0]['permissions']; //用户身份 0游客 1企业用户 2企业管理员
            $rs['productKey'] = $productKey; //产品权限
            $rs['token'] = $upToken; //用户token
            $rs['uname'] = $ret[0]['uname']; //用户姓名
            $rs['userID'] = $ret[0]['userid']; //用户GUID
            $rs['validity'] = $ret[0]['validity']; //账号有效期
            _SUCCESS('000000', '登录成功', $rs);
        } else {
            _ERROR('000002', '登录失败');
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
        if ($ret_codeTime[0]['mik_key'] != "" OR $ret_codeTime[0]['mik_key'] != null) {
            _ERROR('000002', '发送失败,操作频繁,请稍后尝试');
        } else {
            //验证五分钟以内不产生新的验证码
            $sql_codeNews = "SELECT mik_key FROM idt_mobilekey WHERE mik_mobile='{$data['Mobile']}' AND mik_state='0' AND ROUND((UNIX_TIMESTAMP('{$uptimes}')-UNIX_TIMESTAMP(mik_cdate))/60)<=5 ORDER BY mik_cdate DESC LIMIT 1";
            $ret_codeNews = $this->mysqlQuery($sql_codeNews, "all");
            //生成短信验证码
            if ($ret_codeNews[0]['mik_key'] == null OR $ret_codeNews[0]['mik_key'] == null) {
                $data['Code'] = rand(100001, 999999);
            } else {
                $data['Code'] = $ret_codeNews[0]['mik_key'];
            }

            //发送验证码
            $where['mik_mobile'] = $data['Mobile'];//手机号码
            $where['mik_type'] = 0;//验证类型(0.登录 1.注册 2.找回密码)
            $where['mik_key'] = $data['Code'];//验证码
            $where['mik_cdate'] = $uptimes;//创建时间
            $ret_codeSend = $this->mysqlInsert('idt_mobilekey', $where);
            if ($ret_codeSend == '1') {
                //调用SMS,发送验证码
                $content = str_replace("(CODE)", $data['Code'], SMS_CONTENT);
                $phones = $data['Mobile'];
                $sms = Sms::instance()->sendSms($content, $phones);
                if ($sms == '发送成功') {
                    _SUCCESS('000000', '发送成功');
                } else {
                    _ERROR('000002', '发送失败,SMS错误');
                }
            } else {
                _ERROR('000002', '发送失败,数据异常');
            }
        }
    }

    //绑定产品KEY
    public function setProductKey($data)
    {
        //验证IRD账号是否正确
        $where_irdKey['mail'] = $data['account'];
        $where_irdKey['pwd'] = $data['password'];
        $ret_irdKey = $this->request()->_curlRADPost(IRD_SERVER_URL, ['v' => fnEncrypt(json_encode($where_irdKey), KEY)]);
        $ret_irdKey = json_decode($ret_irdKey, JSON_UNESCAPED_UNICODE);
        if ($ret_irdKey['iUserID'] == '-1') {
            _ERROR('000002', '绑定失败,账号密码不正确或用户不存在');
        }

        //查询用户是否已绑定其它账号
        $sql_keyNum = "SELECT COUNT(1) keyNum FROM idt_user WHERE u_product_key='{$ret_irdKey['iUserID']}'";
        $ret_keyNum = $this->mysqlQuery($sql_keyNum, "row");
        //绑定失败,该产品KEY已绑定其它账号
        if ($ret_keyNum[0] > 0) {
            _ERROR('000002', '绑定失败,该用户已绑定其它账号');
        };

        //查询产品Key
        $sql_productkey = "SELECT u_product_key FROM idt_user WHERE u_id='{$data['userID']}'";
        $ret_productkey = $this->mysqlQuery($sql_productkey, "all");
        if ($ret_productkey[0]['u_product_key'] != "" OR $ret_productkey[0]['u_product_key'] != null) {
            _ERROR('000002', '绑定失败,该产品KEY已绑定其它账号');
        }

        //绑定成功,更新产品KEY
        $where['u_product_key'] = $ret_irdKey['iUserID']; //产品Key
        $productkey = " u_id='" . $data['userID'] . "'";//用户GUID
        $ret_productkey = $this->mysqlEdit('idt_user', $where, $productkey);
        if ($ret_productkey == 1) {
            _SUCCESS('000000', '绑定成功');
        } else {
            _ERROR('000002', '绑定失败');
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
        $rs['headImg'] = "upload/head/" . $ret[0]['u_head']; //头像
        $rs['mobile'] = $ret[0]['u_mobile']; //手机
        $rs['position'] = $ret[0]['u_position']; //职位
        $rs['uname'] = $ret[0]['u_name']; //姓名
        _SUCCESS('000000', '获取成功', $rs);
    }

    public function _getUserInfoByToken($data)
    {
        $sql = "SELECT dbb.cpy_cname,dba.u_mail,dba.u_head,dba.u_mobile,dba.u_position,dba.u_name,dba.u_id FROM idt_user dba LEFT JOIN idt_company dbb ON (dba.cpy_id=dbb.cpy_id) WHERE dba.u_token='{$data['token']}'";
        $ret = $this->mysqlQuery($sql, "all");
        //返回用户信息
        $rs['company'] = $ret[0]['cpy_cname']; //公司
        $rs['companyEmail'] = $ret[0]['u_mail']; //公司邮箱
        $rs['headImg'] = "upload/head/" . $ret[0]['u_head']; //头像
        $rs['mobile'] = $ret[0]['u_mobile']; //手机
        $rs['position'] = $ret[0]['u_position']; //职位
        $rs['uname'] = $ret[0]['u_name']; //姓名
        $rs['uid'] = $ret[0]['u_id']; //姓名
        return $rs;
    }

    //修改用户资料
    public function editUserInfo($data)
    {
        //修改用户姓名
        if ($data['uname'] !== null) {
            $where['u_name'] = $data['uname'];
        } //处理NULL
        if ($data['uname'] === "") {
            $where['u_name'] = " ";
        } //处理空
        //修改用户职位
        if ($data['position'] !== null) {
            $where['u_position'] = $data['position'];
        } //处理NULL
        if ($data['position'] === "") {
            $where['u_position'] = " ";
        } //处理空
        //修改用户头像
        if ($data['headImg'] !== null) { //处理NULL
            //图片存储
            $imgName = $data['userID'] . '.png';//头像名称
            $imgPath = 'upload/head/' . $imgName;//头像路径
            $imgVal = base64_decode($data['headImg']);//头像格式化
            file_put_contents($imgPath, $imgVal);//返回的是字节数
            //保存头像
            $where['u_head'] = $imgName; //用户头像
        }
        if ($data['headImg'] === "") {
            $where['u_head'] = "head.png";
        } //处理空
        //修改用户
        $id = " u_id='" . $data['userID'] . "'";//用户GUID
        $ret = $this->mysqlEdit('idt_user', $where, $id);

        //返回响应结果
        if ($ret == '1') {
            _SUCCESS('000000', '修改成功');
        } else {
            _ERROR('000002', '修改失败');
        }
    }

    //冰结用户
    public function iceUser($data)
    {
        //当前时间
        $upTimes = date("Y-m-d H:i:s");

        //解冻用户
        $where_iceUser['u_state'] = 1; //用户状态(0正常 1冰结)
        $where_iceUser['u_edate'] = $upTimes; //最后登录时间
        $id_iceUser = " u_id='" . $data['uid'] . "'";//用户GUID
        $ret = $this->mysqlEdit('idt_user', $where_iceUser, $id_iceUser);

        //验证并返回响应结果
        if ($ret == 1) {
            _SUCCESS('000000', '冻结成功');
        } else {
            _ERROR('000002', '冻结失败');
        }
    }

    //解冰用户
    public function thawUser($data)
    {
        //当前时间
        $upTimes = date("Y-m-d H:i:s");

        //解冻用户
        $where_thawUser['u_state'] = 0; //用户状态(0正常 1冰结)
        $where_thawUser['u_edate'] = $upTimes; //最后登录时间
        $id_thawUser = " u_id='" . $data['uid'] . "'";//用户GUID
        $ret = $this->mysqlEdit('idt_user', $where_thawUser, $id_thawUser);

        //验证并返回响应结果
        if ($ret == 1) {
            _SUCCESS('000000', '解冻成功');
        } else {
            _ERROR('000002', '解冻失败');
        }
    }

    //获取用户List
    public function userList($data)
    {
        //查询初始化条件
        $data['orderByColumn'] == null ? $orderByColumn = 'permissions' : $orderByColumn = $data['orderByColumn']; //排序字段
        $data['orderByType'] == null ? $orderByType = 'DESC' : $orderByType = $data['orderByType']; //排序方式
        $data['pageSize'] == null ? $pageSize = '10' : $pageSize = $data['pageSize']; //查询数据
        $data['pageNo'] == null ? $pageNo = '0' : $pageNo = ($data['pageNo'] - 1) * $pageSize; //查询页数
        $data['keyword'] == null ? $keyword = '' : $keyword = " AND (dba.u_mobile LIKE '%" . $data['keyword'] . "%' OR dbb.cpy_cname LIKE '%" . $data['keyword'] . "%' OR dba.u_name LIKE '%" . $data['keyword'] . "%')"; //查询条件

        //获取当前用户所属公司ID
        $sql_companyID = "SELECT cpy_id FROM idt_user WHERE 1=1 AND u_id='{$data['userID']}'";
        $ret_companyID = $this->mysqlQuery($sql_companyID, "all");
        if ($ret_companyID[0]['cpy_id'] == 0 OR $ret_companyID[0]['cpy_id'] == null OR $ret_companyID[0]['cpy_id'] == "") {
            _ERROR('000002', '查询失败,非法用户');
        }

        //执行查询
        $sql = "SELECT dba.u_id,dba.u_head,dba.u_mobile mobile,dba.u_mail,dba.u_name,dba.u_permissions permissions,dba.u_state,dba.u_edate logindate " .
            "FROM idt_user dba " .
            "LEFT JOIN idt_company dbb ON (dba.cpy_id=dbb.cpy_id AND dba.cpy_id={$ret_companyID[0]['cpy_id']}) " .
            "WHERE 1=1 " .
            "AND dba.u_state=0 " .
            "AND dbb.cpy_state=0 " .
            "AND (dba.u_permissions=1 OR dba.u_permissions=2) " .
            "ORDER BY {$orderByColumn} {$orderByType} " .
            "LIMIT {$pageNo},{$pageSize}";
        $ret = $this->mysqlQuery($sql, "all");

        //执行总数
//        $sql_count = "SELECT COUNT(1) count_num " .
//            "FROM idt_user dba " .
//            "LEFT JOIN idt_company dbb ON (dba.cpy_id=dbb.cpy_id AND dba.cpy_id={$ret_companyID[0]['cpy_id']}) " .
//            "WHERE 1=1 " .
//            "AND dba.u_state=0 " .
//            "AND dbb.cpy_state=0 " .
//            "AND (dba.u_permissions=1 OR dba.u_permissions=2) ";
//        $ret_count = $this->mysqlQuery($sql_count, "all");

        $ret_count = $this->__countUsersByCompany($ret_companyID[0]['cpy_id']);
        //返回结果
        $rs = array();
        //返回参数-执行结果
        foreach ($ret as $a => $v) {
            $rs[$a]['userID'] = $v['u_id']; //用户GUID
            $rs[$a]['head'] = $v['u_head']; //头像
            $rs[$a]['mobile'] = $v['mobile']; //手机
            $rs[$a]['mail'] = $v['u_mail']; //邮箱
            $rs[$a]['name'] = $v['u_name']; //姓名
            $rs[$a]['permissions'] = (int)$v['permissions']; //用户身份
            $rs[$a]['state'] = (int)$v['u_state']; //用户状态
            $rs[$a]['loginDate'] = $v['logindate']; //最后登录时间
        }

        //返回参数-执行总数
        $rs['totalSize'] = $ret_count[0]['count_num'];

        //查询成功,返回响应结果
        _SUCCESS('000000', '查询成功', $rs);
    }

    /**
     * check user role
     *
     * @param $u_id
     *
     * @return array|string
     */
    public function checkUserRole($u_id)
    {
        if ($u_id === null) {
            return false;
        } else {
            $p = $this->__checkUserRole($u_id);
            if ($p === null) {
                return false;
            } else {
                return $p;
            }
        }
    }

    /**
     * check user state
     *
     * @param $u_id
     *
     * @return bool
     */
    public function checkUserState($u_id)
    {
        return $this->__checkUserState($u_id);
    }


    ######################################################################################
    ##################################                     ###############################
    #################################   PRIVATE METHODS   ################################
    ################################                     #################################
    ######################################################################################

    /**
     * check user role sql
     *
     * @param $u_id
     *
     * @return array|string
     */
    private function __checkUserRole($u_id)
    {
        $sql = "SELECT u_permissions FROM idt_user WHERE u_id='{$u_id}'";
        $ret = $this->mysqlQuery($sql, 'all');
        return $ret[0]['u_permissions'];
    }

    /**
     * check user state
     *
     * @param $u_id
     *
     * @return bool
     */
    private function __checkUserState($u_id)
    {
        $sql = "SELECT u_state FROM idt_user WHERE u_id = '{$u_id}'";
        $ret = $this->mysqlQuery($sql, 'all');
        return $this->__checkState($ret[0]['u_state']);
    }

    /**
     * get user of company
     *
     * @param $u_id
     *
     * @return array|string
     */
    private function __getUserOfCompany($u_id)
    {
        $sql = "SELECT cyp_id FROM idt_user WHERE u_id='{$u_id}'";
        return $this->mysqlQuery($sql, 'all');
    }

    private function __checkUserCompanyStateByUserID($u_id)
    {
        $retCompany = $this->__getUserOfCompany($u_id);
        if (!empty($retCompany[0]['cpy_id'])) {
            return $this->__checkUserCompanyStateByCompanyID($retCompany[0]['cpy_id']);
        } else {
            return false;
        }
    }

    /**
     * check company state by company id
     *
     * @param $cpy_id
     *
     * @return bool
     */
    private function __checkUserCompanyStateByCompanyID($cpy_id)
    {
        $sql = "SELECT cpy_state FROM idt_company WHERE 1=1 AND cpy_id='{$cpy_id}'";
        $ret = $this->mysqlQuery($sql, 'all');
        return $this->__checkState($ret[0]['cyp_state']);
    }

    /**
     * count users by company , no include disable user and company
     *
     * @param $cpy_id
     *
     * @return array|string
     */
    private function __countUsersByCompany($cpy_id)
    {
        $sql_count = "SELECT COUNT(1) count_num " .
            "FROM idt_user dba " .
            "LEFT JOIN idt_company dbb ON (dba.cpy_id=dbb.cpy_id AND dba.cpy_id='{$cpy_id}') " .
            "WHERE 1=1 " .
            "AND dba.u_state=0 " .
            "AND dbb.cpy_state=0 " .
            "AND (dba.u_permissions=1 OR dba.u_permissions=2) ";
        return $this->mysqlQuery($sql_count, "all");
    }

    /**
     * check state
     *
     * @param $d
     *
     * @return bool
     */
    private function __checkState($d)
    {
        if ($d !== null OR isset($d)) {
            return $d != 0;
        } else {
            return false;
        }
    }
}