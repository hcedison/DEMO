<?php
class CallbackController extends Yaf_Controller_Abstract{
	private $_pay_model = null;

	function init(){
		$this->_pay_model = new PayModel(0);
	}

	function alipayAction(){
		$alipay_obj = new Alipay_Config();
		$alipay_config = $alipay_obj->getconfig();

		$alipayNotify = new AlipayNotify($alipay_config);

		$verify_result = $alipayNotify->verifyNotify();

		if($verify_result) {//验证成功
			/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			//请在这里加上商户的业务逻辑程序代

	
			//——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
	
    		//获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
	
			//商户订单号
			$out_trade_no = $_POST['out_trade_no'];
			//支付宝交易号
			$trade_no = $_POST['trade_no'];
			//交易状态
			$trade_status = $_POST['trade_status'];
			//买家邮箱账号
			$buyer_email = $_POST['buyer_email'];
			//总价
			$total_fee = $_POST['total_fee'];

			$this->_pay_model->insertAlipayLog($_POST);

    		if($_POST['trade_status'] == 'TRADE_FINISHED') {
			//判断该笔订单是否在商户网站中已经做过处理
			//如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
			//如果有做过处理，不执行商户的业务程序
				
			//注意：
			//该种交易状态只在两种情况下出现
			//1、开通了普通即时到账，买家付款成功后。
			//2、开通了高级即时到账，从该笔交易成功时间算起，过了签约时的可退款时限（如：三个月以内可退款、一年以内可退款等）后。

        	//调试用，写文本函数记录程序运行情况是否正常
       		 //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
        		$this->_pay_model->checkAlipayOrderNumber($out_trade_no);
    		}else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
			//判断该笔订单是否在商户网站中已经做过处理
			//如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
			//如果有做过处理，不执行商户的业务程序
				
			//注意：
			//该种交易状态只在一种情况下出现——开通了高级即时到账，买家付款成功后。

        	//调试用，写文本函数记录程序运行情况是否正常
        	//logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
        		$this->_pay_model->checkAlipayOrderNumber($out_trade_no);
    		}

			//——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
        
			// echo "success";		//请不要修改或删除
			exit('success');
	
			/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		}else {
			$para_filter = paraFilter($_POST);
		
			//对待签名参数数组排序
			$para_sort = argSort($para_filter);
		
			//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
			$prestr = createLinkstring($para_sort);
			$sign = $_POST['sign'];
			$isSgin = false;
			$isSgin = rsaVerify($prestr, $alipay_config['ali_public_key_path'], $sign);
	
    		//验证失败
			$db = SWUtils::getPDO("mosaic");
			$sql = "insert into tmp_log (notify_data) values(?)";
			$stmt = $db->prepare($sql);
			$result = $stmt->execute(array($prestr.'--------'.$sign));

    		exit('fail');

    		//调试用，写文本函数记录程序运行情况是否正常
    		//logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
		}
	}	

	#支付宝的wap回调页面
	function alipaywapAction(){

		#配置文件
		$alipay_wap_obj = new Alipay_Config();
		$alipay_config = $alipay_wap_obj->getconfig();

		$alipayNotify = new AlipayNotify($alipay_config);
		$verify_result = $alipayNotify->verifyNotify();

		if($verify_result) {//验证成功
			/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			//请在这里加上商户的业务逻辑程序代

	
			//——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
	
    		//获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
	
			//商户订单号
			$out_trade_no = $_POST['out_trade_no'];
			//支付宝交易号
			$trade_no = $_POST['trade_no'];
			//交易状态
			$trade_status = $_POST['trade_status'];
			//买家邮箱账号
			$buyer_email = $_POST['buyer_email'];
			//总价
			$total_fee = $_POST['total_fee'];

			$this->_pay_model->insertAlipayLog($_POST);

    		if($_POST['trade_status'] == 'TRADE_FINISHED') {
			//判断该笔订单是否在商户网站中已经做过处理
			//如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
			//如果有做过处理，不执行商户的业务程序
				
			//注意：
			//该种交易状态只在两种情况下出现
			//1、开通了普通即时到账，买家付款成功后。
			//2、开通了高级即时到账，从该笔交易成功时间算起，过了签约时的可退款时限（如：三个月以内可退款、一年以内可退款等）后。

        	//调试用，写文本函数记录程序运行情况是否正常
       		 //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
        		$this->_pay_model->checkAlipayOrderNumber($out_trade_no);
    		}else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
			//判断该笔订单是否在商户网站中已经做过处理
			//如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
			//如果有做过处理，不执行商户的业务程序
				
			//注意：
			//该种交易状态只在一种情况下出现——开通了高级即时到账，买家付款成功后。

        	//调试用，写文本函数记录程序运行情况是否正常
        	//logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
        		$this->_pay_model->checkAlipayOrderNumber($out_trade_no);
    		}

			//——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
        
			// echo "success";		//请不要修改或删除
			exit('success');
	
			/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		}else {
			$para_filter = paraFilter($_POST);
		
			//对待签名参数数组排序
			$para_sort = argSort($para_filter);
		
			//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
			$prestr = createLinkstring($para_sort);
			$sign = $_POST['sign'];
			$isSgin = false;
			$isSgin = rsaVerify($prestr, $alipay_config['ali_public_key_path'], $sign);
	
    		//验证失败
			$db = SWUtils::getPDO("mosaic");
			$sql = "insert into tmp_log (notify_data) values(?)";
			$stmt = $db->prepare($sql);
			$result = $stmt->execute(array($prestr.'--------'.$sign));

    		exit('fail');

    		//调试用，写文本函数记录程序运行情况是否正常
    		//logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
		}
	}

	function alipaywaporderAction(){

		$out_trade_no = isset($_REQUEST['order_number']) ? trim($_REQUEST['order_number']) : '';

		if(empty($out_trade_no)){
			exit("订单号不存在");
		}

		$order_info = $this->_pay_model->getOrderInfo($out_trade_no);


		if(empty($order_info)){
			exit("订单号不存在");
		}

		if($order_info['status'] == 1){
			exit('订单已经支付成功.');
		}

		$total_fee = $order_info['price'];
		$subject = $order_info['subject'];

		$wap_obj = new Alipay_Config();
		$alipay_wap_config = $wap_obj->getconfig();

		$seller_email = $alipay_wap_config['seller_email'];

		$notify_url = $alipay_wap_config['notify_url'];

		$call_back_url = $alipay_wap_config['call_back_url'];
		$merchant_url = "";

		//返回格式
		$format = "xml";
		//必填，不需要修改

		//返回格式
		$v = "2.0";
		//必填，不需要修改

		//请求号
		$req_id = date('Ymdhis');

		//请求业务参数详细
		$req_data = '<direct_trade_create_req><notify_url>' . $notify_url . '</notify_url><call_back_url>' . $call_back_url . '</call_back_url><seller_account_name>' . $seller_email . '</seller_account_name><out_trade_no>' . $out_trade_no . '</out_trade_no><subject>' . $subject . '</subject><total_fee>' . $total_fee . '</total_fee><merchant_url>' . $merchant_url . '</merchant_url></direct_trade_create_req>';
		//必填

		/************************************************************/

		//构造要请求的参数数组，无需改动
		$para_token = array(
				"service" => "alipay.wap.trade.create.direct",
				"partner" => trim($alipay_wap_config['partner']),
				"sec_id" => trim($alipay_wap_config['sign_type']),
				"format"	=> $format,
				"v"	=> $v,
				"req_id"	=> $req_id,
				"req_data"	=> $req_data,
				"_input_charset"	=> trim(strtolower($alipay_wap_config['input_charset']))
		);

		//建立请求
		$alipaySubmit = new AlipaySubmit($alipay_wap_config);
		$html_text = $alipaySubmit->buildRequestHttp($para_token);

		//URLDECODE返回的信息
		$html_text = urldecode($html_text);

		//解析远程模拟提交后返回的信息
		$para_html_text = $alipaySubmit->parseResponse($html_text);

		//获取request_token
		$request_token = $para_html_text['request_token'];


		/**************************根据授权码token调用交易接口alipay.wap.auth.authAndExecute**************************/

		//业务详细
		$req_data = '<auth_and_execute_req><request_token>' . $request_token . '</request_token></auth_and_execute_req>';
		//必填

		//构造要请求的参数数组，无需改动
		$parameter = array(
				"service" => "alipay.wap.auth.authAndExecute",
				"partner" => trim($alipay_wap_config['partner']),
				"sec_id" => trim($alipay_wap_config['sign_type']),
				"format"	=> $format,
				"v"	=> $v,
				"req_id"	=> $req_id,
				"req_data"	=> $req_data,
				"_input_charset"	=> trim(strtolower($alipay_wap_config['input_charset']))
		);

		//建立请求
		$alipaySubmit = new AlipaySubmit($alipay_wap_config);
		$getpara = $alipaySubmit->buildRequestForm($parameter, 'get', '确认');
		$url = "http://wappaygw.alipay.com/service/rest.htm?".http_build_query($getpara);
		header("Location: $url");
		
		$this->_view->html_text = $html_text;
	}

	function wxpayAction(){
		$doc = new DOMDocument();
		$xml = $GLOBALS['HTTP_RAW_POST_DATA'];
		if (empty($xml)) {
			exit('fail');
		}
		$doc->loadXML($xml);

		$total_fee = @$doc->getElementsByTagName( "total_fee" )->item(0)->nodeValue;
		$total_fee = round($total_fee/100,2);

		$trade_no = @$doc->getElementsByTagName( "transaction_id" )->item(0)->nodeValue;
		$out_trade_no = @$doc->getElementsByTagName( "out_trade_no" )->item(0)->nodeValue;
		
		$pay_model = new PayModel();
		try {
			if($pay_model->checkWxpayOrderNumber($out_trade_no) == true){
				exit('success');
			}else{
				exit('fail');
			}
		}catch(Exception $ex){
			exit('fail');
		}
	}
}
?>