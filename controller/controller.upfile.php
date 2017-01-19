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
class UpfileController extends Controller
{
    private $model;
    const M = "Upfile";

    public function __construct()
    {
        $this->model = Model::instance(self::M);
    }

    public function index(){
        $ret_data = array(
            'resTime' => time().'',
            'data' => '',
            'resCode' => '000002',
            'resMsg' => '请求地址错误!!!'
        );
        echo json_encode($ret_data,true);
    }

    //上传图片
    public function imgs(){
        //接收头像信息
        $where = json_decode(file_get_contents('php://input'), true);
        $ret_data = array( 'resTime' => time().'', 'data' => '', 'resCode' => '', 'resMsg' => '' );//初始返回值

        //图片二进制
        $filebase64 = $where['filebase64'];
        //图片类型
        $filetype = $where['filetype'];

        //验证图片类型
        if($filetype === 'png' or $filetype === 'jpg' or $filetype === 'jpeg') { } else {
            $ret_data['resCode'] = '000002';
            $ret_data['resMsg'] = '只支持jpg、png格式';
            echo json_encode($ret_data,JSON_UNESCAPED_UNICODE);
        }

        //上传图片
        $ret = upFile($filebase64,$filetype,"head/");

        //验证是否上传成功
        if($ret['retfile']=='')
        {
            $ret_data['resCode'] = '000003';
            $ret_data['resMsg'] = '上传失败';
            echo json_encode($ret_data,JSON_UNESCAPED_UNICODE);
        } else {
            $ret_data['resCode'] = '000000';
            $ret_data['resMsg'] = '上传成功';
            $ret_data['data']['imageUrl'] = $ret['filepath'];
            echo json_encode($ret_data,JSON_UNESCAPED_UNICODE);
        }
    }

}