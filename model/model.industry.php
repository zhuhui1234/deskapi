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
class IndustryModel extends AgentModel
{

    public function __consturct()
    {

    }

    //查询大行业LIST
    public function IndustryMaxList($where)
    {
        //查询条件
        $where['orderByColumn'] == null ? $orderByColumn = 'ity_id' : $orderByColumn = $where['orderByColumn'];
        $where['orderByType'] == null ? $orderByType = 'asc' : $orderByType = $where['orderByType'];
        $where['pageSize'] == null ? $pageSize = '10' : $pageSize = $where['pageSize'];
        $where['pageNo'] == null ? $pageNo = '0' : $pageNo = ($where['pageNo'] - 1) * $pageSize;
        $where['keyword'] == null ? $keyword = '' : $keyword = " AND ity_name LIKE '%" . $where['keyword'] . "%'";

        //查询大行业LIST
        $sql = "SELECT ity_id,ity_img,ity_name,ity_describe,ity_state,ity_cdate,ity_edate FROM ivw_industry WHERE 1=1 {$keyword} AND ity_type='1' order by {$orderByColumn} {$orderByType} limit {$pageNo},{$pageSize}";
        $ret['data'] = $this->mysqlQuery($sql, "all");
        //查询总条数
        $sql = "SELECT COUNT(1) FROM ivw_industry WHERE 1=1 {$keyword} AND ity_type='1'";
        $ret['size'] = $this->mysqlQuery($sql, "row");
        return $ret;
    }

    //查询小行业LIST
    public function IndustryMinList($where)
    {
        //查询初始化条件
        $where['orderByColumn'] == null ? $orderByColumn = 'ity_id' : $orderByColumn = $where['orderByColumn'];
        $where['orderByType'] == null ? $orderByType = 'asc' : $orderByType = $where['orderByType'];
        $where['pageSize'] == null ? $pageSize = '10' : $pageSize = $where['pageSize'];
        $where['pageNo'] == null ? $pageNo = '0' : $pageNo = ($where['pageNo'] - 1) * $pageSize;
        $where['keyword'] == null ? $keyword = '' : $keyword = " AND ity_name LIKE '%" . $where['keyword'] . "%'";

        //查询小行业LIST
        $sql = "SELECT ity_id,ity_img,ity_name,ity_describe,ity_state,ity_cdate,ity_edate FROM ivw_industry WHERE 1=1 {$keyword} AND ity_type='2' AND ity_sid='{$where['ity_sid']}' order by {$orderByColumn} {$orderByType} limit {$pageNo},{$pageSize}";
        $ret['data'] = $this->mysqlQuery($sql, "all");
        //查询总条数
        $sql = "SELECT COUNT(1) FROM ivw_industry WHERE 1=1 {$keyword} AND ity_type='2' AND ity_sid='{$where['ity_sid']}'";
        $ret['size'] = $this->mysqlQuery($sql, "row");
        return $ret;
    }

}
