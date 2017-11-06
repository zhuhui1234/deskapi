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
        if(empty($data['companyFullNameID'])){
            _ERROR('000002', '公司ID不能为空');
        }
        if(empty($data['productID'])){
            _ERROR('000002', '产品ID不能为空');
        }
        if($data['state'] == 1){
            $state = " and idt_licence.u_id is null";
        }elseif ($data['state'] == 2){
            $state = " and idt_licence.u_id is not null";
        }else{

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
                where idt_licence.state = 1 and idt_licence.cpy_id = {$data['companyFullNameID']} and idt_licence.pdt_id = {$data['productID']}{$keyword}{$state}{$terminal} order by points desc";
        $ret = $this->mysqlQuery($sql, "all");
        foreach($ret as $key => $value){
            if($ret[$key]['u_id'] == $data['userID']){
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
//                if(!empty($ret[$key]['pc_due_time']) && !empty($ret[$key]['mobile_due_time']) && !empty($ret[$key]['ott_due_time'])){
//                    $own[$key]['terminal'] = "PC、Mobile、OTT";
//                }elseif(!empty($ret[$key]['pc_due_time']) && !empty($ret[$key]['mobile_due_time'])){
//                    $own[$key]['terminal'] = "PC、Mobile";
//                }elseif(!empty($ret[$key]['pc_due_time']) && !empty($ret[$key]['ott_due_time'])){
//                    $own[$key]['terminal'] = "PC、OTT";
//                }elseif(!empty($ret[$key]['mobile_due_time']) && !empty($ret[$key]['ott_due_time'])){
//                    $own[$key]['terminal'] = "Mobile、OTT";
//                }elseif(!empty($ret[$key]['pc_due_time'])){
//                    $own[$key]['terminal'] = "PC";
//                }elseif(!empty($ret[$key]['mobile_due_time'])){
//                    $own[$key]['terminal'] = "Mobile";
//                }elseif(!empty($ret[$key]['ott_due_time'])){
//                    $own[$key]['terminal'] = "OTT";
//                }else{
//                    $own[$key]['terminal'] = null;
//                }
                $rs['list'][$key]['terminal']['pc'] = array($ret[$key]['pc_start_time'],$ret[$key]['pc_due_time']);
                $rs['list'][$key]['terminal']['mobile'] = array($ret[$key]['mobile_start_time'],$ret[$key]['mobile_due_time']);
                $rs['list'][$key]['terminal']['ott'] = array($ret[$key]['ott_start_time'],$ret[$key]['ott_due_time']);
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
                if(!empty($ret[$key]['pc_due_time']) && !empty($ret[$key]['mobile_due_time']) && !empty($ret[$key]['ott_due_time'])){
                    $rs[$key]['terminal'] = "PC、Mobile、OTT";
                }elseif(!empty($ret[$key]['pc_due_time']) && !empty($ret[$key]['mobile_due_time'])){
                    $rs[$key]['terminal'] = "PC、Mobile";
                }elseif(!empty($ret[$key]['pc_due_time']) && !empty($ret[$key]['ott_due_time'])){
                    $rs[$key]['terminal'] = "PC、OTT";
                }elseif(!empty($ret[$key]['mobile_due_time']) && !empty($ret[$key]['ott_due_time'])){
                    $rs[$key]['terminal'] = "Mobile、OTT";
                }elseif(!empty($ret[$key]['pc_due_time'])){
                    $rs[$key]['terminal'] = "PC";
                }elseif(!empty($ret[$key]['mobile_due_time'])){
                    $rs[$key]['terminal'] = "Mobile";
                }elseif(!empty($ret[$key]['ott_due_time'])){
                    $rs[$key]['terminal'] = "OTT";
                }else{
                    $rs[$key]['terminal'] = null;
                }
                $rs[$key]['createTime'] = $ret[$key]['lic_cdate'];
                $rs[$key]['lastUpdateTime'] = $ret[$key]['lic_edate'];
                $rs[$key]['remark'] = $ret[$key]['lic_comment'];
            }
        }
        if(!empty($own)){
            $rs = array_merge($own,$rs);
        }
        _SUCCESS('000000', '查询成功', $rs);
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
        $sql = "update idt_licence set u_id = null,lic_edate = $upTimes,lic_author_uid = '{$data['userID']}' where licence_key = '{$data['licenceKey']}'";
        $ret = $this->mysqlQuery($sql);
        if($ret){
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
        if (empty($data['licenceKey'])) {
            _ERROR('000002', '缺少参数');
        }
        $sql = "SELECT
                    point_id,
                    idt_company.cpy_cname,
                    idt_user.u_name,
                    idt_user.u_mobile,
                    point_explain,
                    state,
                    point_value,
                    type,
                    balance,
                    idt_product.pdt_ename,
                    idt_points.cdate 
                FROM
                    idt_points
                    LEFT JOIN idt_company ON idt_company.cpy_id = idt_points.cpy_id
                    LEFT JOIN idt_user ON idt_user.u_id = idt_points.u_id
                    LEFT JOIN idt_product ON idt_product.pdt_id = idt_points.pdt_id 
                WHERE
                    idt_points.licence_key = {$data['licenceKey']} 
                    ORDER BY idt_points.cdate DESC";
        $ret = $this->mysqlQuery($sql, 'all');
        if ($ret) {
            if (empty($ret)) {
                $rs = [];
            }else{
                foreach ($ret as $key => $value){
                    $rs[$key]['changedPoint'] = $ret[$key]['point_value'];
                    $rs[$key]['companyFullName'] = $ret[$key]['cpy_cname'];
                    $rs[$key]['userName'] = $ret[$key]['u_name'];
                    $rs[$key]['mobile'] = $ret[$key]['u_mobile'];
                    $rs[$key]['type'] = $ret[$key]['type'];
                    $rs[$key]['productName'] = $ret[$key]['pdt_ename'];
                    if($ret[$key]['type'] != 1){
                        $arr = json_decode($ret[$key]['point_explain'],true);
                        $rs[$key]['customReportName'] = $arr['Name'];
                        $rs[$key]['customReportTicketID'] = "{$arr['pdt_name']}-{$arr['ID']}";
                        if($ret[$key]['type'] == 6){
                            $rs[$key]['log'] = "生成报告";
                        }elseif($ret[$key]['type'] == 2){
                            $rs[$key]['log'] = "定制报告失败后退回积分";
                        }
                    }else{
                        $rs[$key]['customReportName'] = null;
                        $rs[$key]['customReportTicketID'] = $ret[$key]['point_id'];
                        $rs[$key]['log'] = $ret[$key]['point_explain'];
                    }
                }
            }
            _SUCCESS('000000', 'ok', $rs);
        } else {
            _ERROR('000001', 'error');
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

}