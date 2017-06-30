<?php

class IndexController extends Yaf_Controller_Abstract{
	public function indexAction(){
		$this->getView()->assign("content","Hello World");
	}

	function blacklistAction(){
		$credit_model = new CreditModel();

		$page = intval($_REQUEST[$page]);

		$data = $credit_model->getAllBlacklist($page);

		echo json_encode($data);
		exit;
	}

	function exportAction(){
		$credit_model = new CreditModel();
		$now = gmdate("D, d M Y H:i:s");

		$filename = "blacklist_" . date('Y-m-d', time()) . ".csv";
  		header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
  		header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
	  	header("Last-Modified: {$now} GMT");
	  	// force download 
	  	header("Content-Type: application/force-download");
	  	header("Content-Type: application/octet-stream");
	  	header("Content-Type: application/download");
	  	header('Content-Encoding: UTF-8');
		header('Content-type: text/csv; charset=UTF-8');
		header("Content-Disposition: attachment;filename={$filename}");
		echo "\xEF\xBB\xBF"; 
	  	// disposition / encoding on response body
	  	
	  	header("Content-Transfer-Encoding: binary");
		
		$page = 1;

		ob_start();

		$fp = fopen('php://output', 'a');

		$header = array('姓名', '身份证号', '手机号', '标记次数');
		// foreach ($head as $i => $v) {
  //     		$head[$i] = iconv ('utf-8', 'gbk', $v);
  //   	}

		fputcsv($fp, $header);

		$data = $credit_model->getAllBlacklist($page);

		foreach ($data as $d) {
			$row = array($d['name'], $d['idcard_no'], $d['phone'], $d['reported_times']);
			// foreach ($row as $key => $value) {
			// 	$row[$key] = iconv('utf-8', 'gbk', $value);
			// }
			fputcsv($fp, $row);
		}

		$alldatas[] = $data;
		while(count($data) > 0){
			$page += 1;
			$data = $credit_model->getAllBlacklist($page);
			foreach ($data as $d) {
				$row = array($d['name'], $d['idcard_no'], $d['phone'], $d['reported_times']);
				// foreach ($row as $key => $value) {
				// 	$row[$key] = iconv('utf-8', 'gbk', $value);
				// }
				fputcsv($fp, $row);
			}
		}

		fclose($fp);
		echo ob_get_clean();
		exit();
	}

	function exportCreditAction(){
		$credit_model = new CreditModel();
		$now = gmdate("D, d M Y H:i:s");

		$filename = "credit" . date('Y-m-d', time()) . ".csv";
  		header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
  		header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
	  	header("Last-Modified: {$now} GMT");
	  	// force download 
	  	header("Content-Type: application/force-download");
	  	header("Content-Type: application/octet-stream");
	  	header("Content-Type: application/download");
	  	header('Content-Encoding: UTF-8');
		header('Content-type: text/csv; charset=UTF-8');
		header("Content-Disposition: attachment;filename={$filename}");
		echo "\xEF\xBB\xBF"; 
	  	// disposition / encoding on response body
	  	
	  	header("Content-Transfer-Encoding: binary");
		
		$page = 1;

		ob_start();

		$fp = fopen('php://output', 'a');

		$header = array('姓名', '身份证号', '手机号', '实名认证', '手机认证');
		// foreach ($head as $i => $v) {
  //     		$head[$i] = iconv ('utf-8', 'gbk', $v);
  //   	}

		fputcsv($fp, $header);

		$data = $credit_model->getAllCreditList($page);

		foreach ($data as $d) {
			$row = array($d['name'], $d['idcard_no'], $d['phone'], $d['realname_status'], $d['phone_status']);
			// foreach ($row as $key => $value) {
			// 	$row[$key] = iconv('utf-8', 'gbk', $value);
			// }
			fputcsv($fp, $row);
		}

		$alldatas[] = $data;
		while(count($data) > 0){
			$page += 1;
			$data = $credit_model->getAllCreditList($page);
			foreach ($data as $d) {
				$row = array($d['name'], $d['idcard_no'], $d['phone'], $d['realname_status'], $d['phone_status']);
				// foreach ($row as $key => $value) {
				// 	$row[$key] = iconv('utf-8', 'gbk', $value);
				// }
				fputcsv($fp, $row);
			}
		}

		fclose($fp);
		echo ob_get_clean();
		exit();
	}

	public function scoreAction(){
		$phone = $_REQUEST['phone'];
		$score = -1;
		$score_description = null;
		if (!empty($phone)){
			$account_model = new AccountModel();
			$score = $account_model->submitProfile($_REQUEST);
			$score_description = "您的评分为{$score}分,个人印象:" . ($score >= 60 ? "白" : "灰");
		}

		

		$this->getView()->assign("score_description", $score_description);
	}

	public function testAction(){
		echo "hello world";
		exit;
	}

	public function jrttAction(){
		$device_channel = '今日头条';

		$monitor_model = new MonitorModel();

		$device_id = $_REQUEST['idfa'];
		$os = intval($_REQUEST['os']);

		$device_type = $os == 1 ? 0 : ($os == 0 ? 1 : 2);

		if (!empty($device_id)){
			$monitor_model->recordClick($device_id,$device_type,$device_channel);
		}else{
			echo "no device info";
		}

		

		exit;
	}


	/*
	public function testAction(){
		
		$pay_model = new PayModel(20000019);
		$receipt = "aaa";//$_REQUEST['receipt'];

		$product_id = "6MonthVIP";//$_REQUEST['identifier'];
		$order_number = "123456";//$_REQUEST['order_number'];



		$bundle_id = "com.5stan.Affair";

		if ($bundle_id=='com.5stan.Affair'){
			$is_used = $pay_model->isOrderUsed($order_number);
			if ($is_used)
				$this->echo_error(10029);

			$is_valid = true;//$this->_pay_model->isReceiptValid($receipt);
			if ($is_valid){
				$pay_info = $pay_model->rechargeViaApple($product_id);
				var_dump($pay_info);

				$pay_model->saveAppleReceipt($product_id,$receipt,$order_number);
					
				$this->echo_result($pay_info);
			}else{
				$pay_model->saveAppleReceipt($product_id,$receipt,$order_number);
				$this->echo_error(10028);
			}
		}else
			$this->echo_error(10028);

	}


	public function echo_message($msg = 'fail',$code = 0){
		$ary = array('message'=>$msg,'code'=>$code);
		echo json_encode($ary);
		exit;
	}

	public function echo_result($data){
		$ary = array('code'=>self::RETURN_SUCCESS_CODE,'data'=>$data);
		echo json_encode($this->remove_null($ary));
		exit;
	}

	public function echo_error($code){
		$ary = array('message'=>"error",'code'=>$code);
		echo json_encode($ary);
		exit;
	}

	private function remove_null($ary){
		foreach($ary as $key=>$value){
			if (is_null($value))
				unset($ary[$key]);
			else if (is_array($value)){
				$value = $this->remove_null($value);
				if (is_null($value))
					unset($ary[$key]);
				else
					$ary[$key] = $value;
			}	 
		}
		$ary = array_filter($ary);

		return $ary;
	}
	*/
}

?>