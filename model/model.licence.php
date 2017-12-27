<?php
/**
 * Created by PhpStorm.
 * User: zh
 * Date: 2017/10/27
 * Time: 15:14
 */
class LicenceModel extends AgentModel
{

    public function __construct($classname)
    {
        parent::__construct($classname);
    }

    public function getLicencesByCompanyFullNameID($data)
    {
        $data['pageSize'] == null ? $pageSize = '10' : $pageSize = $data['pageSize']; //查询数据
        $data['pageNo'] == null ? $pageNo = '0' : $pageNo = ($data['pageNo'] - 1) * $pageSize; //查询页数
        if(empty($data['companyFullNameID'])){
            _ERROR('000002', '公司ID不能为空');
        }
        if(empty($data['productID'])){
            _ERROR('000002', '产品ID不能为空');
        }
        if($data['state'] == 1){
            $state = " and (idt_licence.u_id is null or idt_licence.u_id = '{$data['licenceUserID']}')";
        }elseif ($data['state'] == 2){
            $state = " and idt_licence.u_id is not null";
        }else{
            $state = " and 1=1";
        }
        switch($data['terminal']){
            case 1:
                $terminal = " and pc_due_time != null and pc_due_time is not null";
                break;
            case 2:
                $terminal = " and mobile_due_time != null and mobile_due_time is not null";
                break;
            case 3:
                $terminal = " and ott_due_time != null and ott_due_time is not null";
                break;
            case 4:
                $terminal = " and pc_due_time != null and pc_due_time is not null and mobile_due_time != null and mobile_due_time is not null";
                break;
            case 5:
                $terminal = " and pc_due_time != null and pc_due_time is not null and ott_due_time != null and ott_due_time is not null";
                break;
            case 6:
                $terminal = " and ott_due_time != null and ott_due_time is not null and mobile_due_time != null and mobile_due_time is not null";
                break;
            case 7:
                $terminal = " and mobile_due_time != null and mobile_due_time is not null and pc_due_time != null and pc_due_time is not null and ott_due_time != null and ott_due_time is not null";
                break;
        }
        $data['keyword'] == null ? $keyword = '' : $keyword = " AND (idt_user.u_mobile LIKE '%" . $data['keyword'] . "%' or idt_user.u_mobile LIKE '%" . $data['keyword'] . "%')"; //查询条件
        $sql = "select licence_id,idt_licence.licence_key,idt_licence.cpy_id,idt_licence.u_id,idt_licence.pdt_id,points,lic_cdate,lic_edate,lic_comment,u_mobile,u_name,pdt_ename,
                pc_due_time,mobile_due_time,ott_due_time,pc_start_time,mobile_start_time,ott_start_time 
                from idt_licence
                left join idt_product on idt_product.pdt_id = idt_licence.pdt_id
                left join idt_user on idt_user.u_id = idt_licence.u_id
                left join idt_subproduct on idt_subproduct.licence_key = idt_licence.licence_key
                where idt_licence.state = 1 and idt_licence.cpy_id = {$data['companyFullNameID']} and idt_licence.pdt_id = {$data['productID']}{$keyword}{$state}{$terminal} order by points desc LIMIT {$pageNo},{$pageSize}";
        $ret = $this->mysqlQuery($sql, "all");
        foreach($ret as $key => $value){
            if($ret[$key]['u_id'] == $data['licenceUserID']){
                $own[$key]['licenceID'] = $ret[$key]['licence_id'];
                $own[$key]['licenceKey'] = $ret[$key]['licence_key'];
                $own[$key]['companyFullNameID'] = $ret[$key]['cpy_id'];
                $own[$key]['userID'] = $ret[$key]['u_id'];
                $own[$key]['userName'] = $ret[$key]['u_name'];
                $own[$key]['productID'] = $ret[$key]['pdt_id'];
                $own[$key]['productName'] = $ret[$key]['pdt_ename'];
                $own[$key]['mobile'] = $ret[$key]['u_mobile'];
                $own[$key]['initial_points'] = $ret[$key]['points'];
                $own[$key]['remaining_points'] = $this->__computingBalancePoint($ret[$key]['licence_key']); //剩余积分
                $own[$key]['terminal'] = null;
                if(!empty($ret[$key]['pc_due_time'])){
                    $own[$key]['terminal']['pc'] = array($ret[$key]['pc_start_time'],$ret[$key]['pc_due_time']);
                }
                if(!empty($ret[$key]['mpbile_due_time'])){
                    $own[$key]['terminal']['mobile'] = array($ret[$key]['mobile_start_time'],$ret[$key]['mobile_due_time']);
                }
                if(!empty($ret[$key]['ott_due_time'])){
                    $own[$key]['terminal']['ott'] = array($ret[$key]['ott_start_time'],$ret[$key]['ott_due_time']);
                }
                $own[$key]['createTime'] = $ret[$key]['lic_cdate'];
                $own[$key]['lastUpdateTime'] = $ret[$key]['lic_edate'];
                $own[$key]['remark'] = $ret[$key]['lic_comment'];
            }else{
                $rs[$key]['licenceID'] = $ret[$key]['licence_id'];
                $rs[$key]['licenceKey'] = $ret[$key]['licence_key'];
                $rs[$key]['companyFullNameID'] = $ret[$key]['cpy_id'];
                $rs[$key]['userID'] = $ret[$key]['u_id'];
                $rs[$key]['userName'] = $ret[$key]['u_name'];
                $rs[$key]['productID'] = $ret[$key]['pdt_id'];
                $rs[$key]['productName'] = $ret[$key]['pdt_ename'];
                $rs[$key]['mobile'] = $ret[$key]['u_mobile'];
                $rs[$key]['initial_points'] = $ret[$key]['points'];
                $rs[$key]['remaining_points'] = $this->__computingBalancePoint($ret[$key]['licence_key']); //剩余积分
                $rs[$key]['terminal'] = null;
                if(!empty($ret[$key]['pc_due_time'])){
                    $rs[$key]['terminal']['pc'] = array($ret[$key]['pc_start_time'],$ret[$key]['pc_due_time']);
                }
                if(!empty($ret[$key]['mpbile_due_time'])){
                    $rs[$key]['terminal']['mobile'] = array($ret[$key]['mobile_start_time'],$ret[$key]['mobile_due_time']);
                }
                if(!empty($ret[$key]['ott_due_time'])){
                    $rs[$key]['terminal']['ott'] = array($ret[$key]['ott_start_time'],$ret[$key]['ott_due_time']);
                }
                $rs[$key]['createTime'] = $ret[$key]['lic_cdate'];
                $rs[$key]['lastUpdateTime'] = $ret[$key]['lic_edate'];
                $rs[$key]['remark'] = $ret[$key]['lic_comment'];
            }
        }
        if(!empty($own)){
            $rs = array_merge($own,$rs);
        }
        $return['list'] = $rs;
        foreach ($return['list'] as $k => $v){
            $return['list'][$k]['index'] = ($k+1) * ($pageNo+1);
        }
        //返回参数-执行总数
        $sql_count = "select count(*) as count_num
                from idt_licence
                left join idt_product on idt_product.pdt_id = idt_licence.pdt_id
                left join idt_user on idt_user.u_id = idt_licence.u_id
                left join idt_subproduct on idt_subproduct.licence_key = idt_licence.licence_key
                where idt_licence.state = 1 and idt_licence.cpy_id = {$data['companyFullNameID']} and idt_licence.pdt_id = {$data['productID']}{$keyword}{$state}{$terminal}";
        $ret_count = $this->mysqlQuery($sql_count,'all');
        $return['totalSize'] = $ret_count[0]['count_num'];
        _SUCCESS('000000', '查询成功', $return);
    }

    public function getLicencesByUserID($data)
    {
        if(empty($data['userID'])){
            _ERROR('000002', '用户ID不能为空');
        }
        $data['keyword'] == null ? $keyword = '' : $keyword = " AND (idt_product.pdt_ename LIKE '%" . $data['keyword'] . "%' or idt_product.pdt_id LIKE '%" . $data['keyword'] . "%')"; //查询条件
        $sql = "select licence_id,licence_key,idt_licence.cpy_id,idt_licence.u_id,idt_licence.pdt_id,points,lic_cdate,lic_edate,lic_comment,u_mobile,u_name,pdt_ename 
                from idt_licence
                left join idt_product on idt_product.pdt_id = idt_licence.pdt_id
                left join idt_user on idt_user.u_id = idt_licence.u_id
                where idt_licence.state = 1 and idt_licence.u_id = '{$data['userID']}' {$keyword} order by lic_edate desc,lic_cdate desc";
        $ret = $this->mysqlQuery($sql, "all");
        foreach($ret as $key => $value){
            $rs[$key]['licenceID'] = $ret[$key]['licence_id'];
            $rs[$key]['licenceKey'] = $ret[$key]['licence_key'];
            $rs[$key]['companyFullNameID'] = $ret[$key]['cpy_id'];
            $rs[$key]['userID'] = $ret[$key]['u_id'];
            $rs[$key]['userName'] = $ret[$key]['u_name'];
            $rs[$key]['productID'] = $ret[$key]['pdt_id'];
            $rs[$key]['productName'] = $ret[$key]['pdt_ename'];
            $rs[$key]['mobile'] = $ret[$key]['u_mobile'];
            $rs[$key]['initial_points'] = $ret[$key]['points'];//初始积分
            $rs[$key]['remaining_points'] = $this->__computingBalancePoint($ret[$key]['licence_key']); //剩余积分
            $rs[$key]['createTime'] = $ret[$key]['lic_cdate'];
            $rs[$key]['lastUpdateTime'] = $ret[$key]['lic_edate'];
            $rs[$key]['remark'] = $ret[$key]['lic_comment'];
        }
        _SUCCESS('000000', '查询成功', $rs);
    }

    public function editLicencesByUserID($data)
    {
        $upTimes = date("Y-m-d H:i:s");
        if(empty($data['licenceKey'])){
            _ERROR('000002', '许可证Key不能为空');
        }
        $sql = "select licence_id from idt_licence where u_id = '{$data['licenceUserID']}' and pdt_id = {$data['productID']}";
        $ret = $this->mysqlQuery($sql, "all");
        if(count($ret) >0 ){
            _ERROR('000001', '该用户已绑定许可证');
        }
        $where_editUser['u_id'] = $data['licenceUserID']; //姓名
        $where_editUser['lic_author_uid'] = $data['userID']; //最后更新时间
        $where_editUser['lic_edate'] = $upTimes; //最后更新时间
        $where_editUser['lic_comment'] = $data['remark']; //备注
        $id_editUser = " licence_key='" . $data['licenceKey'] . "'";//用户GUID
        $ret = $this->mysqlEdit('idt_licence', $where_editUser, $id_editUser);
        write_to_log(json_encode($where_editUser), '_licence');
        //验证并返回响应结果
        if ($ret == 1) {
            _SUCCESS('000000', '修改成功');
        } else {
            _ERROR('000001', '修改失败');
        }
    }

    public function removeLicencesByLicenceKey($data)
    {
        $upTimes = date("Y-m-d H:i:s");
        if(empty($data['licenceKey'])){
            _ERROR('000002', '许可证Key不能为空');
        }
        $sql = "update idt_licence set u_id = null,lic_edate = '$upTimes',lic_author_uid = '{$data['userID']}' where licence_key = '{$data['licenceKey']}'";
        $ret = $this->mysqlQuery($sql);
        if($ret){
            write_to_log('removeLicence'.$data['licenceKey'], '_licence');
            //验证并返回响应结果
            _SUCCESS('000000', '修改成功');
        }else{
            _ERROR('000001','修改失败');
        }
    }

    /**
     * @param $data
     */
    public function getPointLogByLicenceKey($data)
    {
        $data['pageSize'] == null ? $pageSize = '10' : $pageSize = $data['pageSize']; //查询数据
        $data['pageNo'] == null ? $pageNo = '0' : $pageNo = ($data['pageNo'] - 1) * $pageSize; //查询页数
        if (empty($data['licenceKey'])) {
            _ERROR('000002', '缺少参数');
        }
        $sql = "SELECT
                    point_id,
                    idt_company.cpy_cname,
                    idt_user.u_name,
                    idt_user.u_mobile,
                    point_explain,
                    idt_points.state,
                    point_value,
                    type,
                    balance,
                    idt_product.pdt_ename,
                    idt_points.cdate 
                FROM
                    idt_points
                    LEFT JOIN idt_user ON idt_user.u_id = idt_points.u_id
                    LEFT JOIN idt_company ON idt_company.cpy_id = idt_user.cpy_id
                    LEFT JOIN idt_product ON idt_product.pdt_id = idt_points.pdt_id 
                WHERE
                    idt_points.licence_key = '{$data['licenceKey']}' 
                    ORDER BY idt_points.cdate DESC limit {$pageNo},{$pageSize}";
        $ret = $this->mysqlQuery($sql, 'all');
        if ($ret) {
            if (empty($ret)) {
                $rs = [];
            }else{
                foreach ($ret as $key => $value){
                    $rs['list'][$key]['changedPoint'] = $ret[$key]['point_value'];
                    $rs['list'][$key]['companyFullName'] = $ret[$key]['cpy_cname'];
                    $rs['list'][$key]['userName'] = $ret[$key]['u_name'];
                    $rs['list'][$key]['mobile'] = $ret[$key]['u_mobile'];
                    $rs['list'][$key]['type'] = $ret[$key]['type'];
                    $rs['list'][$key]['productName'] = $ret[$key]['pdt_ename'];
                    $rs['list'][$key]['cDate'] = $ret[$key]['cdate'];
                    if($ret[$key]['type'] != 1){
                        $arr = json_decode($ret[$key]['point_explain'],true);
                        $rs['list'][$key]['customReportName'] = $arr['Name'];
                        $rs['list'][$key]['customReportTicketID'] = "{$arr['pdt_name']}-{$arr['ID']}";
                        if($ret[$key]['type'] == 6){
                            $rs['list'][$key]['remainingPoints'] = $ret[$key]['balance']-$ret[$key]['point_value'];
                            $rs['list'][$key]['log'] = "生成报告";
                        }elseif($ret[$key]['type'] == 2){
                            $rs['list'][$key]['remainingPoints'] = $ret[$key]['balance']+$ret[$key]['point_value'];
                            $rs['list'][$key]['log'] = "定制报告失败后退回积分";
                        }
                    }else{
                        $rs['list'][$key]['remainingPoints'] = $ret[$key]['point_value']+$ret[$key]['balance'];
                        $rs['list'][$key]['customReportName'] = null;
                        $rs['list'][$key]['customReportTicketID'] = null;
                        $rs['list'][$key]['log'] = $ret[$key]['point_explain'];
                    }
                }
            }
            //返回参数-执行总数
            $sql_count = "SELECT
                    count(*) as count_num 
                FROM
                    idt_points
                    LEFT JOIN idt_user ON idt_user.u_id = idt_points.u_id
                    LEFT JOIN idt_company ON idt_company.cpy_id = idt_user.cpy_id
                    LEFT JOIN idt_product ON idt_product.pdt_id = idt_points.pdt_id 
                WHERE
                    idt_points.licence_key = '{$data['licenceKey']}' ";
            $ret_count = $this->mysqlQuery($sql_count,'all');
            $rs['totalSize'] = $ret_count[0]['count_num'];
            foreach ($rs['list'] as $k => $v){
                $rs['list'][$k]['index'] = ($k+1) * ($pageNo+1);
            }
            _SUCCESS('000000', 'ok', $rs);
        } else {
            _ERROR('000001', 'error');
        }
    }

    public function getUserList($data)
    {
        //查询初始化条件
        $data['keyword'] == null ? $keyword = '' : $keyword = " AND (dba.u_mobile LIKE '%" . $data['keyword'] . "%' OR dba.u_name LIKE '%" . $data['keyword'] . "%')"; //查询条件

        //获取当前用户所属公司ID
        $sql_companyID = "SELECT cpy_id FROM idt_user WHERE 1=1 AND u_id='{$data['userID']}'";
        $ret_companyID = $this->mysqlQuery($sql_companyID, "all");
        if ($ret_companyID[0]['cpy_id'] == 0 OR $ret_companyID[0]['cpy_id'] == null OR $ret_companyID[0]['cpy_id'] == "") {
            _ERROR('000002', '查询失败,非法用户');
        }

        //执行查询
        $sql = "SELECT dba.u_id,dba.u_mobile mobile,dba.u_name
            FROM idt_user dba 
            LEFT JOIN idt_company dbb ON (dba.cpy_id=dbb.cpy_id AND dba.cpy_id={$ret_companyID[0]['cpy_id']}) 
            WHERE 1=1 AND dba.u_state=0 
            AND dbb.cpy_state=0 
            AND (dba.u_permissions=1 OR dba.u_permissions=2) {$keyword}";

        $ret = $this->mysqlQuery($sql, "all");
        //返回结果
        $rs = array();
        //返回参数-执行结果
        foreach ($ret as $a => $v) {
            $rs['list'][$a]['userID'] = $v['u_id']; //用户GUID
            $rs['list'][$a]['mobile'] = $v['mobile']; //手机
            $rs['list'][$a]['name'] = $v['u_name']; //姓名
        }
        //查询成功,返回响应结果
        _SUCCESS('000000', '查询成功', $rs);
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

}