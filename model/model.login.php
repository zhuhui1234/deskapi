<?php
/**
 * Created by iResearch
 * Login 数据层
 * Author JOSON <joson@iresearch.com.cn>
 * Create 2017-01-22 11:01
 * Update 2017-01-22 11:01
 * FileName:controller.login.php
 * 描述:
 */
class LoginModel extends AgentModel
{

    public function __consturct()
    {

    }

    //用户注销
    public function cancel($data)
    {
        //用户注销并更新用户TOKEN
        $upTOKEN = md5(rand(100001,999999));
        $where_upTOKEN['u_token'] = $upTOKEN;//更新TOKEN
        $id_upTOKEN = " u_id='".$data['userID']."'";//用户GUID
        $ret_upTOKEN = $this->mysqlEdit('idt_user',$where_upTOKEN,$id_upTOKEN);
        if($ret_upTOKEN != 1){ _ERROR('000002','注销失败'); }

        //返回响应结果
        _SUCCESS('000000','注销成功');
    }

}
