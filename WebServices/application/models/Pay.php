<?php
class PayModel extends MosaicModel{
	private $uid;

	function __construct($uid){
		parent::__construct();
		$this->uid = intval($uid);
	}

	function isOrderUsed($order_no){
		$sql = "select count(*) from recharge_log where order_no=?";
		$stmt = $this->_db->prepare($sql);
		$stmt->execute(array($order_no));

		$count = intval($stmt->fetchColumn());

		return $count>0;
	}

	function getReceiptInfo($receipt){
		$url = "https://buy.itunes.apple.com/verifyReceipt";
		$postData = json_encode(
		    array('receipt-data' => $receipt)
		);
		$ch = curl_init($url);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
	
		$response = curl_exec($ch);
		$errno    = curl_errno($ch);
		$errmsg   = curl_error($ch);
		curl_close($ch);
	
		if ($errno != 0) {
	 	   return null;
		}

		$data = json_decode($response,true);		
		return $data;
	}

	function isReceiptValid($receipt){
	    $sandbox_url = 'https://sandbox.itunes.apple.com/verifyReceipt';
	    $production_url = 'https://buy.itunes.apple.com/verifyReceipt';


	    $ch = curl_init($production_url);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_POST, true);
	    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array('receipt-data' => $receipt)));
	    $response = curl_exec($ch);
	    curl_close($ch);

	    $decoded_response = json_decode($response,true);
	    if (intval($decoded_response['status'] == 21007)){
	        $ch = curl_init($sandbox_url);
	        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	        curl_setopt($ch, CURLOPT_POST, true);
	        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array('receipt-data' => $receipt)));
	        $response = curl_exec($ch);
	        curl_close($ch);

	        $decoded_response = json_decode($response,true);
	    }



	    if (empty($response)){
	        return true;
	    }else{
	        if (intval($decoded_response['status']) == 0){
	            $receiptInfo = $decoded_response['receipt'];
	            $bundle_id = $receiptInfo['bundle_id'];
	            $in_app = $receiptInfo['in_app'];
	            $product_id = $in_app[0]['product_id'];

	            if ($bundle_id == "com.5stan.Lighthouse" && !empty($product_id))
	                return true;
	            else
	                return false;

	        }else{
	            return false;
	        }
	    }
	}

	function rechargeViaApple($product_id){
		$amount = intval(str_replace('Lamp', '', $product_id));
		$myuid = $this->uid;

		$sql = "update `users` set balance = balance + $amount where uid = $myuid";
		$this->_db->exec($sql);
	}


	function saveAppleReceipt($product_id,$receipt,$order_number,$invalid = 0){
		$source = "apple";
		$status = 1;

		if ($invalid != 0)
			$status = 0;

		$amount = intval(str_replace('Lamp', '', $product_id));

		switch ($amount) {
			case 5:
				$price = 30;
				break;
			case 36:
				$price = 198;
				break;
			case 128:
				$price = 648;
				break;
			case 398:
				$price = 1998;
				break;
			case 860:
				$price = 3998;
				break;
			case 1500:
				$price = 6498;
				break;
			default:
				# code...
				$price = 0;
				break;
		}

		$subject = $amount . "个灯泡";


		$order_no = $order_number;

		$sql = "insert into recharge_log (uid,product_id,price,source,order_no,receipt,status,subject) values (?,?,?,?,?,?,?,?)";
		$stmt = $this->_db->prepare($sql);
		$stmt->execute(array($this->uid,$product_id,$price,$source,$order_no,$receipt,$status,$subject));
	}

	private function generateOrderNumber($pay_platform = 'alipay',$length = 32){
        $codeAlphabet  = 'abcdefghijklmnopqrstuvwxyz';

        $order_number  = $pay_platform;
        $order_number .= date('YmdHis',time());

        $length = $length - strlen($order_number);

        for($i = 0;$i < $length;$i ++){
            $order_number .= $codeAlphabet[mt_rand(0,strlen($codeAlphabet)-1)];
        }

        #返回大写订单号
        return strtoupper($order_number);
	}


	static function finishRecharge($order_no){
		$sql = "update recharge_log set status=1 where order_no=$order_no";

		$stmt = $this->_db->query($sql);
	}

	function generateOrder($shop_id, $price, $source){
		$myuid = $this->uid;
		$price = floatval($price);
		// $uid = 0,$pay_platform = 'alipay',$pay_service = 0,$pay_vip_months = 0,$pay_bean = 0

		$shop_model = $shop_model = new ShopModel();
		$shopInfo = $shop_model->getShopInfo($shop_id);

		$discountInfo = $shop_model->discountedPrice($shop_id, $price);

		$origin_price = $price;
		$price = floatval($discountInfo['price']);

		$subject = $shopInfo['name'];



		$sql = "INSERT INTO orders (uid, shop_id, price, origin_price, order_no, source, modify_time) VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
		$stmt = $this->_db->prepare($sql);

		$order_number = $this->generateOrderNumber($source,32);
		$result = $stmt->execute(array($myuid,$shop_id,$price,$origin_price,$order_number,$source));

		while(!$result){
			$order_number = $this->generateOrderNumber($source,32);
			$result = $stmt->execute(array($myuid,$shop_id,$price,$origin_price,$order_number,$source));
		}
		

		#支付宝支付
		if($source == 'alipay'){
			$order_info['order_string'] = $this->generateAlipayOrderString($subject,$price,$order_number);
			$order_info['order_number'] = $order_number;
		}

		#银联接口
		if($source == 'unionpay'){
			$order_info['order_number'] = $this->generateUnionpayTN($subject,$price,$order_number);
		}

		#微信支付接口
		if($source == 'wxpay'){
			$order_info = $this->generateWxpayOrder($subject,$price,$order_number);
			$order_info['order_number'] = $order_number;
		}

		#支付宝wap
		if($source == 'alipaywap'){
			$order_info['order_url'] = $this->generateAlipayWapOrderUrl($order_number);
		}

		return $order_info;
	}

	function generateAlipayWapOrderUrl($order_number = ''){
		if (8888==intval($_SERVER['SERVER_PORT']))
			return "http://".$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT']."/mosaic/index/api/callback/alipaywaporder?order_number={$order_number}";
		else
			return "http://".$_SERVER['SERVER_NAME']."/api/callback/alipaywaporder?order_number={$order_number}";
	}

	#生成支付宝的订单序号
	function generateAlipayOrderString($subject = '',$money = 0.00,$order_number = ''){
		$alipay_obj = new Alipay_Config();
		$alipay_config = $alipay_obj->getconfig();

		#支付宝支付

		$notify_url = $alipay_config['notify_url'];//$this->getAliPayCallBackUrl();
		
		$sign_string = '';
		
		$order['partner'] = $alipay_config['partner'];
		$order['seller_id'] = $alipay_config['seller_id'];
		$order['out_trade_no'] = $order_number;
		$order['subject'] = $subject;
		$order['body'] = $subject;
		$order['total_fee'] = strval($money);
		$order['notify_url'] = $notify_url;
		$order['service'] = 'mobile.securitypay.pay';

		$order['payment_type'] = '1';
		$order['_input_charset'] = 'utf-8';
		$order['int_b_pay'] = '30m';
		$i = 0;
		foreach ($order as $key => $value) {
			if($i > 0){
				$sign_string .= '&'.$key.'="'.$value.'"';
			}else{
				$sign_string .= $key.'="'.$value.'"';
			}
			$i++;
		}

		unset($order);

		$order_string = $sign_string;
		$order_string .= '&sign="'.urlencode(rsaSign($sign_string,$alipay_config['rsa_private_key_path'])).'"';
		$order_string .= '&sign_type="RSA"';
		return $order_string;
	}

	function generateWxpayOrder($subject,$money = 0.00,$order_number = '') {
		$wx = new Wxpay_WxPayApi();
		$input = new WxPayUnifiedOrder();
		$input->SetBody($subject);
		$input->SetOut_trade_no($order_number);
		$input->SetTotal_fee($money * 100);
		$input->SetTrade_type("APP");
		$input->SetNotify_url("http://{$_SERVER['SERVER_NAME']}/api/callback/wxpay/");
		$result = $wx->unifiedOrder($input);
		
		if(empty($result)){
			$this->throwException(-20078);
			return false;
		}
		
		$paysignpara = array(
			'appid' => $result['appid'],
			'noncestr' => $result['nonce_str'],
			'package' => 'Sign=WXPay',
			'partnerid' => $result['mch_id'],
			'prepayid' => $result['prepay_id'],
			'timestamp' => $result['timestamp'],
		);

		$pagesign =  $wx->createPaySign($paysignpara);
		
		if(empty($pagesign)){
			$this->throwException(-20078);
			return false;
		}
		
		$ret = array(
			'partnerid' => $result['mch_id'],
			'prepayid' => $result['prepay_id'],
			'noncestr' => $result['nonce_str'],
			'timestamp' => $result['timestamp'],
			'sign'      => strtoupper($pagesign)
		);

		return $ret;
	}

	static function testPay(){
		$alipay_obj = new Alipay_Config();
			$alipay_config = $alipay_obj->getconfig();

		$alipay_notify = new AlipayNotify($alipay_config);
		$ali_public_key_path = $alipay_config['ali_public_key_path'];


		$data = "body=16个秀币&buyer_email=dreamolight@sohu.com&buyer_id=2088002188363700&discount=0.00&gmt_create=2014-09-12 04:21:28&is_total_fee_adjust=Y&notify_id=72a17ba5908e93f4d3a104911c257f815w&notify_time=2014-09-12 04:21:28&notify_type=trade_status_sync&out_trade_no=ALIPAY20140912042125PGYYCPBQCWWQ&payment_type=1&price=0.01&quantity=1&seller_email=stan@imcharm.com&seller_id=2088511899403800&subject=16个秀币&total_fee=0.01&trade_no=2014091277794870&trade_status=WAIT_BUYER_PAY&use_coupon=N";
		$sign = "eTw8sj+KMa17gFNjmbIZFGDgmKS154gfkDdUpVPNyMqB3uKFDV75YdEOVtuxJYN3QtIL++ps7rESIMjHhdc8dUta0kN+rd7UA/8uFuG3v69qNSdRqXV2ej6tdHRLuAyEyEVvugqbpRT474k8A2AthawziabDKcxIGm/nGq8T6a4=";
		

		$pubKey = file_get_contents($ali_public_key_path);
    	$res = openssl_get_publickey($pubKey);
    	print_r($res);
    	$result = openssl_verify($data, base64_decode($sign), $res);
    	openssl_free_key($res);

    	echo $result;
		// $result = $alipay_notify->getSignVeryfy($data,$data['sign']);

    	if ($result)
    		echo "true";
    	else
    		echo "false";

		// echo urlencode(rsaSign("abcdefghijkl",$alipay_config['rsa_private_key_path']));
	}	

	function insertAlipayLog($alipay_log){
		$trade_no = $alipay_log["trade_no"];
		$out_trade_no = $alipay_log["out_trade_no"];
		$trade_status = $alipay_log["trade_status"];
		$total_fee = $alipay_log["total_fee"];
		$buyer_email = $alipay_log["buyer_email"];

		$sql = "select count(*) from alipay_log where trade_no='".$trade_no."'";
		$stmt = $this->_db->query($sql);
		
		$count = intval($stmt->fetchColumn());

		if ($count>0){
			//	update
			$sql = "update alipay_log set trade_status='".$trade_status."' where trade_no='".$trade_no."'";
			$count = $this->_db->exec($sql);

			if ($count>0)
				return true;
		}else{
			//	insert
			$sql = "insert into alipay_log (trade_no,out_trade_no,trade_status,total_fee,buyer_email) values (?,?,?,?,?)";
			$stmt = $this->_db->prepare($sql);

			$result = $stmt->execute(array($trade_no,$out_trade_no,$trade_status,$total_fee,$buyer_email));

			return $result;
		}

		return false;
	}

	function checkAlipayOrderNumber($out_trade_no){
		$order_no = $out_trade_no;

		$sql = "SELECT uid,shop_id,price FROM orders WHERE order_no='".$order_no."' and status=0";
		$stmt = $this->_db->query($sql);
		$ary = $stmt->fetchAll();
		if (count($ary)==1){
			$info = $ary[0];
			$uid = intval($info["uid"]);
			$shop_id = intval($info["shop_id"]);

			//	update alipay_log with uid
			$sql = "update alipay_log set uid=$uid where out_trade_no='".$out_trade_no."' and uid=0";
			$this->_db->exec($sql);

			$sql = "UPDATE orders SET `status` = 1 WHERE order_no='".$order_no."'";
			$count = $this->_db->exec($sql);
			if ($count>0)
				return true;
			else
				return false;
		}else
			return false;
	}

	function checkWxpayOrderNumber($out_trade_no){
		$order_no = $out_trade_no;

		$sql = "SELECT uid,shop_id,price FROM orders WHERE order_no='".$order_no."' and status=0";
		$stmt = $this->_db->query($sql);
		$ary = $stmt->fetchAll();
		if (count($ary)==1){
			$info = $ary[0];
			$uid = intval($info["uid"]);
			$product_id = $info["product_id"];

			$sql = "UPDATE orders SET `status`=1 WHERE order_no ='".$order_no."'";
			$count = $this->_db->exec($sql);
			if ($count>0)
				return true;
			else
				return false;
		}else
			return false;
	}

	function exchangePointToBalance($balance,$request_time){
		$request_time = intval($request_time);
		$uid = $this->uid;
		$balance = intval($balance);
		if ($balance<=0)
			$balance = 1;

		$redis = new Redis();
    	$redis->connect('www.imcharm.com');

    	$key_request_time = "request_time.exchange.".$this->uid;

    	$last_request_time = intval($redis->get($key_request_time));

    	if ($request_time!=$last_request_time){
    		$redis->set($key_request_time,$request_time);

    		$sql = "update `users` set balance = balance+{$balance} where uid = {$uid}";
    		$this->_db->exec($sql);
    		$sql = "select balance from users where uid=$uid";
			$stmt = $this->_db->query($sql);
			$balance = intval($stmt->fetchColumn());

			return $balance;
    	}else{
    		return 0;
    	}
	}

	function getOrderInfo($order_no){
		$sql = "select * from recharge_log where order_no = ?";
		$stmt = $this->_db->prepare($sql);
		$stmt->execute(array($order_no));

		$ary = $stmt->fetchAll();

		if (count($ary)>0)
			return $ary[0];
		else
			return nil;
	}

	function purchaseOrder($shop_id, $price, $origin_price = 0){
		$sql = "INSERT INTO orders (uid, shop_id, price, origin_price, status, payment_type, modify_time) VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";

		$stmt = $this->_db->prepare($sql);
		$stmt->execute(array($this->uid, $shop_id, $price, $discounted_price, 1, 1));

		$sql = "UPDATE users SET balance = balance - $price WHERE uid = " . $this->uid;
		$this->_db->exec($sql);
	}

}
?>