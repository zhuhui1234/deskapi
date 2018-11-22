<?php

/**
 * Created by iResearch
 * User: JOSON
 * modify: Lane391
 * Date: 16-08-23
 * Time: 下午16:26
 * Email:joson@iresearch.com.cn
 * FileName:controller.user.php
 * 描述:
 */
class UserModel extends AgentModel
{

    function __construct($className)
    {
        parent::__construct($className);
    }

    /**
     * app login for wechat
     * @param $uuid
     * @return array
     */
    public function appLogin($uuid)
    {
        if (empty($uuid)) {

            return ['code' => '500', 'state' => false, 'msg' => 'uuid不能为空'];

        } else {
            $sql = "SELECT count(u_mobile) AS cu FROM idt_user WHERE 1=1 AND u_wxunid='{$uuid}' AND u_state='0' ";

            $ret = $this->mysqlQuery($sql, 'all');

            if ((int)$ret[0]['cu'] > 0) {
                $mobileSQL = "SELECT u_mobile AS cu FROM idt_user WHERE 1=1 AND u_wxunid='{$uuid}' AND u_state='0' ";
                $mobile = $this->mysqlQuery($mobileSQL, 'all');
                return ['code' => '200', 'state' => true, 'msg' => '验证成功', 'mobile' => $mobile[0]];
            } else {
                return ['code' => '404', 'state' => false, 'msg' => '微信号不存在'];
            }

        }
    }

    /**
     * get user info by ird id
     * @param $ird
     * @return array|bool|string
     */
    public function getUserInfoByIRD($ird)
    {
        if (!empty($ird)) {
            $userInfo = $this->__getUserInfo($ird);
            return empty($userInfo) ? false : $userInfo;
        } else {
            return false;
        }
    }


    /**
     * app login for mobile
     *
     * @param $mobile
     * @return array
     */
    public function appMobileLogin($mobile)
    {
        if (empty($mobile)) {

            return ['code' => '500', 'state' => false, 'msg' => 'uuid不能为空'];

        } else {
            $sql = "SELECT count(u_wxunid) AS cu FROM idt_user WHERE 1=1 AND u_mobile='{$mobile}' AND u_state='0' ";

            $ret = $this->mysqlQuery($sql, 'all');

            if ((int)$ret[0]['cu'] > 0) {
                $wxSQL = "SELECT u_wxunid AS cu FROM idt_user WHERE 1=1 AND u_mobile='{$mobile}' AND u_state='0' ";
                $u_wxunid = $this->mysqlQuery($wxSQL, 'all');
                return ['code' => '200', 'state' => true, 'msg' => '验证成功', 'uuid' => $u_wxunid[0]];
            } else {
                return ['code' => '404', 'state' => false, 'msg' => '手机号不存在'];
            }

        }
    }

    /**
     * @param $u_mobile
     * @param $uuid
     * @return array|bool|int|string
     */
    public function appBindAccount($u_mobile, $uuid)
    {
        if (empty($u_mobile)) {
            return ['code' => '500', 'state' => false, 'msg' => '参数不能为空'];
        }

        if (empty($uuid)) {
            return ['code' => '500', 'state' => false, 'msg' => '参数不能为空'];
        }

        $hasMWSQL = "SELECT COUNT(*) as cc FROM idt_user WHERE u_wxunid='{$uuid}' AND u_mobile='{$u_mobile}'";

        $hasMobileSQL = "SELECT u_id FROM idt_user WHERE u_wxunid=NULL AND u_mobile='{$u_mobile}'";

        $ret = $this->mysqlQuery($hasMWSQL, 'all');
        write_to_log('has mwsql :' . json_encode($ret), '_app');
        if ((int)$ret[0]['cc'] == 0) {
            //当前时间
            $upTimes = date("Y-m-d H:i:s");
            //创建TOKEN
            $upToken = md5(rand(1000000001, 9999999999));
            $insertRet = $this->mysqlInsert('idt_user', [
                'u_id' => getGUID(),
                'u_mobile' => $u_mobile,
                'u_wxunid' => $uuid,
                'u_permissions' => 0,
                'u_token' => $upToken,
                'u_cdate' => $upTimes,
                'u_edate' => $upTimes
            ]);

            if ($insertRet) {
                return ['code' => '200', 'state' => true, 'msg' => '绑定成功'];
            } else {
                return ['code' => '500', 'state' => false, 'msg' => '绑定失败'];
            }

        } else {
            $mRet = $this->mysqlQuery($hasMobileSQL, 'all');

            write_to_log('has mobile sql' . json_encode($mRet), '_app');

            if (count($mRet) > 0) {

                if (!empty($mRet[0]['u_id'])) {
                    $updateWx = $this->mysqlEdit('idt_user', ['u_wxunid' => $uuid], "u_id='{$mRet[0]['u_id']}'");
                    if ($updateWx) {
                        return ['code' => '200', 'state' => true, 'msg' => '绑定成功'];
                    } else {
                        return ['code' => '500', 'state' => false, 'msg' => '绑定失败'];
                    }
                } else {
                    return ['code' => '500', 'state' => false, 'msg' => '绑定失败,查询不到用户ID'];
                }

            } else {
                return ['code' => '500', 'state' => false, 'msg' => '想要绑定的微信号，库里已经存在!'];
            }

        }

    }


    /**
     * login // 研究院
     *
     * @param $data
     *      状态:
     *      000010 : 您的帐号当前为冻结状态
     *      000004 : 登录失败,更新微信名称失败
     *      000002 : 登录失败,更新验证码状态失败
     *      000005 : 登录失败,更新token失败
     *      000001 : 未知登录类型
     *      000003 : 手机号已存在
     */
    public function loginApp($data)
    {
        if (empty($data['wxOpenid'])) {
            $data['wxOpenid'] = null;
        }


        if (empty($data['appName'])) {
            $data['appName'] = 'app_null';
        }
        //当前时间
        $upTimes = date("Y-m-d H:i:s");
        //创建TOKEN
        $upToken = md5(rand(1000000001, 9999999999));

        $logsModel = Model::instance('logs');
        //登录方式
        if ($data['LoginType'] === 'mobile') {
            //游客注册

            $ret_checkMUser = $this->checkMobile($data['loginMobile']);

            if ($ret_checkMUser) {
                //创建游客

                $ret_addUser = $this->__addGuest($data, $upToken, $upTimes, 'mobile');

                if ($ret_addUser != '1') {
                    _ERROR('000002', '登录失败,创建游客失败');
                }
            }

            $sql = "SELECT dba.u_id userid,dba.u_mobile mobile,dba.u_mail email,dbc.cpy_id cpy_id,dbc.cpy_cname cpy_cname,dba.dev_id,
                    dba.u_head headimg,dba.u_product_key productkey,
                    dbc.cpy_validity validity,dba.u_name uname,dba.u_permissions permissions,dba.u_token token,
                    dba.u_state u_state , dba.u_department department , dba.u_wxname as wechat
                    FROM idt_user dba 
                    LEFT JOIN idt_company dbc ON (dba.cpy_id=dbc.cpy_id) 
                    WHERE dba.u_mobile='{$data['loginMobile']}'";

        } else if ($data['LoginType'] === 'weixin') {
            $sql = "SELECT dba.u_id userid,dba.u_mobile mobile,dba.u_mail email,dbb.cpy_id cpy_id,dbb.cpy_cname cpy_cname,dba.dev_id,
                    dba.u_head headimg,dba.u_product_key productkey,dbb.cpy_validity validity,dba.u_name uname,
                    dba.u_permissions permissions,dba.u_token token,
                    dba.u_state u_state, dba.u_department department , dba.u_wxname as wechat
                    FROM idt_user dba 
                    LEFT JOIN idt_company dbb ON (dba.cpy_id=dbb.cpy_id) 
                    WHERE dba.u_wxunid='{$data['wxunid']}' ";
        } else {
            //登录失败,参数错误
            _ERROR('000001', '未知登录类型');
        }

        //登录
        if (isset($sql)) {
            $ret = $this->mysqlQuery($sql, "all");
        } else {
            if (DEBUG_LOG) {
                write_to_log('login function no sql execute');
            }
        }


        if (isset($ret)) {


            //验证冰结用户
            if ($ret[0]['u_state'] == 1) {
                _ERROR('000010', '您的帐号当前为冻结状态');
            }

            //验证登录&USER GUID不为空
            if (count($ret) > 0 AND ($ret[0]['userid'] != null OR $ret[0]['userid'] != "")) {
                //更新TOKEN
                $this->redisHere(VERSION . '_' . $ret[0]['userid'] . '_ird', true);
                $where_upToken['u_token'] = $upToken;//更新TOKEN
                $where_upToken['u_edate'] = $upTimes;//更新登录时间
                $id_upToken = " u_id='" . $ret[0]['userid'] . "'";//用户GUID
                $ret_upToken = $this->mysqlEdit('idt_user', $where_upToken, $id_upToken);

                //验证登录状态
                if ($ret_upToken == '1') {


                    //返回用户信息
                    $rs = [
                        'headImg' => $ret[0]['headimg'], //avatar
                        'mobile' => $ret[0]['mobile'],
                        'companyID' => $ret[0]['cpy_id'],
                        'companyName' => $ret[0]['cpy_cname'],
                        'permissions' => $ret[0]['permissions'], //用户身份 0游客 1企业用户 2企业管理员
                        'dev_id' => $ret[0]['dev_id'],
                        'token' => $upToken,
                        'wechat' => $ret[0]['wechat'],
                        'uname' => $ret[0]['uname'],
                        'userID' => $ret[0]['userid'],
                        'validity' => $ret[0]['validity'] //账号有效期
                    ];


                    $log_db = $logsModel->pushLog([
                        'user' => $rs['userID'],
                        'companyID' => $rs['companyID'],
                        'type' => 'irv用户日志',
                        'action' => '睿见登入',
                        'resource' => 'irv登入',
                        'status' => '20000',
                        'level' => '2',
                        'log_ip' => getIp()
                    ]);

                    if (!$log_db) {
                        write_to_log('log error', '_log');
                    }

                    _SUCCESS('000000', '登录成功', $rs);

                } else {
                    _ERROR('000005', '登录失败,更新token失败');
                }
            } else {

                _ERROR('000002', '登录失败');
            }
        }
    }


    /**
     * login
     *
     * @param $data
     *      状态:
     *      000010 : 您的帐号当前为冻结状态
     *      000004 : 登录失败,更新微信名称失败
     *      000002 : 登录失败,更新验证码状态失败
     *      000005 : 登录失败,更新token失败
     *      000001 : 未知登录类型
     *      000003 : 手机号已存在
     */
    public function login($data)
    {
        //当前时间
        $upTimes = date("Y-m-d H:i:s");
        //创建TOKEN
        $upToken = md5(rand(1000000001, 9999999999));
        $permission_model = Model::instance('Permissions');

        $logsModel = Model::instance('logs');
        //登录方式
        if ($data['LoginType'] === 'mobile') {
            //游客注册

            $ret_checkMUser = $this->checkMobile($data['loginMobile']);

            if ($ret_checkMUser) {
                //创建游客
                if (!empty($data['ird_guid'])) {
                    //ird create user
                    $ret_addUser = $this->__addUserFromIrd($data, $upToken, $upTimes, 'mobile');
                } else {
                    $ret_addUser = $this->__addGuest($data, $upToken, $upTimes, 'mobile');
                }

                if ($ret_addUser != '1') {
                    _ERROR('000002', '登录失败,创建游客失败');
                }
            }

            $sql = "SELECT dba.u_id userid,dba.u_mobile mobile,dba.u_mail email,dbc.cpy_id cpy_id,dbc.cpy_cname cpy_cname,dba.dev_id,
                    dba.u_head headimg,dba.u_product_key productkey,
                    dbc.cpy_validity validity,dba.u_name uname,dba.u_permissions permissions,dba.u_token token,
                    dba.u_state u_state , dba.u_department department , dba.u_wxname as wechat
                    FROM idt_user dba 
                    LEFT JOIN idt_mobilekey dbb ON(dba.u_mobile=dbb.mik_mobile) 
                    LEFT JOIN idt_company dbc ON (dba.cpy_id=dbc.cpy_id) 
                    WHERE dba.u_mobile='{$data['loginMobile']}' AND dbb.mik_key='{$data['LoginKey']}' 
                    AND dbb.mik_state=0 AND ROUND((UNIX_TIMESTAMP('{$upTimes}')-UNIX_TIMESTAMP(mik_cdate))/60)<=5";

        } else if ($data['LoginType'] === 'weixin') {
            $sql = "SELECT dba.u_id userid,dba.u_mobile mobile,dba.u_mail email,dbb.cpy_id cpy_id,dbb.cpy_cname cpy_cname,dba.dev_id,
                    dba.u_head headimg,dba.u_product_key productkey,dbb.cpy_validity validity,dba.u_name uname,
                    dba.u_permissions permissions,dba.u_token token,
                    dba.u_state u_state, dba.u_department department , dba.u_wxname as wechat
                    FROM idt_user dba 
                    LEFT JOIN idt_company dbb ON (dba.cpy_id=dbb.cpy_id) 
                    WHERE dba.u_wxopid='{$data['Account']}' AND dba.u_wxunid='{$data['LoginKey']}'";
        } else {
            //登录失败,参数错误
            _ERROR('000001', '未知登录类型');
        }

        //登录
        if (isset($sql)) {
            $ret = $this->mysqlQuery($sql, "all");
        } else {
            if (DEBUG_LOG) {
                write_to_log('login function no sql execute');
            }
        }


        if (isset($ret)) {
            $companyState = $this->__checkUserCompanyStateByUserID($ret[0]['userid']);

            if ($ret[0]['userid'] == null AND $companyState) {
                _ERROR('000010', '您的帐号当前为冻结状态');

            }

            //验证冰结用户
            if ($ret[0]['u_state'] == 1) {
                _ERROR('000010', '您的帐号当前为冻结状态');
            }

            //验证登录&USER GUID不为空
            if (count($ret) > 0 AND ($ret[0]['userid'] != null OR $ret[0]['userid'] != "")) {
                //更新TOKEN
                $this->redisHere(VERSION . '_' . $ret[0]['userid'] . '_ird', true);
                $where_upToken['u_token'] = $upToken;//更新TOKEN
                $where_upToken['u_edate'] = $upTimes;//更新登录时间
                $id_upToken = " u_id='" . $ret[0]['userid'] . "'";//用户GUID
                $ret_upToken = $this->mysqlEdit('idt_user', $where_upToken, $id_upToken);

                //验证登录状态
                if ($ret_upToken == '1') {
                    //更新微信名称
                    if ($data['LoginType'] === 'weixin') {
                        $where_upwxName['u_wxname'] = urlencode($data['wxName']);//微信名称
                        $id_upwxName = " u_wxopid='" . $data['loginMobile'] . "' AND u_wxunid='" . $data['LoginKey'] . "'";//微信帐号
                        $ret_upwxName = $this->mysqlEdit('idt_user', $where_upwxName, $id_upwxName);
                        if ($ret_upwxName != '1') {
                            _ERROR('000004', '登录失败,更新微信名称失败');
                        }
                    }

                    //更新验证码状态
                    if ($data['LoginType'] === 'mobile') {
                        $where_upResCode['mik_state'] = 1; //验证码状态
                        $id_upResCode = " mik_mobile='" . $data['loginMobile'] . "' AND mik_key='" . $data['LoginKey'] . "'";//用户帐号
                        $ret_upResCode = $this->mysqlEdit('idt_mobilekey', $where_upResCode, $id_upResCode);
                        if ($ret_upResCode != '1') {
                            _ERROR('000002', '登录失败,更新验证码状态失败');
                        }
                    }

                    //产品权限
                    if ($ret[0]['productkey'] == 0 OR $ret[0]['productkey'] == null OR $ret[0]['productkey'] == "") {
                        $productKey = 0;
                        $ird_ua_id = null;
                    } else {
                        $productKey = 1;
                        $ird_ua_id = $ret[0]['productkey'];
                    }

                    //返回用户信息
                    $rs = [
                        'headImg' => $ret[0]['headimg'], //avatar
                        'mobile' => $ret[0]['mobile'],
                        'companyID' => $ret[0]['cpy_id'],
                        'companyName' => $ret[0]['cpy_cname'],
                        'permissions' => $ret[0]['permissions'], //用户身份 0游客 1企业用户 2企业管理员
                        'productKey' => $productKey, //ird_user_id
                        'dev_id' => $ret[0]['dev_id'],
                        'token' => $upToken,
                        'wechat' => $ret[0]['wechat'],
                        'uname' => $ret[0]['uname'],
                        'userID' => $ret[0]['userid'],
                        'ird_user_id' => $ird_ua_id,
                        'validity' => $ret[0]['validity'] //账号有效期
                    ];

                    //judge binding
                    if ($ret[0]['permissions'] == 0) {
                        //guest if the data has ird guid
                        if (!empty($data['ird_user'])) {
                            if ((int)$data['ird_user']['iUserID'] > 0) {
                                //nobody binding this id
                                $cpy_id = $this->__getCpyFromIRD($data['ird_user']['CompanyID']);
                                if ($cpy_id) {
                                    $binding_ird = $this->__bindingIRD($ret[0]['userid'], $data['ird_user']);
                                    if ($binding_ird) {
                                        $change_member = $this->__changeToMember($ret[0]['userid'], $cpy_id['cpy_id']);
                                        $ret[0]['cpy_id'] = $cpy_id['cpy_id'];
                                        if ($change_member) {
                                            $rs['permissions'] = 1;
                                            $rs['productKey'] = 1;
                                            $rs['ird_user_id'] = $data['ird_user']['iUserID'];
                                            $rs['companyName'] = $cpy_id['cpy_cname'];
                                            $rs['dev_id'] = $cpy_id['cpy_id'];
                                            $rs['ird'] = 'add permission';
                                            $rs['bind_state'] = $permission_model->addPermission($data['ird_user']['pplist'], $ret[0], $data['ird_user']['iUserID']);
                                        }
                                    }

                                }

                            } else {
                                write_to_log(json_encode($data['ird_user']), '_from_ird');
                                write_to_log('iuser id 非法', '_from_ird');
                            }

                        }
                    } elseif ($ret[0]['productkey'] == null) {
                        //guest if the data has ird guid
                        if (!empty($data['ird_user'])) {
                            if ((int)$data['ird_user']['iUserID'] > 0) {
                                //nobody binding this id
                                $cpy_id = $this->__getCpyFromIRD($data['ird_user']['CompanyID']);
                                if ($cpy_id['cpy_id'] != $ret[0]['cpy_id']) {
                                    $ird_diff = [
                                        'ird_user_id' => $data['ird_user']['iUserID'],
                                        'idt_user_id' => $ret[0]['userid'],
                                        'idt_old_cpy_id' => $ret[0]['cpy_id'],
                                        'idt_new_cpy_id' => $cpy_id['cpy_id'],
                                        'ird_email' => $data['ird_user']['UserName'],
                                        'idt_email' => $ret[0]['email'],
                                        'cdate' => $upTimes
                                    ];
                                    $this->mysqlInsert('ird_diff_user', $ird_diff);
                                    $sql = "update idt_licence set u_id = null where u_id = '{$ret[0]['userid']}'";
                                    $this->mysqlQuery($sql, "all");
                                    $update_data = ['cpy_id' => $cpy_id['cpy_id']];
                                    write_to_log('CHANGE COMPANY ' . json_encode($update_data), '_from_ird');
                                    $this->mysqlEdit('idt_user', $update_data, "u_id='{$ret[0]['userid']}'");
                                    $ret[0]['cpy_id'] = $cpy_id['cpy_id'];
                                }
                                if ($cpy_id) {
                                    $binding_ird = $this->__bindingIRD($ret[0]['userid'], $data['ird_user']);
                                    if ($binding_ird) {
                                        $rs['permissions'] = $ret[0]['permissions'];
                                        $rs['productKey'] = 1;
                                        $rs['ird_user_id'] = $data['ird_user']['iUserID'];
                                        $rs['companyName'] = $cpy_id['cpy_cname'];
                                        $rs['dev_id'] = $cpy_id['cpy_id'];
                                        $rs['ird'] = 'add permission';
                                        $rs['bind_state'] = $permission_model->addPermission($data['ird_user']['pplist'], $ret[0], $data['ird_user']['iUserID']);
                                    }

                                }

                            } else {
                                write_to_log(json_encode($data['ird_user']), '_from_ird');
                                write_to_log('iuser id 非法', '_from_ird');
                            }

                        }
                    }
                    write_to_log(json_encode($data), '_data');
                    if ($this->__checkBind($ret[0]['userid'], $data['userIP'])) {
                        _SUCCESS('000000', '登录成功', $rs);
                    } else {
                        _ERROR('000006', '登录失败,您所在的区域不允许使用产品');
                    }

                } else {
                    _ERROR('000005', '登录失败,更新token失败');
                }
            } else {
                //for JAPANESE Values Inc.
                $sqlb = "SELECT *
                        FROM idt_user WHERE u_mobile='{$data['loginMobile']}' and u_auth_type='{$data['LoginKey']}'";

                $sp_ret = $this->mysqlQuery($sqlb, 'all');

                write_to_log('sp sql: ' . $sqlb, '_sp');
                write_to_log('sp_ret' . json_encode($sp_ret), '_sp');

                if (!empty($sp_ret)) {
                    $this->redisHere(VERSION . '_' . $ret[0]['userid'] . '_ird', true);
                    $where_upToken['u_token'] = $upToken;//更新TOKEN
                    $where_upToken['u_edate'] = $upTimes;//更新登录时间
                    $id_upToken = " u_id='" . $sp_ret[0]['u_id'] . "'";//用户GUID
                    $ret_upToken = $this->mysqlEdit('idt_user', $where_upToken, $id_upToken);
                    if ($ret_upToken == '1') {
                        $rs = [
                            'headImg' => $sp_ret[0]['headimg'], //avatar
                            'mobile' => $sp_ret[0]['u_mobile'],
                            'companyID' => $sp_ret[0]['cpy_id'],
                            'permissions' => $sp_ret[0]['u_permissions'], //用户身份 0游客 1企业用户 2企业管理员
                            'productKey' => $sp_ret[0]['u_product_key'], //ird_user_id
                            'dev_id' => $sp_ret[0]['dev_id'],
                            'token' => $upToken,
                            'uname' => $sp_ret[0]['u_name'],
                            'userID' => $sp_ret[0]['u_id'],
                            'department' => $sp_ret[0]['u_department'],
                            'ird_user_id' => $sp_ret[0]['u_product_key'],
                        ];

                        $logsModel->pushLog([
                            'user' => $sp_ret[0]['u_id'],
                            'companyID' => $sp_ret[0]['cpy_id'],
                            'type' => 'irv用户日志',
                            'resource' => 'irv日本用户登入',
                            'status' => '20000',
                            'level' => '2'
                        ]);

                        _SUCCESS('000000', '登录成功', $rs);
                    }
                }

                _ERROR('000002', '登录失败,账号不存在或验证码失效');
            }
        }
    }

    public function b_login($data)
    {

        //当前时间
        $upTimes = date("Y-m-d H:i:s");
        //创建TOKEN
        $upToken = md5(rand(1000000001, 9999999999));
        $permission_model = Model::instance('Permissions');

        $logsModel = Model::instance('logs');

        switch ($data['LoginType']) {
            case 'mobile':
                $ret_checkMUser = $this->checkMobile($data['loginMobile']);

                if ($ret_checkMUser) {
                    //创建游客
                    if (!empty($data['ird_guid'])) {
                        //ird create user
                        $ret_addUser = $this->__addUserFromIrd($data, $upToken, $upTimes, 'mobile');
                    } else {
                        $ret_addUser = $this->__addGuest($data, $upToken, $upTimes, 'mobile');
                    }

                    if ($ret_addUser != '1') {
                        _ERROR('000002', '登录失败,创建游客失败');
                    }
                }

                $sql = "SELECT dba.u_id userid,dba.u_mobile mobile,dba.u_mail email,dbc.cpy_id cpy_id,dbc.cpy_cname cpy_cname,dba.dev_id,
                    dba.u_head headimg,dba.u_product_key productkey,
                    dbc.cpy_validity validity,dba.u_name uname,dba.u_permissions permissions,dba.u_token token,
                    dba.u_state u_state , dba.u_department department , dba.u_wxname as wechat
                    FROM idt_user dba 
                    LEFT JOIN idt_mobilekey dbb ON(dba.u_mobile=dbb.mik_mobile) 
                    LEFT JOIN idt_company dbc ON (dba.cpy_id=dbc.cpy_id) 
                    WHERE dba.u_mobile='{$data['loginMobile']}' AND dbb.mik_key='{$data['LoginKey']}' 
                    AND dbb.mik_state=0 AND ROUND((UNIX_TIMESTAMP('{$upTimes}')-UNIX_TIMESTAMP(mik_cdate))/60)<=5";
                break;
            case 'weixin':
                $sql = "SELECT dba.u_id userid,dba.u_mobile mobile,dba.u_mail email,dbb.cpy_id cpy_id,dbb.cpy_cname cpy_cname,dba.dev_id,
                    dba.u_head headimg,dba.u_product_key productkey,dbb.cpy_validity validity,dba.u_name uname,
                    dba.u_permissions permissions,dba.u_token token,
                    dba.u_state u_state, dba.u_department department , dba.u_wxname as wechat
                    FROM idt_user dba 
                    LEFT JOIN idt_company dbb ON (dba.cpy_id=dbb.cpy_id) 
                    WHERE dba.u_wxopid='{$data['Account']}' AND dba.u_wxunid='{$data['LoginKey']}'";
                break;
            case 'mail':

                $mail_sql = "select u_mobile as mobile from idt_user where u_mail = '{$data['loginMail']}'";
                $get_mobile = $this->mysqlQuery($mail_sql, 'all');
                if (!empty($get_mobile) and !empty($get_mobile[0]['mobile'])) {
                    $data['loginMobile'] = $get_mobile[0]['mobile'];
                }

                $sql = "SELECT dba.u_id userid,dba.u_mobile mobile,dba.u_mail email,dbc.cpy_id cpy_id,dbc.cpy_cname cpy_cname,dba.dev_id,
                    dba.u_head headimg,dba.u_product_key productkey,
                    dbc.cpy_validity validity,dba.u_name uname,dba.u_permissions permissions,dba.u_token token,
                    dba.u_state u_state , dba.u_department department , dba.u_wxname as wechat
                    FROM idt_user dba 
                    LEFT JOIN idt_mobilekey dbb ON(dba.u_mobile=dbb.mik_mobile) 
                    LEFT JOIN idt_company dbc ON (dba.cpy_id=dbc.cpy_id) 
                    WHERE dba.u_mail='{$data['loginMail']}' AND dbb.mik_key='{$data['LoginKey']}' 
                    AND dbb.mik_state=0 AND ROUND((UNIX_TIMESTAMP('{$upTimes}')-UNIX_TIMESTAMP(mik_cdate))/60)<=5";

                break;

            case 'sp_mail':
                $mail_sql = "select u_mobile as mobile from idt_user where u_mail = '{$data['loginMail']}'";
                $get_mobile = $this->mysqlQuery($mail_sql, 'all');
                if (!empty($get_mobile) and !empty($get_mobile[0]['mobile'])) {
                    $data['loginMobile'] = $get_mobile[0]['mobile'];
                }

                $sql = "SELECT dba.u_id userid,dba.u_mobile mobile,dba.u_mail email,dbc.cpy_id cpy_id,dbc.cpy_cname cpy_cname,dba.dev_id,
                    dba.u_head headimg,dba.u_product_key productkey,
                    dbc.cpy_validity validity,dba.u_name uname,dba.u_permissions permissions,dba.u_token token,
                    dba.u_state u_state , dba.u_department department , dba.u_wxname as wechat
                    FROM idt_user dba 
                    LEFT JOIN idt_mobilekey dbb ON(dba.u_mobile=dbb.mik_mobile) 
                    LEFT JOIN idt_company dbc ON (dba.cpy_id=dbc.cpy_id) 
                    WHERE dba.u_mail='{$data['loginMail']}' AND dbb.mik_key='{$data['LoginKey']}' 
                    AND dbb.mik_state=0 AND ROUND((UNIX_TIMESTAMP('{$upTimes}')-UNIX_TIMESTAMP(mik_cdate))/60)<=5";
                break;

            default:
                _ERROR('000001', '未知登录类型');
                break;
        }


        //登录
        if (isset($sql)) {
            $ret = $this->mysqlQuery($sql, "all");
        } else {
            if (DEBUG_LOG) {
                write_to_log('login function no sql execute');
            }
        }


        if (isset($ret)) {
            $companyState = $this->__checkUserCompanyStateByUserID($ret[0]['userid']);

            if ($ret[0]['userid'] == null AND $companyState) {
                _ERROR('000010', '您的帐号当前为冻结状态');

            }

            //验证冰结用户
            if ($ret[0]['u_state'] == 1) {
                _ERROR('000010', '您的帐号当前为冻结状态');
            }

            //验证登录&USER GUID不为空
            if (count($ret) > 0 AND ($ret[0]['userid'] != null OR $ret[0]['userid'] != "")) {
                //更新TOKEN
                $this->redisHere(VERSION . '_' . $ret[0]['userid'] . '_ird', true);
                $where_upToken['u_token'] = $upToken;//更新TOKEN
                $where_upToken['u_edate'] = $upTimes;//更新登录时间
                $id_upToken = " u_id='" . $ret[0]['userid'] . "'";//用户GUID
                $ret_upToken = $this->mysqlEdit('idt_user', $where_upToken, $id_upToken);

                //验证登录状态
                if ($ret_upToken == '1') {
                    //更新微信名称
                    if ($data['LoginType'] === 'weixin') {
                        $where_upwxName['u_wxname'] = urlencode($data['wxName']);//微信名称
                        $id_upwxName = " u_wxopid='" . $data['loginMobile'] . "' AND u_wxunid='" . $data['LoginKey'] . "'";//微信帐号
                        $ret_upwxName = $this->mysqlEdit('idt_user', $where_upwxName, $id_upwxName);
                        if ($ret_upwxName != '1') {
                            _ERROR('000004', '登录失败,更新微信名称失败');
                        }
                    }

                    //更新验证码状态
                    if ($data['LoginType'] === 'mobile') {
                        $where_upResCode['mik_state'] = 1; //验证码状态
                        $id_upResCode = " mik_mobile='" . $data['loginMobile'] . "' AND mik_key='" . $data['LoginKey'] . "'";//用户帐号

                        $ret_upResCode = $this->mysqlEdit('idt_mobilekey', $where_upResCode, $id_upResCode);

                        if ($ret_upResCode != '1') {
                            _ERROR('000002', '登录失败,更新验证码状态失败');
                        }
                    }

                    if ($data['LoginType'] == 'mail') {
                        $where_upResCode['mik_state'] = 1; //验证码状态
                        $id_upResCode = " mik_mobile='" . $data['loginMobile'] . "' AND mik_key='" . $data['LoginKey'] . "'";//用户帐号

                        $ret_upResCode = $this->mysqlEdit('idt_mobilekey', $where_upResCode, $id_upResCode);

                        if ($ret_upResCode != '1') {
                            _ERROR('000002', '登录失败,更新验证码状态失败');
                        }
                    }


                    //产品权限
                    if ($ret[0]['productkey'] == 0 OR $ret[0]['productkey'] == null OR $ret[0]['productkey'] == "") {
                        $productKey = 0;
                        $ird_ua_id = null;
                    } else {
                        $productKey = 1;
                        $ird_ua_id = $ret[0]['productkey'];
                    }

                    //返回用户信息
                    $rs = [
                        'headImg' => $ret[0]['headimg'], //avatar
                        'mobile' => $ret[0]['mobile'],
                        'companyID' => $ret[0]['cpy_id'],
                        'companyName' => $ret[0]['cpy_cname'],
                        'permissions' => $ret[0]['permissions'], //用户身份 0游客 1企业用户 2企业管理员
                        'productKey' => $productKey, //ird_user_id
                        'dev_id' => $ret[0]['dev_id'],
                        'token' => $upToken,
                        'wechat' => $ret[0]['wechat'],
                        'uname' => $ret[0]['uname'],
                        'userID' => $ret[0]['userid'],
                        'ird_user_id' => $ird_ua_id,
                        'validity' => $ret[0]['validity'] //账号有效期
                    ];

                    //judge binding
                    if ($ret[0]['permissions'] == 0) {
                        //guest if the data has ird guid
                        if (!empty($data['ird_user'])) {
                            if ((int)$data['ird_user']['iUserID'] > 0) {
                                //nobody binding this id
                                $cpy_id = $this->__getCpyFromIRD($data['ird_user']['CompanyID']);
                                if ($cpy_id) {
                                    $binding_ird = $this->__bindingIRD($ret[0]['userid'], $data['ird_user']);
                                    if ($binding_ird) {
                                        $change_member = $this->__changeToMember($ret[0]['userid'], $cpy_id['cpy_id']);
                                        $ret[0]['cpy_id'] = $cpy_id['cpy_id'];
                                        if ($change_member) {
                                            $rs['permissions'] = 1;
                                            $rs['productKey'] = 1;
                                            $rs['ird_user_id'] = $data['ird_user']['iUserID'];
                                            $rs['companyName'] = $cpy_id['cpy_cname'];
                                            $rs['dev_id'] = $cpy_id['cpy_id'];
                                            $rs['ird'] = 'add permission';
                                            $rs['bind_state'] = $permission_model->addPermission($data['ird_user']['pplist'], $ret[0], $data['ird_user']['iUserID']);
                                        }
                                    }

                                }

                            } else {
                                write_to_log(json_encode($data['ird_user']), '_from_ird');
                                write_to_log('iuser id 非法', '_from_ird');
                            }

                        }
                    } elseif ($ret[0]['productkey'] == null) {
                        //guest if the data has ird guid
                        if (!empty($data['ird_user'])) {
                            if ((int)$data['ird_user']['iUserID'] > 0) {
                                //nobody binding this id
                                $cpy_id = $this->__getCpyFromIRD($data['ird_user']['CompanyID']);
                                if ($cpy_id['cpy_id'] != $ret[0]['cpy_id']) {
                                    $ird_diff = [
                                        'ird_user_id' => $data['ird_user']['iUserID'],
                                        'idt_user_id' => $ret[0]['userid'],
                                        'idt_old_cpy_id' => $ret[0]['cpy_id'],
                                        'idt_new_cpy_id' => $cpy_id['cpy_id'],
                                        'ird_email' => $data['ird_user']['UserName'],
                                        'idt_email' => $ret[0]['email'],
                                        'cdate' => $upTimes
                                    ];
                                    $this->mysqlInsert('ird_diff_user', $ird_diff);
                                    $sql = "update idt_licence set u_id = null where u_id = '{$ret[0]['userid']}'";
                                    $this->mysqlQuery($sql, "all");
                                    $update_data = ['cpy_id' => $cpy_id['cpy_id']];
                                    write_to_log('CHANGE COMPANY ' . json_encode($update_data), '_from_ird');
                                    $this->mysqlEdit('idt_user', $update_data, "u_id='{$ret[0]['userid']}'");
                                    $ret[0]['cpy_id'] = $cpy_id['cpy_id'];
                                }
                                if ($cpy_id) {
                                    $binding_ird = $this->__bindingIRD($ret[0]['userid'], $data['ird_user']);
                                    if ($binding_ird) {
                                        $rs['permissions'] = $ret[0]['permissions'];
                                        $rs['productKey'] = 1;
                                        $rs['ird_user_id'] = $data['ird_user']['iUserID'];
                                        $rs['companyName'] = $cpy_id['cpy_cname'];
                                        $rs['dev_id'] = $cpy_id['cpy_id'];
                                        $rs['ird'] = 'add permission';
                                        $rs['bind_state'] = $permission_model->addPermission($data['ird_user']['pplist'], $ret[0], $data['ird_user']['iUserID']);
                                    }

                                }

                            } else {
                                write_to_log(json_encode($data['ird_user']), '_from_ird');
                                write_to_log('iuser id 非法', '_from_ird');
                            }

                        }
                    }

                    if ($this->__checkBind($ret[0]['userid'], $data['userIP'])) {
                        _SUCCESS('000000', '登录成功', $rs);
                    } else {
                        _ERROR('000005', '登录失败,您所在的区域不允许使用产品');
                    }

//                    _SUCCESS('000000', '登录成功', $rs);

                } else {
                    _ERROR('000005', '登录失败,更新token失败');
                }
            } else {
                //for JAPANESE Values Inc.

                if ($data['LoginType'] !== 'sp_mail') {
                    $sqlb = "SELECT *
                        FROM idt_user WHERE u_mobile='{$data['loginMobile']}' and u_auth_type='{$data['LoginKey']}'";
                } else {
                    $sqlb = "SELECT *
                        FROM idt_user WHERE u_mail='{$data['loginMail']}' and u_auth_type='{$data['LoginKey']}'";
                }


                $sp_ret = $this->mysqlQuery($sqlb, 'all');

                write_to_log('sp sql: ' . $sqlb, '_sp');
                write_to_log('sp_ret' . json_encode($sp_ret), '_sp');

                if (!empty($sp_ret)) {
                    $this->redisHere(VERSION . '_' . $ret[0]['userid'] . '_ird', true);
                    $where_upToken['u_token'] = $upToken;//更新TOKEN
                    $where_upToken['u_edate'] = $upTimes;//更新登录时间
                    $id_upToken = " u_id='" . $sp_ret[0]['u_id'] . "'";//用户GUID
                    $ret_upToken = $this->mysqlEdit('idt_user', $where_upToken, $id_upToken);
                    if ($ret_upToken == '1') {
                        $rs = [
                            'headImg' => $sp_ret[0]['headimg'], //avatar
                            'mobile' => $sp_ret[0]['u_mobile'],
                            'companyID' => $sp_ret[0]['cpy_id'],
                            'permissions' => $sp_ret[0]['u_permissions'], //用户身份 0游客 1企业用户 2企业管理员
                            'productKey' => $sp_ret[0]['u_product_key'], //ird_user_id
                            'dev_id' => $sp_ret[0]['dev_id'],
                            'token' => $upToken,
                            'uname' => $sp_ret[0]['u_name'],
                            'userID' => $sp_ret[0]['u_id'],
                            'department' => $sp_ret[0]['u_department'],
                            'ird_user_id' => $sp_ret[0]['u_product_key'],
                        ];

//                        $logsModel->pushLog([
//                            'user' => $sp_ret[0]['u_id'],
//                            'companyID' => $sp_ret[0]['cpy_id'],
//                            'type' => 'irv用户日志',
//                            'resource' => 'irv日本用户登入',
//                            'status' => '20000',
//                            'level' => '2'
//                        ]);

                        _SUCCESS('000000', '登录成功', $rs);
                    }
                }

                if ($data['LoginType'] == 'sp_mail') {
                    _ERROR('000002', '登录失败,账号不存在或密码错误');
                } else {
                    _ERROR('000002', '登录失败,账号不存在或验证码失效');
                }

            }
        }
    }

    /**
     * IR LOGIN
     * code:
     *
     * - 403:  账号被冻结
     * - 500： token出错，数据不匹配
     * - 200:  登入成功
     * - 404： 没有绑定老产品
     *
     * @param $data
     * @return array
     */
    public function ircLogin($data)
    {
        if ($this->__verifyKeyForIRLogin($data)) {
            $userInfo = $this->__getUserInfo($data['irUserID']);

            if (!empty($userInfo) and count($userInfo) == 1) {
                if ($userInfo[0]['u_state'] == 1) {
                    return [
                        'state' => false,
                        'code' => 403,
                        'msg' => '账号被冻结'
                    ];
                }

                //success login

                //create token

                $upToken = md5(rand(1000000001, 9999999999));

                $where_upToken['u_token'] = $upToken;
                $ret_upToken = $this->mysqlEdit('idt_user', $where_upToken, "u_id='{$userInfo[0]['u_id']}'");

                if ($ret_upToken != '1') {
                    return [
                        'state' => false,
                        'code' => 500,
                        'msg' => 'token出错'
                    ];
                }

                //get company name
                if ($userInfo[0]['cpy_id'] !== 0) {
                    $sql = "SELECT cpy_cname FROM idt_company WHERE 1=1 AND cpy_id = '{$userInfo[0]['cpy_id']}'";
                    $getCpyInfo = $this->mysqlQuery($sql, 'all');

                    if (!empty($getCpyInfo)) {
                        $getCpyInfo = $getCpyInfo[0]['cpy_cname'];
                    } else {
                        $getCpyInfo = null;
                    }

                }

                $rs = [
                    'headImg' => $userInfo[0]['u_head'], //avatar
                    'mobile' => $userInfo[0]['u_mobile'],
                    'companyID' => $userInfo[0]['cpy_id'],
                    'companyName' => $getCpyInfo,
                    'permissions' => $userInfo[0]['u_permissions'], //用户身份 0游客 1企业用户 2企业管理员
                    'productKey' => $userInfo[0]['u_product_key'], //老产品id
                    'token' => $upToken,
                    'department' => $userInfo[0]['u_department'],
                    'uname' => $userInfo[0]['u_name'],
                    'userID' => $userInfo[0]['u_id'],
                ];

                return [
                    'state' => true,
                    'code' => 200,
                    'msg' => '登入成功',
                    'userInfo' => $rs,
                    'irUserInfo' => $this->getIrUser($userInfo[0]['u_product_key']) //get ir product list
                ];

            } else {
                //no product key
                return [
                    'state' => false,
                    'code' => 404,
                    'msg' => '没有绑定老产品'
                ];

            }
        } else {
            return [
                'state' => false,
                'code' => 500,
                'msg' => '提交数据不匹配'
            ];
        }
    }

    /**
     * check pdt permissions
     *
     * @param $pdtID
     * @param $ppList
     * @return bool
     */
    public function checkPdtPermissions($pdtID, $ppList)
    {
        if (empty($ppList)) {
            return false;
        }

        if (empty($pdtID)) {
            return false;
        }

        $ppId = $this->__getPPID($pdtID);

        if (!empty($ppId)) {
            $ppId = $ppId[0]['pp_id'];

            if (is_array($ppList)) {
                if (count($ppList) > 0) {
                    $checkStatus = false;

//                    pr($ppList);

                    foreach ($ppList as $pp) {

                        if ($pp['ppid'] == $ppId) {
                            $checkStatus = true;
                        }

                    }

                    return $checkStatus;

                } else {

                    return false;
                }
            } else {

                return false;

            }

        } else {
            return false;
        }

    }

    /**
     * 用户注册且绑定微信
     *
     * @param $data
     */
    public function addUser($data)
    {
        //响应时间
        $upTimes = date("Y-m-d H:i:s");
        //创建token
        $upToken = md5(rand(1000000001, 9999999999));

        $permission_model = Model::instance('Permissions');

        //验证手机验证码
        $sql_resCode = "SELECT COUNT(1) chk_codenum FROM idt_mobilekey WHERE mik_mobile='{$data['loginMobile']}' 
                        AND mik_type=0 AND mik_state=0 AND mik_key='{$data['loginKey']}' 
                        AND ROUND((UNIX_TIMESTAMP('{$upTimes}')-UNIX_TIMESTAMP(mik_cdate))/60)<=5";
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
            $where_editwx = [
                'u_wxname' => urlencode($data['wxName']),
                'u_wxopid' => $data['wxOpenid'],
                'u_wxunid' => $data['wxUnionid'],
                'u_edate' => $upTimes,
            ];

            $id_editwx = " u_mobile='" . $data['loginMobile'] . "'";//用户帐号
            $ret_chk = $this->mysqlEdit('idt_user', $where_editwx, $id_editwx);


        } else {

            if (!empty($data['ird_guid'])) {
                //ird create user
                $ret_chk = $this->__addUserFromIrd($data, $upToken, $upTimes, 'wechat');
            } else {
                $ret_chk = $this->__addGuest($data, $upToken, $upTimes, 'wechat');
            }

//            $ret_chk = $this->__addGuest($data, $upToken, $upTimes, 'wechat');
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
            $sql = "SELECT dba.u_id userid,dba.u_mobile mobile,dbb.cpy_id cpy_id,dbb.cpy_cname cpy_cname,dba.u_head headimg,
                    dba.u_product_key productkey,dbb.cpy_validity validity,dba.u_name uname,dba.u_permissions permissions,
                    dba.u_token token,dba.u_state u_state 
                    FROM idt_user dba LEFT JOIN idt_company dbb ON (dba.cpy_id=dbb.cpy_id) 
                    WHERE u_mobile='{$data['loginMobile']}'";
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


            //judge binding
            if ($ret[0]['permissions'] == 0) {
                //guest if the data has ird guid
                if (!empty($data['ird_user'])) {
                    if ((int)$data['ird_user']['iUserID'] > 0) {
                        //nobody binding this id
                        $cpy_id = $this->__getCpyFromIRD($data['ird_user']['CompanyID']);
                        if ($cpy_id) {
                            $binding_ird = $this->__bindingIRD($ret[0]['userid'], $data['ird_user']);
                            if ($binding_ird) {
                                $change_member = $this->__changeToMember($ret[0]['userid'], $cpy_id['cpy_id']);
                                if ($change_member) {
                                    write_to_log(' chenge to member is success', '_from_ird');
                                    $rs['permissions'] = 1;
                                    $rs['productKey'] = 1;
                                    $rs['ird_user_id'] = $data['ird_user']['iUserID'];
                                    $rs['companyName'] = $cpy_id['cpy_cname'];
                                    $rs['dev_id'] = $cpy_id['cpy_id'];
                                    $rs['ird'] = 'add permission';
                                    $permission_model->addPermission($data['ird_user']['pplist'], $ret[0], $data['ird_user']['iUserID']);
                                }
                            }

                        }

                    } else {
                        write_to_log(json_encode($data['ird_user']), '_from_ird');
                        write_to_log('iuser id 非法', '_from_ird');
                    }

                }
            } elseif ($ret[0]['u_product_key'] == null) {
                //guest if the data has ird guid
                if (!empty($data['ird_user'])) {
                    if ((int)$data['ird_user']['iUserID'] > 0) {
                        //nobody binding this id
                        $cpy_id = $this->__getCpyFromIRD($data['ird_user']['CompanyID']);
                        if ($cpy_id) {
                            $binding_ird = $this->__bindingIRD($ret[0]['userid'], $data['ird_user']);
                            if ($binding_ird) {
                                $rs['permissions'] = $ret[0]['u_permissions'];
                                $rs['productKey'] = 0;
                                $rs['ird_user_id'] = $data['ird_user']['iUserID'];
                                $rs['companyName'] = $cpy_id['cpy_cname'];
                                $rs['dev_id'] = $cpy_id['cpy_id'];
                                $rs['ird'] = 'add permission';
                                $permission_model->addPermission($data['ird_user']['pplist'], $ret[0], $data['ird_user']['iUserID']);
                            }

                        }

                    } else {
                        write_to_log(json_encode($data['ird_user']), '_from_ird');
                        write_to_log('iuser id 非法', '_from_ird');
                    }

                }
            }


            _SUCCESS('000000', '登录成功', $rs);
        } else {
            _ERROR('000002', '登录失败');
        }
    }

    /**
     * 发送验证码
     * 需要一个nation 参数有值，则使用国际短信
     *
     * @param $data
     * @throws phpmailerException
     */

    public function setMobileKey($data)
    {
        //当前时间
        $upTimes = date("Y-m-d H:i:s");

        $ret_codeSend = $this->__setMobileKey($data, $upTimes, 0);

        if ($ret_codeSend) {
            $data = $ret_codeSend;
            //调用SMS,发送验证码
            $content = str_replace("(CODE)", $data['Code'], SMS_CONTENT);
            $phones = $data['Mobile'];
            $mail = $this->__checkHasEmail($data['Mobile']);
            write_to_log('the mobile: ' . $data['Mobile'] . 'ready to send mail: ' . $mail, '_mail');

            if (!empty($mail)) {
                write_to_log('send mail: ' . $mail . ' and code is ' . $data['Code'], '_mail');
                foreach (NEED_MAIL as $wMail) {
                    $t = strpos($mail, $wMail);
                    if ($t !== false) {
                        write_to_log('the mail in need send mail list : ' . $mail . ',' . $wMail, '_mail');
                        $this->__sendCode($mail, $data['Code']);
                    } else {
                        write_to_log('the mail not in need send mail list : ' . $mail . ',' . $wMail, '_mail');
                    }
                }
            } else {
                write_to_log('no email send ', '_mail');
                //var_dump('no mail');
            }

            $sms = Sms::instance()->aliSMS($phones, 'SMS_137315006', ['code' => $data['Code']]);


//            if ($sms == '发送成功') {
//                _SUCCESS('000000', '发送成功');
//            } else {
//                _ERROR('000002', '发送失败,SMS错误');
//            }

            if ($sms->Code == 'OK') {
                _SUCCESS('000000', '发送成功');
            } else {
                write_to_log(json_encode((array)$sms), '_sms');
                _ERROR('000002', '发送失败,SMS错误');
            }

        } else {

            _ERROR('000002', '发送失败,数据异常');
        }
    }

    /**
     * verify sms and mail for login
     * @param $getData
     * @throws phpmailerException
     */
    public function setVerKey($getData)
    {
        //当前时间
        $upTimes = date("Y-m-d H:i:s");

        if ($getData['LoginType'] == 'mail') {
            $sql = "select u_mobile as mobile from idt_user where u_mail = '{$getData['Mail']}'";
            $get_mobile = $this->mysqlQuery($sql, 'all');
            if (!empty($get_mobile) and !empty($get_mobile[0]['mobile'])) {
                $getData['Mobile'] = $get_mobile[0]['mobile'];
            }
        }

        $ret_codeSend = $this->__setMobileKey($getData, $upTimes, 0);


        if ($ret_codeSend) {
            $data = $ret_codeSend;

            //调用SMS,发送验证码

            $phones = $data['Mobile'];
            $mail = $this->__checkHasEmail($data['Mobile']);

            //海外账号登入
            if (!empty($phones) and $getData['LoginType'] !== 'mail') {
                if ((int)$phones > 40000000000) {
                    $getData['LoginType'] = 'oversea';
                }
            }

            write_to_log('the mobile: ' . $data['Mobile'] . 'ready to send mail: ' . $mail, '_mail');

            switch ($getData['LoginType']) {
                case 'mobile':
                    if (!empty($mail)) {
                        write_to_log('send mail: ' . $mail . ' and code is ' . $data['Code'], '_mail');
                        foreach (NEED_MAIL as $wMail) {
                            $t = strpos($mail, $wMail);
                            if ($t !== false) {
                                write_to_log('the mail in need send mail list : ' . $mail . ',' . $wMail, '_mail');
                                $this->__sendCode($mail, $data['Code']);
                            } else {
                                write_to_log('the mail not in need send mail list : ' . $mail . ',' . $wMail, '_mail');
                            }
                        }
                    } else {
                        write_to_log('no email send ', '_mail');
                        //var_dump('no mail');
                    }

//                    $content = str_replace("(CODE)", $data['Code'], SMS_CONTENT_NATION);
//                    $content = str_replace("#CODE#", $data['Code'], SMS_CONTENT_NATION);

                    if (IS_TEST) {
                        $sms = '发送成功';
                    } else {
                        if ($data['CountryCode'] == '86' or $data['CountryCode'] == 'CN' or empty($data['CountryCode'])) {
//                            $sms = Sms::instance()->sendSingleSMS($this->__textTemple($data['Code'], $phones,
//                                SMS_TEMP_LOGIN), $phones, tr);
                            $sms = Sms::instance()->aliSMS($phones, 'SMS_137315006', ['code' => $ret_codeSend['Code']]);
                        } else {
//                            $sms = Sms::instance()->sendSingleSMS($this->__textTemple($data['Code'], $phones,
//                                SMS_TEMP_NATION_LOGIN), $phones, tr);
                            $sms = Sms::instance()->aliSMS($phones, 'SMS_138063591', [
                                'code' => (string)$ret_codeSend['Code'],
                                'time_val' => '30'], 'iResearch', $data['CountryCode']);
                        }

                    }


                    if ($sms->Code == 'OK') {
                        _SUCCESS('000000', '发送成功');
                    } else {
                        write_to_log(json_encode($sms), '__alisms');
                        _ERROR('000002', '发送失败,SMS错误');

                    }
                    break;

                case 'mail':
                    $find_mail = $this->__checkHasEmailByMail($data['Mail']);
                    if (!empty($find_mail)) {
                        write_to_log('send mail: ' . $find_mail . ' and code is ' . $data['Code'], '_mail');
                        $this->__sendCode($find_mail, $data['Code']);
                        _SUCCESS('000000', '发送成功');

                    } else {
                        write_to_log('no email send ', '_mail');
                        _ERROR('000002', '此邮箱没有登记，请联系客服');
                    }

                    break;
                case 'oversea':
                    _SUCCESS('000000', 'Please login with special password!');
                    break;
                default:
                    _ERROR('000002', '发送失败,数据异常');
                    break;
            }


        } else {

            _ERROR('000002', '发送失败,code数据异常');
        }
    }


    /**
     * 发送验证码 for manager
     *
     * @param $data
     * @throws phpmailerException
     */
    public function sendKey($data)
    {
        //当前时间
        $upTimes = date("Y-m-d H:i:s");


        $ret_codeSend = $this->__setMobileKey($data, $upTimes, 3);


        if ($ret_codeSend) {
            $data = $ret_codeSend;
            //调用SMS,发送验证码
            $content = str_replace("(CODE)", $data['Code'], SMS_CONTENT_CHECK);
            $phones = $data['Mobile'];
            $mail = $this->__checkHasEmail($data['Mobile']);
            write_to_log('the mobile: ' . $data['Mobile'] . 'ready to send mail: ' . $mail, '_mail');

            if (!empty($mail)) {
                write_to_log('send mail: ' . $mail . ' and code is ' . $data['Code'], '_mail');
                foreach (NEED_MAIL as $wMail) {
                    $t = strpos($mail, $wMail);
                    if ($t !== false) {
                        write_to_log('the mail in need send mail list : ' . $mail . ',' . $wMail, '_mail');
                        $this->__sendCode($mail, $data['Code']);
                    } else {
                        write_to_log('the mail not in need send mail list : ' . $mail . ',' . $wMail, '_mail');
                    }
                }
            } else {
                write_to_log('no email send ', '_mail');
                //var_dump('no mail');
            }

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
        $sql_productkey = "SELECT u_product_key,u_mail,u_name FROM idt_user WHERE u_id='{$data['userID']}'";
        $ret_productkey = $this->mysqlQuery($sql_productkey, "all");
        if ($ret_productkey[0]['u_product_key'] != "" OR $ret_productkey[0]['u_product_key'] != null) {
            _ERROR('000002', '绑定失败,该产品KEY已绑定其它账号');
        }
        if ($ret_productkey[0]['u_mail'] == "" OR $ret_productkey[0]['u_mail'] == null) {
            $sql_mail = "update idt_user set u_mail = '{$data['account']}' where u_id = '{$data['userID']}'";
            $this->mysqlQuery($sql_mail);
        } else {
            write_to_log(json_encode($data), '_diffmail');
        }
        $ret_productkey[0]['u_name'] = trim($ret_productkey[0]['u_name']);
        if ($ret_productkey[0]['u_name'] == "" OR $ret_productkey[0]['u_name'] == null) {
            $sql_name = "update idt_user set u_name = '{$ret_irdKey['TrueName']}' where u_id = '{$data['userID']}'";
            $this->mysqlQuery($sql_name);
        } else {
            write_to_log(json_encode($data), '_diffname');
        }

        //绑定成功,更新产品KEY
        $where['u_product_key'] = $ret_irdKey['iUserID']; //产品Key
        $productkey = " u_id='" . $data['userID'] . "'";//用户GUID
        $ret_productkey = $this->mysqlEdit('idt_user', $where, $productkey);
        if ($ret_productkey == 1) {
            Model::instance('Permissions')->addPermission($ret_irdKey['pplist'], $data, $ret_irdKey['iUserID']);
            _SUCCESS('000000', '绑定成功');
        } else {
            _ERROR('000002', '绑定失败');
        }
    }

    //获取用户资料
    public function getUserInfo($data)
    {
        //查询产品Key
        $sql = "SELECT dbb.cpy_cname,dba.u_mail,dba.u_head,
                dba.u_mobile,dba.u_position,dba.u_name,dba.u_permissions, dba.u_department,dba.u_wxname, 
                dba.u_product_key, dbb.ird_ca_id as ird_ca_id
                FROM idt_user dba 
                LEFT JOIN idt_company dbb ON (dba.cpy_id=dbb.cpy_id) 
                WHERE dba.u_id='{$data['userID']}'";

        $ret = $this->mysqlQuery($sql, "all");

        //返回用户信息
        $rs['company'] = $ret[0]['cpy_cname']; //公司
        $rs['companyEmail'] = $ret[0]['u_mail']; //公司邮箱
        $rs['department'] = $ret[0]['u_department']; //公司邮箱
        $rs['headImg'] = "upload/head/" . $ret[0]['u_head']; //头像
        $base_image = base64_encode(file_get_contents(ROOT_PATH . 'upload/head/' . $ret[0]['u_head']));

        if (!empty($base_image)) {
            $rs['avatar_base64'] = 'data:image/png;base64,' . $base_image;
        } else {
            $rs['avatar_base64'] = null;
        }

        $rs['mobile'] = $ret[0]['u_mobile']; //手机
        $rs['position'] = $ret[0]['u_position']; //职位
        $rs['uname'] = $ret[0]['u_name']; //姓名
        $rs['permissions'] = $ret[0]['u_permissions']; //姓名
        $rs['wechat'] = $ret[0]['u_wxname'];
        $rs['ird_user_Id'] = $ret[0]['u_product_key'];
        $rs['ird_ca_id'] = $ret[0]['ird_ca_id'];
        $rs['productList'] = $this->__getUserProductList($data['userID']);
        _SUCCESS('000000', '获取成功', $rs);
    }

    public function getUserInfoClean($data)
    {
        $sql = "SELECT dbb.cpy_cname,dba.u_mail,dba.u_head,
                dba.u_mobile,dba.u_position,dba.u_name,dba.u_permissions, dba.u_department,dba.u_wxname, 
                dba.u_product_key, dbb.ird_ca_id as ird_ca_id
                FROM idt_user dba 
                LEFT JOIN idt_company dbb ON (dba.cpy_id=dbb.cpy_id) 
                WHERE dba.u_id='{$data['userID']}'";

        $ret = $this->mysqlQuery($sql, "all");

        //返回用户信息
        $rs['company'] = $ret[0]['cpy_cname']; //公司
        $rs['companyEmail'] = $ret[0]['u_mail']; //公司邮箱
        $rs['department'] = $ret[0]['u_department']; //公司邮箱
        $rs['headImg'] = "upload/head/" . $ret[0]['u_head']; //头像
        $base_image = base64_encode(file_get_contents(ROOT_PATH . 'upload/head/' . $ret[0]['u_head']));

        if (!empty($base_image)) {
            $rs['avatar_base64'] = 'data:image/png;base64,' . $base_image;
        } else {
            $rs['avatar_base64'] = null;
        }

        $rs['mobile'] = $ret[0]['u_mobile']; //手机
        $rs['position'] = $ret[0]['u_position']; //职位
        $rs['uname'] = $ret[0]['u_name']; //姓名
        $rs['permissions'] = $ret[0]['u_permissions']; //姓名
        $rs['wechat'] = $ret[0]['u_wxname'];
        $rs['ird_user_Id'] = $ret[0]['u_product_key'];
        $rs['ird_ca_id'] = $ret[0]['ird_ca_id'];
        $rs['productList'] = $this->__getUserProductList($data['userID']);
        return $rs;
    }


    public function _getUserInfoByToken($data)
    {
        $sql = "SELECT dbb.cpy_cname,dbb.cpy_id,dba.u_mail,dba.u_head,
                dba.u_mobile,dba.u_position,dba.u_permissions,dba.u_name,dba.u_id ,devdb.dev_name, dba.dev_id,dba.u_edate, dba.u_product_key, dba.u_wxname
                FROM idt_user dba 
                LEFT JOIN idt_company dbb ON (dba.cpy_id=dbb.cpy_id) 
                LEFT JOIN idt_devs devdb on (dba.dev_id = devdb.dev_id)
                WHERE dba.u_token='{$data['token']}'";

        $ret = $this->mysqlQuery($sql, "all");

        if (!empty($data['pdt_id'])) {

            $getExpDateSQL = "SELECT end_date ,pnum_type
                              FROM idt_permissions_number 
                              WHERE cpy_id='{$ret[0]['cpy_id']}' AND pdt_id='{$data['pdt_id']}'";
            $getExpDate = $this->mysqlQuery($getExpDateSQL, 'all');

            if ($getExpDate) {
                $pnum_type = $getExpDate[0]['pnum_type'];
                $getExpDate = $getExpDate[0]['end_date'];
            } else {
                if ($ret[0]['cpy_id'] == 1 and OPEN_ME) {
                    $getExpDate = '无限';
                } else {
                    $getExpDate = '已';
                }
            }
        } else {
            $getExpDate = '已';
        }

//        write_to_log(json_encode($ret),'_test');
        //返回用户信息
        return [
            'company' => $ret[0]['cpy_cname'],
            'companyID' => $ret[0]['cpy_id'],
            'companyEmail' => $ret[0]['u_mail'],
            'headImg' => 'upload/head/' . $ret[0]['u_head'],
            'mobile' => $ret[0]['u_mobile'],
            'position' => $ret[0]['u_position'],
            'uname' => $ret[0]['u_name'],
            'tokenDate' => $ret[0]['u_edate'],
            'uid' => $ret[0]['u_id'],
            'devID' => $ret[0]['dev_id'],
            'devName' => $ret[0]['dev_name'],
            'expDate' => $getExpDate,
            'pnum_type' => $pnum_type,
            'permissions' => $ret[0]['u_permissions'],
            'wechat' => $ret[0]['u_wxname'],
            'ird_user_id' => $ret[0]['product_key'],
        ];
    }


    public function _getUserInfoByToken_no_dev($data)
    {
        $sql = "SELECT dbb.cpy_cname,dbb.cpy_id,dba.u_mail,dba.u_head,
                dba.u_mobile,dba.u_position,dba.u_permissions,dba.u_name,dba.u_id , dba.dev_id,dba.u_edate, dba.u_product_key, dba.u_wxname
                FROM idt_user dba 
                LEFT JOIN idt_company dbb ON (dba.cpy_id=dbb.cpy_id) 
                WHERE dba.u_token='{$data['token']}'";

        $key = md5($sql);
        if ($this->redis()->exists($key)) {
            $ret = json_decode($this->redis()->get($key), true);
        } else {
            $ret = $this->mysqlQuery($sql, "all");
            $this->redis()->set(md5($sql), json_encode($ret), 500);
        }

        if (!empty($data['pdt_id'])) {

            $getExpDateSQL = "SELECT end_date ,pnum_type
                              FROM idt_permissions_number 
                              WHERE cpy_id='{$ret[0]['cpy_id']}' AND pdt_id='{$data['pdt_id']}'";
            $getExpDate = $this->mysqlQuery($getExpDateSQL, 'all');

            if ($getExpDate) {
                $pnum_type = $getExpDate[0]['pnum_type'];
                $getExpDate = $getExpDate[0]['end_date'];
            } else {
                if ($ret[0]['cpy_id'] == 1 and OPEN_ME) {
                    $getExpDate = '无限';
                } else {
                    $getExpDate = '已';
                }
            }
        } else {
            $getExpDate = '已';
        }

        //返回用户信息
        return [
            'company' => $ret[0]['cpy_cname'],
            'companyID' => $ret[0]['cpy_id'],
            'companyEmail' => $ret[0]['u_mail'],
            'headImg' => 'upload/head/' . $ret[0]['u_head'],
            'mobile' => $ret[0]['u_mobile'],
            'position' => $ret[0]['u_position'],
            'uname' => $ret[0]['u_name'],
            'tokenDate' => $ret[0]['u_edate'],
            'uid' => $ret[0]['u_id'],
            'devID' => $ret[0]['dev_id'],
            'devName' => $ret[0]['dev_name'],
            'expDate' => $getExpDate,
            'pnum_type' => $pnum_type,
            'permissions' => $ret[0]['u_permissions'],
            'wechat' => $ret[0]['u_wxname'],
            'ird_user_id' => $ret[0]['product_key'],
        ];
    }

    /**
     * update user info
     *
     * @param $data
     */
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

        if ($data['department'] === '') {
            $where['u_department'] = ' ';
        } else {
            $where['u_department'] = $data['department'];
        }
        //修改用户头像
        if ($data['headImg'] !== null) { //处理NULL
            //图片存储
            $imgName = $data['userID'] . '.png';//头像名称
            $imgPath = 'upload/head/' . $imgName;//头像路径
            $imgVal = base64_decode($data['headImg']);//头像格式化
            write_to_log($data['headImg'], '_pic');
            write_to_log($imgVal, '_pic');
            file_put_contents($imgPath, $imgVal);//返回的是字节数
            //保存头像
            $where['u_head'] = $imgName; //用户头像
        }
        if ($data['headImg'] === "") {
            $where['u_head'] = "head.png";
        } //处理空
        //修改用户

        if (!empty($data['toUserID'])) {
            $id = " u_id='" . $data['toUserID'] . "'";//用户GUID
        } else {
            $id = " u_id='" . $data['userID'] . "'";//用户GUID
        }

//        $id = " u_id='" . $data['userID'] . "'";//用户GUID
        if (isset($where)) {
            $ret = $this->mysqlEdit('idt_user', $where, $id);
        }

        //返回响应结果
        if (isset($ret)) {
            if ($ret == '1') {
                _SUCCESS('000000', '修改成功', $this->getUserInfo($data));
            } else {
                _ERROR('000002', '修改失败');
            }
        }
    }

    /**
     * block user
     *
     * @param $data
     */
    public function iceUser($data)
    {
        $this->__changeUserState($data['userID'], 1);
    }

    /**
     * unblock user
     *
     * @param $data
     */
    public function thawUser($data)
    {
        $this->__changeUserState($data['userID'], 0);
    }


    /**
     * user list
     *
     * @param $data
     */
    public function userList($data)
    {
        //查询初始化条件
        $data['orderByColumn'] == null ? $orderByColumn = 'dba.u_udate' : $orderByColumn = $data['orderByColumn']; //排序字段
        $data['orderByType'] == null ? $orderByType = 'DESC' : $orderByType = $data['orderByType']; //排序方式
        $data['pageSize'] == null ? $pageSize = '10' : $pageSize = $data['pageSize']; //查询数据
        $data['pageNo'] == null ? $pageNo = '0' : $pageNo = ($data['pageNo'] - 1) * $pageSize; //查询页数
        $data['keyword'] == null ? $keyword = '' : $keyword = " AND (dba.u_mobile LIKE '%" . $data['keyword'] .
            "%' OR dbb.cpy_cname LIKE '%" . $data['keyword'] . "%' OR dba.u_name LIKE '%" . $data['keyword'] .
            "%' OR dba.u_mail LIKE '%" . $data['keyword'] . "%')"; //查询条件

        //获取当前用户所属公司ID
        $sql_companyID = "SELECT cpy_id FROM idt_user WHERE 1=1 AND u_id='{$data['userID']}'";
        $ret_companyID = $this->mysqlQuery($sql_companyID, "all");
        if ($ret_companyID[0]['cpy_id'] == 0 OR $ret_companyID[0]['cpy_id'] == null OR $ret_companyID[0]['cpy_id'] == "") {
            _ERROR('000002', '查询失败,非法用户');
        }

        //执行查询
        $sql = "SELECT dba.u_id,dba.u_head,dba.u_mobile mobile,dba.u_mail,dba.u_name,dba.u_permissions permissions,
                dba.u_state,dba.u_edate logindate ,IFNULL(dbc.pcount,0) power, u_position, u_department
                FROM idt_user dba 
                LEFT JOIN idt_company dbb ON (dba.cpy_id=dbb.cpy_id AND dba.cpy_id={$ret_companyID[0]['cpy_id']}) 
                LEFT JOIN (SELECT u_id,COUNT(1) pcount FROM idt_licence where state = 1 GROUP BY u_id) dbc ON (dba.u_id=dbc.u_id)
                WHERE 1=1 AND dba.u_state=0 
                AND dbb.cpy_state=0 
                AND (dba.u_permissions=1 OR dba.u_permissions=2) {$keyword}
                ORDER BY {$orderByColumn} {$orderByType} LIMIT {$pageNo},{$pageSize}";

        $ret = $this->mysqlQuery($sql, "all");

        //执行总数
        $sql_count = "SELECT COUNT(1) count_num " .
            "FROM idt_user dba " .
            "LEFT JOIN idt_company dbb ON (dba.cpy_id=dbb.cpy_id AND dba.cpy_id='{$ret_companyID[0]['cpy_id']}') " .
            "WHERE 1=1 " .
            "AND dba.u_state=0 " .
            "AND dbb.cpy_state=0 " .
            "AND (dba.u_permissions=1 OR dba.u_permissions=2) {$keyword}";
        $ret_count = $this->mysqlQuery($sql_count, "all");
        //返回结果
        $rs = array();
        //返回参数-执行结果
        foreach ($ret as $a => $v) {
//            $lic_sql = "select idt_licence.pdt_id,pdt_ename,pc_start_time,pc_due_time,mobile_start_time,mobile_due_time,ott_start_time,ott_due_time,
//                        DATE_FORMAT(`start_date`,'%Y-%m-%d') start_date,DATE_FORMAT(`end_date`,'%Y-%m-%d') end_date from idt_licence
//                        left join idt_product on idt_product.pdt_id = idt_licence.pdt_id
//                        left join idt_subproduct on idt_subproduct.licence_key = idt_licence.licence_key
//                        left join idt_permissions_number on idt_permissions_number.pdt_id = idt_licence.pdt_id
//                        where state = 1 and idt_licence.u_id = '{$v['u_id']}' and idt_permissions_number.cpy_id = {$ret_companyID[0]['cpy_id']}";
//            $rs['list'][$a]['productInfo'] = $this->mysqlQuery($lic_sql, "all");
            $rs['list'][$a]['userID'] = $v['u_id']; //用户GUID
            $rs['list'][$a]['mobile'] = $v['mobile']; //手机
            $rs['list'][$a]['power'] = $v['power']; //被分配许可证数
            $rs['list'][$a]['mail'] = $v['u_mail']; //邮箱
            $rs['list'][$a]['name'] = $v['u_name']; //姓名
            $rs['list'][$a]['position'] = $v['u_position'];
            $rs['list'][$a]['department'] = $v['u_department'];
            $rs['list'][$a]['permissions'] = (int)$v['permissions']; //用户身份
            $rs['list'][$a]['state'] = (int)$v['u_state']; //用户状态
            $rs['list'][$a]['loginDate'] = $v['logindate']; //最后登录时间
        }
        foreach ($rs['list'] as $k => $v) {
            $rs['list'][$k]['index'] = ($k + 1) * ($pageNo + 1);
        }
        //返回参数-执行总数
        $rs['totalSize'] = $ret_count[0]['count_num'];

        //查询成功,返回响应结果
        _SUCCESS('000000', '查询成功', $rs);
    }

    /**
     * 用户产品列表
     * @param $data
     */
    public function userProductInfo($data)
    {
        $sql = "select idt_product.pdt_id,pdt_name as pdt_ename,DATE_FORMAT(`start_date`,'%Y-%m-%d') start_date,DATE_FORMAT(`end_date`,'%Y-%m-%d') end_date,false as state from idt_permissions_number
                left join idt_product on idt_permissions_number.pdt_id = idt_product.pdt_id
                where idt_product.pdt_vtype = 1 and pdt_sid<>0 and pdt_label is null and idt_product.pdt_state = 0 and cpy_id = {$data['cpy_id']} and meu_id = 0 order by pdt_ename asc";
        $ret = $this->mysqlQuery($sql, "all");
        $lic_sql = "select idt_licence.pdt_id,pdt_name as pdt_ename,pc_start_time,pc_due_time,mobile_start_time,mobile_due_time,ott_start_time,ott_due_time,
                    DATE_FORMAT(`start_date`,'%Y-%m-%d') start_date,DATE_FORMAT(`end_date`,'%Y-%m-%d') end_date,true as state from idt_licence
                    left join idt_product on idt_product.pdt_id = idt_licence.pdt_id
                    left join idt_subproduct on idt_subproduct.licence_key = idt_licence.licence_key
                    left join idt_permissions_number on idt_permissions_number.pdt_id = idt_licence.pdt_id
                    where state = 1 and idt_licence.u_id = '{$data['u_id']}' and idt_permissions_number.cpy_id = {$data['cpy_id']}";
        $rs = $this->mysqlQuery($lic_sql, "all");
        $hasProduct = array_column($rs, 'pdt_id');
        foreach ($ret as $key => $value) {
            if (!in_array($ret[$key]['pdt_id'], $hasProduct)) {
                $lic_type_sql = "select pc_due_time,mobile_due_time,ott_due_time from idt_licence
                    left join idt_subproduct on idt_licence.licence_key = idt_subproduct.licence_key
                    where cpy_id = {$data['cpy_id']} and idt_licence.pdt_id ={$ret[$key]['pdt_id']} and u_id is null and state =1
                    group by pc_due_time,mobile_due_time,ott_due_time";
                $lic_type_ret = $this->mysqlQuery($lic_type_sql, "all");
                if (empty($lic_type_ret)) {
                    $ret[$key]['has_licence'] = 0;
                } else {
                    $ret[$key]['has_licence'] = 1;
                    foreach ($lic_type_ret as $k => $v) {
                        $state = false;
                        if (!empty($lic_type_ret[$k]['pc_due_time'])) {
                            $ret[$key]['licence_type'][$k] = 'pc';
                            $state = true;
                        }
                        if (!empty($lic_type_ret[$k]['mobile_due_time'])) {
                            if ($state) {
                                $ret[$key]['licence_type'][$k] .= '+mobile';
                            } else {
                                $ret[$key]['licence_type'][$k] = 'mobile';
                                $state = true;
                            }
                        }
                        if (!empty($lic_type_ret[$k]['ott_due_time'])) {
                            if ($state) {
                                $ret[$key]['licence_type'][$k] .= '+ott';
                            } else {
                                $ret[$key]['licence_type'][$k] = 'ott';
                            }
                        }
                    }
                }
                array_push($rs, $ret[$key]);
            }
        }
        array_multisort(array_column($rs, 'pdt_id'), SORT_ASC, $rs);
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

    /**
     * get user's company id
     *
     * @param $u_id
     *
     * @return array|string
     */
    public function getUserOfCompany($u_id)
    {
        return $this->__getUserOfCompany($u_id);
    }

    /**
     * check company status
     *
     * @param $cpy_id
     *
     * @return bool
     */
    public function checkCompanyState($cpy_id)
    {
        return $this->__checkState($this->__checkCompanyState($cpy_id));
    }

    /**
     * check mobile
     * has is FALSE
     *
     * @param $mobile
     *
     * @return bool
     */
    public function checkMobile($mobile)
    {
        $ret = $this->__checkMobile($mobile);
        return count($ret) == 0;
    }

    /**
     * check wechat opid
     * has is FALSE
     *
     * @param $wxopid
     *
     * @return bool
     */
    public function checkWXOpID($wxopid)
    {
        $ret = $this->__checkWXOPID($wxopid);
        return count($ret) == 0;
    }

    /**
     * check wechat unid
     *
     * @param $wxunid
     *
     * @return bool
     */
    public function checkWXUnID($wxunid)
    {
        $ret = $this->__checkWXUNID($wxunid);
        return count($ret) == 0;
    }

    /**
     * check token
     *
     * has is TRUE
     *
     * @param $u_token
     *
     * @return bool
     */
    public function checkToken($data)
    {
        if (!empty($data['token']) and !empty($data['userID'])) {
            $ret = $this->__checkToken($data['token'], $data['userID']);
            return count($ret) == 1;
        } else {
            return false;
        }
    }

    /**
     * get user info by userID
     *
     * @param $userID
     *
     * @return mixed
     */
    public function getUserInfoByUserID($userID)
    {
        $sql = "SELECT u_id,dbb.cpy_cname,dba.u_mail,dba.u_head,dba.u_mobile,dba.u_position,dba.u_name,dba.cpy_id
                FROM idt_user dba 
                LEFT JOIN idt_company dbb ON (dba.cpy_id=dbb.cpy_id) 
                WHERE dba.u_id='{$userID}'";

        $ret = $this->mysqlQuery($sql, "all");
        return $ret[0];

    }

    public function getIrUser($irUserID)
    {
        return $this->__getIRUserList($irUserID);
    }

    /**
     * getProductsByCompanyFullNameID
     *
     * @param $data
     */
    public function getProductList($data)
    {
        if ($data['keyword'] == '正式') {
            $state = " and idt_permissions_number.pnum_type = 0";
        } elseif ($data['keyword'] == '试用') {
            $state = " and idt_permissions_number.pnum_type = 1";
        } elseif ($data['keyword'] == '无权限') {
            $state = " and idt_permissions_number.pnum_type = -1";
        } else {
            $state = "";
            $data['keyword'] == null ? $keyword = '' : $keyword = " AND (idt_product.pdt_ename LIKE '%" . $data['keyword'] . "%')"; //查询条件
        }
        $sql = "select idt_product.pdt_id,pdt_name,pdt_ename,IFNULL(pnum_number,0) pnum_number,
                DATE_FORMAT(`start_date`,'%Y-%m-%d') start_date,DATE_FORMAT(`end_date`,'%Y-%m-%d') end_date,
                IFNULL(pnum_type,-1) pnum_type from idt_permissions_number
                left join idt_product on idt_permissions_number.pdt_id = idt_product.pdt_id
                where idt_product.pdt_vtype = 1 {$state}{$keyword} and pdt_sid<>0 and pdt_label is null and idt_product.pdt_state = 0 and cpy_id = {$data['cpy_id']} and meu_id = 0 order by pdt_ename asc";
        $ret = $this->mysqlQuery($sql, "all");
        if (count($ret) <= 0) {
            _SUCCESS('000000', '查询成功', null);
        } else {
            foreach ($ret as $key => $value) {
                $sql = "SELECT IFNULL(COUNT(1),0) have_pnum 
                FROM idt_licence WHERE 1=1 AND state=1 AND cpy_id={$data['cpy_id']} AND pdt_id={$ret[$key]['pdt_id']} AND u_id is not null";
                $have_pnum = $this->mysqlQuery($sql, "all");
                $rs['list'][$key]['productID'] = $ret[$key]['pdt_id'];
                $rs['list'][$key]['productName'] = $ret[$key]['pdt_name'];
                $rs['list'][$key]['totalLicenseNumber'] = $ret[$key]['pnum_number'];
                $rs['list'][$key]['usedLicenseNumber'] = $have_pnum[0]['have_pnum'];
                $rs['list'][$key]['startDate'] = $ret[$key]['start_date'];
                $rs['list'][$key]['endDate'] = $ret[$key]['end_date'];
                if ($ret[$key]['pnum_type'] == 0) {
                    $rs['list'][$key]['accountType'] = '正式';
                } elseif ($ret[$key]['pnum_type'] == 1) {
                    $rs['list'][$key]['accountType'] = '试用';
                } else {
                    $rs['list'][$key]['accountType'] = '无权限';
                }
            }
        }

        //查询成功,返回响应结果
        _SUCCESS('000000', '查询成功', $rs);
    }

    /**
     * removeUser
     *
     * @param $data
     */
    public function removeUser($data)
    {
        $pointModel = Model::instance('points');
        if ($data['lic_author_uid'] != $data['toUserID']) {

            if (empty($data['cpy_id'])) {
                $userInfo = $this->mysqlQuery("select cpy_id from idt_user WHERE u_id='{$data['toUserID']}'", "all");
                if (!empty($userInfo)) {
                    $cpy_id = $userInfo[0]['cpy_id'];
                } else {
                    $cpy_id = false;
                }
            } else {
                $cpy_id = $data['cpy_id'];
            }

            $sql = "update idt_licence set u_id = null,lic_author_uid='{$data['lic_author_uid']}' where u_id = '{$data['toUserID']}'";
            $ret = $this->mysqlQuery($sql);

            $licenceModel = Model::instance('licence');
            $rmIRD = $licenceModel->removeIRDPermission($data['toUserID']);

            if (!$rmIRD) {
                write_to_log('remove user permission fails, user id' . $data['toUserID'], '_ps_ird');
            }

            $sql = "update idt_user set cpy_id = null,u_permissions = 0,u_product_key=null where u_id = '{$data['toUserID']}'";


            $rs = $this->mysqlQuery($sql);

            if ($cpy_id) {
                $pointModel->removeUserPoint([
                    'u_id' => $data['toUserID'],
                    'cpy_id' => $cpy_id
                ]);
            }
            if ($ret && $rs) {
                _SUCCESS('000000', '移除成功');
            } else {
                _ERROR('000001', '移除失败');
            }
        } else {
            _ERROR('000001', '无法将自己移除');
        }
    }


    public function addMyEmployee($data)
    {
        $this->__addEmployee($data);
    }

    public function getIRVuserid($data)
    {
        $sql = "select u_product_key from idt_user where u_product_key = {$data['iUserID']}";
        $rs = $this->mysqlQuery($sql, "all");
        if (count($rs) > 0) {
            _SUCCESS('000000', '成功');
        } else {
            _ERROR('000001', '失败');
        }
    }

    /**
     * 产品权限
     *
     * @param $u_id
     * @return array
     */
    public function hasProductList($u_id)
    {
        $productList = $this->__getUserProductList($u_id);
        write_to_log('plist: ' . json_encode($productList), '_ps_ird');
        if (!empty($productList) and is_array($productList)) {
            $ret = [];
            $right_now = date('Y-m-d');
            foreach ($productList as $product) {
                //ut
                if ($product['pdt_id'] == 48) {
                    if ($right_now >= $product['pc_start_time'] and $right_now <= $product['pc_due_time']) {
                        array_push($ret, 'iut');
                    }

                    if ($right_now >= $product['mobile_start_time'] and $right_now <= $product['mobile_due_time']) {
                        array_push($ret, 'mut');
                    }

                    if ($right_now >= $product['ott_start_time'] and $right_now <= $product['ott_due_time']) {
                        array_push($ret, 'out');
                    }
                }
                //ut-en
                if ($product['pdt_id'] == 51) {
                    if ($right_now >= $product['pc_start_time'] and $right_now <= $product['pc_due_time']) {
                        array_push($ret, 'iut_en');
                    }

                    if ($right_now >= $product['mobile_start_time'] and $right_now <= $product['mobile_due_time']) {
                        array_push($ret, 'mut_en');
                    }

                    if ($right_now >= $product['ott_start_time'] and $right_now <= $product['ott_due_time']) {
                        array_push($ret, 'out_en');
                    }
                }

                //ut for yonghong report
                if ($product['pdt_id'] == 49) {
                    if ($right_now >= $product['pc_start_time'] and $right_now <= $product['pc_due_time']) {
                        array_push($ret, 'iut_yh');
                    }

                    if ($right_now >= $product['mobile_start_time'] and $right_now <= $product['mobile_due_time']) {
                        array_push($ret, 'mut_yh');
                    }

                    if ($right_now >= $product['mobile_start_time'] and $right_now <= $product['mobile_due_time']) {
                        array_push($ret, 'mut_yh');
                    }
                }

                //vt
                if ($product['pdt_id'] == 47) {
                    if ($right_now >= $product['pc_start_time'] and $right_now <= $product['pc_due_time']) {
                        array_push($ret, 'ivt');
                    }

                    if ($right_now >= $product['mobile_start_time'] and $right_now <= $product['mobile_due_time']) {
                        array_push($ret, 'mvt');
                    }

                    if ($right_now >= $product['ott_start_time'] and $right_now <= $product['ott_due_time']) {
                        array_push($ret, 'ovt');
                    }
                }
                //vt-en
                if ($product['pdt_id'] == 52) {
                    if ($right_now >= $product['pc_start_time'] and $right_now <= $product['pc_due_time']) {
                        array_push($ret, 'ivt_en');
                    }

                    if ($right_now >= $product['mobile_start_time'] and $right_now <= $product['mobile_due_time']) {
                        array_push($ret, 'mvt_en');
                    }

                    if ($right_now >= $product['ott_start_time'] and $right_now <= $product['ott_due_time']) {
                        array_push($ret, 'ovt_en');
                    }
                }

                //ad
                if ($product['pdt_id'] == 42) {
                    if ($right_now >= $product['pc_start_time'] and $right_now <= $product['pc_due_time']) {
                        array_push($ret, 'iadt');
                    }

                    if ($right_now >= $product['mobile_start_time'] and $right_now <= $product['mobile_due_time']) {
                        array_push($ret, 'madt');
                    }

                    if ($right_now >= $product['ott_start_time'] and $right_now <= $product['ott_due_time']) {
                        array_push($ret, 'oadt');
                    }
                }


                //ad_en
                if ($product['pdt_id'] == 53) {
                    if ($right_now >= $product['pc_start_time'] and $right_now <= $product['pc_due_time']) {
                        array_push($ret, 'iadt_en');
                    }

                    if ($right_now >= $product['mobile_start_time'] and $right_now <= $product['mobile_due_time']) {
                        array_push($ret, 'madt_en');
                    }

                    if ($right_now >= $product['ott_start_time'] and $right_now <= $product['ott_due_time']) {
                        array_push($ret, 'oadt_en');
                    }
                }
            }

            write_to_log('pplist: ' . json_encode($ret), '_ps_ird');

            return $ret;
        } else {

            return [];

        }
    }

    public function industryList()
    {
        $sql = "select id,title from idt_industry where Enabled = 1";
        return $this->mysqlQuery($sql, 'all');
    }

    public function regionList()
    {
        $sql = "select id,title from idt_region where Enabled = 1";
        return $this->mysqlQuery($sql, 'all');
    }

    public function productInfo($data)
    {
        $sql = "select pdt_logo_url,pdt_intro from idt_product where pdt_id = {$data['pdt_id']}";
        return $this->mysqlQuery($sql, 'all');
    }

    ######################################################################################
    ##################################                     ###############################
    #################################   PRIVATE METHODS   ################################
    ################################                     #################################
    ######################################################################################

    /**
     * set mobile key
     *
     * @param $data
     * @param $upTimes
     * @param  $keyType
     * @return array|int|string
     */
    private function __setMobileKey($data, $upTimes, $keyType = 0)
    {
        //验证短信30内秒不可重复操作
        $sql_codeTime = "SELECT mik_key 
                        FROM idt_mobilekey 
                        WHERE mik_mobile='{$data['mobile']}' AND mik_state='0' 
                        AND ROUND((UNIX_TIMESTAMP('{$upTimes}')-UNIX_TIMESTAMP(mik_cdate))/60)<=0.01 
                        ORDER BY mik_cdate DESC LIMIT 1";

        $ret_codeTime = $this->mysqlQuery($sql_codeTime, "all");

        //验证短信30内秒不可重复操作
        if ($ret_codeTime[0]['mik_key'] != "" OR $ret_codeTime[0]['mik_key'] != null) {
            _ERROR('000002', '发送失败,操作频繁,请稍后尝试');
        } else {
            //验证五分钟以内不产生新的验证码
            $sql_codeNews = "SELECT mik_key 
                            FROM idt_mobilekey WHERE mik_mobile='{$data['Mobile']}' 
                            AND mik_state='0' AND ROUND((UNIX_TIMESTAMP('{$upTimes}')-UNIX_TIMESTAMP(mik_cdate))/60)<=5 
                            ORDER BY mik_cdate DESC LIMIT 1";

            $ret_codeNews = $this->mysqlQuery($sql_codeNews, "all");
            //生成短信验证码
            if ($ret_codeNews[0]['mik_key'] == null OR $ret_codeNews[0]['mik_key'] == null) {
                $data['Code'] = rand(100001, 999999);
            } else {
                $data['Code'] = $ret_codeNews[0]['mik_key'];
            }

            //发送验证码
            $where['mik_mobile'] = $data['Mobile'];//手机号码
            $where['mik_type'] = $keyType;//验证类型(0.登录 1.注册 2.找回密码,3 验证手机)
            $where['mik_key'] = $data['Code'];//验证码
            $where['mik_cdate'] = $upTimes;//创建时间
            if ($this->mysqlInsert('idt_mobilekey', $where)) {
                return $data;
            } else {
                return false;
            };
        }
    }

    /**
     *
     * @param $mobile
     *
     * @return array|string
     */
    private function __checkHasEmail($mobile)
    {
        $sql = "select u_mail from idt_user WHERE u_mobile='{$mobile}'";
        $ret = $this->mysqlQuery($sql, 'all');
        return $ret[0]['u_mail'];
    }

    private function __checkHasEmailByMail($mail)
    {
        $sql = "select u_mail from idt_user WHERE u_mail='{$mail}'";
        $ret = $this->mysqlQuery($sql, 'all');
        return $ret[0]['u_mail'];
    }

    /**
     * check mobile sql
     *
     * @param $mobile
     *
     * @return array|string
     */
    private function __checkMobile($mobile)
    {
        $sql = "SELECT u_id,u_mobile FROM idt_user WHERE u_mobile='{$mobile}'";
        return $this->mysqlQuery($sql, 'all');
    }

    /**
     * check wechat opid sql
     *
     * @param $wxopid
     *
     * @return array|string
     */
    private function __checkWXOPID($wxopid)
    {
        $sql = "SELECT u_id, u_wxname, u_wxunid, u_wxopid FROM idt_user WHERE 1=1 AND u_wxopid='{$wxopid}' ";
        return $this->mysqlQuery($sql, 'all');
    }

    /**
     * check wechat unid sql
     *
     * @param $wxunid
     *
     * @return array|string
     */
    private function __checkWXUNID($wxunid)
    {
        $sql = "SELECT u_id, u_wxname, u_wxunid, u_wxopid FROM idt_user WHERE 1=1 AND u_wxunid='{$wxunid}' ";
        return $this->mysqlQuery($sql, 'all');
    }

    /**
     * check token sql
     *
     * @param $u_token
     *
     * @return array|string
     */
    private function __checkToken($u_token, $u_id)
    {
        $sql = "SELECT u_id,u_token FROM idt_user WHERE u_token='{$u_token}' and u_id='{$u_id}'";
        return $this->mysqlQuery($sql, 'all');
    }


    /**
     * check company state
     *
     * @param $cpy_id
     *
     * @return array|string
     */
    private function __checkCompanyState($cpy_id)
    {
        $sql = "SELECT cpy_state FROM idt_company WHERE 1=1 AND cpy_id='{$cpy_id}'";
        return $this->mysqlQuery($sql, 'all');
    }

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
     * change user status
     *
     * @param $u_id
     * @param $state
     */
    private function __changeUserState($u_id, $state)
    {
        $where['u_state'] = $state; //用户状态(0正常 1冰结)
        $where['u_edate'] = date("Y-m-d H:i:s"); //最后登录时间
        $id_thawUser = " u_id='" . $u_id . "'";//用户GUID
        $ret = $this->mysqlEdit('idt_user', $where, $id_thawUser);
        //验证并返回响应结果
        if ($ret == 1) {
            _SUCCESS('000000', '冻结成功');
        } else {
            _ERROR('000002', '冻结失败');
        }
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
        $sql = "SELECT cpy_id FROM idt_user WHERE u_id='{$u_id}'";
        return $this->mysqlQuery($sql, 'all');
    }

    /**
     * check user company state by user id
     *
     * @param $u_id
     *
     * @return bool
     */
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

    /**
     * add guest
     *
     * @param        $data
     * @param        $upToken
     * @param        $upTimes
     * @param string $type
     *
     * @return array|int|string
     */
    private function __addGuest($data, $upToken, $upTimes, $type = 'mobile')
    {
        write_to_log(json_encode($data), '_debug');
        if ($type == 'mobile') {
            $addUser = [
                'u_id' => getGUID(),
                'u_mobile' => $data['loginMobile'],
                'u_permissions' => 0,
                'u_token' => $upToken,
                'u_cdate' => $upTimes,
                'u_edate' => $upTimes
            ];
        } else {

            $addUser = [
                'u_id' => getGUID(),
                'u_mobile' => $data['loginMobile'],
                'u_wxname' => $data['wxName'],
                'u_wxopid' => $data['wxOpenid'],
                'u_wxunid' => $data['wxUnionid'],
                'u_permissions' => 0, //用户身份(0游客 1公司用户)
                'u_token' => $upToken,
                'u_cdate' => $upTimes,
                'u_edate' => $upTimes
            ];
        }
        return $this->mysqlInsert('idt_user', $addUser);
    }

    /**
     * ird 添加用户
     * @param $data
     * @param $upToken
     * @param $upTimes
     * @param string $type
     * @return array|int|string
     */
    private function __addUserFromIrd($data, $upToken, $upTimes, $type = 'mobile')
    {
        if (!empty($data['ird_user'])) {

            if (!empty($data['ird_user']['iUserID'])) {

                //判断是否绑定成功
                $sql_keyNum = "SELECT COUNT(1) keyNum FROM idt_user WHERE u_product_key='{$data['ird_user']['iUserID']}'";
                $ret_keyNum = $this->mysqlQuery($sql_keyNum, "row");

                //绑定失败,该产品KEY已绑定其它账号
                if ($ret_keyNum[0] > 0) {
                    _ERROR('000002', '绑定失败,该用户已绑定其它账号');
                }

                if (empty($data['ird_user']['CompanyID'])) {
                    $this->__addGuest($data, $upToken, $upTimes, $type);
                } else {
                    $find_cpy_sql = "SELECT ird_ca_id,cpy_id FROM idt_company WHERE idt_company.ird_ca_id = '{$data['ird_user']['CompanyID']}'";
                    $cpy_id = $this->mysqlQuery($find_cpy_sql, 'all');
                    if (!empty($cpy_id) and !empty($cpy_id[0]['cpy_id'])) {

                        //find cpy id
                        if ($type == 'mobile') {
                            $add_user = [
                                'u_id' => getGUID(),
                                'cpy_id' => $cpy_id[0]['cpy_id'],
                                'u_mobile' => $data['loginMobile'],
                                'u_permissions' => 0,//用户身份(0游客 1公司用户)
                                'u_token' => $upToken,
                                'u_cdate' => $upTimes,
                                'u_edate' => $upTimes,
//                                'u_product_key' => $data['ird_user']['iUserID']
                            ];
                        } else {
                            $add_user = [
                                'u_id' => getGUID(),
                                'u_mobile' => $data['loginMobile'],
                                'cpy_id' => $cpy_id[0]['cpy_id'],
                                'u_wxname' => $data['wxName'],
                                'u_wxopid' => $data['wxOpenid'],
                                'u_wxunid' => $data['wxUnionid'],
                                'u_permissions' => 0, //用户身份(0游客 1公司用户)
                                'u_token' => $upToken,
                                'u_cdate' => $upTimes,
                                'u_edate' => $upTimes,
//                                'u_product_key' => $data['ird_user']['iUserID']
                            ];
                        }

                        return $this->mysqlInsert('idt_user', $add_user);

                    } else {

                        //没有找到对应公司，当作游客处理
                        $this->__addGuest($data, $upToken, $upTimes);
                    }

                }

            } else {
                //没有ird user id 当作游客处理
                $this->__addGuest($data, $upToken, $upTimes);
            }

        }

    }


    /**
     * get user info by irUserID
     *
     * @param $irUserID
     * @return array|string
     */
    private function __getUserInfo($irUserID)
    {
        $sql = "SELECT * FROM idt_user WHERE u_product_key='{$irUserID}'";
        return $this->mysqlQuery($sql, 'all');
    }

    /**
     * get ir user product list
     *
     * @param $irUserID
     * @return mixed
     */
    private function __getIRUserList($irUserID)
    {
        $irUserID = ['iUserID' => $irUserID];
        $ret = $this->request()->_curlRADPost(IRD_SERVER_URL, ['v' => fnEncrypt(json_encode($irUserID), KEY)]);
        return json_decode($ret, true);
    }

    /**
     *
     * verify key for ir login
     *
     * @param $data
     * @return bool
     */
    private function __verifyKeyForIRLogin($data)
    {
        if (is_array($data)) {

            if (
                !isset($data['pdtID']) ||
                !isset($data['irUserID']) ||
                !isset($data['date']) ||
                !isset($data['key'])
            ) {
                return false;
            }

            if (
                empty($data['pdtID']) ||
                empty($data['irUserID']) ||
                empty($data['date']) ||
                empty($data['key'])
            ) {
                return false;
            }

            $clientDate = new DateTime($data['date']);
            $dateObj = new DateTime();
            if ($dateObj->format('Ymd') !== $clientDate->format('Ymd')) {
                return false;
            }

            $key = md5(KEY . $data['irUserID'] . $data['pdtID'] . $dateObj->format('Ymd'));

            return $key == $data['key'];

        } else {
            return false;
        }
    }

    /**
     * get pdtID
     *
     * @param $ppID
     * @return array|string
     */
    private function __getPdtID($ppID)
    {
        $sql = "SELECT pdt_id FROM idt_ircp_con_idatap WHERE pp_id='{$ppID}'";
        return $this->mysqlQuery($sql, 'all');
    }

    /**
     * get ppid
     * @param $pdtID
     * @return array|string
     */
    private function __getPPID($pdtID)
    {
        $sql = "SELECT pp_id FROM idt_ircp_con_idatap WHERE pdt_id='{$pdtID}'";
        return $this->mysqlQuery($sql, 'all');
    }

    /**
     * send code
     * @param $sender
     * @param $code
     * @throws phpmailerException
     */
    private function __sendCode($sender, $code)
    {
        $phpMail = new PHPMailer;
        $phpMail->isSMTP();
        $phpMail->Host = EMAIL_SMTPSERVER;
        $phpMail->SMTPAuth = true;
        $phpMail->Username = EMAIL_SMTPUSER;
        $phpMail->Password = EMAIL_SMTPPASS;
        $phpMail->SMTPSecure = 'tls';
        $phpMail->Port = EMAIL_SMTPSERVERPORT;
        $phpMail->setFrom(EMAIL_SMTPUSER, 'iResearchGroup');
        $phpMail->addAddress($sender);
        $phpMail->isHTML(true);
        $phpMail->CharSet = "UTF-8";
        $phpMail->Subject = '[iRD] Authentication Code';
//        $phpMail->Body = "The recent authentication code of acessing iRD is <span style='color: red'>{$code}</span>, which will be expired in 5 mins </br>";
//        $phpMail->Body = $phpMail->Body . "你的验证码是： <span style='color: red'>{$code}</span>, 并且五分钟过期 \n";
        $phpMail->Body = "
        <p>尊敬的艾瑞数据用户：</p></br>

        <p>您好，</p>
        
        <p>您的登录验证码为：{$code}，请在5分钟内使用该验证码登录。如非本人操作请忽略这封邮件！</p>
        
        <p>Your validation code is {$code}, which will be expired in 5 minues. Please ignore this email if you don't know what it is for.</p>
        
        <p>祝您生活愉快！</p>
        
        <p>Regards & Best wishes!</p>
        
         <p>艾瑞数据运营团队</p>
        
        <p>iResearch Tech Team.</p>
        ";

        if (!$phpMail->send()) {
            write_to_log("{$sender} sent error " . $phpMail->ErrorInfo, '_mail');
        } else {
            write_to_log("{$sender} is sent!", '_mail');
        }
    }

    /**
     * binding ird when ird to irv
     *
     * @param $u_id
     * @param $ird_user
     * @return bool
     */
    private function __bindingIRD($u_id, $ird_user)
    {
        //
        if (!$this->__checkIRDBinding($ird_user['iUserID'])) {
            write_to_log('[binding fails] u_id:' . $u_id . ' ird_u_id: ' . $ird_user['iUserID'], '_from_ird');
            return false;
        } else {
            if ($this->__checkEmail($ird_user['UserName'])) {
                $this->mysqlEdit('idt_user', ['u_mail' => $ird_user['UserName']], "u_id='{$u_id}'");
                write_to_log('[email SUCCESS]  u_id ' . $u_id . ', ird_u_id: ' . $ird_user['iUserID'], '_from_ird');
            } else {
                write_to_log('[email fails]  u_id ' . $u_id . ', ird_u_id: ' . $ird_user['iUserID'], '_from_ird');
            }
            if ($this->__checkUserName($u_id)) {
                $this->mysqlEdit('idt_user', ['u_name' => $ird_user['TrueName']], "u_id='{$u_id}'");
                write_to_log('[name SUCCESS]  u_id ' . $u_id . ', ird_u_id: ' . $ird_user['iUserID'], '_from_ird');
            } else {
                write_to_log('[name fails]  u_id ' . $u_id . ', ird_u_id: ' . $ird_user['iUserID'], '_from_ird');
            }
            write_to_log('[binding SUCCESS]  u_id ' . $u_id . ', ird_u_id: ' . $ird_user['iUserID'], '_from_ird');
            return $this->mysqlEdit('idt_user', ['u_product_key' => $ird_user['iUserID']], "u_id='{$u_id}'");
        }


    }

    /**
     * @param $u_id
     * @return bool
     */
    private function __checkUserName($u_id)
    {
        $sql = "SELECT u_name FROM idt_user WHERE u_id='{$u_id}'";
        $ret = $this->mysqlQuery($sql, "all");
        $ret[0]['u_name'] = trim($ret[0]['u_name']);
        if (empty($ret[0]['u_name'])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $mail
     * @return bool
     */
    private function __checkEmail($mail)
    {
        $sql = "SELECT u_permissions FROM idt_user WHERE u_mail='{$mail}'";
        $ret = $this->mysqlQuery($sql, "all");
        if (count($ret) > 0) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * change to member
     *
     * @param $u_id
     * @param $cpy_id
     * @return array|bool|string
     */
    private function __changeToMember($u_id, $cpy_id)
    {
        //check is member
        $sql = "SELECT COUNT(*) AS co FROM idt_user WHERE u_permissions=1 AND u_id='{$u_id}'";
        $get_value = $this->mysqlQuery($sql, 'all');
        write_to_log('chang to member sql : ' . $sql, '_from_ird');
        write_to_log(json_encode('get result: ' . $get_value, '_from_id'));

        if ($get_value[0]['co'] <= 0) {
            $update_data = [
                'u_permissions' => '1',
                'cpy_id' => $cpy_id,
                'dev_id' => $cpy_id];

            write_to_log('CHANGE TO MEMBER check return: ' . json_encode($update_data), '_from_ird');
            return $this->mysqlEdit('idt_user', $update_data, "u_id='{$u_id}'");
        } else {
            //已经正式用户
            return false;
        }

    }

    /**
     * get cpy id from ird
     *
     * @param $ird_cpy_id
     * @return bool
     */
    private function __getCpyFromIRD($ird_cpy_id)
    {
        $sql = "SELECT cpy_id, cpy_cname FROM idt_company WHERE ird_ca_id='{$ird_cpy_id}'";
        $cpy_id = $this->mysqlQuery($sql, 'all');
        write_to_log('get cpy from ird sql: ' . $sql, '_from_ird');
        if (!empty($cpy_id[0]['cpy_id'])) {
            write_to_log('get cpy id: ' . json_encode($cpy_id[0]), '_from_ird');
            return $cpy_id[0];
        } else {
            write_to_log('get cpy id: ' . 'false', '_from_ird');
            return false;
        }
    }

    /**
     * check ird binding for ird_user_id
     *
     * @param $ca_id
     * @return bool
     */
    private function __checkIRDBinding($ca_id)
    {
        $sql = "SELECT count(*) AS ca FROM idt_user WHERE u_product_key='{$ca_id}'";
        write_to_log('check binding sql: ' . $sql, '_from_ird');
        $ret = $this->mysqlQuery($sql, 'all');
        write_to_log('check return: ' . json_encode($ret), '_from_ird');
        return $ret[0]['ca'] <= 0;
    }

    /**
     * check mobile key
     *
     * @param $mobile
     * @param $key
     * @param int $type
     * @return array|string
     */
    private function __checkMobileKey($mobile, $key, $type = 0)
    {
        $upTimes = date("Y-m-d H:i:s");
        $sql = "SELECT mik_id FROM idt_mobilekey 
            WHERE idt_mobilekey.mik_mobile='{$mobile}' AND idt_mobilekey.mik_key='{$key}' 
            AND idt_mobilekey.mik_state=0 AND idt_mobilekey.mik_type='{$type}' AND 
            ROUND((UNIX_TIMESTAMP('{$upTimes}')-UNIX_TIMESTAMP(mik_cdate))/60)<=5";

        $ret = $this->mysqlQuery($sql, 'all');
        return $ret;
    }

    /**
     * update mobile key
     *
     * @param $mik_id
     * @return array|string
     */
    private function __updateMobileKey($mik_id)
    {
        return $this->mysqlEdit('idt_mobilekey', ['mik_state' => '1'], ['mik_id' => $mik_id]);
    }

    /**
     * has the user?
     * @param $mobile
     * @return array|string
     */
    private function __hasUser($mobile)
    {
        $sql = "SELECT * FROM idt_user WHERE u_mobile='{$mobile}'";
        return $this->mysqlQuery($sql, 'all');

    }


    /**
     * add employee
     *
     * @param $data
     */
    private function __addEmployee($data)
    {
        $hasUser = $this->__hasUser($data['mobile']);
        $hasMail = $this->__hasMail($data['u_mail']);
        //手机验证码
//        $checkMobile = $this->__checkMobileKey($data['mobile'], $data['mobile_key'], 3);

        if (!$this->__check_mail_suffix($data)) {
            _ERROR('000003', '所填写邮箱不包好在预设的邮箱域名范围之内');
        }

//        if (!empty($checkMobile)) {

//            $this->__updateMobileKey($checkMobile[0]['mik_id']);

        if (empty($hasUser)) {
            //添加用户
            $uid = getGUID();
            $ret = $this->mysqlInsert('idt_user', [
                    'u_id' => $uid,
                    'u_name' => $data['uname'],
                    'u_position' => $data['position'],
                    'u_permissions' => '1',
                    'u_state' => '0',
                    'u_department' => $data['department'],
                    'u_mobile' => $data['mobile'],
                    'u_mail' => $data['u_mail'],
                    'cpy_id' => $data['cpy_id'],
                    'u_cdate' => $upTimes = date("Y-m-d H:i:s")
                ]
            );

            if ($ret) {
                _SUCCESS('000000', 'ok', ['u_id' => $uid,]);
            } else {
                _ERROR('000003', '添加失败');
            }

        } else {
            //修改用户
            if ($hasUser[0]['cpy_id'] == $data['cpy_id']) {
                _ERROR('000003', '该公司下，此用户已存在');
            } elseif ($hasUser[0]['cpy_id'] == null || $hasUser[0]['cpy_id'] == 0) {
                $sql = "update idt_licence set u_id = null,lic_author_uid='{$data['userID']}'  where u_id = '{$hasUser[0]['u_id']}'";
                $ret = $this->mysqlQuery($sql);
                if (!$ret) {
                    _ERROR('000003', '权限授权失败');
                }
                $updateUser = $this->mysqlEdit('idt_user', ['cpy_id' => $data['cpy_id'], 'u_permissions' => '1', 'u_mail' => $data['u_mail']], ['u_id' => $hasUser[0]['u_id']]);

                if ($updateUser) {
                    _SUCCESS('000000', 'ok');
                } else {
                    _ERROR('000003', '更新用户失败');
                }
            } else {
                _ERROR('000003', '该手机号系统中已存在！');
            }

        }
//        } else {
//            _ERROR('000001', '验证码失败');
//        }
    }

    /**
     * check mail suffix
     * @param $data
     * @return bool
     */
    private function __check_mail_suffix($data)
    {
        if (empty($data['u_mail'])) {
            return true;
        }
        $sql = "select cpy_mail_suffix from idt_company where cpy_id='{$data['cpy_id']}'";
        $cpy_mail_suffix = $this->mysqlQuery($sql, 'all');
        $mail_suffix = explode(",", $cpy_mail_suffix[0]['cpy_mail_suffix']);
        $array = explode("@", $data['u_mail']);
        $user_mail_suffix = "@" . $array[1];
        $state = false;
        foreach ($mail_suffix as $key => $value) {
            if ($mail_suffix[$key] == $user_mail_suffix) {
                $state = true;
            }
        }
        return $state;
    }

    /**
     * get user product list
     *
     * @param $userID
     * @return array|string
     */
    private function __getUserProductList($userID)
    {

        $lic_sql = "SELECT
                      idt_licence.u_id,
                      idt_licence.cpy_id,
                      idt_licence.pdt_id,
                      idt_subproduct.pc_start_time,
                      idt_subproduct.mobile_start_time,
                      idt_subproduct.ott_start_time,
                      idt_subproduct.mobile_due_time,
                      idt_subproduct.pc_due_time,
                      idt_subproduct.ott_due_time,
                      idt_permissions_number.start_date,
                      idt_permissions_number.end_date,
                      idt_product.pdt_state,
                      idt_product.pdt_name,
                      idt_product.pdt_ename,
                      now() AS now
                    FROM idt_licence
                      LEFT JOIN idt_product ON idt_product.pdt_id = idt_licence.pdt_id
                      LEFT JOIN idt_subproduct
                        ON idt_subproduct.licence_key = idt_licence.licence_key
                      LEFT JOIN idt_permissions_number ON idt_permissions_number.pdt_id = idt_subproduct.pdt_id
                    WHERE idt_licence.u_id = '{$userID}'
                          AND idt_licence.state = 1 
                          AND pdt_state = 0 AND start_date <= now() AND end_date >= now() AND
      idt_permissions_number.cpy_id = idt_licence.cpy_id;";
        $ret = $this->mysqlQuery($lic_sql, 'all');

        $result = array();
        foreach ($ret as $key => $value) {
            if (isset($result[$value['pdt_id']])) {
                $old_key = $result[$value['pdt_id']]['key'];
                if (!empty($value['pc_due_time'])) {
                    $ret[$old_key]['pc_start_time'] = $value['pc_start_time'];
                    $ret[$old_key]['pc_due_time'] = $value['pc_due_time'];
                } elseif (!empty($value['mobile_due_time'])) {
                    $ret[$old_key]['mobile_start_time'] = $value['mobile_start_time'];
                    $ret[$old_key]['mobile_due_time'] = $value['mobile_due_time'];
                } elseif (!empty($value['ott_due_time'])) {
                    $ret[$old_key]['ott_start_time'] = $value['ott_start_time'];
                    $ret[$old_key]['ott_due_time'] = $value['ott_due_time'];
                }
                unset($ret[$key]);
            } else {
                $result[$value['pdt_id']]['key'] = $key;
                $result[$value['pdt_id']]['pdt_id'] = $value['pdt_id'];
            }
        }
        return array_values($ret);
    }

    /**
     * check bind ip
     * @param $u_id
     * @param $ip
     * @return bool
     */
    private function __checkBind($u_id, $ip)
    {
        write_to_log('user id: ' . $u_id . ', ip: ' . $ip, 'login_ip');
        $sql = "select bind_ip FROM idt_user where u_id = '{$u_id}'";
        $ret = $this->mysqlQuery($sql, 'all');
        write_to_log(json_encode($ret), '_data');
        if (count($ret) <= 0) {
            return false;
        } else {
            if (empty($ret[0]['bind_ip'])) {
                return true;
            } else {
                $ip_list = explode(',', $ret[0]['bind_ip']);

                return in_array($ip, $ip_list);
            }

        }


    }


    /**
     * sms template
     *
     * @param $code
     * @param $mobile
     * @param $templID
     * @return array
     */
    private function __textTemple($code, $mobile, $templID)
    {
        $ret = ['tpl_id' => $templID,
            'tpl_value' =>
                urlencode('#code#') . '=' . urlencode($code),
            'apikey' => NATION_API, 'mobile' => $mobile
        ];
        return $ret;
    }

}