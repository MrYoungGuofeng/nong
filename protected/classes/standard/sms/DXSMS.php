<?php

/**
 * 天翼验证码及模板短信发送类,
 * 
 * @author Young
 * @class DXSMS
 */
class DXSMS extends ClassConfig implements SMSInterface
{
    private $app_id = '';// 应用ID
    private $app_secret = '';// 应用的密钥
    private $timestamp=''; //时间戳，格式为：yyyy-MM-dd hh:mm:ss
    private $phone='';
    private $exp_time=''; //验证码有效期长度，单位是分钟，可以为空，默认有效2分钟
    private $grant_type='client_credentials'; //Client Credentials授权模式
    private $access_token_url = 'https://oauth.api.189.cn/emp/oauth2/v2/access_token';// 获取access_token的URL
    private $send_url = 'http://api.189.cn/v2/dm/randcode/sendSms';// 自定义短信发送信息的URL
    private $token_url = 'http://api.189.cn/v2/dm/randcode/token';// 获取token的URL
    private $send_template_url = 'http://api.189.cn/v2/emp/templateSms/sendSms';
    
    //构造方法
    public function __construct(){
        parent::__construct();
        $this->timestamp = date('Y-m-d H:i:s');
		$this->app_id = $this->config['app_id'];
        $this->app_secret = $this->config['app_secret'];
    }

    //发送验证码
    public function sendCode($mobile,$code='123456')
    {
        $result = array('status'=>'fail','message'=>'手机号码不正确！');
        $code = Filter::int($code);
        if(Validator::mobi($mobile)){
            $access_token=$this->getAccessKey();
            $token=$this->getToken($access_token);
            $param['app_id']= "app_id=".$this->app_id;
            $param['access_token'] = "access_token=".$access_token;
            $param['timestamp'] = "timestamp=".$this->timestamp;
            $param['token'] = "token=".$token;
            $param['phone'] = "phone=".$mobile;
            $param['randcode'] = "randcode=".$code;
            if(!empty($this->exp_time)) $param['exp_time'] = "exp_time=".$this->exp_time;
            ksort($param);
            $plaintext = implode("&",$param);
            $param['sign'] = "sign=".rawurlencode(base64_encode(hash_hmac("sha1", $plaintext, $this->app_secret, $raw_output=True)));
            ksort($param);
            $str = implode("&",$param);
            $result = $this->curl_post($this->send_url,$str);
            $obj = JSON::decode($result); 
            if(isset($obj['res_code']) && $obj['res_code']==0) $result = array('status'=>'success','message'=>'');
            else $result = array('status'=>'fail','message'=>'发送失败，请检查配制是否正确，或是否欠费');
        }
        return $result;
    }

    //改送模板短信
    public function sendTemplate($mobile,$templateId=0,$template_param=array())
    {
        $result = array('status'=>'fail','message'=>'手机号码不正确！');
        if(Validator::mobi($mobile)){
            if(is_array($template_param)){
                $template_param = json_encode($template_param);
                $access_token=$this->getAccessKey();
                $token=$this->getToken($access_token);
                $param['app_id']= "app_id=".$this->app_id;
                $param['access_token'] = "access_token=".$access_token;
                $param['timestamp'] = "timestamp=".$this->timestamp;
                $param['token'] = "token=".$token;
                $param['acceptor_tel'] = "acceptor_tel=".$mobile;
                $param['template_id'] = "template_id=".$templateId;
                $param['template_param'] = "template_param=".$template_param;
                ksort($param);
                $plaintext = implode("&",$param);
                $param['sign'] = "sign=".rawurlencode(base64_encode(hash_hmac("sha1", $plaintext, $this->app_secret, $raw_output=True)));
                ksort($param);
                $str = implode("&",$param);
                $result = $this->curl_post($this->send_template_url,$str);
                $obj = JSON::decode($result);
                if(isset($obj['res_code']) && $obj['res_code']==0) $result = array('status'=>'success','message'=>'');
                else $result['message']="发送失败";
            }
            else $result['message']="没有指定模板参数";
        }
        return $result;
    }

    //取得配制参数
    public static function config()
    {
    	return array(
    		array('field'=>'app_id','caption'=>'App Id','type'=>'string'),
    		array('field'=>'app_secret','caption'=>'App Secret','type'=>'string'),
    	);
    }

    //获取access_token
    public function getAccessKey() {
        $param['grant_type']= "grant_type=".$this->grant_type;
        $param['app_id'] = "app_id=".$this->app_id;
        $param['app_secret'] = "app_secret=".$this->app_secret;
        $plaintext = implode("&",$param);
        $result = $this->curl_post($this->access_token_url,$plaintext);
        $obj = JSON::decode($result);
        return $obj['access_token'];
    }
    
    //获取Token
    private function getToken($access_token) {
        $param = array();
        $param['app_id']= "app_id=".$this->app_id;
        $param['access_token'] = "access_token=".$access_token;
        $param['timestamp'] = "timestamp=".$this->timestamp;
        ksort($param);
        $plaintext = implode("&",$param);
        $param['sign'] = "sign=".rawurlencode(base64_encode(hash_hmac("sha1", $plaintext, $this->app_secret, true)));
        ksort($param);
        $param['timestamp'] = "timestamp=".urlencode($this->timestamp);
        $url = $this->token_url.'?'.implode("&",$param);
        $result = $this->curl_get($url);
        $obj = JSON::decode($result);
        return $obj['token'];
    }
    
    //cURL库抓网页,从一个链接上取数据(get方式)
    private function curl_get($url=''){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
    
    //cURL库抓网页,从一个链接上取数据(post方式)
    private function curl_post($url='', $postdata=''){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
}