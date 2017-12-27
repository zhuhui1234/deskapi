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
class PermissionsModel extends AgentModel
{
    private $userModel;

    function __construct($classname)
    {
        $this->userModel = Model::instance('User');
        parent::__construct($classname);
    }

    //获取菜单导航
//    public function getMenuList($data)
    public function getMenuList()
    {
        //获取菜单导航&报表GUID
        $sql = "SELECT dba.meu_id id,dba.meu_name,dba.meu_describe,dba.meu_type,dba.meu_sid sid,dbb.cfg_guid FROM idt_menu dba 
                LEFT JOIN idt_config dbb ON (dba.meu_id=dbb.meu_id) 
                ORDER BY dba.meu_id";
        $ret = $this->mysqlQuery($sql, "all");

        //格式化菜单数组
        $cleaningList = array('menuName' => 'meu_name', 'menuDescribe' => 'meu_describe', 'menuType' => 'meu_type', 'reportID' => 'cfg_guid');
        $rs['dataList'] = _findChildren($ret, 0, $cleaningList);

        //返回响应结果
        _SUCCESS('000000', '查询成功', $rs);
    }

    public function getProduct($data)
    {
        $sql = "SELECT pdt_name, pdt_ename FROM idatadb.idt_product WHERE pdt_id='{$data['pdt_id']}'";
        $ret = $this->mysqlQuery($sql, 'all');
        return $ret;
    }


    //获取用户权限
    public function getPermissionsList($data)
    {
        //查询初始化条件
        $data['orderByColumn'] == null ? $orderByColumn = 'permissions DESC,state DESC' : $orderByColumn = $data['orderByColumn']; //排序字段
        $data['orderByType'] == null ? $orderByType = '' : $orderByType = $data['orderByType']; //排序方式
        $data['pageSize'] == null ? $pageSize = '10' : $pageSize = $data['pageSize']; //查询数据
        $data['pageNo'] == null ? $pageNo = '0' : $pageNo = ($data['pageNo'] - 1) * $pageSize; //查询页数
        $data['keyword'] == null ? $keyword = '' : $keyword = " AND dba.u_mobile LIKE '%" . $data['keyword'] . "%' OR dba.u_name LIKE '%" . $data['keyword'] . "%'"; //查询条件

        //获取用户权限
        $sql = "SELECT dba.u_mobile mobile,dba.u_permissions permissions,dbb.prs_state state " .
            "FROM idt_user dba " .
            "LEFT JOIN idt_permissions dbb ON (dba.u_id=dbb.u_id AND dbb.meu_id=1) " .
            "WHERE 1=1{$keyword} " .
            "ORDER BY {$orderByColumn} {$orderByType} " .
            "LIMIT {$pageNo},{$pageSize}";
        $ret = $this->mysqlQuery($sql, "all");

        //格式化菜单数组
        foreach ($ret as $a => $v) {
            $rs['dataList'][$a]['mobile'] = $v['mobile'];
            $rs['dataList'][$a]['permissions'] = $v['permissions'];
            $rs['dataList'][$a]['state'] = $v['state'];
        }

        //返回响应结果
        if (isset($rs)) {
            _SUCCESS('000000', '查询成功', $rs);
        } else {
            _ERROR('000001', '查询异常');
        }
    }

    /**
     * 获取首页菜单导航
     *
     * @param $data
     */
    public function getHomeMenu($data)
    {

        $cptID = $this->userModel->getUserOfCompany($data['userID']);
        $cptID = $cptID[0]['cpy_id'];


        if ((int)$this->userModel->checkUserRole($data['userID']) > 0) {
            //no guest
            $ret = $this->__getOfficialMenuSQL($data);
            $role = 'member';

        } else {
            //guest
            $ret = $this->__getGustMenuSQL();
            $role = 'guest';
        }

        //转义功能标签
        foreach ($ret as $a => $v) {
            $ret[$a]['pdt_label'] = json_decode($v['pdt_label'], true);
        }

        //格式化菜单数组
        $cleaningList = [
            'menuID' => 'id', //pdt_id
            'menuName' => 'pdt_name',
            'menuEName' => 'pdt_ename',
            'menuIntro' => 'pdt_intro',
            'functionLabel' => 'pdt_label',
            'curl' => 'pdt_url',
            'versionType' => 'pdt_vtype',
            'series' => 'pdt_series',
            'menuVersion' => 'pdt_version',
            'pState' => 'prs_state',
            'pcState' => 'prsc_state'
        ];

//        pr($ret);
        $rs['dataList'] = _findChildren($ret, 0, $cleaningList);
//        pr($rs['dataList']);
//        exit();

        $ret_irdID = $this->__getBindingIRD($data);

        //初始化空IRD权限LIST
        $ret_irdKey_format = array();
        //验证产品ID
        if ($ret_irdID[0]['u_product_key'] != null AND $ret_irdID[0]['u_product_key'] != "") {

            //绑定的老产品

            $getRedis = $this->redisHere(VERSION . '_' . $data['userID'] . '_ird');

//            write_to_log(json_encode($getRedis), '_redis');
            if (!$getRedis['0']) {
                $where_irdKey['iUserID'] = $ret_irdID[0]['u_product_key'];
                $ret_irdKey = $this->request()->_curlRADPost(IRD_SERVER_URL, ['v' => fnEncrypt(json_encode($where_irdKey), KEY)]);
//                write_to_log($ret_irdKey,'_redis');
                $this->redis()->setex(VERSION . '_' . $data['userID'] . '_ird', REDIS_TIME_OUT, $ret_irdKey);
                $ret_irdKey = json_decode($ret_irdKey, JSON_UNESCAPED_UNICODE);

            } else {
                $ret_irdKey = json_decode($getRedis['1'], JSON_UNESCAPED_UNICODE);

            }
            //获取IRD权限LIST


            //格式化IRD权限LIST
            foreach ($ret_irdKey['pplist'] as $a_format => $v_format) {
                $ret_irdKey_format[$v_format['ppname']] = $v_format;
            }

            //追加产品权限
            foreach ($rs['dataList'] as $a => $v) {
                if ($v['menuName'] == '艾瑞睿见') {
                    foreach ($v['lowerTree'] as $a2 => $v2) {
                        foreach ($v2['lowerTree'] as $a3 => $v3) {
                            //过滤老产品
                            if ($v3['versionType'] === "2") {
                                if ($v3['menuEName'] == 'iUserTracker') {
                                    if ($ret_irdKey_format['iut']) {
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 1;
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = $ret_irdKey_format['iut']['ppurl'] . "?guid=" . $ret_irdKey['iRGuid'];
                                    } else {
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 0;
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "";
                                    }
                                } else if ($v3['menuEName'] == 'iAdTracker') {
                                    if ($ret_irdKey_format['iadt']) {
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 1;
//                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = $ret_irdKey_format['iadt']['ppurl']."?guid=".$ret_irdKey['iRGuid'];
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "http://madt.irs01.net/ProductSelection.aspx?guid=" . $ret_irdKey['iRGuid'];
                                    } else {
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 0;
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "";
                                    }
                                } else if ($v3['menuEName'] == 'iVideoTracker') {
                                    if ($ret_irdKey_format['ivt']) {
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 1;
//                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = $ret_irdKey_format['ivt']['ppurl']."?guid=".$ret_irdKey['iRGuid'];
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "http://ivt.itracker.cn/?guid=" . $ret_irdKey['iRGuid'];
                                    } else {
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 0;
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "";
                                    }
                                } else if ($v3['menuEName'] == 'mUserTracker') {
                                    if ($ret_irdKey_format['mut']) {
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 1;
//                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = $ret_irdKey_format['mut']['ppurl']."?guid=".$ret_irdKey['iRGuid'];
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "http://musertracker.itracker.cn/LLogin.aspx?guid=" . $ret_irdKey['iRGuid'];
                                    } else {
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 0;
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "";
                                    }
                                } else if ($v3['menuEName'] == 'iMediaMatix') {
                                    if ($ret_irdKey_format['imm']) {
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 1;
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = $ret_irdKey_format['imm']['ppurl'] . "?guid=" . $ret_irdKey['iRGuid'];
                                    } else {
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 0;
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "";
                                    }
                                } else if ($v3['menuEName'] == 'mAdTracker') {
                                    if ($ret_irdKey_format['madt']) {
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 1;
//                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = $ret_irdKey_format['madt']['ppurl']."?guid=".$ret_irdKey['iRGuid'];
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "http://iadt-alpha.chinacloudsites.cn/ws_login.aspx?ProductSelection=ProductSelection&guid=" . $ret_irdKey['iRGuid'];
                                    } else {
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 0;
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "";
                                    }
                                } else if ($v3['menuEName'] == 'iEcTracker') {
                                    if ($ret_irdKey_format['ect']) {
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 1;
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = $ret_irdKey_format['ect']['ppurl'] . "?guid=" . $ret_irdKey['iRGuid'];
                                    } else {
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 0;
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "";
                                    }
                                } else if ($v3['menuEName'] == 'imBlogTracker') {
                                    if ($ret_irdKey_format['imbt']) {
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 1;
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = $ret_irdKey_format['imbt']['ppurl'] . "?guid=" . $ret_irdKey['iRGuid'];
                                    } else {
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 0;
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "";
                                    }
                                } else if ($v3['menuEName'] == 'TargetPlus') {
                                    if ($ret_irdKey_format['tgp']) {
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 1;
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = $ret_irdKey_format['tgp']['ppurl'] . "?guid=" . $ret_irdKey['iRGuid'];
                                    } else {
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 0;
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "";
                                    }
                                } else if ($v3['menuEName'] == 'iUserTracker-en') {
                                    if ($ret_irdKey_format['iut-en']) {
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 1;
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = $ret_irdKey_format['iut-en']['ppurl'] . "?guid=" . $ret_irdKey['iRGuid'];
                                    } else {
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 0;
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "";
                                    }
                                } else if ($v3['menuEName'] == 'mUserTracker-en') {
                                    if ($ret_irdKey_format['mut-en']) {
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 1;
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = $ret_irdKey_format['mut-en']['ppurl'] . "?guid=" . $ret_irdKey['iRGuid'];
                                    } else {
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 0;
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "";
                                    }
                                } else if ($v3['menuEName'] == 'loyaltyPlus') {
                                    if ($ret_irdKey_format['lps']) {
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 1;
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = $ret_irdKey_format['lps']['ppurl'] . "?guid=" . $ret_irdKey['iRGuid'];
                                    } else {
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 0;
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "";
                                    }
                                } else if ($v3['menuEName'] == 'EcommercePlus') {
                                    if ($ret_irdKey_format['ecp']) {
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 1;
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = $ret_irdKey_format['ecp']['ppurl'] . "?guid=" . $ret_irdKey['iRGuid'];
                                    } else {
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 0;
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "";
                                    }
                                } else if ($v3['menuEName'] == 'iUserServey') {
                                    if ($ret_irdKey_format['ius']) {
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 1;
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = $ret_irdKey_format['ius']['ppurl'] . "?guid=" . $ret_irdKey['iRGuid'];
                                    } else {
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 0;
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "";
                                    }
                                } else if ($v3['menuEName'] == 'EcommercePlus-en') {
                                    if ($ret_irdKey_format['ecp-en']) {
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 1;
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = $ret_irdKey_format['ecp-en']['ppurl'] . "?guid=" . $ret_irdKey['iRGuid'];
                                    } else {
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 0;
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "";
                                    }
                                } else if ($v3['menuEName'] == 'iMediaMatix-en') {
                                    if ($ret_irdKey_format['imm-en']) {
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 1;
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = $ret_irdKey_format['imm-en']['ppurl'] . "?guid=" . $ret_irdKey['iRGuid'];
                                    } else {
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 0;
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "";
                                    }
                                } else if ($v3['menuEName'] == 'mGameTracker') {
                                    if ($ret_irdKey_format['mgt']) {
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 1;
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = $ret_irdKey_format['mgt']['ppurl'] . "?guid=" . $ret_irdKey['iRGuid'];
                                    } else {
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 0;
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "";
                                    }
                                } else if ($v3['menuEName'] == 'iAdMatrix') {
                                    if ($ret_irdKey_format['iadm']) {
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 1;
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = $ret_irdKey_format['iadm']['ppurl'] . "?guid=" . $ret_irdKey['iRGuid'];
                                    } else {
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 0;
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "";
                                    }
                                } else if ($v3['menuEName'] == 'mStoreTracker') {
                                    if ($ret_irdKey_format['mst']) {
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 1;
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = $ret_irdKey_format['mst']['ppurl'] . "?guid=" . $ret_irdKey['iRGuid'];
                                    } else {
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 0;
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "";
                                    }
                                } else if ($v3['menuEName'] == 'ECTracker') {
                                    if ($ret_irdKey_format['ect']) {
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 1;
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = $ret_irdKey_format['ect']['ppurl'] . "?guid=" . $ret_irdKey['iRGuid'];
                                    } else {
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 0;
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "";
                                    }
                                } else {
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 0;
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "";
                                }
                            } else {
//                                $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 1;
//                                var_dump($rs['dataList'][$a]['lowerTree'][$a2]['lowerTree']);
                                if ($this->__verifyLicense($cptID, $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['menuID'])) {
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] =
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['pState'] == null ? 0 : $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['pState'];
                                } else {
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 0;
                                }
                                unset($rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['pState']);
                            }
                        }
                    }
                }
            }
        } else {
            //追加无绑定产品权限
            foreach ($rs['dataList'] as $a => $v) {
                if ($v['menuName'] == '艾瑞睿见') {
                    foreach ($v['lowerTree'] as $a2 => $v2) {
                        foreach ($v2['lowerTree'] as $a3 => $v3) {
                            //过滤老产品
                            if ($v3['versionType'] !== "2") {
                                if ($this->__verifyLicense($cptID, $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['menuID'])) {
//                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 1;
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] =
                                        $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['pState'] == null ? 0 : $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['pState'];
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = $v3['curl'];
                                } else {
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 0;
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = '';
                                }

                            } else {

                                $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 0;
                                $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "";
                            }
                        }
                    }
                }
            }
        }

        if ($_GET['debug'] == 1) {
            pr('debug');
            print_r($ret);
            pr('end debug');
        }

        $rs['role'] = $role;
        $rs['ird_guid'] = $ret_irdKey['iRGuid'];

        //返回响应结果
        _SUCCESS('000000', '查询成功', $rs);
    }

    /**
     * check user pro permissions  for BEIJING
     *
     * @param $data
     */
    public function checkUserProPer($data)
    {
        $rs = Model::instance("user")->_getUserInfoByToken($data);
        if ($rs['uid'] == null or $rs['uid'] == '') {
            _SUCCESS('000001', 'TOKEN验证失败');
        }
        _SUCCESS('000000', '验证成功', $rs);
    }

    /**
     * get permissionInfo
     *
     * @param $data ['token', 'pdt_id']
     *
     * @return string/bool
     */
    public function getPermissionInfo($data)
    {
        $userInfo = Model::instance('user')->_getUserInfoByToken($data);

        if ((OPEN_ME AND $userInfo['companyID'] == 1) and $data['pdt_id'] !== 38) {

            return $this->getPdtInfo($data['pdt_id']);

        } else {

            if (!empty($userInfo['uid']) AND !empty($userInfo['companyID']) AND !empty($data['pdt_id'])) {

                if (!isset($data['terminal'])) {

                    $data['terminal'] = null;
                }

                if ($this->__checkPermission($userInfo['uid'], $data['pdt_id'], $userInfo['companyID'], $data['terminal'])) {

                    return $this->getPdtInfo($data['pdt_id']);

                } else {

                    return false;

                }

            } else {
                return false;
            }
        }
    }

    /**
     * get permission info by user id
     *
     * @param $data
     * @return bool
     */
    public function getPermissionInfoByUserID($data)
    {
        $userInfo = Model::instance('user')->getUserInfoByUserID($data['userID']);
        if ((OPEN_ME AND $userInfo['companyID'] == 1) and $data['pdt_id'] !== 38) {
            return $this->getPdtInfo($data['pdt_id']);
        } else {
            if (!empty($userInfo['u_id']) AND !empty($userInfo['cpy_id']) AND !empty($data['pdt_id'])) {
                if ($this->__checkPermission($userInfo['u_id'], $data['pdt_id'], $userInfo['cpy_id'])) {
                    return $this->getPdtInfo($data['pdt_id']);
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }
    }

    /**
     * 根据URI 拿权限
     *
     * @param $data
     *
     * @return bool
     */
    public function getPermissionInfoByURI($data)
    {
        $userInfo = Model::instance('user')->_getUserInfoByToken($data);
        if ((OPEN_ME AND $userInfo['companyID'] == 1) and $data['pdt_id'] !== 38) {
            if (!empty($data['uri'])) {
                $pdt = $this->getPdtInfoByURI($data['uri']);
                return $this->getPdtInfo($pdt['pdt_id']);
            } else {
                return false;
            }
        } else {
            if (!empty($data['uri'])) {
                $pdt = $this->getPdtInfoByURI($data['uri']);
                if ($pdt) {
                    if (!empty($userInfo['uid']) AND !empty($userInfo['companyID']) AND !empty($pdt['pdt_id'])) {
                        if ($this->__checkPermission($userInfo['uid'], $pdt['pdt_id'], $userInfo['companyID'])) {
                            return $this->getPdtInfo($pdt['pdt_id']);
                        } else {
                            return false;
                        }
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
    }

    /**
     * 获取产品信息
     *
     * @param $pdt_id
     *
     * @return bool
     */
    public function getPdtInfo($pdt_id)
    {
        if (!empty($pdt_id)) {
            $sql = "SELECT pdt_name, pdt_url,pdt_try_cdate,pdt_try_edate FROM idt_product WHERE pdt_id='{$pdt_id}' 
                AND pdt_state=0 AND pdt_vtype=1";
            $ret = $this->mysqlQuery($sql, 'all');
            return $ret[0];
        } else {
            return false;
        }
    }

    /**
     * 通过uri获取或许产品信息
     *
     * @param $uri
     *
     * @return bool
     */
    public function getPdtInfoByURI($uri)
    {
        $uri = trim($uri);
        if (!empty($uri)) {
            $sql = "SELECT pdt_id,pdt_name,pdt_url 
                    FROM idt_product WHERE pdt_url='{$uri}'
                    AND pdt_ptype=0 AND pdt_vtype=1";

            $ret = $this->mysqlQuery($sql, 'all');
            return $ret[0];
        } else {
            return false;
        }
    }

    /**
     * apply product permission
     *
     * @param $data
     *
     * @return array|int|string
     */
    public function applyPermission($data)
    {
        $saveData = [
            'u_id' => $data['userID'],
            'cpy_id' => empty($data['companyID']) ? 0 : $data['companyID'],
            'pdt_id' => $data['pdt_id'],
            'u_name' => $data['username'],
            'state' => 0,
            'cdate' => time(),
            'u_mail' => $data['mail'],
            'cpy_cname' => $data['companyName'],
            'u_mobile' => $data['mobile'],
            'city' => $data['city'],
            'comment' => $data['comment'],
            'position' => $data['position'],
            'region' => $data['region']
        ];

        $getApplyPermission = $this->__getApplyPermission($data);

        if ($getApplyPermission[0]['co'] > 0) {
            _ERROR('40000', '之前已提交过申请');
        }

        if ((int)$this->__getProduct($data) < 0) {
            _ERROR('40000', '你申请的产品不存在');
        }

        //send mail if the user is not a guest or the guest haven't

        if (!$this->__checkNotGuest($data['userID'])) {
            //is guest
            if ($this->__checkVerifyMail($data['mail'])) {
                //no same mail then  send mail
                $saveData['check_mail'] = 1;
                $pi = $this->__enCode($data['userID'], $data['mail'], $data['pdt_id']);
                $cd = $this->__en($pi);
//                $url = 'http://irv.iresearch.com.cn/iResearchDataWeb/?m=index&a=checkMail&pi=' . $pi . '&cd=' . $cd;
//                $this->__sendMail($data['mail'], "
//                    您好，</br></br>
//                    感谢您申请使用艾瑞数据产品，请点击以下链接，验证您的邮箱：{$data['mail']}
//                    </br></br>
//
//                    <a href='{$url}' >点击链接验证</a> </br></br>
//
//                    如果你无法点击链接，可以复制改链接，访问：</br>
//                    {$url} </br>
//
//                    </br></br>
//                    艾瑞数据产品组
//
//                ", "邮箱验证邮件【系统邮件】");
                return true;
            } else {
                return false;
            }
        } else {
            //is not guest
            if (!$this->__isYourMail($data['userID'], $data['mail'])) {
                write_to_log('not your mail', 'check_mail');
                write_to_log('userID: ' . $data['userID'] . ' , ' . 'mail: ' . $data['mail']);
                $saveData['check_mail'] = 1;
                return true;
            } else {
                $saveData['check_mail'] = 1;
            }

        }

        if ($data['pdt_id'] == 38) {
            $this->__sendMail('wanghaiyan@iresearch.com.cn',
                "
                        城市: {$data['city']}</br>
                        用戶ID:  {$data['userID']}</br>
                        用戶名稱: {$data['username']} </br>
                        公司名称: {$data['companyName']} </br>
                        手机: {$data['mobile']} </br>
                        邮箱: {$data['mail']} </br>
                        产品ID:  {$data['pdt_id']} </br>
                        职位: {$data['position']} </br>
                        ");
        }

        return $this->mysqlInsert('idt_apply', $saveData);

    }

    /**
     * check code
     *
     * @param $data
     * @return array|bool|string
     */
    public function checkCode($data)
    {
        if (!empty($data['cd']) && !empty($data['pi'])) {
            if ($this->__checkCode($data['pi'], $data['cd'])) {
                $re = $this->__deCode($data['pi']);
                $checkSql = "SELECT COUNT(u_id) AS cu FROM idt_apply 
                             where 1=1 AND u_id='{$re['userID']}' AND pdt_id='{$re['pdt_id']}' 
                            AND check_mail=0 AND u_mail='{$re['u_mail']}' ";
                $co = $this->mysqlQuery($checkSql, 'all');
                if ($co[0]['cu'] > 0) {
                    $ret = $this->mysqlEdit('idt_apply',
                        ['check_mail' => 1], ['u_id' => $re['userID'], 'pdt_id' => $re['pdt_id'], 'u_mail' => $re['u_mail']]);

                    if ($ret) {
                        $this->__sendMail($re['u_mail'], "
                            你邮箱{$re['u_mail']} 已经验证通过，因此成功提交产品试用申请! 
                            </br>
                            </br>
            
                            艾瑞数据产品组
                    
                        ", "邮箱验证通过【系统邮件】");
                    }
                    return $ret;
                } else {
                    return false;
                }

            } else {
                return false;
            }
        }
    }

    public function tranIRDPdtName($ird_p_name)
    {
        $sql = "SELECT idt_pdt_id FROM idt_irdp_con_irvp WHERE ird_pdt_name='{$ird_p_name}'";
        $ret = $this->mysqlQuery($sql, 'all');
        write_to_log('tran ird pdt:' . $sql, '_from_ird');
        write_to_log('tran ird return ' . json_encode($ret), '_from_ird');

        if (!empty($ret[0]['idt_pdt_id'])) {

            write_to_log('get pdt id: ' . $ret[0]['idt_pdt_id'], '_from_ird');
            return $ret[0]['idt_pdt_id'];

        } else {

            write_to_log('no pdt id', '_from_ird');
            return false;

        }

    }

    /**
     * add permission
     * @param $pp_list
     * @param $user_obj
     * @param $ird_user_id
     * @return bool
     */
    public function addPermission($pp_list, $user_obj, $ird_user_id)
    {
        if (!empty($pp_list)) {
            foreach ($pp_list as $key => $value) {
                $pdtarr[] = $pp_list[$key]['ppname'];
                $pdttimearr[$pp_list[$key]['ppname']] = $pp_list[$key]['proexpire'];
            }
            foreach ($pp_list as $pp) {
                $pdt = $this->tranIRDPdtName($pp['ppname']);
                if ($pdt) {
//                    $checkPermission = $this->checkPermission($user_obj['cpy_id'], $pdt);
//                    write_to_log('check permission'. json_encode($checkPermission), '_from_ird');
//                    if ($checkPermission) {
                    $this->__addPdtPermission($pdtarr, $pdt, $user_obj['userid'], $user_obj['cpy_id'], $ird_user_id, $pdttimearr, $pp);
//                    }
                }
            }
            return true;
        }
        return false;

    }

    public function checkPermission($cpy_id, $pdtID)
    {
        $sql_parent = "select pdt_label from idt_product where pdt_id = {$pdtID}";
        $ret_parent = $this->mysqlQuery($sql_parent, 'all');
        if (!empty($ret_parent[0]['pdt_label'])) {
            $rq = json_decode($ret_parent[0]['pdt_label'], true);
            $sql = "SELECT COUNT(pdt_id) as co_pdt FROM idt_permissions_number where cpy_id='{$cpy_id}' and pdt_id='{$rq['parentID']}'";
        } else {
            $sql = "SELECT COUNT(pdt_id) as co_pdt FROM idt_permissions_number where cpy_id='{$cpy_id}' and pdt_id='{$pdtID}'";
        }
        $ret = $this->mysqlQuery($sql, 'all');
        return $ret[0]['co_pdt'] > 0;
    }

    ######################################################################################
    ##################################                     ###############################
    #################################   PRIVATE METHODS   ################################
    ################################                     #################################
    ######################################################################################

    private function __addPdtPermission($pdtarr, $pdtId, $userID, $cpy_id, $ird_user_id, $pdttimearr, $pp)
    {
        $upTimes = date("Y-m-d H:i:s");
        $rs = $this->__getLicenceKey($pdtarr, $pdtId, $userID, $cpy_id, $ird_user_id);
        if ($rs && count($rs) > 0) {
            $update_data = [
                'u_id' => $userID,
                'lic_author_uid' => '8cbd411a-28ae-11e7-8cab-0017fa012439',
                'lic_edate' => $upTimes,
                'lic_comment' => 'from ird ' . $ird_user_id,
            ];
            write_to_log('add Pdt licence: ' . json_encode($update_data), '_from_ird');
            $ret = $this->mysqlEdit('idt_licence', $update_data, ['licence_key' => $rs[0]['licence_key']]);
            write_to_log('ret: ' . json_encode($ret), '_from_ird');
            return $ret !== '1';
        } else {
            write_to_log('create Pdt licence ', '_from_ird');
            $ret =  $this->__createLicence($pdtarr, $pdtId, $userID, $cpy_id, $ird_user_id, $pdttimearr, $pp);
            write_to_log('create Pdt licence return '.$ret, '_from_ird');
            return $ret;
        }
    }

    private function __createLicence($pdtarr, $pdtId, $userID, $cpy_id, $ird_user_id, $pdttimearr, $pp)
    {
        $upTimes = date("Y-m-d H:i:s");
        $sql_parent = "select pdt_label from idt_product where pdt_id = {$pdtId}";
        $ret_parent = $this->mysqlQuery($sql_parent, 'all');
        if (!empty($ret_parent[0]['pdt_label'])) {
            $rq = json_decode($ret_parent[0]['pdt_label'], true);
            //判断用户是否有这个权限的产品
            if ($pdtId == 12) {
                $sql = "select pnum_id,end_date from idt_permissions_number where cpy_id = {$cpy_id} and pdt_id = {$rq['parentID']}";
                $ret = $this->mysqlQuery($sql, "all");
                $sql_default_points = "select IFNULL(pdt_default_points,0) pdt_default_points from idt_product where pdt_id = {$rq['parentID']}";
                $pdt_default_points = $this->mysqlQuery($sql_default_points, "all");
                $lic['licence_key'] = getGUID();
                $lic['u_id'] = $userID;
                $lic['cpy_id'] = $cpy_id;
                $lic['pdt_id'] = $rq['parentID'];
                $lic['points'] = $pdt_default_points[0]['pdt_default_points'];
                $lic['lic_author_uid'] = "admin@iresearch.com.cn";
                $lic['lic_cdate'] = $upTimes;
                $lic['lic_edate'] = $upTimes;
                $lic['state'] = 1;
                $lic['lic_comment'] = 'from ird';
                $point['licenceKey'] = $lic['licence_key'];
                $point['pdt_id'] = $rq['parentID'];
                $point['points'] = $lic['points'];
                $this->mysqlInsert('idt_licence', $lic);
                $this->__newtopUp($point);
                if (count($ret) > 0) {
                    $ird_end_date = date("Y-m-d", strtotime($pp['proexpire']));
                    if ($ird_end_date > $ret[0]['end_date']) {
                        write_to_log('old pnum_end_date:' . $ret[0]['end_date'], '_from_ird');
                        $end_date = ",end_date = '{$ird_end_date}'";
                    } else {
                        $end_date = "";
                    }
                    $pnumSql = "update idt_permissions_number set pnum_number = pnum_number+1{$end_date} where cpy_id = {$cpy_id} and pdt_id = {$rq['parentID']}";
                    $ret_pnum = $this->mysqlQuery($pnumSql);
                } else {
                    $where['cpy_id'] = $cpy_id; //公司ID
                    $where['pdt_id'] = $rq['parentID']; //产品ID
                    $where['meu_id'] = 0; //报告ID
                    $where['pnum_number'] = 1; //权限数量
                    $where['pnum_cdate'] = $upTimes; //创建时间
                    $where['pnum_edate'] = $upTimes; //更新时间
                    $where['start_date'] = $upTimes; //开通日期
                    $where['end_date'] = date("Y-m-d", strtotime($pp['proexpire'])); //到期日期
                    $where['pnum_type'] = 0;
                    $ret_pnum = $this->mysqlInsert('idt_permissions_number', $where);
                }
                if (in_array('mut', $pdtarr)) {
                    $subproduct['licence_key'] = $lic['licence_key'];
                    $subproduct['pdt_id'] = $rq['parentID'];
                    $subproduct['pc_start_time'] = $upTimes;
                    $subproduct['pc_due_time'] = date("Y-m-d", strtotime($pp['proexpire']));
                    $subproduct['mobile_start_time'] = $upTimes;
                    $subproduct['mobile_due_time'] = date("Y-m-d", strtotime($pdttimearr['mut']));
                    $subproduct['spdt_comment'] = "from ird";
                } else {
                    $subproduct['licence_key'] = $lic['licence_key'];
                    $subproduct['pdt_id'] = $rq['parentID'];
                    $subproduct['pc_start_time'] = $upTimes;
                    $subproduct['pc_due_time'] = date("Y-m-d", strtotime($pp['proexpire']));
                    $subproduct['spdt_comment'] = "from ird";
                }
                return $this->mysqlInsert('idt_subproduct', $subproduct);
            } elseif ($pdtId == 37) {
                $sql = "select pnum_id,end_date from idt_permissions_number where cpy_id = {$cpy_id} and pdt_id = {$rq['parentID']}";
                $ret = $this->mysqlQuery($sql, "all");
                $sql_default_points = "select IFNULL(pdt_default_points,0) pdt_default_points from idt_product where pdt_id = {$rq['parentID']}";
                $pdt_default_points = $this->mysqlQuery($sql_default_points, "all");
                $lic['licence_key'] = getGUID();
                $lic['u_id'] = $userID;
                $lic['cpy_id'] = $cpy_id;
                $lic['pdt_id'] = $rq['parentID'];
                $lic['points'] = $pdt_default_points[0]['pdt_default_points'];
                $lic['lic_author_uid'] = "admin@iresearch.com.cn";
                $lic['lic_cdate'] = $upTimes;
                $lic['lic_edate'] = $upTimes;
                $lic['state'] = 1;
                $lic['lic_comment'] = 'from ird';
                $point['licenceKey'] = $lic['licence_key'];
                $point['pdt_id'] = $rq['parentID'];
                $point['points'] = $lic['points'];
                $this->mysqlInsert('idt_licence', $lic);
                $this->__newtopUp($point);
                if (count($ret) > 0) {
                    $ird_end_date = date("Y-m-d", strtotime($pp['proexpire']));
                    if ($ird_end_date > $ret[0]['end_date']) {
                        write_to_log('old pnum_end_date:' . $ret[0]['end_date'], '_from_ird');
                        $end_date = ",end_date = '{$ird_end_date}'";
                    } else {
                        $end_date = "";
                    }
                    $pnumSql = "update idt_permissions_number set pnum_number = pnum_number+1{$end_date} where cpy_id = {$cpy_id} and pdt_id = {$rq['parentID']}";
                    $ret_pnum = $this->mysqlQuery($pnumSql);
                } else {
                    $where['cpy_id'] = $cpy_id; //公司ID
                    $where['pdt_id'] = $rq['parentID']; //产品ID
                    $where['meu_id'] = 0; //报告ID
                    $where['pnum_number'] = 1; //权限数量
                    $where['pnum_cdate'] = $upTimes; //创建时间
                    $where['pnum_edate'] = $upTimes; //更新时间
                    $where['start_date'] = $upTimes; //开通日期
                    $where['end_date'] = date("Y-m-d", strtotime($pp['proexpire'])); //到期日期
                    $where['pnum_type'] = 0;
                    $ret_pnum = $this->mysqlInsert('idt_permissions_number', $where);
                }
                if (in_array('iut', $pdtarr)) {
                    $subproduct['licence_key'] = $lic['licence_key'];
                    $subproduct['pdt_id'] = $rq['parentID'];
                    $subproduct['pc_start_time'] = $upTimes;
                    $subproduct['pc_due_time'] = date("Y-m-d", strtotime($pdttimearr['iut']));
                    $subproduct['mobile_start_time'] = $upTimes;
                    $subproduct['mobile_due_time'] = date("Y-m-d", strtotime($pp['proexpire']));
                    $subproduct['spdt_comment'] = "from ird";
                } else {
                    $subproduct['licence_key'] = $lic['licence_key'];
                    $subproduct['pdt_id'] = $rq['parentID'];
                    $subproduct['mobile_start_time'] = $upTimes;
                    $subproduct['mobile_due_time'] = date("Y-m-d", strtotime($pp['proexpire']));
                    $subproduct['spdt_comment'] = "from ird";
                }
                return $this->mysqlInsert('idt_subproduct', $subproduct);
            } elseif ($pdtId == 45) {
                $sql = "select pnum_id,end_date from idt_permissions_number where cpy_id = {$cpy_id} and pdt_id = {$rq['parentID']}";
                $ret = $this->mysqlQuery($sql, "all");
                $sql_default_points = "select IFNULL(pdt_default_points,0) pdt_default_points from idt_product where pdt_id = {$rq['parentID']}";
                $pdt_default_points = $this->mysqlQuery($sql_default_points, "all");
                $lic['licence_key'] = getGUID();
                $lic['u_id'] = $userID;
                $lic['cpy_id'] = $cpy_id;
                $lic['pdt_id'] = $rq['parentID'];
                $lic['points'] = $pdt_default_points[0]['pdt_default_points'];
                $lic['lic_author_uid'] = "admin@iresearch.com.cn";
                $lic['lic_cdate'] = $upTimes;
                $lic['lic_edate'] = $upTimes;
                $lic['state'] = 1;
                $lic['lic_comment'] = 'from ird';
                $point['licenceKey'] = $lic['licence_key'];
                $point['pdt_id'] = $rq['parentID'];
                $point['points'] = $lic['points'];
                $this->mysqlInsert('idt_licence', $lic);
                $this->__newtopUp($point);
                if (count($ret) > 0) {
                    $ird_end_date = date("Y-m-d", strtotime($pp['proexpire']));
                    if ($ird_end_date > $ret[0]['end_date']) {
                        write_to_log('old pnum_end_date:' . $ret[0]['end_date'], '_from_ird');
                        $end_date = ",end_date = '{$ird_end_date}'";
                    } else {
                        $end_date = "";
                    }
                    $pnumSql = "update idt_permissions_number set pnum_number = pnum_number+1{$end_date} where cpy_id = {$cpy_id} and pdt_id = {$rq['parentID']}";
                    $ret_pnum = $this->mysqlQuery($pnumSql);
                } else {
                    $where['cpy_id'] = $cpy_id; //公司ID
                    $where['pdt_id'] = $rq['parentID']; //产品ID
                    $where['meu_id'] = 0; //报告ID
                    $where['pnum_number'] = 1; //权限数量
                    $where['pnum_cdate'] = $upTimes; //创建时间
                    $where['pnum_edate'] = $upTimes; //更新时间
                    $where['start_date'] = $upTimes; //开通日期
                    $where['end_date'] = date("Y-m-d", strtotime($pp['proexpire'])); //到期日期
                    $where['pnum_type'] = 0;
                    $ret_pnum = $this->mysqlInsert('idt_permissions_number', $where);
                }
                $mvtSql = "select ird_tmp_id from ird_user_tmp where pdt_type = 'mvt' and ird_cu_id = '{$ird_user_id}'";
                $ovtSql = "select ird_tmp_id from ird_user_tmp where pdt_type = 'ovt' and ird_cu_id = '{$ird_user_id}'";
                $mvtret = $this->mysqlQuery($mvtSql, 'all');
                $ovtret = $this->mysqlQuery($ovtSql, 'all');
                if (count($mvtret) > 0 && count($ovtret) > 0) {
                    $subproduct['licence_key'] = $lic['licence_key'];
                    $subproduct['pdt_id'] = $rq['parentID'];
                    $subproduct['pc_start_time'] = $upTimes;
                    $subproduct['pc_due_time'] = date("Y-m-d", strtotime($pp['proexpire']));
                    $subproduct['mobile_start_time'] = $upTimes;
                    $subproduct['mobile_due_time'] = date("Y-m-d", strtotime($pp['proexpire']));
                    $subproduct['ott_start_time'] = $upTimes;
                    $subproduct['ott_due_time'] = date("Y-m-d", strtotime($pp['proexpire']));
                    $subproduct['spdt_comment'] = "from ird";
                } elseif (count($mvtret) > 0) {
                    $subproduct['licence_key'] = $lic['licence_key'];
                    $subproduct['pdt_id'] = $rq['parentID'];
                    $subproduct['pc_start_time'] = $upTimes;
                    $subproduct['pc_due_time'] = ddate("Y-m-d", strtotime($pp['proexpire']));
                    $subproduct['mobile_start_time'] = $upTimes;
                    $subproduct['mobile_due_time'] = date("Y-m-d", strtotime($pp['proexpire']));
                    $subproduct['spdt_comment'] = "from ird";
                } elseif (count($ovtret) > 0) {
                    $subproduct['licence_key'] = $lic['licence_key'];
                    $subproduct['pdt_id'] = $rq['parentID'];
                    $subproduct['pc_start_time'] = $upTimes;
                    $subproduct['pc_due_time'] = date("Y-m-d", strtotime($pp['proexpire']));
                    $subproduct['ott_start_time'] = $upTimes;
                    $subproduct['ott_due_time'] = date("Y-m-d", strtotime($pp['proexpire']));
                    $subproduct['spdt_comment'] = "from ird";
                } else {
                    $subproduct['licence_key'] = $lic['licence_key'];
                    $subproduct['pdt_id'] = $rq['parentID'];
                    $subproduct['pc_start_time'] = $upTimes;
                    $subproduct['pc_due_time'] = date("Y-m-d", strtotime($pp['proexpire']));
                    $subproduct['spdt_comment'] = "from ird";
                }
                return $this->mysqlInsert('idt_subproduct', $subproduct);
            }
        } else {
            if ($pdtId == 42) {
                $sql = "select pnum_id,end_date from idt_permissions_number where cpy_id = {$cpy_id} and pdt_id = $pdtId";
                $ret = $this->mysqlQuery($sql, "all");
                $sql_default_points = "select IFNULL(pdt_default_points,0) pdt_default_points from idt_product where pdt_id = $pdtId";
                $pdt_default_points = $this->mysqlQuery($sql_default_points, "all");
                $lic['licence_key'] = getGUID();
                $lic['u_id'] = $userID;
                $lic['cpy_id'] = $cpy_id;
                $lic['pdt_id'] = $pdtId;
                $lic['points'] = $pdt_default_points[0]['pdt_default_points'];
                $lic['lic_author_uid'] = "admin@iresearch.com.cn";
                $lic['lic_cdate'] = $upTimes;
                $lic['lic_edate'] = $upTimes;
                $lic['state'] = 1;
                $lic['lic_comment'] = 'from ird';
                $point['licenceKey'] = $lic['licence_key'];
                $point['pdt_id'] = $pdtId;
                $point['points'] = $lic['points'];
                $this->mysqlInsert('idt_licence', $lic);
                $this->__newtopUp($point);
                if (count($ret) > 0) {
                    $ird_end_date = date("Y-m-d", strtotime($pp['proexpire']));
                    if ($ird_end_date > $ret[0]['end_date']) {
                        write_to_log('old pnum_end_date:' . $ret[0]['end_date'], '_from_ird');
                        $end_date = ",end_date = '{$ird_end_date}'";
                    } else {
                        $end_date = "";
                    }
                    $pnumSql = "update idt_permissions_number set pnum_number = pnum_number+1{$end_date} where cpy_id = {$cpy_id} and pdt_id = {$pdtId}";
                    $ret_pnum = $this->mysqlQuery($pnumSql);
                } else {
                    $where['cpy_id'] = $cpy_id; //公司ID
                    $where['pdt_id'] = $pdtId; //产品ID
                    $where['meu_id'] = 0; //报告ID
                    $where['pnum_number'] = 1; //权限数量
                    $where['pnum_cdate'] = $upTimes; //创建时间
                    $where['pnum_edate'] = $upTimes; //更新时间
                    $where['start_date'] = $upTimes; //开通日期
                    $where['end_date'] = date("Y-m-d", strtotime($pp['proexpire'])); //到期日期
                    $where['pnum_type'] = 0;
                    $ret_pnum = $this->mysqlInsert('idt_permissions_number', $where);
                }
                if (in_array('iadt', $pdtarr) && in_array('madt', $pdtarr)) {
                    $subproduct['licence_key'] = $lic['licence_key'];
                    $subproduct['pdt_id'] = $pdtId;
                    $subproduct['pc_start_time'] = $upTimes;
                    $subproduct['pc_due_time'] = date("Y-m-d", strtotime($pdttimearr['iadt']));
                    $subproduct['mobile_start_time'] = $upTimes;
                    $subproduct['mobile_due_time'] = date("Y-m-d", strtotime($pdttimearr['madt']));
                    $subproduct['ott_start_time'] = $upTimes;
                    $subproduct['ott_due_time'] = date("Y-m-d", strtotime($pdttimearr['madt']));
                    $subproduct['spdt_comment'] = "from ird";
                } elseif (in_array('iadt', $pdtarr)) {
                    $subproduct['licence_key'] = $lic['licence_key'];
                    $subproduct['pdt_id'] = $pdtId;
                    $subproduct['pc_start_time'] = $upTimes;
                    $subproduct['pc_due_time'] = date("Y-m-d", strtotime($pdttimearr['iadt']));
                    $subproduct['spdt_comment'] = "from ird";
                } elseif (in_array('madt', $pdtarr)) {
                    $subproduct['licence_key'] = $lic['licence_key'];
                    $subproduct['pdt_id'] = $pdtId;
                    $subproduct['mobile_start_time'] = $upTimes;
                    $subproduct['mobile_due_time'] = date("Y-m-d", strtotime($pdttimearr['madt']));
                    $subproduct['ott_start_time'] = $upTimes;
                    $subproduct['ott_due_time'] = date("Y-m-d", strtotime($pdttimearr['madt']));
                    $subproduct['spdt_comment'] = "from ird";
                }
                return $this->mysqlInsert('idt_subproduct', $subproduct);
            } else {
                $sql = "select pnum_id,end_date from idt_permissions_number where cpy_id = {$cpy_id} and pdt_id = $pdtId";
                $ret = $this->mysqlQuery($sql, "all");
                $lic['licence_key'] = getGUID();
                $lic['u_id'] = $userID;
                $lic['cpy_id'] = $cpy_id;
                $lic['pdt_id'] = $pdtId;
                $lic['lic_author_uid'] = "admin@iresearch.com.cn";
                $lic['lic_cdate'] = $upTimes;
                $lic['lic_edate'] = $upTimes;
                $lic['state'] = 1;
                $lic['lic_comment'] = 'from ird';
                $this->mysqlInsert('idt_licence', $lic);
                if (count($ret) > 0) {
                    $ird_end_date = date("Y-m-d", strtotime($pp['proexpire']));
                    if ($ird_end_date > $ret[0]['end_date']) {
                        write_to_log('old pnum_end_date:' . $ret[0]['end_date'], '_from_ird');
                        $end_date = ",end_date = '{$ird_end_date}'";
                    } else {
                        $end_date = "";
                    }
                    $pnumSql = "update idt_permissions_number set pnum_number = pnum_number+1{$end_date} where cpy_id = {$cpy_id} and pdt_id = {$pdtId}";
                    return $this->mysqlQuery($pnumSql);
                } else {
                    $where['cpy_id'] = $cpy_id; //公司ID
                    $where['pdt_id'] = $pdtId; //产品ID
                    $where['meu_id'] = 0; //报告ID
                    $where['pnum_number'] = 1; //权限数量
                    $where['pnum_cdate'] = $upTimes; //创建时间
                    $where['pnum_edate'] = $upTimes; //更新时间
                    $where['start_date'] = $upTimes; //开通日期
                    $where['end_date'] = date("Y-m-d", strtotime($pp['proexpire'])); //到期日期
                    $where['pnum_type'] = 0;
                    return $this->mysqlInsert('idt_permissions_number', $where);
                }
            }
        }
    }

    /**
     * @param $data
     */
    private function __newtopUp($data)
    {
        if (is_array($data)) {
            $data = [
                'type' => 1,
                'licence_key' => $data['licenceKey'],
                'point_explain' => "充值" . $data['points'] . "积分",
                'point_value' => $data['points'],
                'pdt_id' => $data['pdt_id'],
                'u_id' => "11111111-1111-1111-1111-111111111111",
                'balance' => $this->__computingBalancePoint($data['licenceKey'])
            ];
            if (is_numeric($data['point_value'])) {
                if ($data['point_value'] >= 0) {
                    if (floor($data['point_value']) == $data['point_value']) {

                    } else {
                        _ERROR('001', '积分格式错误');
                    }
                } else {
                    _ERROR('001', '积分格式错误');
                }
            } else {
                _ERROR('001', '积分格式错误');
            }
            $this->__insertRow($data);
        }
    }

    /**
     * computing balance point
     *
     * @param $licenceKey
     * @return array
     */
    private function __computingBalancePoint($licenceKey)
    {
        $positiveNumSQL = "SELECT sum(point_value) as positiveNum FROM idt_points WHERE type <= 5 AND licence_key='{$licenceKey}'";
        $negativeNumSQL = "SELECT sum(point_value) as negativeNum FROM idt_points WHERE type > 5 AND licence_key='{$licenceKey}'";
        $positiveNum = $this->mysqlQuery($positiveNumSQL, 'all');
        $negativeNum = $this->mysqlQuery($negativeNumSQL, 'all');
        $ret = (int)$positiveNum[0]['positivenum'] - (int)$negativeNum[0]['negativenum'];
        return $ret;
    }

    /**
     * insert row data
     *
     * @param array $data
     * @return array|int|string
     */
    private function __insertRow(array $data)
    {
        unset($data['token']);
        if (empty($data['u_id'])) {
            _ERROR('002', 'no user id');
        }

        if (empty($data['type'])) {
            _ERROR('002', 'no point type');
        }

        if (empty($data['point_explain'])) {
            _ERROR('002', 'no comment');
        }

        $ret = $this->mysqlInsert('idt_points', $data);
        if ($ret) {
            return $ret;
        } else {
            _ERROR('002', $data);
        }
    }

    /**
     * @array $pdtarr 产品列表
     * @param $pdtId 产品ID
     * @param $userID 用户ID
     * @param $cpy_id 公司ID
     * @param $ird_user_id IRD用户ID
     * @return array|bool|string
     */
    private function __getLicenceKey($pdtarr, $pdtId, $userID, $cpy_id, $ird_user_id)
    {
        $sql_parent = "select pdt_label from idt_product where pdt_id = {$pdtId}";
        $ret_parent = $this->mysqlQuery($sql_parent, 'all');
        if (!empty($ret_parent[0]['pdt_label'])) {
            $rq = json_decode($ret_parent[0]['pdt_label'], true);
            //判断用户是否有这个权限的产品
            $sql = "SELECT licence_key FROM idt_licence
            WHERE u_id='{$userID}' AND cpy_id = {$cpy_id}
            AND pdt_id='{$rq['parentID']}' AND state= 1";
            $rs = $this->mysqlQuery($sql, 'all');
            if (count($rs) > 0) {
                $where = [
                    'licence_key' => $rs[0]['licence_key']
                ];
                $ret = $this->mysqlDelete('idt_licence', $where);
                write_to_log('delete  :' . $ret, '_from_ird');
                write_to_log('delete licence :' . $rs[0]['licence_key'], '_from_ird');
                $pnumSql = "update idt_permissions_number set pnum_number = pnum_number-1 where cpy_id = {$cpy_id} and pdt_id = {$rq['parentID']}";
                $ret_pnum = $this->mysqlQuery($pnumSql);
                return false;
            } else {
                if ($pdtId == 12) {
                    if (in_array('mut', $pdtarr)) {
                        $terminalSql = "SELECT idt_licence.licence_key FROM idt_licence
                                left join idt_subproduct on idt_subproduct.licence_key = idt_licence.licence_key
                                WHERE idt_licence.pdt_id = {$rq['parentID']} and cpy_id = $cpy_id and u_id is null AND pc_due_time is not null AND mobile_due_time is not null limit 1";
                    } else {
                        $terminalSql = "SELECT idt_licence.licence_key FROM idt_licence
                                left join idt_subproduct on idt_subproduct.licence_key = idt_licence.licence_key
                                WHERE idt_licence.pdt_id = {$rq['parentID']} and cpy_id = $cpy_id and u_id is null AND pc_due_time is not null AND mobile_due_time is null limit 1";
                    }
                } elseif ($pdtId == 37) {
                    if (in_array('iut', $pdtarr)) {
                        $terminalSql = "SELECT idt_licence.licence_key FROM idt_licence
                                left join idt_subproduct on idt_subproduct.licence_key = idt_licence.licence_key
                                WHERE idt_licence.pdt_id = {$rq['parentID']} and cpy_id = $cpy_id and u_id is null AND pc_due_time is not null AND mobile_due_time is not null limit 1";
                    } else {
                        $terminalSql = "SELECT idt_licence.licence_key FROM idt_licence
                                left join idt_subproduct on idt_subproduct.licence_key = idt_licence.licence_key
                                WHERE idt_licence.pdt_id = {$rq['parentID']} and cpy_id = $cpy_id and u_id is null AND pc_due_time is null AND mobile_due_time is not null limit 1";
                    }
                } elseif ($pdtId == 45) {
                    $mvtSql = "select ird_tmp_id from ird_user_tmp where pdt_type = 'mvt' and ird_cu_id = '{$ird_user_id}' and idt_user_id is null";
                    $ovtSql = "select ird_tmp_id from ird_user_tmp where pdt_type = 'ovt' and ird_cu_id = '{$ird_user_id}' and idt_user_id is null";
                    $mvtret = $this->mysqlQuery($mvtSql, 'all');
                    $ovtret = $this->mysqlQuery($ovtSql, 'all');
                    if (count($mvtret) > 0 && count($ovtret) > 0) {
                        $this->mysqlEdit('ird_user_tmp',
                            ['idt_user_Id' => $userID, 'idt_cpy_id' => $cpy_id], ['ird_tmp_id' => $mvtret[0]['ird_tmp_id']]);
                        $this->mysqlEdit('ird_user_tmp',
                            ['idt_user_Id' => $userID, 'idt_cpy_id' => $cpy_id], ['ird_tmp_id' => $ovtret[0]['ird_tmp_id']]);
                        $terminalSql = "SELECT idt_licence.licence_key FROM idt_licence
                                left join idt_subproduct on idt_subproduct.licence_key = idt_licence.licence_key
                                WHERE idt_licence.pdt_id = {$rq['parentID']} and cpy_id = $cpy_id and u_id is null AND pc_due_time is not null AND mobile_due_time is not null AND ott_due_time is not null limit 1";
                    } elseif (count($mvtret) > 0) {
                        $this->mysqlEdit('ird_user_tmp',
                            ['idt_user_Id' => $userID, 'idt_cpy_id' => $cpy_id], ['ird_tmp_id' => $mvtret[0]['ird_tmp_id']]);
                        $terminalSql = "SELECT idt_licence.licence_key FROM idt_licence
                                left join idt_subproduct on idt_subproduct.licence_key = idt_licence.licence_key
                                WHERE idt_licence.pdt_id = {$rq['parentID']} and cpy_id = $cpy_id and u_id is null AND pc_due_time is not null AND mobile_due_time is not null AND ott_due_time is null limit 1";
                    } elseif (count($ovtret) > 0) {
                        $this->mysqlEdit('ird_user_tmp',
                            ['idt_user_Id' => $userID, 'idt_cpy_id' => $cpy_id], ['ird_tmp_id' => $ovtret[0]['ird_tmp_id']]);
                        $terminalSql = "SELECT idt_licence.licence_key FROM idt_licence
                                left join idt_subproduct on idt_subproduct.licence_key = idt_licence.licence_key
                                WHERE idt_licence.pdt_id = {$rq['parentID']} and cpy_id = $cpy_id and u_id is null AND pc_due_time is not null AND mobile_due_time is null AND ott_due_time is not null limit 1";
                    } else {
                        $terminalSql = "SELECT idt_licence.licence_key FROM idt_licence
                                left join idt_subproduct on idt_subproduct.licence_key = idt_licence.licence_key
                                WHERE idt_licence.pdt_id = {$rq['parentID']} and cpy_id = $cpy_id and u_id is null AND pc_due_time is not null AND mobile_due_time is null AND ott_due_time is null limit 1";
                    }
                }
                return $this->mysqlQuery($terminalSql, 'all');
            }
        } else {
            $sql = "select licence_key from idt_licence where cpy_id = $cpy_id and pdt_id = $pdtId and u_id = '{$userID}' and state = 1";
            $rs = $this->mysqlQuery($sql, 'all');
            if (count($rs) > 0) {
                $where = [
                    'licence_key' => $rs[0]['licence_key']
                ];
                $ret = $this->mysqlDelete('idt_licence', $where);
                write_to_log('delete  :' . $ret, '_from_ird');
                write_to_log('delete licence :' . $rs[0]['licence_key'], '_from_ird');
                $pnumSql = "update idt_permissions_number set pnum_number = pnum_number-1 where cpy_id = {$cpy_id} and pdt_id = {$pdtId}";
                $ret_pnum = $this->mysqlQuery($pnumSql);
                return false;
            } else {
                if ($pdtId == 42) {
                    if (in_array('iadt', $pdtarr) && in_array('madt', $pdtarr)) {
                        $sql = "SELECT idt_licence.licence_key FROM idt_licence
                                left join idt_subproduct on idt_subproduct.licence_key = idt_licence.licence_key
                                WHERE idt_licence.pdt_id = $pdtId and cpy_id = $cpy_id and u_id is null AND pc_due_time is not null AND mobile_due_time is not null limit 1";
                    } elseif (in_array('iadt', $pdtarr)) {
                        $sql = "SELECT idt_licence.licence_key FROM idt_licence
                                left join idt_subproduct on idt_subproduct.licence_key = idt_licence.licence_key
                                WHERE idt_licence.pdt_id = $pdtId and cpy_id = $cpy_id and u_id is null AND pc_due_time is not null AND mobile_due_time is null limit 1";
                    } elseif (in_array('madt', $pdtarr)) {
                        $sql = "SELECT idt_licence.licence_key FROM idt_licence
                                left join idt_subproduct on idt_subproduct.licence_key = idt_licence.licence_key
                                WHERE idt_licence.pdt_id = $pdtId and cpy_id = $cpy_id and u_id is null AND pc_due_time is null AND mobile_due_time is not null limit 1";
                    }
                } else {
                    $sql = "select licence_key from idt_licence where cpy_id = $cpy_id and pdt_id = $pdtId and u_id is null and state = 1 limit 1";
                }
                return $this->mysqlQuery($sql, 'all');
            }
        }
    }

    /**
     * get apply information for user
     *
     * @param $data
     *
     * @return array|string
     */
    private function __getApplyPermission($data)
    {
        $sql = "SELECT COUNT(*) co FROM idt_apply WHERE pdt_id = '{$data['pdt_id']}' AND state='0' AND u_id='{$data['userID']}' ";
        return $this->mysqlQuery($sql, 'all');
    }

    private function __getProduct($data)
    {
        $sql = "SELECT COUNT(*) co FROM idatadb.idt_product WHERE pdt_id='{$data['pdt_id']}' AND pdt_state='0'";
        $ret = $this->mysqlQuery($sql, 'all');
        return $ret[0];
    }

    /**
     * get user menu for permission sql 获取首页菜单导航
     *
     * @param $data
     *
     * @return array|string
     */
    private function __getOfficialMenuSQL($data)
    {
        $sql = "SELECT dba.pdt_id id, dba.pdt_name, dba.pdt_ename, dba.pdt_intro, dba.pdt_label, dba.pdt_url, dba.pdt_vtype, 
            dba.pdt_series,  dba.pdt_ptype, dba.pdt_sid sid, dba.pdt_version, IFNULL(dbc.prs_state,0) prs_state 
            FROM idt_product dba 
            LEFT JOIN (SELECT pdt_id,prs_state FROM idt_permissions WHERE cpy_id={$data['companyID']} AND meu_id=0 AND 
            u_id='{$data['userID']}') dbc ON (dba.pdt_id=dbc.pdt_id) WHERE dba.pdt_state=0 
            AND (dbc.prs_state IS NULL OR dbc.prs_state=1 OR dbc.prs_state=0) ORDER BY dba.pdt_id";
        return $this->mysqlQuery($sql, "all");
    }

    /**
     * guest menu
     *
     * @return array|string
     */
    private function __getGustMenuSQL()
    {
        $sql = 'SELECT 
                dba.pdt_id id, 
                dba.pdt_name, 
                dba.pdt_ename, 
                dba.pdt_intro, 
                dba.pdt_label, 
                dba.pdt_url,dba.
                pdt_vtype, 
                dba.pdt_series, 
                dba.pdt_ptype,
                dba.pdt_sid sid, 
                dba.pdt_version,
                1 prs_state 
                FROM idt_product dba WHERE dba.pdt_state = 0 AND (dba.pdt_ptype = 1) ORDER BY dba.pdt_id';
        return $this->mysqlQuery($sql, 'all');
    }


    /**
     * 获取IRD产品绑定ID
     *
     * @param $data
     *
     * @return array|string
     */
    private function __getBindingIRD($data)
    {
        /** @var array $data */
        if (!empty($data['userID'])) {
            return $this->mysqlQuery(
                "SELECT u_product_key FROM idt_user WHERE u_id='{$data['userID']}'",
                "all");
        } else {
            return false;
        }
    }

    /**
     * verify license
     *
     * @param $cpy_id
     * @param $pdt_id
     *
     * @return bool
     */
    private function __verifyLicense($cpy_id, $pdt_id)
    {
        $sql = "SELECT start_date, end_date, pnum_number FROM idt_permissions_number WHERE cpy_id='{$cpy_id}' 
                AND pdt_id='{$pdt_id}'";
        $ret = $this->mysqlQuery($sql, 'all');
        $now = strtotime('now');
        if ($ret[0]['start_date' !== null] AND $ret[0]['end_date'] !== null) {
            return false;
        } else {
            if (strtotime($ret[0]['start_date']) < $now AND strtotime($ret[0]['end_date']) > $now) {
                return $this->__countLicense($cpy_id, $pdt_id) <= $ret[0]['pnum_number'];
            } else {
                write_to_log('no license for ' . $cpy_id, '_noLicense');
                return false;
            }
        }
    }

    /**
     * count license
     *
     * @param $cpy_id
     *
     * @param $pdt_id
     *
     * @return int
     */
    private function __countLicense($cpy_id, $pdt_id)
    {
        $sql = "SELECT COUNT(*) co FROM idt_permissions WHERE cpy_id='{$cpy_id}' AND pdt_id='{$pdt_id}' 
                AND prs_state=0 ";
        $ret = $this->mysqlQuery($sql, 'all');
        write_to_log('debug ' . json_encode($ret), '_debug');
        if (count($ret) <= 0) {
            return 0;
        } else {
            return $ret[0]['co'];
        }
    }

    /**
     * check permission 检查权限
     *
     * @param $userID
     * @param $pdt_id
     * @param $cpy_id
     * @param null $get_terminal
     * @return bool
     */
    private function __checkPermission($userID, $pdt_id, $cpy_id, $get_terminal = null)
    {
        $now = date('Y-m-d H:i:s');
        $sql_parent = "select pdt_label from idt_product where pdt_id = {$pdt_id}";
        $ret_parent = $this->mysqlQuery($sql_parent, 'all');
        if (!empty($ret_parent[0]['pdt_label'])) {
            $rq = json_decode($ret_parent[0]['pdt_label'], true);
            //判断用户是否有这个权限的产品
            $sql = "SELECT licence_key FROM idt_licence
                WHERE u_id='{$userID}' AND cpy_id = {$cpy_id}
                AND pdt_id='{$rq['parentID']}' AND state='1' ";
            $res = $this->mysqlQuery($sql, 'all');
            write_to_log($sql, '_test');
            write_to_log(json_encode($res), '_test');
            if (count($res) > 0) {
                //权限是否过期
                $numSql = "SELECT COUNT(*) co FROM idt_permissions_number 
                    WHERE cpy_id='{$cpy_id}' 
                    AND pdt_id='{$rq['parentID']}'
                    AND end_date>='{$now}' AND start_date<='{$now}'";
                $num = $this->mysqlQuery($numSql, 'all');
                write_to_log($numSql, '_test');
                write_to_log(json_encode($num), '_test');
                if ($num[0]['co'] > 0) {
                    if ($rq['terminal'] == 'pc') {
                        //终端权限是否过期
                        $terminalSql = "SELECT COUNT(*) co FROM idt_subproduct 
                                        WHERE licence_key='{$res[0]['licence_key']}' AND pdt_id = {$rq['parentID']}
                                        AND pc_due_time>='{$now}' AND pc_start_time<='{$now}'";
                    } elseif ($rq['terminal'] == 'mobile') {
                        $terminalSql = "SELECT COUNT(*) co FROM idt_subproduct 
                                        WHERE licence_key='{$res[0]['licence_key']}' AND pdt_id = {$rq['parentID']}
                                        AND mobile_due_time>='{$now}' AND mobile_start_time<='{$now}'";
                    } elseif ($rq['terminal'] == 'ott') {
                        $terminalSql = "SELECT COUNT(*) co FROM idt_subproduct 
                                        WHERE licence_key='{$res[0]['licence_key']}' AND pdt_id = {$rq['parentID']}
                                        AND ott_due_time>='{$now}' AND ott_start_time<='{$now}'";
                    }
                    $terminal = $this->mysqlQuery($terminalSql, 'all');
                    if ($terminal[0]['co'] > 0) {
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {

            //判断用户是否有这个权限的产品
            $sql = "SELECT licence_key  FROM idt_licence
                WHERE u_id='{$userID}' AND cpy_id = {$cpy_id}
                AND pdt_id='{$pdt_id}' AND state='1' ";

            $res = $this->mysqlQuery($sql, 'all');
            write_to_log($sql, '_test');
            write_to_log(json_encode($res), '_test');

            if (count($res) > 0) {
                //权限是否过期
                $numSql = "SELECT COUNT(*) co FROM idt_permissions_number 
                    WHERE cpy_id='{$cpy_id}' 
                    AND pdt_id='{$pdt_id}'
                    AND end_date>='{$now}' AND start_date<='{$now}'";
                $num = $this->mysqlQuery($numSql, 'all');

                write_to_log($numSql, '_test');
                write_to_log(json_encode($num), '_test');

                if ($num[0]['co'] > 0) {

                    if (!empty($get_terminal)) {

                        switch ($get_terminal) {
                            case 'pc':
                                $ptSql = "SELECT COUNT(*) co FROM idt_subproduct 
                                        WHERE licence_key='{$res[0]['licence_key']}' AND pdt_id = '{$pdt_id}'
                                        AND pc_due_time>='{$now}' AND pc_start_time<='{$now}'";
                                break;
                            case 'mobile':
                                $ptSql = "SELECT COUNT(*) co FROM idt_subproduct 
                                        WHERE licence_key='{$res[0]['licence_key']}' AND pdt_id = '{$pdt_id}'
                                        AND mobile_due_time>='{$now}' AND mobile_start_time<='{$now}'";
                                break;
                            case 'ott':
                                $ptSql = "SELECT COUNT(*) co FROM idt_subproduct 
                                        WHERE licence_key='{$res[0]['licence_key']}' AND pdt_id = '{$pdt_id}'
                                        AND ott_due_time>='{$now}' AND ott_start_time<='{$now}'";
                                break;
                            default:
                                $ptSql = "SELECT COUNT(*) co FROM idt_subproduct 
                                        WHERE licence_key='{$res[0]['licence_key']}' AND pdt_id = '{$pdt_id}'
                                        AND pc_due_time>='{$now}' AND pc_start_time<='{$now}'";
                                break;
                        }

                        $t_res = $this->mysqlQuery($ptSql, 'all');

                        return $t_res[0]['co'] > 0;

                    } else {
                        return true;
                    }
                } else {
                    return false;
                }


            } else {
                return false;
            }
        }
    }

    /**
     *
     * check same mail
     *
     * @param $mail
     * @return bool
     */
    private function __checkVerifyMail($mail)
    {
        if (!empty($mail)) {
            $sql = "SELECT count(u_mail) AS has_mail FROM idt_user WHERE u_mail= '{$mail}' ";
            $ret = $this->mysqlQuery($sql, 'all');
            return $ret[0]['has_mail'] == 0;
        } else {
            return false;
        }
    }

    /**
     *
     * check user mail
     *
     * @param $userID
     * @param $mail
     * @return bool
     */
    private function __isYourMail($userID, $mail)
    {
        if (!empty($userID) && !empty($mail)) {
            $sql = "SELECT count(u_id) AS u FROM idt_user WHERE u_id = '{$userID}' AND u_mail = '{$mail}' ";
            $ret = $this->mysqlQuery($sql, 'all');
            return $ret[0]['u'] > 0;
        } else {
            return false;
        }
    }

    /**
     * check the user is not a guest
     *
     * @param $userID
     * @return bool
     */
    private function __checkNotGuest($userID)
    {
        if (!empty($userID)) {
            $sql = "SELECT u_permissions FROM idatadb.idt_user WHERE u_id = '{$userID}'";
            $ret = $this->mysqlQuery($sql, 'all');
            if (count($ret) > 0) {
                return $ret[0]['u_permissions'] > 0;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * send mail
     *
     * @param $sender
     * @param $body
     * @param string $subject
     */
    private function __sendMail($sender, $body, $subject = '智云产品申请')
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
        $phpMail->CharSet = 'UTF-8';
        $phpMail->Subject = $subject;
        $phpMail->Body = $body;
        if (!$phpMail->send()) {
            write_to_log("{$sender} sent error " . $phpMail->ErrorInfo, '_mail');
        } else {
            write_to_log("{$sender} is sent!", '_mail');
        }

    }

    private function __enCode($userID, $mail, $pdt_id)
    {
        return urlencode(base64_encode(json_encode(['userID' => $userID, 'u_mail' => $mail, 'pdt_id' => $pdt_id])));
    }

    private function __deCode($baseCode)
    {
        return json_decode(base64_decode(urldecode($baseCode)), true);
    }

    private function __en($baseCode)
    {
        return md5(base64_decode(urldecode($baseCode)) . KEY);
    }

    /**
     * check code
     *
     * @param $baseCode
     * @param $code
     * @return bool
     */
    private function __checkCode($baseCode, $code)
    {
        $baseCode = md5(base64_decode(urldecode($baseCode)) . KEY);
        return $baseCode == $code;
    }

}