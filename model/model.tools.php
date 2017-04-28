<?php
/**
 * Created by iResearch
 * 工具 数据层
 * Author JOSON <joson@iresearch.com.cn>
 * Create 2017-01-16 11:52
 * Update 2017-01-16 11:52
 * FileName:model.tools.php
 * 描述:
 * isToken 验证TOKEN方法
 */
class ToolsModel extends AgentModel
{

    public function __consturct()
    {

    }

    //验证TOKEN
    public function isToken($data)
    {
        //验证TOKEN
        $sql = "SELECT count(1) token_number FROM idt_user WHERE 1=1 AND u_id='{$data['userID']}' AND u_token='{$data['TOKEN']}'";
        $ret = $this->mysqlQuery($sql, "all");
        if($ret[0]['token_number'] <= 0){ _ERROR('000002','用户失效,请重新登录'); }
    }

    //日志方法
    public function logs($resCode,$resMsg,$data)
    {
        //请求模块
        $m = $_GET['m'];
        //请求方法
        $a = $_GET['a'];
        //请求数据
        $postVal = _POST();

        //无用户GUID请求过滤
        if($_GET['m']=='User' AND $_GET['a']=='login'){ //登录
            $postVal['userID'] = $postVal['Account'];
        } else if($_GET['m']=='User' AND $_GET['a']=='setMobileKey'){ //短信服务
            $postVal['userID'] = $postVal['loginMobile'];
        } else if($_GET['m']=='User' AND $_GET['a']=='addUser'){ //用户注册
            $postVal['userID'] = $postVal['Mobile'];
        }

        //新增日志
        $where_logs['sys_user'] = $postVal['userID']; //操作用户
        $where_logs['sys_m'] = $m; //模块
        $where_logs['sys_a'] = $a; //方法
        $where_logs['sys_sys'] = 'irview_deskapi'; //系统名称
        $where_logs['sys_ip'] = getIp(); //请求IP
        $where_logs['sys_rescode'] = $resCode; //响应状态码
        $where_logs['sys_resmsg'] = $resMsg; //响应信息
        $where_logs['sys_request'] = json_encode($postVal,JSON_UNESCAPED_UNICODE); //日志request信息
        $where_logs['sys_response'] = json_encode($data,JSON_UNESCAPED_UNICODE); //日志response信息
        $this->mysqlInsert('idt_sys_logs',$where_logs);
    }


}
