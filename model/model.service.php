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
        if ($ret[0]['u_wxopid'] != null AND $ret[0]['u_wxopid'] != "" AND $ret[0]['u_wxunid'] != null AND $ret[0]['u_wxunid'] != "") {
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
        if ($ret[0]['u_mobile'] != null AND $ret[0]['u_mobile'] != "") {
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
        _SUCCESS('000000', '查询成功', $rs);
    }

    //绑定微信
    public function setWxService($data)
    {
        //绑定微信
        $where['u_wxname'] = urlencode($data['wxName']); //微信名称
        $where['u_wxopid'] = $data['wxOpenid']; //微信Openid
        $where['u_wxunid'] = $data['wxUnionid']; //微信Unionid
        $id = " u_id='" . $data['userID'] . "'";//用户GUID
        $ret = $this->mysqlEdit('idt_user', $where, $id);

        //验证绑定操作
        if ($ret == '1') {
            //绑定成功,,并返回响应结果
            _SUCCESS('000000', '绑定成功');
        } else {
            //绑定失败,,并返回响应结果
            _SUCCESS('000001', '绑定失败');
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
        } else {
            return false;
        }
    }

    /*
        ==============================
               MSG SERVICE DATABASE
               - msg_type:
                 0: all, just public msg list
                 1: only user msg
                 2: product msg
                 3: product msg without user
                 4: knowledge base
                 5: industry msgs

               - state:
                 0: unread
                 1: read
                 -1: don't show this.

        ==============================
     */

    /**
     * msg list
     *
     * @param array $data
     *
     * -type:
     * -1: all, without user msg
     * 1: all, just public msg list
     * 2: only user msg
     * 3: product msg with user ()
     * 4: product msg without user
     * 5: industry msgs
     * 6: knowledge base
     *
     */
    public function msgList(array $data)
    {
        if (empty($data['type'])) {
            _ERROR('00001', '参数错误');
        }
        $ret = $this->__getMsgList($data);
        if (!empty($ret) and $ret) {
            _SUCCESS('000000', 'ok', $ret);
        } else {
            _SUCCESS('000000', 'no data', $ret);
        }

    }

    /**
     * count unread msg
     * @param array $data
     */
    public function countUnMsg(array $data)
    {
        if (empty($data['type'])) {
            _ERROR('00001', '参数错误');
        }
        $ret = $this->__getMsgList($data);
        $countUnReadMsg = 0;
        if (is_array($ret) and count($ret) > 0) {
            foreach ($ret as $v) {
                if ($v['msg_state'] == 0) {
                    $countUnReadMsg = $countUnReadMsg + 1;
                }
            }
        }

        _SUCCESS('000000', 'ok', ['count' => $countUnReadMsg]);
    }


    /**
     * create single msg
     *
     * @param $title
     * @param $content
     * @param $auth
     * @param $toUID
     * @param $pdtID
     * @return array|int|string
     */
    public function createSingleMsg($title, $content, $auth, $toUID, $pdtID)
    {
        $ret = $this->mysqlInsert('idt_msgs', [
            'msg_title' => $title,
            'msg_content' => $content,
            'msg_auth' => $auth,
            'msg_type' => 1,
            'msg_uid' => $toUID,
            'msg_pdt_id' => $pdtID
        ]);

        if ($ret) {
            _SUCCESS('000000', '添加成功' . $ret);
        } else {
            _ERROR('000001', '添加失败', $ret);
        }
    }

    public function createPdtMsg($title, $content, $auth, $pdtID)
    {
        $ret = $this->mysqlInsert('idt_msgs', [
            'msg_title' => $title,
            'msg_content' => $content,
            'msg_auth' => $auth,
            'msg_type' => 0,
            'msg_uid' => null,
            'msg_pdt_id' => $pdtID
        ]);

        if ($ret) {
            _SUCCESS('000000', '添加成功' . $ret);
        } else {
            _ERROR('000001', '添加失败', $ret);
        }
    }

    /**
     * remove user msg
     *
     * @param $msgID
     * @return array|string
     */
    public function rmMsg($msgID, $userID)
    {
        $ret = $this->mysqlDelete('idt_msgs', "msg_id='{$msgID}' AND msg_type=1 AND msg_uid='{$userID}'");
        if ($ret) {
            _SUCCESS('000000', '删除成功');
        } else {
            _ERROR('0000001', '删除失败');
        }
    }

    /**
     * read msg detail
     *
     * @param $msgID
     * @param $userID
     */
    public function readMsg($msgID, $userID)
    {
        $msgType = $this->__getMsgType($msgID);


        if (count($msgType) > 0) {

            if ($msgType[0]['msg_type'] == 0 or $msgType[0]['msg_type'] == 5) {
                $ret = $this->mysqlQuery("SELECT * FROM idt_msgs 
                                               WHERE 1=1 AND msg_id='{$msgID}'", 'all');

                if (count($ret) > 0) {
                    _SUCCESS('000000', 'ok', $ret);
                } else {
                    _ERROR('000002', '没有找到该消息');
                }

            } else {

                $this->mysqlEdit('idt_msgs', ['msg_state' => 1], "msg_id='{$msgID}' AND  msg_uid='{$userID}'");

                $ret = $this->mysqlQuery("SELECT * 
                                               FROM idt_msgs 
                                               WHERE 1=1 AND msg_id='{$msgID}'", 'all');

                if (count($ret) > 0) {
                    _SUCCESS('000000', 'ok', $ret);
                } else {
                    _ERROR('000002', '没有找到该消息');
                }

            }
        } else {
            _ERROR('000002', '没有找到该消息');
        }

    }


    ######################################################################################
    ##################################                     ###############################
    #################################   PRIVATE METHODS   ################################
    ################################                     #################################
    ######################################################################################

    /**
     * get msg list
     *
     * @param $data
     * @return array|string
     *
     * -type:
     *  -1: all, without user msg
     *  1: all, just public msg list
     *  2: only user msg without public msg
     *  3: product msg
     *  4: product msg without user
     *  5: industry msgs
     *  6: knowledge base
     *
     *
     */
    private function __getMsgList($data)
    {

        switch ($data['type']) {
            case '-1':
                return $this->__publicMsg();
                break;

            case '1':

                if (empty($data['userID'])) {
                    _ERROR('000001', '缺少参数');
                }

                return $this->__publicMsgAndUser($data['userID']);
                break;
            case '2':
                if (empty($data['userID'])) {
                    _ERROR('000001', '缺少参数');
                }

                return $this->__userMsg($data['userID']);
                break;
            case '3':

                if (empty($data['userID'] or empty($data['pdtID']))) {
                    _ERROR('000001', '缺少参数');
                }

                return $this->__pdtMsgs($data['userID'], $data['pdtID']);
                break;
            case '4':

                if (empty($data['pdtID'])) {
                    _ERROR('000001', '缺少参数');
                }

                return $this->__pdtMsgWithoutUserMsg($data['pdtID']);
                break;

            case '5':
                if (empty($data['pdtID'])) {
                    _ERROR('000001', '缺少参数');
                }
                return $this->__industryMsg($data['pdtID']);
                break;

            case '6':
                if (empty($data['pdtID'])) {
                    _ERROR('000001', '缺少参数');
                }
                return $this->__KnowledgeMsg($data['pdtID']);
                break;

            case '7':
                if (empty($data['pdtID'])) {
                    _ERROR('000001', '缺少参数');
                }
                return $this->__reportMsg($data['pdtID']);
                break;

            default:
                return $this->__publicMsg();
                break;
        }
    }

    /**
     * ALL Msgs for pdt
     *
     * @param $uid
     * @return array|string
     */
    private function __publicMsgAndUser($uid)
    {
        $sql = "SELECT msg_id,  msg_title, msg_cdate, msg_state,msg_udate 
                FROM idt_msgs 
                WHERE (msg_type=0 or msg_uid='{$uid}') AND msg_state >=0 ";
        $ret = $this->mysqlQuery($sql, 'all');
        return $ret;
    }

    private function __publicMsg()
    {
        $sql = "SELECT msg_id,  msg_title, msg_cdate, msg_state,msg_udate 
                FROM idt_msgs 
                WHERE msg_type=0 AND msg_uid IS NULL AND msg_state >=0 ";
        $ret = $this->mysqlQuery($sql, 'all');
        return $ret;
    }


    private function __allMsgWithOutUser()
    {
        $sql = "SELECT msg_id, msg_title, msg_cdate, msg_state, msg_udate 
                FROM idt_msgs WHERE 1=1 AND msg_type=0 AND msg_state >=0 AND msg_uid = NULL ";
        return $this->mysqlQuery($sql, 'all');
    }

    /**
     * user msgs without public msg
     *
     * @param $userID
     * @return array|string
     */
    private function __userMsg($userID)
    {
        $sql = "SELECT msg_id, msg_title, msg_cdate, msg_state, msg_udate 
                FROM idt_msgs 
                WHERE 1=1 AND msg_type<>0 AND msg_uid='{$userID}' AND msg_state >=0 ";

        return $this->mysqlQuery($sql, 'all');
    }

    /**
     * for pdt msg
     *
     * @param $uid
     * @param $pdtID
     * @return array|string
     */
    private function __pdtMsgs($uid, $pdtID)
    {
        $sql = "SELECT msg_id,  msg_title, msg_cdate, msg_state,msg_udate 
                FROM idt_msgs 
                WHERE msg_state>=0 AND msg_pdt_id='{$pdtID}' AND msg_uid = '{$uid}'";
        $ret = $this->mysqlQuery($sql, 'all');
        return $ret;
    }

    private function __pdtMsgWithoutUserMsg($pdtID)
    {
        $sql = "SELECT msg_id,  msg_title, msg_cdate, msg_state,msg_udate 
                FROM idt_msgs 
                WHERE 1=1 AND msg_state>=0 AND msg_pdt_id='{$pdtID}' AND msg_type='0'";

        return $this->mysqlQuery($sql, 'all');
    }

    private function __industryMsg($pdtID)
    {
        $sql = "SELECT msg_id,  msg_title,msg_content, msg_cdate, msg_state,msg_udate 
                FROM idt_msgs 
                WHERE 1=1 AND msg_state>=0 AND msg_pdt_id='{$pdtID}' AND msg_type='5' ";

        return $this->mysqlQuery($sql, 'all');
    }

    private function __KnowledgeMsg($pdtID)
    {
        $sql = "SELECT msg_id,  msg_title,msg_content, msg_cdate, msg_state,msg_udate 
                FROM idt_msgs 
                WHERE 1=1 AND msg_state>=0 AND msg_pdt_id='{$pdtID}' AND msg_type='4' ";

        return $this->mysqlQuery($sql, 'all');
    }

    private function __reportMsg($pdtID)
    {
        $sql = "SELECT msg_id,  msg_title,msg_content, msg_cdate, msg_state,msg_udate 
                FROM idt_msgs 
                WHERE 1=1 AND msg_state>=0 AND msg_pdt_id='{$pdtID}' AND msg_type='6' ";

        return $this->mysqlQuery($sql, 'all');
    }

    private function __getMsgType($msgID)
    {
        $sql = "SELECT msg_type FROM idt_msgs WHERE msg_id='{$msgID}'";
        $ret = $this->mysqlQuery($sql, 'all');
        return $ret;
    }


}