<?php

/**
 * Created by PhpStorm.
 * User: robinwong51
 * Date: 01/09/2017
 * Time: 1:45 PM
 */
class PointsModel extends AgentModel
{
    /*
        point type:
        包含5分以及以上为增加积分，大于5的时候，则减积分

        加法：
            1： 充值
            2： 撤回分值（加）
            3： 赠送分值
            4： 预留
            5： 预留
        ----------------------------
        减法：
            6： 定制报告
            7： 撤销分值（减）

        ----------------------------
        转账计算
            21： 公司加法，用户减法
            22： 公司减法，用户加法

    */

    public function __construct($classname)
    {
        parent::__construct($classname);
    }

    /**
     * 定制报告积分添加
     * @param $data
     */
    public function addCustomizationReport($data)
    {
        if (is_array($data)) {

            $data['type'] = 6;

            if (empty($data['pdt_id'])) {
                _ERROR('000002', 'NO Product id');
            }

            if (empty($data['u_id'])) {
                _ERROR('000002', 'NO User id');
            }


            $sql = "select licence_key,cpy_id 
                    from idt_licence where u_id = '{$data['u_id']}' and pdt_id = {$data['pdt_id']}";

            $ret = $this->mysqlQuery($sql, 'all');


            if (count($ret) > 0) {
                $balance = $this->__computingBalancePoint($data['u_id']);
                if (empty($data['cpy_id'])) {
                    $data['cpy_id'] = $ret[0]['cpy_id'];
                }

            } else {
                $balance = 0;
                _ERROR('000001', '无许可证');
            }

            if ($balance - $data['point_value'] >= 0) {
                $ret = $this->__insertRow($data);

                if ($ret) {
                    $lastSql = "SELECT point_id FROM idt_points
                                WHERE u_id='{$data['u_id']}' AND point_explain='{$data['point_explain']}'
                                ORDER BY point_id DESC ";
                    $last = $this->mysqlQuery($lastSql, 'all');

                    $last = $last[0]['point_id'];
                } else {
                    $last = false;
                    _ERROR('0000002', '积分变动失败');
                }

                _SUCCESS('000000', 'ok', ['point_id' => $last]);
            } else {
                _ERROR('0000002', '余额积分不足');
            }

        } else {

            _ERROR('000003', '参数不对');
        }
    }

    /**
     * cancel customization report
     *
     * @param $data
     */
    public function cancelCustomizationReport($data)
    {
        if (is_array($data)) {
            if ($data['key'] !== KEY) {
                _ERROR('000005', 'ERROR KEY');
            }

            $data['type'] = 2;

            if (empty($data['pointID'])) {
                _ERROR('000002', '缺少参数');
            }

            $pointInfo = $this->__getPointInfoForPointID($data['pointID']);

            if ($pointInfo) {
                $data['licence_key'] = $pointInfo['licence_key'];
                $data['u_id'] = $pointInfo['u_id'];
                $data['point_value'] = $pointInfo['point_value'];
                $data['sub_point_id'] = $data['pointID'];
            } else {
                _ERROR('000002', 'not found licence key');
            }

            if (!$this->__checkPoint($data['pointID'])) {
                _ERROR('000004', '已经被处理过');
            }

            $data['balance'] = $this->__computingBalancePoint($data['licence_key']);

            //get point value


            unset($data['pointID']);
            unset($data['key']);
            $ret = $this->__insertRow($data);

            _SUCCESS('000000', 'ok', $ret);

        } else {

            _ERROR('000003', '参数不对');

        }
    }

    /**
     * cancel
     *
     * @param $data
     */
    public function cancel($data)
    {

        if (!is_array($data)) {
            _ERROR('000003', '参数不对');
        }


        if (empty($data['pointID'])) {
            _ERROR('000002', 'id不能为空');
        }


        $ret = $this->__getPointInfoForPointID($data['pointID']);

        if (!$ret) {
            _ERROR('000002', '无记录');
        }


        $data['point_value'] = $ret[0]['point_value'];
        $data['sub_point_id'] = $data['pointID'];
        $checkPoint = $this->__checkPoint($data['pointID']);

        if (!$checkPoint) {
            _ERROR('0000002', '该ID已撤回过了');
        }

        unset($data['pointID']);

        if (
            $ret[0]['type'] <= 5
            and $ret[0]['type'] != 2
            and $ret[0]['type'] != 7
            and $ret[0]['type'] > 0
            and $ret[0]['type'] != 21
            and $ret[0]['type'] != 22
        ) {

            //原数据为加
            $data['type'] = 2;
            $ret = $this->__insertRow($data);

            _SUCCESS('000000', 'ok', $ret);

        } elseif (
            $ret[0]['type'] > 5
            and $ret[0]['type'] != 2
            and $ret[0]['type'] != 7
            and $ret[0]['type'] > 0
            and $ret[0]['type'] != 21
            and $ret[0]['type'] != 22
        ) {

            //原数据为减
            $data['type'] = 7;
            $ret = $this->__insertRow($data);
            _SUCCESS('0000000', 'ok', $ret);

        } else {

            _ERROR('000002', '异常');

        }

    }

    /**
     * compute point for user
     *
     * @param $data
     */
    public function computePoint($data)
    {
        if (empty($data['u_id'])) {
            _ERROR('000002', '参数错误');
        }

        _SUCCESS('000000', 'ok', [
            'getValue' => $this->__computingBalancePoint($data['u_id'])
        ]);

    }

    /**
     * computing point for company
     * @param $data
     */
    public function computePointForCompany($data)
    {
        if (empty($data['cpy_id'])) {
            _ERROR('000002', '参数错误');
        }

        _SUCCESS('000000', 'OK', [
            'getValue' => $this->__computingBalancePointForCompany($data['cpy_id'])
        ]);
    }

    /**
     * remove user points
     * @param $data
     */
    public function removeUserPoint($data)
    {
        $userPoint = $this->__computingBalancePoint($data['u_id']);
        $update_ret = $this->mysqlEdit('idt_points', ['state' => 1], "u_id='{$data['u_id']}'");
        $tr_ret = $this->__transferAccountBackCompany($data['cpy_id'], $data['u_id'], $userPoint, $data['by_u_id']);
        if ($tr_ret and $update_ret) {
            _SUCCESS('000000', 'OK', []);
        } else {
            _ERROR('000002', '删除失败');
        }
    }

    /**
     * transfer account to user
     *
     * @param $data
     */
    public function allotUser($data)
    {
        $ret = $this->__transferAccountToUser(
            $data['cpy_id'], $data['userID'], $data['point_value'], $data['author']
        );
        if ($ret['status']) {
            _SUCCESS('000000', $ret['msg'], $ret['data']);
        } else {
            _ERROR('000002', $ret['msg']);
        }
    }

    /**
     * put back point to company
     *
     * @param $data
     */
    public function putBackPointToCompany($data)
    {
        $ret = $this->__transferAccountBackCompany(
            $data['cpy_id'], $data['userID'], $data['point_value'], $data['author']
        );
        if ($ret['status']) {
            _SUCCESS('000000', $ret['msg'], $ret['data']);
        } else {
            _ERROR('000002', $ret['msg']);
        }
    }

    public function getPointListCompany($data)
    {
        if (empty($data)) {
            _ERROR('000002', '参数不能为空');
        }

        if (empty($data['cpy_id'])) {
            _ERROR('000002', '公司ID不能为空');
        }

        $data['pageSize'] == null ? $pageSize = '10' : $pageSize = $data['pageSize']; //查询数据
        $data['pageNo'] == null ? $pageNo = '0' : $pageNo = $data['pageNo'] - 1; //查询页数

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
                    idt_points.cpy_id = '{$data['cpy_id']}' and idt_points.state !=1
                    ORDER BY idt_points.cdate DESC ";
        $ret = $this->mysqlQuery($sql, 'all');

//        if (!$ret) {
//            _ERROR('000002', '查询失败');
//        }
        if (empty($ret)) {
            $rs = null;
        } else {
            if (is_array($ret)) {
                $rs = $this->__formatPointCompanyDataList($ret);
                if (isset($data['type'])) {
                    if ($data['type'] == 1) {
                        $type = ['1','21','22'];
                        foreach($rs as $key => $value){
                            if(!in_array($rs[$key]['type'],$type)) {
                                unset($rs[$key]);
                            }
                        }
                    } else {
                        $type = ['2','6'];
                        foreach($rs as $key => $value){
                            if(!in_array($rs[$key]['type'],$type)) {
                                unset($rs[$key]);
                            }
                        }
                    }
                }
                $total = count($rs);
                $rs = $this->__makePaging($rs, $pageSize);
            } else {
                $rs = null;
                $total = 0;
            }
        }

        _SUCCESS('000000', 'OK', ['list' => $rs[$pageNo], 'totalSize' => $total]);
    }

    public function getPointListUser($data)
    {
        $data['pageSize'] == null ? $pageSize = '10' : $pageSize = $data['pageSize']; //查询数据
        $data['pageNo'] == null ? $pageNo = '0' : $pageNo = $data['pageNo'] - 1; //查询页数

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
                    idt_points.u_id = '{$data['u_id']}'  and state != 1 
                    ORDER BY idt_points.cdate DESC ";
        $ret = $this->mysqlQuery($sql, 'all');

        if (empty($ret)) {
            $rs = null;
        } else {
            $rs = $this->__formatPointUserDataList($ret);
            $rs = $this->__makePaging($rs, $pageSize);
        }

        _SUCCESS('000000', 'OK', ['list' => $rs[$pageNo], 'totalSize' => count($ret)]);
    }

    ######################################################################################
    ##################################                     ###############################
    #################################   PRIVATE METHODS   ################################
    ################################                     #################################
    ######################################################################################


    private function __transTypeValue($type)
    {
        $type = (int)$type;
        switch ($type) {
            case 1:
                return '充值积分';
                break;
            case 2:
                return '定制报告失败后退回积分';
                break;
            case 3:
                return '赠送积分';
                break;
            case 6:
                return '定制报告';
                break;
            case 7:
                return '撤销获得积分';
                break;
            case 21:
                return '积分转账给公司';
                break;
            case 22:
                return '积分转账给用户';
                break;
        }
    }

    /**
     * format point array (user point history)
     *
     * @param array $ret
     * @return array
     */
    private function __formatPointUserDataList(array $ret = [])
    {
        $rs = [];
        krsort($ret);
        $point_all_temp = 0;
        foreach ($ret as $key => $value) {
            $rs[$key]['changedPoint'] = $ret[$key]['point_value'];
            $rs[$key]['companyFullName'] = $ret[$key]['cpy_cname'];
            $rs[$key]['userName'] = $ret[$key]['u_name'];
            $rs[$key]['mobile'] = $ret[$key]['u_mobile'];
            $rs[$key]['type'] = $ret[$key]['type'];
            $rs[$key]['productName'] = $ret[$key]['pdt_ename'];
            $rs[$key]['cDate'] = $ret[$key]['cdate'];

            $arr = json_decode($ret[$key]['point_explain'], true);

            if ($ret[$key]['type'] == 1 || $ret[$key]['type'] == 2) {
                if($rs[$key]['changedPoint'] != 0){
                    $rs[$key]['changedPoint'] = '+'.$rs[$key]['changedPoint'];
                }
                $point_all_temp = $point_all_temp + $ret[$key]['point_value'];

                if ($ret[$key]['type'] == 2) {
                    $rs[$key]['customReportTicketID'] = "{$arr['pdt_name']}-{$arr['ID']}";
                    $rs[$key]['customReportName'] = $arr['Name'];
                } else {
                    $rs[$key]['log'] = $ret[$key]['point_explain'];
                    $rs[$key]['customReportName'] = null;
                }

            } else {
                $rs[$key]['log'] = $this->__transTypeValue($ret[$key]['type']);
                $rs[$key]['customReportTicketID'] = "{$arr['pdt_name']}-{$arr['ID']}";
                $rs[$key]['customReportName'] = $arr['Name'];
            }

            if ($ret[$key]['type'] == 6 || $ret[$key]['type'] == 7) {
                if($rs[$key]['changedPoint'] != 0){
                    $rs[$key]['changedPoint'] = '-'.$rs[$key]['changedPoint'];
                }
                $point_all_temp = $point_all_temp - $ret[$key]['point_value'];
            }

            if ($ret[$key]['type'] == 21) {
                if($rs[$key]['changedPoint'] != 0){
                    $rs[$key]['changedPoint'] = '-'.$rs[$key]['changedPoint'];
                }
                $point_all_temp = $point_all_temp - $ret[$key]['point_value'];
                $rs[$key]['log'] = '用户退回'.$ret[$key]['point_value'].'积分';
            }

            if ($ret[$key]['type'] == 22) {
                if($rs[$key]['changedPoint'] != 0){
                    $rs[$key]['changedPoint'] = '+'.$rs[$key]['changedPoint'];
                }
                $point_all_temp = $point_all_temp + $ret[$key]['point_value'];
                $rs[$key]['log'] = '分配给用户'.$ret[$key]['point_value'].'积分';
            }


            $rs[$key]['remainingPointsAll'] = $point_all_temp;

        }

        return $rs;
    }

    private function __formatPointCompanyDataList(array $ret = [])
    {
        $rs = [];
        krsort($ret);
        $point_temp = 0;
        $point_all_temp = 0;
        foreach ($ret as $key => $value) {
            $rs[$key]['changedPoint'] = $ret[$key]['point_value'];
            $rs[$key]['companyFullName'] = $ret[$key]['cpy_cname'];
            $rs[$key]['userName'] = $ret[$key]['u_name'];
            $rs[$key]['mobile'] = $ret[$key]['u_mobile'];
            $rs[$key]['type'] = $ret[$key]['type'];
            $rs[$key]['productName'] = $ret[$key]['pdt_ename'];
            $rs[$key]['cDate'] = $ret[$key]['cdate'];

            $arr = json_decode($ret[$key]['point_explain'], true);

            if ($ret[$key]['type'] == 1 || $ret[$key]['type'] == 2) {

                $point_temp = $point_temp + $ret[$key]['point_value'];
                $point_all_temp = $point_all_temp + $ret[$key]['point_value'];

                if ($ret[$key]['type'] == 2) {
                    $rs[$key]['customReportTicketID'] = "{$arr['pdt_name']}-{$arr['ID']}";
                    $rs[$key]['customReportName'] = $arr['Name'];
                } else {
                    $rs[$key]['log'] = $ret[$key]['point_explain'];
                    $rs[$key]['customReportName'] = null;
                }

            } else {
                $rs[$key]['log'] = $this->__transTypeValue($ret[$key]['type']);
                $rs[$key]['customReportTicketID'] = "{$arr['pdt_name']}-{$arr['ID']}";
                $rs[$key]['customReportName'] = $arr['Name'];
            }

            if ($ret[$key]['type'] == 6 || $ret[$key]['type'] == 7) {
                $point_all_temp = $point_all_temp - $ret[$key]['point_value'];
                $point_temp = $point_temp - $ret[$key]['point_value'];
            }

            if ($ret[$key]['type'] == 21) {
                $point_temp = $point_temp + $ret[$key]['point_value'];
            }

            if ($ret[$key]['type'] == 22) {
                $point_temp = $point_temp - $ret[$key]['point_value'];
            }

            $rs[$key]['remainingPoints'] = $point_temp;

            $rs[$key]['remainingPointsAll'] = $point_all_temp;

        }

        return $rs;
    }

    /**
     * insert row data
     *
     * @param array $data
     * @return array|int|string
     */
    private function __insertRow(array $data)
    {
        unset($data['token']);
        if (empty($data['licence_key'])) {
            _ERROR('000002', 'no licence key');
        }
        if (empty($data['u_id'])) {
            _ERROR('000002', 'no user id');
        }

        if (empty($data['type'])) {
            _ERROR('000002', 'no point type');
        }

        if (empty($data['point_explain'])) {
            _ERROR('000002', 'no comment');
        }

        $ret = $this->mysqlInsert('idt_points', $data, 'single', true);

        if ($ret) {
            return $ret;
        } else {
            _ERROR('000002', '数据插入失败');
        }

    }

    /**
     * transfer account to user
     *
     * @param $cpy_id
     * @param $u_id
     * @param $point_value
     * @param $author
     * @return array
     */
    private function __transferAccountToUser($cpy_id, $u_id, $point_value, $author)
    {
        $company_points = $this->__computingBalancePointForCompany($cpy_id);
        $uptimes = date('Y-m-d H:i:s', time());
        if ($company_points > 0 and $company_points >= $point_value) {
            $ret = $this->mysqlInsert('idt_points', [
                'u_id' => $u_id,
                'type' => 22,
                'cpy_id' => $cpy_id,
                'point_value' => $point_value,
                'point_explain' => "企业分配给用户:{$u_id} 积分，{$point_value} ",
                'author' => $author,
                'cdate' => $uptimes
            ]);
            if ($ret) {
                return ['status' => true, 'msg' => 'ok', 'data' => $ret];
            } else {
                return ['status' => false, 'msg' => '转账失败'];
            }
        } else {
            return ['status' => false, 'msg' => '积分不足'];
        }
    }

    /**
     * transfer account back to company
     *
     * @param $cpy_id
     * @param $u_id
     * @param $point_value
     * @param $author
     * @return array
     */
    private function __transferAccountBackCompany($cpy_id, $u_id, $point_value, $author)
    {
        $user_point = $this->__computingBalancePoint($u_id);
        $uptimes = date('Y-m-d H:i:s', time());
        if ($user_point > 0 and $user_point >= $point_value) {
            $ret = $this->mysqlInsert('idt_points', [
                'u_id' => $u_id,
                'type' => 21,
                'cpy_id' => $cpy_id,
                'point_value' => $point_value,
                'point_explain' => "用户:{$u_id} 退回到企业积分",
                'author' => $author,
                'cdate' => $uptimes
            ]);
            if ($ret) {
                return ['status' => true, 'msg' => 'ok', 'data' => $ret];
            } else {
                return ['status' => false, 'msg' => '转账失败'];
            }
        } else {
            return ['status' => false, 'msg' => '积分不足'];
        }
    }

    /**
     * computing balance point for company
     * @param $cpy_id
     * @return int
     */
    private function __computingBalancePointForCompany($cpy_id)
    {
        $positiveNumSQL = "SELECT sum(point_value) as positiveNum 
                           FROM idt_points WHERE type in (1,21) and  cpy_id='{$cpy_id}'";

        $negativeNumSQL = "SELECT sum(point_value) as negativeNum 
                           FROM idt_points WHERE type=22 and cpy_id='{$cpy_id}'";

        $positiveNum = $this->mysqlQuery($positiveNumSQL, 'all');
        $negativeNum = $this->mysqlQuery($negativeNumSQL, 'all');

        if (empty($positiveNum)) {
            $positiveNum = [['positivenum' => 0]];
        }

        if (empty($negativeNum)) {
            $negativeNum = [['negativenum' => 0]];
        }

        return (int)$positiveNum[0]['positivenum'] - (int)$negativeNum[0]['negativenum'];
    }

    /**
     * computing company balance point (include user points)
     * @param $cpy_id
     * @return int
     */
    private function __computingCompanyBalancePointIncludeUser($cpy_id)
    {
        $positiveNumSQL = "SELECT sum(point_value) as positiveNum 
                           FROM idt_points 
                           WHERE type<=5 and state!=1 and  cpy_id='{$cpy_id}'";

        $negativeNumSQL = "SELECT sum(point_value) as negativeNum 
                           FROM idt_points 
                           WHERE (type=6 or type=7 ) and cpy_id='{$cpy_id}'";

        $positiveNum = $this->mysqlQuery($positiveNumSQL, 'all');
        $negativeNum = $this->mysqlQuery($negativeNumSQL, 'all');

        if (empty($positiveNum)) {
            $positiveNum = [['positivenum' => 0]];
        }

        if (empty($negativeNum)) {
            $negativeNum = [['negativenum' => 0]];
        }

        return (int)$positiveNum[0]['positivenum'] - (int)$negativeNum[0]['negativenum'];
    }


    /**
     * computing balance point for user
     *
     * @param $u_id
     * @return int
     */
    private function __computingBalancePoint($u_id)
    {
        $positiveNumSQL = "SELECT sum(point_value) as positiveNum 
                           FROM idt_points WHERE (type =22 or type=2) and u_id='{$u_id}' AND state!=1";

        $negativeNumSQL = "SELECT sum(point_value) as negativeNum 
                           FROM idt_points WHERE (type = 6 or type =7 or type=21 ) and u_id='{$u_id}' AND state!=1";

        $positiveNum = $this->mysqlQuery($positiveNumSQL, 'all');
        $negativeNum = $this->mysqlQuery($negativeNumSQL, 'all');
        $ret = (int)$positiveNum[0]['positivenum'] - (int)$negativeNum[0]['negativenum'];
        return $ret;
    }

    /**
     * get point value
     *
     * @param $pointID
     * @return bool
     */
    private function __getPointValue($pointID)
    {
        $sql = "SELECT point_value FROM idt_points WHERE point_id='{$pointID}'";

        $ret = $this->mysqlQuery($sql, 'all');
        if ($ret) {
            return $ret[0]['point_value'];
        } else {
            return false;
        }
    }

    /**
     * get dev id
     *
     * @param $pointID
     * @return bool
     */
    private function __getPointInfoForPointID($pointID)
    {
        $sql = "SELECT licence_key,u_id,point_value,type, cpy_id, author
                FROM idt_points WHERE point_id='{$pointID}' AND state!=1";
        $ret = $this->mysqlQuery($sql, 'all');
        if ($ret) {
            return $ret[0];
        } else {
            return false;
        }

    }

    /**
     * check point
     * @param $subPointID
     * @return bool
     */
    private function __checkPoint($subPointID)
    {
        $sql = "SELECT count(sub_point_id) as has_spi 
                FROM idt_points WHERE sub_point_id='{$subPointID}' AND state!=1";

        $ret = $this->mysqlQuery($sql, 'all');

        if ($ret) {
            return (int)$ret[0]['has_spi'] == 0;
        } else {
            return false;
        }

    }


    private function __makePaging(array $data, $pageSize)
    {
        if (empty($pageSize)) {
            $pageSize = 10;
        }
        ksort($data);
        $ret = array_chunk($data, $pageSize);
        return $ret;
    }

}
