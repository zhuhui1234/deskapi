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
class IndustryController extends Controller
{
    private $model;
    const M = "Industry";

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

    //查询大行业LIST
    public function IndustryMaxList(){
        //接收登录信息
        $where = json_decode(file_get_contents('php://input'), true);
        $ret = $this->model->IndustryMaxList($where);

        $rs = array( 'resTime' => time().'' );

        //验证登录
        if(count($ret) <= 0){
            $rs['data']['totalSize'] = '0';
            $rs['data']['IndustryMaxList'] = '';
            $rs['resCode'] = '000000';
            $rs['resMsg'] = '无数据';
        } else {
            $rs['data']['totalSize'] = '6';
            $rs['data']['IndustryMaxList'] = $ret;
            $rs['resCode'] = '000000';
            $rs['resMsg'] = '';
        }

        //返回请求数据
        echo json_encode($rs,true);
    }

    //查询小行业LIST
    public function IndustryMinList(){
        //接收登录信息
        $where = json_decode(file_get_contents('php://input'), true);
        $ret = $this->model->IndustryMinList($where);

        $rs = array( 'resTime' => time().'' );

        //验证登录
        if(count($ret['data']) <= 0){
            $rs['data']['totalSize'] = $ret['size'][0];
            $rs['data']['IndustryMinList'] = '';
            $rs['resCode'] = '000000';
            $rs['resMsg'] = '无数据';
        } else {
            $rs['data']['totalSize'] = $ret['size'][0];
            $rs['data']['IndustryMinList'] = $ret['data'];
            $rs['resCode'] = '000000';
            $rs['resMsg'] = '';
        }

        //返回请求数据
        echo json_encode($rs,true);
    }

}