<?php
/**
 * Created by iResearch
 * Logs 数据层
 * Author JOSON <joson@iresearch.com.cn>
 * Create 2017-01-22 15:38
 * Update 2017-01-22 15:38
 * FileName:controller.logs.php
 * 描述:
 */
class LogsController extends Controller
{
    private $model;
    const M = "Logs";

    public function __construct()
    {
        $this->model = Model::instance(self::M);
    }

    //初始方法
    public function index(){

    }

    //获取日志LIST
    public function logList()
    {
        //获取POST请求数据
        $data = _POST();

        //查询成功,并返回响应结果
        $this->model->logList($data);
    }

}