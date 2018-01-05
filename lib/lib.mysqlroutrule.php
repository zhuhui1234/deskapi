<?php
/**
 * Copyright © 艾瑞咨询集团(http://www.iresearch.com.cn/)
 * 基础路由
 * Author JOSON <joson@iresearch.com.cn>
 * Create 16-08-23 16:24
 */
interface IMysqlRoutRule
{

    function getRout();

}
class MysqlRoutRule implements IMysqlRoutRule{

    private $routArr = array();
    private static $select = 'S';
    static $instances = null;
    final static public function Rout($r = 'S'){

		$r = strtoupper($r);
        $r && self::$select = in_array($r,array('M','S', 'RM', 'RS')) ? $r : self::$select;
        if(self::$instances == null){

            self::$instances = new self();
        }
        return self::$instances->getRout();
    }

    final function getRout(){
        $this->routArr = array(
            'M'=>array(
                '0'=>array(
//                    'host'=>'180.169.19.208',
//                    'user'=>'idata_admin',
//                    'pass'=>'1qaz2wsx',
//                    'db'=>'idatadb'
                    'host' => '42.159.231.97',
                    'user' => 'idata_admin',
                    'pass' => '1qaz2wsx',
                    'db' => 'idata2'
                )
            ),
            'S'=>array(
                '0'=>array(
//                    'host'=>'180.169.19.208',
//                    'user'=>'idata_admin',
//                    'pass'=>'1qaz2wsx',
//                    'db'=>'idatadb'
                    'host'=>'42.159.231.97',
                    'user'=>'idata_admin',
                    'pass'=>'1qaz2wsx',
                    'db'=>'idata2'
                )
            ),
//            'M'=>array(
//                '0'=>array(
//                    'host'=>'127.0.0.1',
//                    'user'=>'root',
//                    'pass'=>'weiwei',
//                    'db'=>'idatadb'
//                )
//            ),
//            'S'=>array(
//                '0'=>array(
//                    'host'=>'127.0.0.1',
//                    'user'=>'root',
//                    'pass'=>'weiwei',
//                    'db'=>'idatadb'
//                )
//            ),
            //redis主从
            'RM'=>array(
                '0'=>array(
                    'host'=>'127.0.0.1:6379',
//                    'pass' => 'weiwei',
                    'db'=>''
                )
            ),
            'RS'=>array(
                '0'=>array(
                    'host'=>'127.0.0.1:6379',
//                    'pass' => 'weiwei ',
                    'db'=>''
                )
            )
        );
       
	   $opr = self::$select;

       is_array($this->routArr[$opr]) && $res = $this->routArr[$opr][array_rand($this->routArr[$opr])];

       return $res;
    }

}
//var_dump(MysqlRoutRule::Rout('mdd'));

