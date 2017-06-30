<?php
class UserController extends BaseApiController{
	private $_user_model = null;
	private $_memcached_user_model = null;
	
	function init(){
		parent::init();
		$this->_user_model = new UserModel($this->_requestor_uid);
		$this->_memcached_user_model = new MemcachedUserModel();
	}

	function loginAction(){
		$phone = $_REQUEST['phone'];
		$channel = $_REQUEST['channel'];

		$profile = null;
		$password_hash = strtolower($_REQUEST['password']);

		if ($password_hash==null)
			$this->echo_error(10009);
		if (!$this->_user_model->isPhoneUserExist($phone)){
			$this->echo_error(10003);
		}
			
		$profile = $this->_user_model->loginAccount($phone,$password_hash);


		if ($profile==null)
			$this->echo_error(10012);

		if (intval($profile['group_id']) <= 0){
			$this->echo_error(10046);
		}

		$uid = intval($profile['uid']);

		$access_token = $this->gen_access_token($uid);

		if ($access_token==null)
			$this->echo_error(10011);
		$profile['access_token'] = $access_token;

		$result = $this->insert_auth_log(intval($profile['uid']));

		$this->echo_result($profile);
	}

	function profileAction(){
		$uid = intval($_REQUEST['uid']);
		if (0 == $uid)
			$uid = $this->_requestor_uid;

		$profile = $this->_user_model->getProfile($uid);

		$this->echo_result($profile);
	}

	function creditAction(){
		$idcard_no = $_REQUEST['idcard_no'];
		$name = $_REQUEST['name'];
		$phone = $_REQUEST['phone'];
		$page = intval($_REQUEST['page']);

		$credit_model = new CreditModel();
		$ary = $credit_model->getCreditRecords($idcard_no, $name, $phone, $page);

		$this->echo_result($ary);
	}
}
?>