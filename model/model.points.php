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
            7： 撤销分值(减)
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
            $sql = "select licence_key from idt_licence where u_id = '{$data['u_id']}' and pdt_id = {$data['pdt_id']}";
            $ret = $this->mysqlQuery($sql, 'all');
            if(count($ret)>0){
                $data['licence_key'] = $ret[0]['licence_key'];
                $data['balance'] = $this->__computingBalancePoint($data['licence_key']);
            }else{
                _ERROR('000001', '无许可证');
            }

            if ($data['balance'] - $data['point_value'] >= 0) {
                $ret = $this->__insertRow($data);

                if ($ret) {
                    $lastSql = "SELECT point_id FROM idt_points
                                WHERE licence_key='{$data['licence_key']}' AND point_explain='{$data['point_explain']}'
                                ORDER BY point_id DESC ";
                    $last = $this->mysqlQuery($lastSql, 'all');
                    $last = $last[0]['point_id'];
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

        if (is_array($data)) {
            if (!empty($data['pointID'])) {
                $sql = "SELECT type FROM idt_points where point_id='{$data['pointID']}'";
                $ret = $this->mysqlQuery($sql, 'all');

                if ($ret) {
                    $getValue = $this->__getPointValue($data['pointID']);
                    $data['point_value'] = $getValue;
                    $data['balance'] = $this->__computingBalancePoint($data['dev_id']);
                    $data['sub_point_id'] = $data['pointID'];
                    $checkPoint = $this->__checkPoint($data['pointID']);
                    if (!$checkPoint) {
                        _ERROR('0000002', '该ID已撤回过了');
                    }
                    unset($data['pointID']);
                    if ($ret[0]['type'] <= 5 and $ret[0]['type'] != 2 and $ret[0]['type'] != 7 and $ret[0]['type'] > 0) {
                        //原数据为加
                        $data['type'] = 2;
                        $ret = $this->__insertRow($data);

                        _SUCCESS('000000', 'ok', $ret);
                    } elseif ($ret[0]['type'] > 5 and $ret[0]['type'] != 2 and $ret[0]['type'] != 7 and $ret[0]['type'] > 0) {
                        //原数据为减

                        $data['type'] = 7;
                        $ret = $this->__insertRow($data);

                        _SUCCESS('0000000', 'ok', $ret);
                    } else {
                        _ERROR('000002', '异常');
                    }
                } else {
                    _ERROR('000002', '无记录');
                }
            } else {
                _ERROR('000002', 'id不能为空');
            }
        } else {
            _ERROR('000003', '参数不对');
        }
    }

    /**
     * compute point for dev id
     * @param $data
     * @return array
     */
    public function computePoint($data)
    {
        if (empty($data['u_id'])) {
            _ERROR('000002', '参数错误');
        }
        if (empty($data['pdt_id'])) {
            _ERROR('000002', '参数错误');
        }
        $sql = "select licence_key from idt_licence where u_id = '{$data['u_id']}' and pdt_id = {$data['pdt_id']}";
        $ret = $this->mysqlQuery($sql, 'all');
        if(count($ret)>0){
            $licenceKey = $ret[0]['licence_key'];
            _SUCCESS('000000', 'ok', [
                'getValue' => $this->__computingBalancePoint($licenceKey)
            ]);
        }else{
            _ERROR('000001', '无许可证');
        }
    }


    ######################################################################################
    ##################################                     ###############################
    #################################   PRIVATE METHODS   ################################
    ################################                     #################################
    ######################################################################################

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

        $ret = $this->mysqlInsert('idt_points', $data);
        if ($ret) {
            return $ret;
        } else {
            _ERROR('000002', '数据插入失败');
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
        $sql = "SELECT licence_key,u_id,point_value FROM idt_points WHERE point_id='{$pointID}'";
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
        $sql = "SELECT count(sub_point_id) as has_spi FROM idt_points WHERE sub_point_id='{$subPointID}'";
        $ret = $this->mysqlQuery($sql, 'all');
        if ($ret) {
            return (int)$ret[0]['has_spi'] == 0;
        } else {
            return false;
        }
    }

}
