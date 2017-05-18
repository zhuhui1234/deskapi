<?php
/**
 * Copyright © 艾瑞咨询集团(http://www.iresearch.com.cn/)
 * mysql操作类
 * MyDb
 * Author Zhangwenjun <zhangwenjun@iresearch.com.cn>
 * Create 13-11-14 17:45
 */
class MyDb{

    private $host = "";
    private $user = "";
    private $pass = "";
    private $db = "";
    private $queries = 0;
    private $linkID;
    private $stop_on_error = 1;
    private $db_errors = 0;

    static $log = array();
    private $connect_time = 0;

    function __construct($_host,$_user,$_pass,$_db){
        $this->host = $_host;
        $this->user = $_user;
        $this->pass = $_pass;
        $this->db = $_db;
    }

    function __destruct(){
        if($this->linkID){
//			mssql_close($this->linkID);
            $this->linkID=null;//关闭pdo连接
        }
    }

    private function doLog($sql, $time, $error = null){

        if (DEBUG == false)
            return;
        $traces = array();

        foreach (array_slice(debug_backtrace(), 1) as $trace)
        {
            $traces[] = array(
                'file'  =>  $trace['file'],
                'line'  =>  $trace['line'],
                'func'  =>  $trace['function'],
            );
        }

        $this->log[] = array(
            'sql'   =>  $sql,
            'execute_time'  =>  $time,
            'connect_time'=>$this->connect_time,
            'trace' =>  $traces,
            'error' =>  $error,
        );

    }

    public function getLog(){
        return $this->log;
    }

    public function getConnTime() {
        return $this->connect_time;
    }
    function connect(){
        if(count(explode(':',$this->host))>1){
            list($host,$port) = explode(':',$this->host);
            !$port && $port = 3306;
        }else{
            $host = $this->host;
            $port = 3306;
        }
        $start_time = microtime(true);
//		$this->linkID=mssql_connect($host . ':' . $port, $this->user,$this->pass,$this->db) or die('DB Connect Error!');
        try{
    	    $this->linkID = new PDO ("mysql:host=".$host.":".$port.";dbname=".$this->db,$this->user,$this->pass);
//            $this->linkID = new PDO("mysql:host={$host}:{$port};dbname={$this->db}", $this->user, $this->pass);
        }
        catch (PDOException $e){
            echo "DB Connect Error: " . $e->getMessage() . "\n";
            write_to_log("DB Connect Error: " . $e->getMessage() , '_ERROR');
        }
        $end_time = microtime(true);

        $connect_time = $end_time - $start_time;

        $this->connect_time = $connect_time;
    }

    function query($querysql = '', $returnType = ''){
        if(!$this->linkID){
            $this->connect();
        }
        $start_time = microtime(true);
        if ($querysql != ''){
//            $querysql = iconv("UTF-8", "gb2312", $querysql);
            $result = $this->linkID->query($querysql);
//            print_r($result->fetchAll());
//            echo $querysql;exit();
//			$result = mssql_query( $querysql) or $this->db_error("{$querysql}");
            $this->queries++;


            $end_time = microtime(true);
            $exec_time = $end_time - $start_time;
            if (defined('DEBUG') && DEBUG == true){
                $this->doLog($querysql, $exec_time, $this->db_errors > 0 ? $this->db_error('db wrong') : '');
                debug_info($this->log);

            }

            if ($returnType == ''){

                return $result;
            }elseif ($returnType == 'row'){
//				$row = mssql_fetch_row($result);
                $row = $result->fetch();
                $this->free_result($result);
                $row=gbk2utf8($row);
                return $row;
            }elseif ($returnType == 'assoc'){
                $row = @array_change_key_case($result->fetch(PDO::FETCH_ASSOC),CASE_LOWER);
//                $row = @array_change_key_case(mssql_fetch_assoc($result),CASE_LOWER);
                $this->free_result($result);
                return $row;
            }elseif($returnType == 'all'){

                $i = 0;
                $all=array();
                while ($row = $result->fetch(PDO::FETCH_ASSOC)){
                    $all[$i] = @array_change_key_case($row , CASE_LOWER);
                    $i++;
                }
                $this->free_result($result);
                return $all;
            }elseif($returnType == 'res'){
                $i = 0;
                $j = 0;
                $all=array();
                $res = array();
                do{
                    if($result==""){
                        return $all;
                    }

                    while ($row = $result->fetch(PDO::FETCH_ASSOC)){
                        $all[$j][] = @array_change_key_case($row , CASE_LOWER);
                        $i++;
                    }
                    $j++;
                }while($result->nextRowset());
                if($returnType == 'res' && count($all) > 1){
                    $all[count($all) -1 ] = $all[count($all) -1 ][0];
                }
                $this->free_result($result);
                $all=gbk2utf8($all);
                return $all;
            }
        }else{
            $this->db_error ('NO SQL');
        }
    }

    function checkInsert($dataArray='',$type='single'){
        if(empty($dataArray)){
            return -2;
        }
        if(!is_array($dataArray)){
            return -3;
        }
        if($type=='mult'){
            $values='';
            foreach($dataArray as $key=>$value){

                $tmp=implode("','",$value);
                if(!empty($tmp)){
                    $values.=',(\''.$tmp.'\')';
                }
                $feild='';
                foreach($value as $key=>$v){
                    $feild.=','.$key;
                }
            }
            $feild=substr($feild,1);
            $values=substr($values,1);
        }else{
            $feild = '';
            $values = '';
            foreach($dataArray as $key=>$value){
                $feild.=','.$key;
                $values.=",'".$value."'";
            }
            $feild=substr($feild,1);
            $values=substr($values,1);
        }
        return array('feild'=>$feild,'values'=>$values);

    }

    function insert($tab,$arr,$type='single',$showsql=false){

        $ret = $this->checkInsert($arr,$type);

        if($type=='mult'){

            $sql='insert into '.$tab.'('.$ret['feild'].') values '.$ret['values'].'';

        }else{

            $sql='insert into '.$tab.'('.$ret['feild'].') values ('.$ret['values'].')';
        }


        if($showsql){
            echo $sql;
        }else{
            return $this->query($sql);
        }
    }

    function update($tab,$where,$arr,$showsql=false){
        $sql='update '.$tab;
        $i=0;
        foreach($arr as $key => $value){
            $value=$this->escape_string($value).'';
            if($value!=''){
                if($i==0){
                    $sql.=" set $key ='$value'";
                }else{
                    $sql.=",$key ='$value'";
                }
                $i++;
            }
        }
        $w = '';
        if(is_array($where) && !empty($where)) {
            foreach($where as $k=>$v){
                $w .= " and $k = '$v'";
            }
            $sql .= " where 1=1".$w;
        }
        if(is_string($where) && !empty($where)) {
            $sql .= " where 1=1 and ".$where;
        }
        if($showsql){
            echo $sql;
        }else{
            return $this->query($sql);
        }
    }

    function delete($tab,$where,$showsql=false){

        $w = "";

        if(is_array($where) && !empty($where)) {
            foreach($where as $k=>$v){
                $w .= " and $k = '$v'";
            }

        }
        if(is_string($where) && !empty($where)) {
            $w .= " and " . $where;
        }

        $sql = "delete from $tab where 1=1" . $w;

        if($showsql){
            echo $sql;
        }else{
            return $this->query($sql);
        }
    }

    /**
     * @param $s
     * @return mixed
     * 防止SQL注入字符
     */
    function escape_string($s){
        return $s;
    }

    function free_result($res){
        //释放
        $res = null;
//		return mssql_free_result($res);
        return $res;
    }

    function fetch_row($res){
        return mssql_fetch_assoc($res);
    }

    function num_rows($result){
        return mssql_num_rows($result);
    }

    function affected_rows(){
        return mssql_affected_rows($this->linkID);
    }

    function last_insert_id(){
        return $this->linkID->lastInsertId();
    }

    function db_error($msg){
        $this->db_errors++;
        if($this->stop_on_error){
            return $msg . " : " . $this->linkID;
        }
    }
    function dbSize($db) {
        $size = 0;
        $all = $this->query( 'show table status from ' . $db, 'all' );
        foreach($all as $row){
            $size += $row["data_length"] + $row["index_length"];
        }
        return tosize($size);
    }
    function dbVersion() {

        list($version) = $this->query( 'select version()', 'row' );

        return $version;
    }
}
