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
            if (empty($data['dev_id'])) {
                _ERROR('000002', 'no department id');
            }

            if (empty($data['pdt_id'])) {
                _ERROR('000002', 'NO Product id');
            }

            $data['balance'] = $this->__computingBalancePoint($data['dev_id']);

            if ($data['balance'] - $data['point_value'] >= 0) {
                $ret = $this->__insertRow($data);

                if ($ret) {
                    $lastSql = "SELECT point_id FROM idt_points
                                WHERE dev_id='{$data['dev_id']}' AND point_explain='{$data['point_explain']}'
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
     * @param $data
     */
    public function pointList($data)
    {
        if (is_array($data)) {

            if (empty($data['dev_id'])) {
                _ERROR('000002', '缺少参数');
            }

            $sql = "SELECT
                        point_id,
                        idt_company.cpy_cname,
                        idt_user.u_name,
                        point_explain,
                        state,
                        type,
                        balance,
                        idt_product.pdt_name,
                        idt_points.cdate 
                    FROM
                        idt_points
                        LEFT JOIN idt_company ON idt_company.cpy_id = idt_points.cpy_id
                        LEFT JOIN idt_user ON idt_user.u_id = idt_points.u_id
                        LEFT JOIN idt_product ON idt_product.pdt_id = idt_points.pdt_id 
                    WHERE
	                    idt_points.dev_id = {$data['dev_id']} ";
            $ret = $this->mysqlQuery($sql, 'all');
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
            if (empty($data['dev_id'])) {
                _ERROR('000002', 'no department id');
            }
            $data['balance'] = $this->__computingBalancePoint($data['dev_id']);

            if (empty($data['pointID'])) {
                _ERROR('000002', '缺少参数');
            }

            //get point value

            $pointValue = $this->__getPointValue($data['pointID']);

            if ($pointValue) {
                $data['point_value'] = $pointValue;
            } else {
                _ERROR('0000002', 'pointID出错');
            }

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
     * top up
     *
     * @param $data
     */
    public function topUp($data)
    {
        if (is_array($data)) {
            $data['type'] = 1;

            if (empty($data['dev_id'])) {
                _ERROR('000002', 'no department id');
            }

            $data['balance'] = $this->__computingBalancePoint($data['dev_id']);

            $ret = $this->__insertRow($data);
            _SUCCESS('000000', 'OK', $ret);

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
        if (empty($data['dev_id'])) {
            _ERROR('000002', '参数错误');
        }
        _SUCCESS('000000', 'ok', ['getValue' => $this->__computingBalancePoint($data['dev_id'])]);
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
        if (empty($data['cpy_id'])) {
            _ERROR('000002', 'no company id');
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

        if (empty($data['point_value'])) {
            _ERROR('000002', 'no point value');
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
     * @param $devID
     * @return array
     */
    private function __computingBalancePoint($devID)
    {
        $positiveNumSQL = "SELECT sum(point_value) as positiveNum FROM idt_points WHERE type <= 5 AND dev_id='{$devID}'";
        $negativeNumSQL = "SELECT sum(point_value) as negativeNum FROM idt_points WHERE type > 5 AND dev_id='{$devID}'";
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
     * check point
     * @param $subPointID
     * @return bool
     */
    private function __checkPoint($subPointID)
    {
        $sql = "SELECT count(sub_point_id) as has_spi FROM idt_points WHERE sub_point_id='{$subPointID}'";
        $ret = $this->mysqlQuery($sql, 'all');
        if ($ret) {
            return $ret[0]['sub_point_id'] == 0;
        } else {
            return false;
        }
    }


}
