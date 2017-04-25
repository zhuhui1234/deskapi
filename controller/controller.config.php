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
class ConfigController extends Controller
{
    private $model;
    const M = "Config";

    public function __construct()
    {
        $this->model = Model::instance(self::M);
    }

    public function index(){
        $ret_data = array(
            'resTime' => time().'',
            'data' => '',
            'resCode' => '000003',
            'resMsg' => '请求地址错误!!!'
        );
        echo json_encode($ret_data,true);
    }

    //查询配置LIST
    public function configList(){
        //接收请求信息
        $where = json_decode(file_get_contents('php://input'), true);
        $ret = $this->model->configList($where);

        //格式化数据
        $ret_data = array();
        foreach($ret['data'] as $a=>$v){
            if($v['cfg_type']==='1'){
                $ret_data[$v['cfg_id']]['cfg_url'] = $v['cfgs_url'].$v['cfg_guid'];
                $ret_data[$v['cfg_id']]['cfg_name'] = $v['cfg_name'];
                $ret_data[$v['cfg_id']]['cfg_id'] = $v['cfg_id'];
            } else if($v['cfg_type']==='2'){
                $ret_data[$v['cfg_sid']]['ConfigMinList'][$v['cfg_id']]['cfg_url'] = $v['cfgs_url'].$v['cfg_guid'];
                $ret_data[$v['cfg_sid']]['ConfigMinList'][$v['cfg_id']]['cfg_name'] = $v['cfg_name'];
                $ret_data[$v['cfg_sid']]['ConfigMinList'][$v['cfg_id']]['cfg_id'] = $v['cfg_id'];
            }
        }

        //格式array key
        $ret_data = array_merge($ret_data);
        foreach($ret_data as $a2=>$v2){
            $ret_data[$a2]['ConfigMinList'] = array_merge($v2['ConfigMinList']);
        }

        //实始结果数组
        $rs = array( 'resTime' => time().'' );

        //验证登录
        if(count($ret_data) <= 0){
            $rs['data']['totalSize'] = $ret['size'][0];
            $rs['data']['ConfigMaxList'] = $ret_data;
            $rs['resCode'] = '000000';
            $rs['resMsg'] = '无数据';
        } else {
            $rs['data']['totalSize'] = $ret['size'][0];
            $rs['data']['ConfigMaxList'] = $ret_data;
            $rs['resCode'] = '000000';
            $rs['resMsg'] = '';
        }

        //返回用户信息
        echo json_encode($rs,JSON_UNESCAPED_UNICODE);

    }

    //申请服务权限
    public function setAudit(){

        //申请服务权限
        $where = json_decode(file_get_contents('php://input'), true);
        $rs = array( 'resTime' => time().'', 'data' => '', 'resCode' => '', 'resMsg' => '' );//初始返回值

        //申请服务权限
        $ret = $this->model->setAudit($where);

        //验证
        if($ret == "1"){
            $rs['resMsg'] = '申请成功';
            $rs['resCode'] = '000000';
        } else {
            $rs['resCode'] = '000002';
            $rs['resMsg'] = '申请失败';
        }

        echo json_encode($rs,JSON_UNESCAPED_UNICODE);

    }

    //服务列表
    public function getAuditList(){

        //服务列表
        $where = json_decode(file_get_contents('php://input'), true);
        $rs = array( 'resTime' => time().'', 'data' => '', 'resCode' => '', 'resMsg' => '' );//初始返回值

        //获取服务列表
        $ret = $this->model->getAuditList($where);

        //返回结果
        $rs['resCode'] = '000000';
        $rs['data']['AuditList'] = $ret['data'];
        $rs['data']['totalSize'] = $ret['size'][0];

        echo json_encode($rs,JSON_UNESCAPED_UNICODE);

    }

    //获取服务详情
    public function getAuditInfo(){

        //服务列表
        $where = json_decode(file_get_contents('php://input'), true);
        $rs = array( 'resTime' => time().'', 'data' => '', 'resCode' => '', 'resMsg' => '' );//初始返回值

        //获取服务列表
        $ret = $this->model->getAuditInfo($where);

        //验证
        if(COUNT($ret) >= 1){
            $rs['resMsg'] = '查询成功';
            $rs['resCode'] = '000000';
            $rs['data'] = $ret[0];
        } else {
            $rs['resCode'] = '000002';
            $rs['resMsg'] = '查询失败';
        }

        echo json_encode($rs,JSON_UNESCAPED_UNICODE);

    }

    //服务审核
    public function upAudit(){

        //服务列表
        $where = json_decode(file_get_contents('php://input'), true);
        $rs = array( 'resTime' => strtotime('now'), 'data' => '', 'resCode' => '', 'resMsg' => '' );//初始返回值

        //获取服务列表
        $ret = $this->model->upAudit($where);

        //验证
        if(COUNT($ret) >= 1){
            $rs['resMsg'] = '审核成功';
            $rs['resCode'] = '000000';
        } else {
            $rs['resCode'] = '000002';
            $rs['resMsg'] = '审核失败';
        }

        echo json_encode($rs,JSON_UNESCAPED_UNICODE);

    }

}