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
class TokenModel extends AgentModel
{

    public function __construct($className)
    {
        parent::__construct($className);
    }

    /**
     * isToken
     *
     * @param $where
     *
     * @return array|string
     */
    public function isToken($where)
    {
        //查询大行业LIST
        $sql = "SELECT count(1) FROM idt_user WHERE 1=1 AND u_token='{$where['token']}'";
        return $this->mysqlQuery($sql, "row");
    }

}
