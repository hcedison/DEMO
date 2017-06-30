<?php

abstract class BaseApiController extends Yaf_Controller_Abstract{
	const RETURN_SUCCESS_CODE = 200;


	private $client_id = '';
	private $client_secret = '';
	private $request_api = '';
	private $access_token = '';
	protected $device_id = '';

	protected $_requestor_uid = 0;

	private $api_need_login = 0;

	protected $_mosaic_model = null;
	protected $_error_codes = null;

	public function init(){
		Yaf_Dispatcher::getInstance()->autoRender(FALSE);

		$this->_mosaic_model = new MosaicModel();
		$this->_error_codes = new Mosaic_ApiError();

		$this->get_api();
		$this->get_device_id();
		$this->get_client_id();
		$this->get_client_secret();

		#check access_token
		$this->check_access_token();
		#check sign
		$this->check_sign();		
	}

	private function get_api(){
		$c_name = strtolower($this->getRequest()->controller);
		$a_name = strtolower($this->getRequest()->action);

		$this->request_api = $c_name.'.'.$a_name;

		$this->api_need_login = $this->check_need_login();
		// if (strtolower($this->getModuleName())!='api')
			// $this->api_need_login = false;
	}

	private function get_device_id(){
		$params = $this->get_params();
		$this->device_id = $params['device_id'];
	}

	private function get_client_id(){
		$params = $this->get_params();
		$this->client_id = $params['client_id'];
	}

	private function get_client_secret(){
		$this->client_secret = $this->_mosaic_model->getClientSecret($this->client_id);
	}

	private function check_access_token(){
		$request = $this->get_params();

		if ($this->api_need_login){
			$this->access_token = $request['access_token'];
			if ($this->access_token==null){
				$this->echo_error(10006);
			}else{
				$auth_info = $this->_mosaic_model->getAuthLog($this->access_token);
				if ($auth_info==null)
					$this->echo_error(10007);
				else{
					if (!empty($_REQUEST['device_id']) && !empty($auth_info['device_id']) && $_REQUEST['device_id'] != $auth_info['device_id'])
						$auth_info['device_id'] = $_REQUEST['device_id'];
					if (empty($this->device_id))
						$this->device_id = $auth_info['device_id'];
					$this->client_id = $auth_info['client_id'];
					$this->get_client_secret();
					$this->_requestor_uid = intval($auth_info['uid']);

					if ($this->_mosaic_model->isDeviceBlocked($this->device_id))
						$this->echo_error(10008);

					$this->_mosaic_model->updateLastVisitTime($this->_requestor_uid);

					if (isset($request["latitude"]) && isset($request["longitude"])){
     				$latitude = $request["latitude"];
     				$longitude = $request["longitude"];
     				$this->_mosaic_model->updateLocation($this->_requestor_uid,$latitude,$longitude);
     			}
				}
			}
		}else{
			$this->access_token = $request['access_token'];
			if ($this->access_token!=null && $this->client_id==null){
				$auth_info = $this->_mosaic_model->getAuthLog($this->access_token);
				$this->_requestor_uid = intval($auth_info['uid']);
				if (empty($this->device_id))
					$this->device_id = $auth_info['device_id'];
				
				$this->client_id = $auth_info['client_id'];
				$this->get_client_secret();
			}
		}
	}

	private function check_sign(){
		$request = $this->get_params();

		$sign = $request["sign"];
		$request_time = $request["request_time"];
		$device_id = $this->device_id;

		$client_id = $this->client_id;
		$client_secret = $this->client_secret;

		if ($client_id==null or $client_secret==null){
			$this->echo_error(10001);
		}

		$key = strtoupper(md5(strtoupper(md5($client_id).md5($device_id).md5($request_time))));
		// echo md5($client_id);
		// echo "<br/>" . md5($device_id) . "<br/>" . md5($request_time);
     	$algo = "sha1";
     	
    	$real_key = strtoupper(hash_hmac($algo,$key,$client_secret));
     	$data = $this->gen_data_string($request);

     	
     	$gen_sign = hash_hmac($algo,$data,$real_key);

     	// echo json_encode(array('key'=>$key,'sign'=>$sign,'gensign'=>$gen_sign,'data'=>$data,'real_key'=>$real_key,'device_id'=>$device_id,'client_id'=>$client_id,'client_secret'=>$client_secret));

     	// echo "$sign; $gen_sign ,$algo;data : $data  ,real key:  $real_key  /  $device_id  / $client_id";

     	if (strtolower($gen_sign)!=strtolower($sign)){
			$this->echo_error(10002);
     	}

     	//	API Calls Continues Here, So record here
     	$this->_mosaic_model->recordAction($this->_requestor_uid, $endpoint, $module_name, $parameters);
	}

	function hasPrefix($content,$prefix){
		$length = strlen($prefix);

		return (substr($content, 0,$length) === $prefix);
	}

	private function gen_data_string(){

		$request = $this->get_params();

		unset($request["sign"]);
		unset($request["a"]);
		unset($request['PHPSESSID']);
		unset($request['SQLiteManager_currentLangue']);

		ksort($request);

		$data_string = "";
		foreach ($request as $key => $value) {
			if ($this->hasPrefix($key,'_')){
				continue;
			}
			$data_string .= $key."=".$value;
		}

		return $data_string;
	}

	private function get_params(){
		if (isset($_REQUEST['receipt'])){
			$receipt = $_REQUEST['receipt'];
			$receipt = str_replace(' ', '+', $receipt);
			$_REQUEST['receipt'] = $receipt;
		}
		return $_REQUEST;
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
		$msg = $this->_error_codes->system_errors[$code];
		$ary = array('message'=>$msg,'code'=>$code);
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

	private function check_need_login(){
		$without_apis = array('user.register', 'user.authcode','user.login','lamp.list', 'track.bind','track.updatelocation','account.submit','ad.providers','user.forgot','user.resetpassword');
		foreach ($without_apis as $api){
			if ($api==$this->request_api)
				return false;
		}

		return true;
	}

	function gen_access_token($uid){
		$uid = intval($uid);
		if ($uid==0)
			$uid = $this->_requestor_uid;
		if ($uid==0)
			return null;

		if ($this->device_id==null || $this->client_secret==null)
			return null;

		$key = md5($this->device_id.$uid.$uid/16);
		$algo = "sha1";
		$data = md5($this->client_secret."(".date("Y-m-d H:i:s").")");

		$access_token = hash_hmac($algo,$data,$key);

		$this->access_token = $access_token;

		return $access_token;
	}

	function insert_auth_log($uid){
		$this->_mosaic_model->insertAuthLog($uid,$this->device_id,$this->client_id,$this->access_token);
	}

	
}

?>