<?php
#载入需要文件
require_once dirname(__FILE__)."/alipay_notify.class.php";
require_once dirname(__FILE__)."/alipay_submit.class.php";

class Alipay_Config{

	function getconfig(){
		
		$alipay_config['partner']		= '2088511899403800';

		//安全检验码，以数字和字母组成的32位字符
		//如果签名方式设置为“MD5”时，请设置该参数
		$alipay_config['key']			= 'y9c12ai860iwhenhio7omynb9gr2jh00';

		//签约支付宝账号或卖家支付宝帐户
		$alipay_config['seller_email']	= "stan@imcharm.com";
		$alipay_config['seller_id']	= "stan@imcharm.com";

		//商户的私钥（后缀是.pen）文件相对路径
		//如果签名方式设置为“0001”时，请设置该参数
		$alipay_config['private_key_path']	= dirname(__FILE__).'/key/rsa_private_key.pem';
		$alipay_config['rsa_private_key_path']	= dirname(__FILE__).'/key/rsa_private_key.pem';

		$alipay_config['mobile_private_key_path'] = dirname(__FILE__).'/key/rsa_alipay_private_key_mobile.pem';

		//支付宝公钥（后缀是.pen）文件相对路径
		//如果签名方式设置为“0001”时，请设置该参数
		$alipay_config['ali_public_key_path']= dirname(__FILE__).'/key/alipay_public_key.pem';

		//签名方式 不需修改
		$alipay_config['sign_type']    = 'RSA';#'0001';#

		//字符编码格式 目前支持 gbk 或 utf-8
		$alipay_config['input_charset']= 'utf-8';

		//ca证书路径地址，用于curl中ssl校验
		//请保证cacert.pem文件在当前文件夹目录中
		$alipay_config['cacert']    = getcwd().'\\cacert.pem';

		//访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
		$alipay_config['transport']    = 'http';
		//返回格式
		$alipay_config['format'] = "xml";
		//必填，不需要修改

		//返回格式
		$alipay_config['v'] = "2.0";
		

		$alipay_config['Service_Create']			= "alipay.wap.trade.create.direct";
		$alipay_config['Service_authAndExecute']	= "alipay.wap.auth.authAndExecute";
		
		$alipay_config['notify_url']	= 'http://'.$_SERVER['SERVER_NAME'].'/api/callback/alipay/';//服务端获取通知地址，用户交易完成异步返回地址
		$alipay_config['call_back_url']	= 'http://'.$_SERVER['SERVER_NAME'].'/api/callback/alipaywap/';//用户交易完成同步返回地址

		return $alipay_config;
	}
}