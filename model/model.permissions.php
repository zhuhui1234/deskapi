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
    public function __consturct()
    {

    }

    //获取菜单导航
    public function getMenuList($data)
    {
        //获取菜单导航&报表GUID
        $sql = "SELECT dba.meu_id id,dba.meu_name,dba.meu_describe,dba.meu_type,dba.meu_sid sid,dbb.cfg_guid " .
                        "FROM idt_menu dba " .
                        "LEFT JOIN idt_config dbb ON (dba.meu_id=dbb.meu_id) " .
                        "WHERE dba.meu_state=0 ".
                        "ORDER BY dba.meu_id";
        $ret = $this->mysqlQuery($sql, "all");

        //格式化菜单数组
        $cleaningList = array('menuName'=>'meu_name','menuDescribe'=>'meu_describe','menuType'=>'meu_type','reportID'=>'cfg_guid');
        $rs['dataList'] = _findChildren($ret, 0, $cleaningList);

        //返回响应结果
        _SUCCESS('000000','查询成功',$rs);
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
        $sql = "SELECT dba.u_mobile mobile,dba.u_permissions permissions,dbb.prs_state state ".
            "FROM idt_user dba ".
            "LEFT JOIN idt_permissions dbb ON (dba.u_id=dbb.u_id AND dbb.meu_id=1) ".
            "WHERE 1=1{$keyword} ".
            "ORDER BY {$orderByColumn} {$orderByType} ".
            "LIMIT {$pageNo},{$pageSize}";
        $ret = $this->mysqlQuery($sql, "all");

        //格式化菜单数组
        foreach($ret as $a=>$v){
            $rs['dataList'][$a]['mobile'] = $v['mobile'];
            $rs['dataList'][$a]['permissions'] = $v['permissions'];
            $rs['dataList'][$a]['state'] = $v['state'];
        }

        //返回响应结果
        _SUCCESS('000000','查询成功',$rs);
    }

    //获取首页菜单导航
    public function getHomeMenu($data)
    {
        //获取首页菜单导航
        $sql = "SELECT dba.pdt_id id,dba.pdt_name,dba.pdt_ename,dba.pdt_intro,dba.pdt_label,dba.pdt_url,dba.pdt_vtype,dba.pdt_series,dbb.prsc_state,dba.pdt_ptype,dba.pdt_sid sid,dba.pdt_version " .
            "FROM idt_product dba " .
            "LEFT JOIN idt_permissions_company dbb ON (dba.pdt_id=dbb.pdt_id AND dbb.cpy_id={$data['companyID']}) " .
            "WHERE dba.pdt_state=0 " .
            " AND (dba.pdt_ptype=1 OR dbb.prsc_state=1) " .
            "ORDER BY dba.pdt_id";
        $ret = $this->mysqlQuery($sql, "all");

        //转义功能标签
        foreach($ret as $a=>$v){
            $ret[$a]['pdt_label'] = json_decode($v['pdt_label'],true);
        }

        //格式化菜单数组
        $cleaningList = array('menuID'=>'id','menuName'=>'pdt_name','menuEName'=>'pdt_ename','menuIntro'=>'pdt_intro','functionLabel'=>'pdt_label','curl'=>'pdt_url','versionType'=>'pdt_vtype','series'=>'pdt_series','menuVersion'=>'pdt_version');
        $rs['dataList'] = _findChildren($ret, 0, $cleaningList);

        //获取IRD产品绑定ID
        $sql_irdID = "SELECT u_product_key FROM idt_user WHERE u_id='{$data['userID']}'";
        $ret_irdID = $this->mysqlQuery($sql_irdID, "all");

        //初始化空IRD权限LIST
        $ret_irdKey_format = array();
        //验证产品ID
        if($ret_irdID[0]['u_product_key'] != null AND $ret_irdID[0]['u_product_key'] != ""){
            //获取IRD权限LIST
            $where_irdKey['iUserID'] = $ret_irdID[0]['u_product_key'];
            $ret_irdKey = $this->request()->_curlRADPost(IRD_SERVER_URL, ['v' => fnEncrypt(json_encode($where_irdKey), KEY)]);
            $ret_irdKey = json_decode($ret_irdKey,JSON_UNESCAPED_UNICODE);

            //格式化IRD权限LIST
            foreach($ret_irdKey['pplist'] as $a_format=>$v_format){
                $ret_irdKey_format[$v_format['ppname']] = $v_format;
            }

            //追加产品权限
            foreach($rs['dataList'] as $a=>$v){
                if($v['menuName'] == '艾瑞睿见'){
                    foreach($v['lowerTree'] as $a2=>$v2){
                        foreach($v2['lowerTree'] as $a3=>$v3){
                            if($v3['menuEName'] == 'iUserTracker'){
                                if($ret_irdKey_format['iut']){
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 1;
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = $ret_irdKey_format['iut']['ppurl']."?guid=".$ret_irdKey['iRGuid'];
                                } else {
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 0;
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "";
                                }
                            } else if($v3['menuEName'] == 'iAdTracker'){
                                if($ret_irdKey_format['iadt']){
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 1;
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = $ret_irdKey_format['iadt']['ppurl']."?guid=".$ret_irdKey['iRGuid'];
                                } else {
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 0;
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "";
                                }
                            } else if($v3['menuEName'] == 'iVideoTracker'){
                                if($ret_irdKey_format['ivt']){
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 1;
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = $ret_irdKey_format['ivt']['ppurl']."?guid=".$ret_irdKey['iRGuid'];
                                } else {
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 0;
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "";
                                }
                            } else if($v3['menuEName'] == 'mUserTracker'){
                                if($ret_irdKey_format['mut']){
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 1;
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = $ret_irdKey_format['mut']['ppurl']."?guid=".$ret_irdKey['iRGuid'];
                                } else {
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 0;
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "";
                                }
                            } else if($v3['menuEName'] == 'iMediaMatix'){
                                if($ret_irdKey_format['imm']){
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 1;
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = $ret_irdKey_format['imm']['ppurl']."?guid=".$ret_irdKey['iRGuid'];
                                } else {
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 0;
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "";
                                }
                            } else if($v3['menuEName'] == 'madTracker'){
                                if($ret_irdKey_format['madt']){
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 1;
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = $ret_irdKey_format['madt']['ppurl']."?guid=".$ret_irdKey['iRGuid'];
                                } else {
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 0;
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "";
                                }
                            } else if($v3['menuEName'] == 'iECTracker'){
                                if($ret_irdKey_format['iect']){
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 1;
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = $ret_irdKey_format['iect']['ppurl']."?guid=".$ret_irdKey['iRGuid'];
                                } else {
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 0;
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "";
                                }
                            } else if($v3['menuEName'] == 'imBlogTracker'){
                                if($ret_irdKey_format['imbt']){
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 1;
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = $ret_irdKey_format['imbt']['ppurl']."?guid=".$ret_irdKey['iRGuid'];
                                } else {
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 0;
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "";
                                }
                            } else if($v3['menuEName'] == 'TargetPlus'){
                                if($ret_irdKey_format['tgp']){
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 1;
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = $ret_irdKey_format['tgp']['ppurl']."?guid=".$ret_irdKey['iRGuid'];
                                } else {
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 0;
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "";
                                }
                            } else if($v3['menuEName'] == 'iUserTracker-en'){
                                if($ret_irdKey_format['iut-en']){
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 1;
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = $ret_irdKey_format['iut-en']['ppurl']."?guid=".$ret_irdKey['iRGuid'];
                                } else {
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 0;
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "";
                                }
                            } else if($v3['menuEName'] == 'mUserTracker-en'){
                                if($ret_irdKey_format['mut-en']){
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 1;
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = $ret_irdKey_format['mut-en']['ppurl']."?guid=".$ret_irdKey['iRGuid'];
                                } else {
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 0;
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "";
                                }
                            } else if($v3['menuEName'] == 'loyaltyPlus'){
                                if($ret_irdKey_format['lps']){
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 1;
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = $ret_irdKey_format['lps']['ppurl']."?guid=".$ret_irdKey['iRGuid'];
                                } else {
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 0;
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "";
                                }
                            } else if($v3['menuEName'] == 'EcommercePlus'){
                                if($ret_irdKey_format['ecp']){
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 1;
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = $ret_irdKey_format['ecp']['ppurl']."?guid=".$ret_irdKey['iRGuid'];
                                } else {
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 0;
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "";
                                }
                            } else if($v3['menuEName'] == 'iUserServey'){
                                if($ret_irdKey_format['ius']){
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 1;
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = $ret_irdKey_format['ius']['ppurl']."?guid=".$ret_irdKey['iRGuid'];
                                } else {
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 0;
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "";
                                }
                            } else if($v3['menuEName'] == 'EcommercePlus-en'){
                                if($ret_irdKey_format['ecp-en']){
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 1;
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = $ret_irdKey_format['ecp-en']['ppurl']."?guid=".$ret_irdKey['iRGuid'];
                                } else {
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 0;
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "";
                                }
                            } else if($v3['menuEName'] == 'iMediaMatix-en'){
                                if($ret_irdKey_format['imm-en']){
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 1;
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = $ret_irdKey_format['imm-en']['ppurl']."?guid=".$ret_irdKey['iRGuid'];
                                } else {
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 0;
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "";
                                }
                            } else if($v3['menuEName'] == 'mGameTracker'){
                                if($ret_irdKey_format['mgt']){
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 1;
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = $ret_irdKey_format['mgt']['ppurl']."?guid=".$ret_irdKey['iRGuid'];
                                } else {
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 0;
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "";
                                }
                            } else if($v3['menuEName'] == 'iAdMatrix'){
                                if($ret_irdKey_format['iadm']){
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 1;
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = $ret_irdKey_format['iadm']['ppurl']."?guid=".$ret_irdKey['iRGuid'];
                                } else {
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 0;
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "";
                                }
                            } else if($v3['menuEName'] == 'mStoreTracker'){
                                if($ret_irdKey_format['mst']){
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 1;
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = $ret_irdKey_format['mst']['ppurl']."?guid=".$ret_irdKey['iRGuid'];
                                } else {
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 0;
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "";
                                }
                            } else if($v3['menuEName'] == 'ECTracker'){
                                if($ret_irdKey_format['ect']){
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 1;
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = $ret_irdKey_format['ect']['ppurl']."?guid=".$ret_irdKey['iRGuid'];
                                } else {
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 0;
                                    $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "";
                                }
                            } else {
                                $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 0;
                                $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "";
                            }
                        }
                    }
                }
            }
        } else {
            //追加产品权限
            foreach($rs['dataList'] as $a=>$v){
                if($v['menuName'] == '艾瑞睿见'){
                    foreach($v['lowerTree'] as $a2=>$v2){
                        foreach($v2['lowerTree'] as $a3=>$v3){
                            $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['ptype'] = 0;
                            $rs['dataList'][$a]['lowerTree'][$a2]['lowerTree'][$a3]['curl'] = "";
                        }
                    }
                }
            }
        }

        //返回响应结果
        _SUCCESS('000000','查询成功',$rs);
    }
}