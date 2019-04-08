<?php
/**
 * @class pay_weixin
 * @brief 微信支付
 */

class pay_weixin extends PaymentPlugin
{
    //支付插件名称
    public $name = '微信支付';
    protected $needSubmit =  false;

	public function __construct($paymentId=0){
        //同步回调地址
        $this->callbackUrl = str_replace('plugins/','',Url::fullUrlFormat("/payment/notify"));

        //异步回调地址
        $this->asyncCallbackUrl = str_replace('plugins/','',Url::fullUrlFormat("/payment/notify"));
        $this->paymentId = $paymentId;
    }

    public function submitUrl(){
        return 'https://api.mch.weixin.qq.com/pay/unifiedorder';
    }

	//取得配制参数
    public static function config()
    {
    	return array(
    		array('field'=>'APPID','caption'=>'支付APPID','type'=>'string'),
    		array('field'=>'MCHID','caption'=>'商户号','type'=>'string'),
			array('field'=>'KEY','caption'=>'商户支付密钥','type'=>'string'),
    		array('field'=>'APPSECRET','caption'=>'公众帐号secert','type'=>'string'),
    	);
    }

    //同步处理
    public function callback($callbackData,&$paymentId,&$money,&$message,&$orderNo)
    {
		WxPayConfig::setConfig($this->classConfig);
        $input = new WxPayOrderQuery();
        $input->SetTransaction_id($transaction_id);
        $result = WxPayApi::orderQuery($input);
        if(array_key_exists("return_code", $result)
            && array_key_exists("result_code", $result)
            && $result["return_code"] == "SUCCESS"
            && $result["result_code"] == "SUCCESS")
        {
            return true;
        }
        return false;
    }

    //异步处理
    public function asyncCallback($callbackData,&$paymentId,&$money,&$message,&$orderNo)
    {
        $input = new WxPayOrderQuery();
        $input->SetTransaction_id($transaction_id);
        $result = WxPayApi::orderQuery($input);
        if(array_key_exists("return_code", $result)
            && array_key_exists("result_code", $result)
            && $result["return_code"] == "SUCCESS"
            && $result["result_code"] == "SUCCESS")
        {
            return true;
        }
        return false;
    }

    //数据打包
    public function packData($payment)
    {
		WxPayConfig::setConfig($this->classConfig);
        $price  = ceil($payment['M_Amount'] * 100);
        //获取用户openid
        $tools = new JsApiPay();
        $weixin_openid = Cookie::get('weixin_openid');
        //统一下单
        $input = new WxPayUnifiedOrder();
        $input->SetBody($payment['R_Name']);
        //$input->SetAttach("test");
        $input->SetOut_trade_no($payment['M_OrderNO']);
        $input->SetTotal_fee($price);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        //$input->SetGoods_tag("test");
        $input->SetNotify_url($this->asyncCallbackUrl);
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($weixin_openid);
        $order = WxPayApi::unifiedOrder($input);
        //return var_export($payment,true);
        $jsApiParameters = $tools->GetJsApiParameters($order);
        $backUrl = Url::fullUrlFormat("/simple/order_status/order_id/$payment[order_id]");
		$successUrl = Url::fullUrlFormat("/ucenter/order_detail/id/$payment[order_id]");
        $html=<<<EOT
<html>
	<head>
		<meta http-equiv="content-type" content="text/html;charset=utf-8"/>
		<meta name="viewport" content="width=device-width, initial-scale=1"/>
		<title>微信支付</title>
		<script type="text/javascript">

			//调用微信JS api 支付
			function jsApiCall()
			{
				WeixinJSBridge.invoke(
					'getBrandWCPayRequest',
					$jsApiParameters,
					function(res){
						WeixinJSBridge.log(res.err_msg);
						switch (res.err_msg){
							case 'get_brand_wcpay_request:cancel':
								location.href="$backUrl";
								break;
							 case 'get_brand_wcpay_request:fail':
								location.href="$backUrl";
								break;
							 case 'get_brand_wcpay_request:ok':
								location.href="$successUrl";
								break;
						}
					}
				);
			}

			function callpay()
			{
				if (typeof WeixinJSBridge == "undefined"){
					if( document.addEventListener ){
						document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);
					}else if (document.attachEvent){
						document.attachEvent('WeixinJSBridgeReady', jsApiCall);
						document.attachEvent('onWeixinJSBridgeReady', jsApiCall);
					}
				}else{
					jsApiCall();
				}
			}
		</script>
	</head>
	<body>
		<div style="width:95%;margin:auto;">
		<div style="margin-top:20px;text-align:center;border-bottom:#ccc 1px solid;padding-bottom:20px;font-size:18px;font-weight: 800;">{$payment['R_Name']}</div>
		<div style="margin-top:20px;">订单编号：{$payment['M_OrderNO']}</div>
		<div style="margin-top:20px;border-bottom:#ccc 1px solid;padding-bottom:20px;">支付金额：{$payment['M_Amount']}</div>
		<div style="margin-top:20px;">收货人：{$payment['P_Name']}</div>
		<div style="margin-top:20px;">联系电话：{$payment['P_Mobile']}</div>
		</div>
		<div align="center" style="width:95%;margin:auto;margin-top:40px;">
			<button style="width:100%; height:50px; border-radius: 15px;background-color:#21A62B; border:0px #21A62B solid; cursor: pointer;  color:white;  font-size:16px;" type="button" onclick="callpay()" >微信安全支付</button>
		</div>
	</body>
</html>
EOT;
return $html;
    }
}
