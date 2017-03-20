<?php
/**
 * Created by iResearch
 * Service 数据层
 * Author JOSON <joson@iresearch.com.cn>
 * Create 2017-01-24 15:38
 * Update 2017-01-24 15:38
 * FileName:model.service.php
 * 描述:
 */
class ServiceModel extends AgentModel
{

    public function __consturct()
    {

    }

    //获取绑定服务
    public function getService($data)
    {
        $sql = "SELECT u_mobile,u_wxname,u_wxopid,u_wxunid FROM idt_user WHERE u_id='{$data['userID']}'";
        $ret = $this->mysqlQuery($sql, "all");

        //微信服务
        if($ret[0]['u_wxopid'] != null AND $ret[0]['u_wxopid'] != "" AND $ret[0]['u_wxunid'] != null AND $ret[0]['u_wxunid'] != ""){
            //应用名称
            $rs['weixin']['application'] = 'weixin';
            //应用名称
            $rs['weixin']['name'] = urldecode($ret[0]['u_wxname']);
            //应用类型
            $rs['weixin']['type'] = 1;
        } else {
            //应用名称
            $rs['weixin']['application'] = 'weixin';
            //应用名称
            $rs['weixin']['name'] = "";
            //应用类型
            $rs['weixin']['type'] = 0;
        }

        //手机服务
        if($ret[0]['u_mobile'] != null AND $ret[0]['u_mobile'] != ""){
            //应用名称
            $rs['mobile']['application'] = 'mobile';
            //应用名称
            $rs['mobile']['name'] = $ret[0]['u_mobile'];
            //应用类型
            $rs['mobile']['type'] = 1;
        } else {
            //应用名称
            $rs['mobile']['application'] = 'mobile';
            //应用名称
            $rs['mobile']['name'] = "";
            //应用类型
            $rs['mobile']['type'] = 0;
        }

        //查询成功,,并返回响应结果
        _SUCCESS('000000','查询成功',$rs);
    }

    //绑定微信
    public function setWxService($data)
    {
        //绑定微信
        $where['u_wxname'] = urlencode($data['wxName']); //微信名称
        $where['u_wxopid'] = $data['wxOpenid']; //微信Openid
        $where['u_wxunid'] = $data['wxUnionid']; //微信Unionid
        $id = " u_id='".$data['userID']."'";//用户GUID
        $ret = $this->mysqlEdit('idt_user',$where,$id);

        //验证绑定操作
        if($ret == '1'){
            //绑定成功,,并返回响应结果
            _SUCCESS('000000','绑定成功');
        } else {
            //绑定失败,,并返回响应结果
            _SUCCESS('000001','绑定失败');
        }
    }

    /**
     * check state
     * @param $d
     *
     * @return bool
     */
    public function checkState($d)
    {
        if ($d !== null OR isset($d)) {
            return $d != 0;
        }else {
            return false;
        }
    }

}