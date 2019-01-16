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

    /**
     * log list
     *
     * @param $data
     */
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
        foreach ($ret as $a => $v) {
            $rs['dataList'][$a]['logID'] = (int)$v['logid']; //日志ID
            $rs['dataList'][$a]['logUser'] = $v['loguser']; //操作用户
            $rs['dataList'][$a]['resMsg'] = $v['resmsg']; //操作内容
            $rs['dataList'][$a]['logIP'] = $v['logip']; //所在IP
            $rs['dataList'][$a]['createTime'] = $v['createtime']; //操作时间
        }

        //返回参数-执行总数
        $rs['totalSize'] = $ret_count[0]['count_num'];

        //返回响应结果
        _SUCCESS('000000', '查询日志', $rs);
    }

    /**
     * push log
     *
     * @param $data
     * @return array|bool|int|string
     */
    public function pushLog($data)
    {
        if (isset($data['user']) && isset($data['action'])) {
            $data = $this->__checkLogData($data);

            if (!empty($data['log_ip'])) {
                $this->__createIPLog($data['log_ip']);
            }

            if (!empty($data['user']) AND !empty($data['action'])) {

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

    public function test($data)
    {
        if (!empty($data['log_ip'])) {
            $ret = $this->__createIPLog($data['log_ip']);
        }

        return $ret;
    }


    ######################################################################################
    ##################################                     ###############################
    #################################   PRIVATE METHODS   ################################
    ################################                     #################################
    ######################################################################################

    /**
     * create ip log
     * @param $ip
     */
    private function __createIPLog($ip)
    {
        if ($this->__hasIpLog($ip)) {
            write_to_log('ip: ' . $ip, '_ip');
            if ($ip != '127.0.0.1' and $ip != '10.110.91.127') {
                $ip_api = IpInfo::query($ip);
                write_to_log('get ip: ' . $ip . ':   ' . json_encode((array)$ip_api), '_ip');
            } else {
                $ip_api = null;
            }

            if (!empty($ip_api)) {
                $ret = $this->mysqlInsert('idt_iplog', [
                    'ipl_as' => $ip_api->as,
                    'ipl_city' => $ip_api->city,
                    'ipl_country' => $ip_api->country,
                    'ipl_country_code' => $ip_api->countryCode,
                    'ipl_region' => $ip_api->region,
                    'ipl_region_name' => $ip_api->regionName,
                    'ipl_lat' => $ip_api->lat,
                    'ipl_lon' => $ip_api->lon,
                    'ipl_timezone' => $ip_api->timezone,
                    'ipl_isp' => $ip_api->isp,
                    'ipl_org' => $ip_api->org,
                    'ipl_reverse' => $ip_api->reverse,
                    'ipl_ip' => $ip_api->query,
                    'ipl_mobile' => $ip_api->mobile,
                    'ipl_proxy' => (bool)$ip_api->proxy,
                    'ipl_create_time' => time()
                ]);
                write_to_log($ret, '_ip');
            } else {
                write_to_log('ip not found: ' . $ip, '_ip');
            }

        } else {
            $sql = "select * from idt_iplog where ipl_ip='{$ip}'";
            $ret = $this->mysqlQuery($sql, 'all');
            write_to_log('db: ' . json_encode($ret), '_ip');
        }
    }

    /**
     * has ip ?
     * @param $ip
     * @return bool
     */
    private function __hasIpLog($ip)
    {
        $sql = "select count(ipl_ip) as co_ip from idt_iplog where ipl_ip='{$ip}'";
        $ret = $this->mysqlQuery($sql, 'all');
        return $ret[0]['co_ip'] <= 0;
    }

    private function __checkLogData(array $data)
    {
        if (empty($data['companyID'])) {
            $data['companyID'] = null;
            if (!empty($data['user'])) {
                $sql = "select cpy_id from idt_user where u_id = '{$data['user']}'";
                $ret = $this->mysqlQuery($sql, 'all');
                if (!empty($ret)) {
                    $data['companyID'] = $ret[0]['cpy_id'];
                }
            }
        }

        if (!isset($data['status'])) {
            $data['status'] = null;
        }

        if (!isset($data['type'])) {
            $data['type'] = null;
        }

        if (!isset($data['content'])) {
            $data['content'] = null;
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
