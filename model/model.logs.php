<?php
/**
 * Created by iResearch
 * Logs 数据层
 * Author JOSON <joson@iresearch.com.cn>
 * Create 2017-01-22 15:38
 * Update 2017-01-22 15:38
 * FileName:controller.logs.php
 * 描述:
 */
class LogsModel extends AgentModel
{

    public function __construct($className)
    {
        parent::__construct($className);
    }

    //获取日志LIST
    public function logList($data)
    {
        //查询初始化条件
        $data['orderByColumn'] == null ? $orderByColumn = 'sys_id' : $orderByColumn = $data['orderByColumn']; //排序字段
        $data['orderByType'] == null ? $orderByType = 'DESC' : $orderByType = $data['orderByType']; //排序方式
        $data['pageSize'] == null ? $pageSize = '10' : $pageSize = $data['pageSize']; //查询数据
        $data['pageNo'] == null ? $pageNo = '0' : $pageNo = ($data['pageNo'] - 1) * $pageSize; //查询页数
        $data['keyword'] == null ? $keyword = '' : $keyword = " AND (loguser LIKE '%" . $data['logUser'] . "%' OR resmsg LIKE '%" . $data['resMsg'] . "%')"; //查询条件

        //获取日志
        $sql = "SELECT sys_id logid,sys_user loguser,sys_resmsg resmsg,sys_ip logip,sys_ctime createtime 
                FROM idt_sys_logs WHERE 1=1{$keyword} AND sys_sys='irview_deskapi' 
                ORDER BY {$orderByColumn} {$orderByType} LIMIT {$pageNo},{$pageSize}";
        $ret = $this->mysqlQuery($sql, "all");

        //执行总数
        $sql_count = "SELECT COUNT(1) count_num FROM idt_sys_logs WHERE 1=1{$keyword} AND sys_sys='irview_deskapi'";
        $ret_count = $this->mysqlQuery($sql_count, "all");

        //返回参数-执行结果
        foreach($ret as $a=>$v){
            $rs['dataList'][$a]['logID'] = (int)$v['logid']; //日志ID
            $rs['dataList'][$a]['logUser'] = $v['loguser']; //操作用户
            $rs['dataList'][$a]['resMsg'] = $v['resmsg']; //操作内容
            $rs['dataList'][$a]['logIP'] = $v['logip']; //所在IP
            $rs['dataList'][$a]['createTime'] = $v['createtime']; //操作时间
        }

        //返回参数-执行总数
        $rs['totalSize'] = $ret_count[0]['count_num'];

        //返回响应结果
        _SUCCESS('000000','查询日志',$rs);
    }

    public function pushLog($data)
    {
        if (isset($data['user']) && isset($data['action'])) {
            $data = $this->__checkLogData($data);

            if (!empty($data['user']) AND !empty($data['action'])) {
//                var_dump($data);
                $where = [
                    'log_user' => $data['user'],
                    'log_companyID' => $data['companyID'],
                    'log_type' => $data['type'],
                    'log_status' => $data['status'],
                    'log_resource' => $data['resource'],
                    'log_content' => $data['content'],
                    'log_level' => $data['level'],
                    'log_subid' => $data['sub_id'],
                    'log_fingerprint' => $data['fingerprint'],
                    'log_ip' => $data['log_ip'],
                    'log_datetime' => time(),
                    'log_action' => $data['action'],
                ];
                return $this->mysqlInsert('idt_logs', $where);
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    private function __checkLogData(array $data) {
        if (!isset($data['companyID'])) {
            $data['companyID'] = null;
        }

        if (!isset($data['status'])){
            $data['status'] = null;
        }

        if (!isset($data['type'])) {
            $data['type'] = null;
        }

        if (!isset($data['content'])) {
            $data['content']  = null;
        }

        if (!isset($data['level'])) {
            $data['level'] = 0;
        }

        if (!isset($data['sub_id'])) {
            $data['sub_id'] = null;
        }

        if (!isset($data['resource'])) {
            $data['resource'] = null;
        }

        if (!isset($data['fingerprint'])) {
            $data['fingerprint'] = time();
        }
        return $data;
    }

}
