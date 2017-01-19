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
class ConfigModel extends AgentModel
{

    public function __consturct()
    {

    }

    //查询配置LIST
    public function configList($where)
    {
        //查询自定义条件
        $where['cfg_model'] == null ? $cfg_model = "" : $cfg_model = "AND a1.cfg_model=" . $where['cfg_model'];
        $where['cfg_model'] == null ? $model_key = 2 : $model_key = 1;

        //查询小行业LIST
        $sql = "SELECT a1.cfg_id cfg_id,a1.cfg_guid cfg_guid,a1.cfg_name cfg_name,a1.cfg_type cfg_type,a1.cfg_sid cfg_sid,a2.cfgs_url cfgs_url FROM ivw_config a1 LEFT JOIN ivw_config_service a2 ON(a1.cfg_service = a2.cfgs_id) WHERE 1={$model_key} {$cfg_model}";
        $ret['data'] = $this->mysqlQuery($sql, "all");
        //查询总条数
        $sql = "SELECT COUNT(1) FROM ivw_config WHERE 1={$model_key} AND cfg_type=1 {$cfg_model}";
        $ret['size'] = $this->mysqlQuery($sql, "row");
        return $ret;
    }

    //服务申请
    public function setAudit($where)
    {
        //用户参数
        $uptimes = date("Y-m-d H:i:s");//new date
        $sql = "SELECT u_id,cpy_id FROM ivw_user WHERE 1=1 AND u_account='".$where['u_account']."'";
        $ret_cuid = $this->mysqlQuery($sql, "all");

        //查询申请记录
        $sql_count = "SELECT COUNT(1) FROM ivw_audit WHERE 1=1 AND u_id='{$ret_cuid[0]['u_id']}' AND cpy_id='{$ret_cuid[0]['cpy_id']}' AND cfg_id='{$where['cfg_id']}'";
        $ret_ccount = $this->mysqlQuery($sql_count, "row");

        //服务申请参数
        $audit_where['adt_mobile'] = $where['adt_mobile']; //手机号码
        $audit_where['adt_supplement'] = $where['adt_supplement']; //补充需求
        $audit_where['adt_managers'] = $where['adt_managers']; //审核对象(1.艾瑞管理员 2.公司内部管理员)
        $audit_where['adt_edate'] = $uptimes; //最后审核时间

        //验证
        if($ret_ccount[0] >= 1)
        {
            $id = "u_id='{$ret_cuid[0]['u_id']}' AND cpy_id='{$ret_cuid[0]['cpy_id']}' AND cfg_id='{$where['cfg_id']}'";
            //更新服务申请
            $ret = $this->mysqlEdit('ivw_audit',$audit_where, $id);
        } else {
            $audit_where['adt_cdate'] = $uptimes; //申请时间
            $audit_where['cpy_id'] = $ret_cuid[0]['cpy_id']; //所属公司
            $audit_where['u_id'] = $ret_cuid[0]['u_id']; //申请帐号
            $audit_where['cfg_id'] = $where['cfg_id']; //申请报表ID
            //提交服务申请
            $ret = $this->mysqlInsert('ivw_audit',$audit_where);
        }

        return $ret;
    }

    //获取服务列表
    public function getAuditList($where)
    {
        //查询初始化条件
        $where['orderByColumn'] == null ? $orderByColumn = 'adt_id' : $orderByColumn = $where['orderByColumn']; //排序字段
        $where['orderByType'] == null ? $orderByType = 'asc' : $orderByType = $where['orderByType']; //排序方式
        $where['pageSize'] == null ? $pageSize = '10' : $pageSize = $where['pageSize']; //查询数据
        $where['pageNo'] == null ? $pageNo = '0' : $pageNo = ($where['pageNo'] - 1) * $pageSize; //查询页数
        $where['keyword'] == null ? $keyword = '' : $keyword = " AND db2.u_account LIKE '%" . $where['keyword'] . "%'"; //查询条件

        //获取公司ID
        $sql = "SELECT u_id,cpy_id FROM ivw_user WHERE 1=1 AND u_account='".$where['u_account']."'";
        $ret_cuid = $this->mysqlQuery($sql, "all");

        //查询申请LIST
        $sql = "SELECT db1.adt_id adt_id,db3.cpy_cname cpy_cname,db2.u_account u_account,db1.adt_mobile adt_mobile,db4.cfg_name cfg_name,db1.adt_supplement adt_supplement,db1.adt_cdate adt_cdate,db1.adt_state adt_state FROM ivw_audit db1 LEFT JOIN ivw_user db2 ON (db1.u_id = db2.u_id) LEFT JOIN ivw_company db3 ON (db1.cpy_id = db3.cpy_id) LEFT JOIN ivw_config db4 ON (db1.cfg_id = db4.cfg_id) WHERE 1=1{$keyword} AND db1.cpy_id={$ret_cuid[0]['cpy_id']} order by {$orderByColumn} {$orderByType} limit {$pageNo},{$pageSize}";
        $ret['data'] = $this->mysqlQuery($sql, "all");
        //查询总条数
        $sql = "SELECT COUNT(1) FROM ivw_audit db1 LEFT JOIN ivw_user db2 ON (db1.u_id = db2.u_id) LEFT JOIN ivw_company db3 ON (db1.cpy_id = db3.cpy_id) LEFT JOIN ivw_config db4 ON (db1.cfg_id = db4.cfg_id) WHERE 1=1{$keyword} AND db1.cpy_id={$ret_cuid[0]['cpy_id']}";
        $ret['size'] = $this->mysqlQuery($sql, "row");

        return $ret;
    }

    //获取服务详情
    public function getAuditInfo($where)
    {
        //查询申请详情
        $sql = "SELECT db1.adt_id adt_id,db3.cpy_cname cpy_cname,db2.u_account u_account,db1.adt_mobile adt_mobile,db4.cfg_name cfg_name,db1.adt_supplement adt_supplement,db1.adt_cdate adt_cdate,db1.adt_state adt_state FROM ivw_audit db1 LEFT JOIN ivw_user db2 ON (db1.u_id = db2.u_id) LEFT JOIN ivw_company db3 ON (db1.cpy_id = db3.cpy_id) LEFT JOIN ivw_config db4 ON (db1.cfg_id = db4.cfg_id) WHERE 1=1 AND db1.adt_id='{$where['adt_id']}'";
        $ret = $this->mysqlQuery($sql, "all");

        return $ret;
    }

    //服务审核
    public function upAudit($where)
    {
        //获取用户GUID
        $sql = "SELECT u_id FROM ivw_user WHERE 1=1 AND u_account='".$where['u_account']."'";
        $ret_cuid = $this->mysqlQuery($sql, "all");

        //审核操作
        $uptimes = date("Y-m-d H:i:s");//new date
        $upaudit_where['adt_state'] = $where['adt_state'];
        $upaudit_where['adt_author'] = $ret_cuid[0]['u_id'];
        $upaudit_where['adt_edate'] = $uptimes;
        $id = " adt_id='".$where['adt_id']."'";//用户帐号
        $ret = $this->mysqlEdit('ivw_audit',$upaudit_where,$id);

        return $ret;
    }

}
