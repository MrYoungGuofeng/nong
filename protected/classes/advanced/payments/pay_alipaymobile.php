<?php
 /**
 * @class pay_alipay
 * @brief 支付宝【移动支付】插件类
 */
class pay_alipaymobile extends PaymentPlugin{

    //支付插件名称
    public $name = '支付宝';
    protected $needSubmit =  false;
	protected $config = array(
		'partner'=>'',
		'seller_email'=>'',
		'key'=>'',
		'sign_type'=>'0001',
		'input_charset'=>'utf-8',
		'transport'=>'http'
	);
	public function __construct($paymentId=0){
		parent::__construct($paymentId);
		$this->config['private_key_path'] = dirname(__File__).'/alipay/key/rsa_private_key.pem';
		$this->config['ali_public_key_path'] = dirname(__File__).'/alipay/key/alipay_public_key.pem';
		$this->config['cacert'] = dirname(__File__).'/alipay/cacert.pem';
	}
	
	//取得配制参数
    public static function config()
    {
    	return array(
    		array('field'=>'partner','caption'=>'合作身份者id','type'=>'text'),
    		array('field'=>'seller_email','caption'=>'收款支付宝帐户','type'=>'text'),
    	);
    }
	
    //同步处理
    public function callback($callbackData,&$paymentId,&$money,&$message,&$orderNo)
    {
		//$this->config['partner']		= '2088711664714164';
		//$this->config['seller_email']	= 'tinyrise@163.com';
		$this->config['partner']		= $this->classConfig['partner'];
		$this->config['seller_email']	= $this->classConfig['seller_email'];
		$orderNo = $callbackData['out_trade_no'];
		$alipayNotify = new AlipayMobileNotify($this->config);
		$verify_result = $alipayNotify->getSignVeryfy($callbackData, $callbackData["sign"],true);
		if($verify_result) {//验证成功
			return true;
		}
		else{
			return false;
		}
    }

    //异步处理
    public function asyncCallback($callbackData,&$paymentId,&$money,&$message,&$orderNo)
    {
        $this->config['partner']		= $this->classConfig['partner'];
		$this->config['seller_email']	= $this->classConfig['seller_email'];
		
		$alipayNotify = new AlipayMobileNotify($this->config);
		$verify_result = $alipayNotify->verifyNotify();
		if($verify_result) {//验证成功
			$doc = new DOMDocument();	
			if ($this->config['sign_type'] == 'MD5') {
				$doc->loadXML($_POST['notify_data']);
			}
			if ($this->config['sign_type'] == '0001') {
				$doc->loadXML($alipayNotify->decrypt($_POST['notify_data']));
			}
			if( ! empty($doc->getElementsByTagName( "notify" )->item(0)->nodeValue) ) {
				//商户订单号
				$orderNo = $doc->getElementsByTagName( "out_trade_no" )->item(0)->nodeValue;
				//支付宝交易号
				$trade_no = $doc->getElementsByTagName( "trade_no" )->item(0)->nodeValue;
				//交易状态
				$trade_status = $doc->getElementsByTagName( "trade_status" )->item(0)->nodeValue;
				if($trade_status == 'TRADE_FINISHED') {
					return true;
				}
				else if ($trade_status == 'TRADE_SUCCESS') {
					return true;
				}
			}
		}
		else {
			return false;
		}
    }
    
    //后期与服务同步处理类
    public function afterAsync()
    {
        $payment = new Payment($this->paymentId);
        $paymentObj = $payment->getPayment();
        return new AlipayDelivery($paymentObj['partner_id'],$paymentObj['partner_key']);
    }
    
    //打包数据
    public function packData($payment)
    {
		//$this->config['partner']		= '2088711664714164';
		//$this->config['seller_email']	= 'tinyrise@163.com';
		$this->config['partner']		= $this->classConfig['partner'];
		$this->config['seller_email']	= $this->classConfig['seller_email'];
		$format = "xml";
		$v = "2.0";
		$req_id = date('Ymdhis');
		$notify_url = $this->asyncCallbackUrl;
		$call_back_url = $this->callbackUrl;
		$merchant_url = "";
		$out_trade_no = $payment['M_OrderNO'];
		$subject = $payment['R_Name'];
		$total_fee = number_format($payment['M_Amount'], 2, '.', '');
		$req_data = '<direct_trade_create_req><notify_url>' . $notify_url . '</notify_url><call_back_url>' . $call_back_url . '</call_back_url><seller_account_name>' . trim($this->config['seller_email']) . '</seller_account_name><out_trade_no>' . $out_trade_no . '</out_trade_no><subject>' . $subject . '</subject><total_fee>' . $total_fee . '</total_fee><merchant_url>' . $merchant_url . '</merchant_url></direct_trade_create_req>';

		//构造要请求的参数数组，无需改动
		$para_token = array(
				"service" => "alipay.wap.trade.create.direct",
				"partner" => trim($this->config['partner']),
				"sec_id" => trim($this->config['sign_type']),
				"format"	=> $format,
				"v"	=> $v,
				"req_id"	=> $req_id,
				"req_data"	=> $req_data,
				"_input_charset"	=> trim(strtolower($this->config['input_charset']))
		);
		
		//建立请求
		$AlipayMoblieSubmit = new AlipayMoblieSubmit($this->config);
		$html_text = $AlipayMoblieSubmit->buildRequestHttp($para_token);
		//URLDECODE返回的信息
		
		$html_text = urldecode($html_text);
		//解析远程模拟提交后返回的信息
		$para_html_text = $AlipayMoblieSubmit->parseResponse($html_text);
		//获取request_token
		$request_token = $para_html_text['request_token'];

		$req_data = '<auth_and_execute_req><request_token>' . $request_token . '</request_token></auth_and_execute_req>';
		$parameter = array(
				"service" => "alipay.wap.auth.authAndExecute",
				"partner" => trim($this->config['partner']),
				"sec_id" => trim($this->config['sign_type']),
				"format"	=> $format,
				"v"	=> $v,
				"req_id"	=> $req_id,
				"req_data"	=> $req_data,
				"_input_charset"	=> trim(strtolower($this->config['input_charset']))
		);
		$AlipayMoblieSubmit = new AlipayMoblieSubmit($this->config);
		$html_text = $AlipayMoblieSubmit->buildRequestForm($parameter, 'get', '确认');
		return $html_text;
    }
}
