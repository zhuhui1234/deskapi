<?php
/**
 *  SEND SMS MESSAGE.
 * Created by PhpStorm.
 * User: JOSON
 * Date: 2016/11/28
 * Time: 17:36
 *
 * 1.    我司接口编码是GB2312 如果出现手机收到短信内容是乱码 需对短信内容做URLENCODE编码
 *
 * 2.    建议不要写IE缓存 因为出现异常掉线了 没有正常注销的情况下 重连可能长时间返回1000的错误（当前用户已登录）
 *
 * 3.    心跳连接建议 50秒--2分钟做一次 如果出现异常掉线 没有正常注销 会在5—15分钟后掉线（前提没写IE缓存） 所以建议5分钟重连一次 频繁的连接将当恶意连接处理
 * 另外没有正常注销导致不能正常登陆的 也可以直接联系我们工作人员手动清除登陆标识
 *
 * 4.    建议发送短信 间隔0.1秒提交一次， 群发一次最多提交1000个手机号码
 *
 * 5.    用到ActiveID的接口都必须在同一个SESSION下面操作  同一个账号只能有一个连接 不能同时多连接 每次登录成功ActiveID 都不同
 *
 * 6.    支持账号和IP绑定
 *
 * 7.    对于发送验证码的用户（验证码 密码 注册 等等行为的短信）
 * 需要加强安全管理：采用安全图片验证码、单IP
 * 请求次数限制、限制发送时长限制三个措施对动态短信验证码类业务进行信息安全防护
 * 其他行为的短信 有需要的最好也做一下以上3点限制  避免不必要的投诉！
 *
 * 8. 密码需加密传输，密码转换器转密文 用密文登录
 *
 */

class Sms
{

    //Sms的命名前缀
    private $prefix;
    //Sms请求URL
    private $sURL;
    //Sms请求IP
    private $srv_ip;
    //Sms企业ID
    private $userID;
    //Sms账号
    private $account;
    //Sms密码 加密方式SHA1 40位大写
    private $password;
    //Sms短信签名
    private $signature;

    function __construct($prefix = SITE_PREFIX)
    {
        $this->prefix = $prefix;
        $this->sURL = SMS_SERVER_URL;
        $this->srv_ip = SMS_SERVER_IP;
        $this->userID = SMS_USER_ID;
        $this->account = SMS_ACCOUNT;
        $this->password = SMS_PWD;
        $this->signature = SMS_SIGNATURE;
    }

    /**
     * 返回Sms的单例
     * @access public
     * @return object Session类的实例
     */
    public static function instance($prefix = SITE_PREFIX)
    {
        static $instance;
        if (! $instance)
        {
            $instance = new Sms($prefix);
        }
        return $instance;
    }

    /**
     * Explains Error Codes 解释错误代码
     *
     * @param $error_code
     * @return string
     */
    private function _catchError($error_code)
    {
        switch ($error_code) {
            case 1000 :
                return "当前用户已经登录";
                break;
            case 1001 :
                return "当前用户没有登录";
                break;
            case 1002 :
                return "登录被拒绝";
                break;
            case 2001 :
                return "短信发送失败";
                break;
            case 2002 :
                return "短信库存不足";
                break;
            case 2003 :
                return "存在无效的手机号码";
                break;
            case 2004 :
                return "短信内容包含禁用词语";
            case 2008 :
                return "短信签名不正确或者没有签名";
                break;
            case 2009 :
                return "短信模版错误";
                break;
            case 3001 :
                return "没有要接收的短信";
                break;
            case 3002 :
                return "没有要接收的回复状态";
                break;
            case 9001 :
                return "JobID参数不符合要求";
                break;
            case 9002 :
                return "SendDate或SendTime参数不是有效日期";
                break;
            case 9003 :
                return "短信内容长度超过300";
                break;
            case 9004 :
                return "参数不符合要求";
                break;
            case 9099 :
                return "其它系统错误";
                break;
            default:
                return NULL;
                break;
        }
    }

    //发动请求
    private function httppost($keyVars)
    {
        //你的目标服务地址或频道
        $srv_ip = $this->srv_ip;
        $srv_port = 80;
        //接收你post的URL具体地址
        $url = $this->sURL;
        $fp = '';
        $resp_str = '';
        $errno = 0;
        $errstr = '';
        $timeout = 300;
        $post_str = $keyVars;//要提交的内容.

        $fp = fsockopen($srv_ip,$srv_port,$errno,$errstr,$timeout);
        if (!$fp)
        {
            echo('fp fail');
        }

        $content_length = strlen($post_str);
        $post_header = "POST $url HTTP/1.1\r\n";
        $post_header .= "Content-Type:application/x-www-form-urlencoded\r\n";
        $post_header .= "User-Agent: MSIE\r\n";
        $post_header .= "Host: ".$srv_ip."\r\n";;
        $post_header .= "Content-Length: ".$content_length."\r\n";
        $post_header .= "Connection: close\r\n\r\n";
        $post_header .= $post_str."\r\n\r\n";

        //请求
        fwrite($fp,$post_header);

        //接收返回值
        while (!feof($fp)) {
            //去除请求包的头只显示页面的返回数据
            $line = fgets($fp,1024);
        }

        //转义数据
        $ret = json_decode($line,true);
        write_to_log('send sms report: '. $line, '_sms');
        //定义返回值
        if($ret['LANZ_ROOT']['ErrorNum'] != 0){
            $rs = $this->_catchError($ret['LANZ_ROOT']['ErrorNum']);
        } else {
            $rs = '发送成功';
        }

        return $rs;
    }

    /**
     * gb2312 converts to utf-8
     *
     * @param $gb_text
     * @return string
     */
    private function _convertsToUTF8($gb_text)
    {
        return iconv("utf-8","gb2312//IGNORE",$gb_text);
    }

    //发送短信
    public function sendSms($content,$phones)
    {
        //GB2312编辑转义
        $content = $this->_convertsToUTF8($this->signature.$content);
        $keyVars = "UserID=$this->userID&Account=$this->account&Password=$this->password&Content=$content&Phones=$phones&ReturnXJ=1";

        return $this->httppost($keyVars);
    }

}
